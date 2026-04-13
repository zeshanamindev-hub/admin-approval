<?php
/**
 * User Registration Approval Management
 * Handles new user registration approvals similar to New User Approve plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class RequflprUsers
{

    // User status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_DENIED = 'denied';

    // Meta key for user approval status
    const META_KEY = 'requflpr_status';

    public function __construct()
    {
        // Registration hooks
        add_action('user_register', array($this, 'user_registered'), 10, 1);

        // Authentication hooks
        add_filter('wp_authenticate_user', array($this, 'authenticate_user'), 10, 2);
        add_filter('login_message', array($this, 'login_message'));

        // Admin menu
        add_action('admin_menu', array($this, 'add_users_menu'), 20);

        // User list columns
        add_filter('manage_users_columns', array($this, 'add_approval_column'));
        add_filter('manage_users_custom_column', array($this, 'approval_column_content'), 10, 3);

        // User actions
        add_action('admin_post_requflpr_user_action', array($this, 'handle_user_action'));
        add_action('admin_post_requflpr_bulk_user_action', array($this, 'handle_bulk_user_action'));

        // AJAX actions
        add_action('wp_ajax_requflpr_update_user_status', array($this, 'ajax_update_user_status'));

        // Registration form message
        add_filter('register_message', array($this, 'registration_message'));
    }

    /**
     * Handle new user registration
     */
    public function user_registered($user_id)
    {
        // Check if user approval is enabled
        if (!get_option('requflpr_enable_user_approval', 0)) {
            return;
        }

        // Set user status to pending
        update_user_meta($user_id, self::META_KEY, self::STATUS_PENDING);

        // Prevent automatic login after registration
        add_filter('woocommerce_registration_auth_new_customer', '__return_false');

        // Send notification to admin
        $this->send_admin_notification($user_id);

        // Send pending notification to user
        $this->send_user_pending_notification($user_id);
    }

    /**
     * Authenticate user - block login for pending/denied users
     */
    public function authenticate_user($user, $password)
    {
        if (is_wp_error($user)) {
            return $user;
        }

        if (!get_option('requflpr_enable_user_approval', 0)) {
            return $user;
        }

        $status = $this->get_user_status($user->ID);

        if ($status === self::STATUS_PENDING) {
            return new WP_Error(
                'pending_approval',
                __('<strong>ERROR</strong>: Your account is pending approval. You will receive an email once your account has been approved.', 'request-flow-pro')
            );
        }

        if ($status === self::STATUS_DENIED) {
            return new WP_Error(
                'account_denied',
                __('<strong>ERROR</strong>: Your account registration has been denied.', 'request-flow-pro')
            );
        }

        return $user;
    }

    /**
     * Add custom login message
     */
    public function login_message($message)
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['approval']) && $_GET['approval'] === 'pending') {
            $message .= '<p class="message">' . esc_html__('Registration complete. Your account is pending approval.', 'request-flow-pro') . '</p>';
        }
        return $message;
    }

    /**
     * Add registration message
     */
    public function registration_message($message)
    {
        if (get_option('requflpr_enable_user_approval', 0)) {
            $custom_message = get_option(
                'requflpr_user_registration_message',
                esc_html__('After you register, your account will need to be approved before you can login.', 'request-flow-pro')
            );
            $message .= '<p class="message">' . $custom_message . '</p>';
        }
        return $message;
    }

    /**
     * Get user approval status
     */
    public function get_user_status($user_id)
    {
        $status = get_user_meta($user_id, self::META_KEY, true);

        // If no status set, assume approved (for existing users)
        if (empty($status)) {
            return self::STATUS_APPROVED;
        }

        return $status;
    }

    /**
     * Update user status
     */
    public function update_user_status($user_id, $status, $admin_notes = '')
    {
        if (!in_array($status, array(self::STATUS_APPROVED, self::STATUS_DENIED, self::STATUS_PENDING))) {
            return false;
        }

        $old_status = $this->get_user_status($user_id);
        update_user_meta($user_id, self::META_KEY, $status);

        // Send notification email
        if ($old_status !== $status) {
            $this->send_status_change_notification($user_id, $status, $admin_notes);
        }

        return true;
    }

    /**
     * Add admin menu for user approvals
     */
    public function add_users_menu()
    {
        // Get pending count for badge
        $pending_count = $this->get_users_count(self::STATUS_PENDING);
        if ($pending_count > 0) {
            /* translators: %s: pending count badge */
            $menu_title = sprintf(__('User Approvals %s', 'request-flow-pro'), '<span class="awaiting-mod count-' . $pending_count . '"><span class="pending-count">' . number_format_i18n($pending_count) . '</span></span>');
        } else {
            $menu_title = __('User Approvals', 'request-flow-pro');
        }

        add_submenu_page(
            'requflpr-requests',
            __('User Approvals', 'request-flow-pro'),
            $menu_title,
            'manage_options',
            'requflpr-users',
            array($this, 'users_page')
        );
    }

    /**
     * Add approval column to users list
     */
    public function add_approval_column($columns)
    {
        if (get_option('requflpr_enable_user_approval', 0)) {
            $columns['requflpr_status'] = __('Approval Status', 'request-flow-pro');
        }
        return $columns;
    }

    /**
     * Show approval status in user list column
     */
    public function approval_column_content($value, $column_name, $user_id)
    {
        if ($column_name === 'requflpr_status') {
            $status = $this->get_user_status($user_id);

            $badge_class = 'status-' . $status;
            $badge_text = ucfirst($status);

            return '<span class="status-badge ' . esc_attr($badge_class) . '">' . esc_html($badge_text) . '</span>';
        }
        return $value;
    }

    /**
     * Get count of users by status
     */
    public function get_users_count($status)
    {
        $args = array(
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            'meta_key' => self::META_KEY,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            'meta_value' => $status,
            'count_total' => true,
            'fields' => 'ID'
        );


        $user_query = new WP_User_Query($args);
        return $user_query->get_total();
    }

    /**
     * Get users by status
     */
    public function get_users_by_status($status = null, $number = 20, $offset = 0)
    {
        $args = array(
            'number' => $number,
            'offset' => $offset,
            'orderby' => 'registered',
            'order' => 'DESC'
        );

        if ($status) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            $args['meta_key'] = self::META_KEY;
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            $args['meta_value'] = $status;
        }


        return new WP_User_Query($args);
    }

    /**
     * Send admin notification for new registration
     */
    private function send_admin_notification($user_id)
    {
        $user = get_userdata($user_id);
        $admin_email = get_option('requflpr_admin_email', get_option('admin_email'));

        /* translators: %s: site name */
        $subject = sprintf(__('[%s] New User Registration Pending Approval', 'request-flow-pro'), get_bloginfo('name'));

        /* translators: 1: username, 2: email, 3: registration date, 4: admin URL */
        $message = sprintf(
            __("A new user has registered and is pending approval:\n\nUsername: %1\$s\nEmail: %2\$s\nRegistered: %3\$s\n\nTo approve or deny this user, visit:\n%4\$s", 'request-flow-pro'),
            $user->user_login,
            $user->user_email,
            date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($user->user_registered)),
            admin_url('admin.php?page=requflpr-users')
        );

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Send pending notification to user
     */
    private function send_user_pending_notification($user_id)
    {
        $user = get_userdata($user_id);

        $subject = get_option(
            'requflpr_user_pending_subject',
            __('Your registration is pending approval', 'request-flow-pro')
        );

        $body = get_option(
            'requflpr_user_pending_body',
            "Hello {user_name},\n\nThank you for registering at {site_name}.\n\nYour account is currently pending approval. You will receive another email once your account has been approved.\n\nThank you for your patience!"
        );

        $placeholders = array(
            '{user_name}' => $user->display_name,
            '{username}' => $user->user_login,
            '{user_email}' => $user->user_email,
            '{site_name}' => get_bloginfo('name'),
            '{site_url}' => get_site_url()
        );

        $subject = str_replace(array_keys($placeholders), array_values($placeholders), $subject);
        $body = str_replace(array_keys($placeholders), array_values($placeholders), $body);

        wp_mail($user->user_email, $subject, $body);
    }

    /**
     * Send status change notification to user
     */
    private function send_status_change_notification($user_id, $status, $admin_notes = '')
    {
        $user = get_userdata($user_id);

        if ($status === self::STATUS_APPROVED) {
            $subject = get_option(
                'requflpr_user_approved_subject',
                __('Your account has been approved!', 'request-flow-pro')
            );

            $body = get_option(
                'requflpr_user_approved_body',
                "Hello {user_name},\n\nGreat news! Your account at {site_name} has been approved.\n\nYou can now login at: {site_url}/wp-login.php\n\nUsername: {username}\n\n{admin_notes}"
            );
        } else {
            $subject = get_option(
                'requflpr_user_denied_subject',
                __('Your account registration', 'request-flow-pro')
            );

            $body = get_option(
                'requflpr_user_denied_body',
                "Hello {user_name},\n\nWe regret to inform you that your registration at {site_name} has been denied.\n\n{admin_notes}\n\nIf you have any questions, please contact us."
            );
        }

        $placeholders = array(
            '{user_name}' => $user->display_name,
            '{username}' => $user->user_login,
            '{user_email}' => $user->user_email,
            '{site_name}' => get_bloginfo('name'),
            '{site_url}' => get_site_url(),
            '{admin_notes}' => $admin_notes ? "\nAdmin notes: " . $admin_notes : ''
        );

        $subject = str_replace(array_keys($placeholders), array_values($placeholders), $subject);
        $body = str_replace(array_keys($placeholders), array_values($placeholders), $body);

        wp_mail($user->user_email, $subject, $body);
    }

    /**
     * Handle single user action
     */
    public function handle_user_action()
    {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'requflpr_user_action')) {
            wp_die(esc_html__('Security check failed.', 'request-flow-pro'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'request-flow-pro'));
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $action = isset($_POST['user_action']) ? sanitize_text_field(wp_unslash($_POST['user_action'])) : '';
        $admin_notes = isset($_POST['admin_notes']) ? sanitize_textarea_field(wp_unslash($_POST['admin_notes'])) : '';

        if ($action === 'approve') {
            $this->update_user_status($user_id, self::STATUS_APPROVED, $admin_notes);
        } elseif ($action === 'deny') {
            $this->update_user_status($user_id, self::STATUS_DENIED, $admin_notes);
        }

        wp_safe_redirect(add_query_arg('updated', '1', wp_get_referer()));
        exit;
    }

    /**
     * Handle bulk user actions
     */
    public function handle_bulk_user_action()
    {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'requflpr_bulk_user_action')) {
            wp_die(esc_html__('Security check failed.', 'request-flow-pro'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'request-flow-pro'));
        }

        $action = isset($_POST['bulk_action']) ? sanitize_text_field(wp_unslash($_POST['bulk_action'])) : '';
        $user_ids = isset($_POST['user_ids']) ? array_map('intval', $_POST['user_ids']) : array();

        if (empty($user_ids) || $action === '-1') {
            wp_safe_redirect(wp_get_referer());
            exit;
        }

        $status = null;
        if ($action === 'approve') {
            $status = self::STATUS_APPROVED;
        } elseif ($action === 'deny') {
            $status = self::STATUS_DENIED;
        }

        if ($status) {
            foreach ($user_ids as $user_id) {
                $this->update_user_status($user_id, $status);
            }
        }

        wp_safe_redirect(add_query_arg('bulk_updated', count($user_ids), wp_get_referer()));
        exit;
    }

    /**
     * AJAX: Update user status
     */
    public function ajax_update_user_status()
    {
        check_ajax_referer('requflpr_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'request-flow-pro')));
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';
        $admin_notes = isset($_POST['admin_notes']) ? sanitize_textarea_field(wp_unslash($_POST['admin_notes'])) : '';

        if ($this->update_user_status($user_id, $status, $admin_notes)) {
            wp_send_json_success(array(
                /* translators: %s: status (e.g. approved, denied) */
                'message' => sprintf(__('User %s successfully!', 'request-flow-pro'), $status)
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to update user.', 'request-flow-pro')));
        }
    }

    /**
     * Users approval page
     */
    public function users_page()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $status_filter = isset($_GET['user_status']) ? sanitize_text_field(wp_unslash($_GET['user_status'])) : null;

        $user_query = $this->get_users_by_status($status_filter, 50, 0);
        $users = $user_query->get_results();

        $counts = array(
            'all' => count_users()['total_users'],
            'pending' => $this->get_users_count(self::STATUS_PENDING),
            'approved' => $this->get_users_count(self::STATUS_APPROVED),
            'denied' => $this->get_users_count(self::STATUS_DENIED)
        );

        require_once REQUFLPR_PATH . 'templates/admin-users.php';
    }
}
