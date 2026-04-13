<?php
/**
 * Admin interface for the Approval Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class RequflprAdmin
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_post_requflpr_update_status', array($this, 'handle_status_update'));
        add_action('admin_post_requflpr_bulk_action', array($this, 'handle_bulk_action'));
        add_action('admin_post_requflpr_export_csv', array($this, 'export_csv'));
        add_action('wp_ajax_requflpr_get_request_details', array($this, 'ajax_get_request_details'));
        add_action('wp_ajax_requflpr_update_request', array($this, 'ajax_update_request'));
    }

    /**
     * Add admin menu pages
     */
    public function add_admin_menu()
    {
        add_menu_page(
            __('Approval Requests', 'request-flow-pro'),
            __('Approvals', 'request-flow-pro'),
            'manage_options',
            'requflpr-requests',
            array($this, 'admin_page'),
            'dashicons-yes-alt',
            30
        );

        add_submenu_page(
            'requflpr-requests',
            __('All Requests', 'request-flow-pro'),
            __('All Requests', 'request-flow-pro'),
            'manage_options',
            'requflpr-requests',
            array($this, 'admin_page')
        );

        add_submenu_page(
            'requflpr-requests',
            __('Settings', 'request-flow-pro'),
            __('Settings', 'request-flow-pro'),
            'manage_options',
            'requflpr-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook)
    {
        if (strpos($hook, 'requflpr') !== false) {
            wp_enqueue_style('requflpr-admin-css', REQUFLPR_URL . 'css/admin-style.css', array(), REQUFLPR_VERSION);
            wp_enqueue_script('requflpr-admin-js', REQUFLPR_URL . 'js/admin-script.js', array('jquery'), REQUFLPR_VERSION, true);

            wp_localize_script('requflpr-admin-js', 'requflpr_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('requflpr_nonce'),
                'strings' => array(
                    'select_action' => __('Please select an action.', 'request-flow-pro'),
                    'select_user' => __('Please select at least one user.', 'request-flow-pro'),
                    'approve' => __('approve', 'request-flow-pro'),
                    'deny' => __('deny', 'request-flow-pro'),
                    'confirm_bulk' => __('Are you sure you want to', 'request-flow-pro'),
                    'users_part' => __('user(s)?', 'request-flow-pro'),
                    'confirm_single' => __('Are you sure you want to', 'request-flow-pro'),
                    'this_user' => __('this user?', 'request-flow-pro'),
                    'approving' => __('Approving...', 'request-flow-pro'),
                    'denying' => __('Denying...', 'request-flow-pro'),
                    'approve_btn' => __('Approve', 'request-flow-pro'),
                    'deny_btn' => __('Deny', 'request-flow-pro'),
                    'error' => __('An error occurred. Please try again.', 'request-flow-pro')
                )
            ));
        }
    }

    /**
     * Main admin page
     */
    public function admin_page()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $status_filter = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : null;
        $requests = RequflprDatabase::get_requests($status_filter);
        $counts = array(
            'all' => RequflprDatabase::get_count(),
            'pending' => RequflprDatabase::get_count('pending'),
            'approved' => RequflprDatabase::get_count('approved'),
            'rejected' => RequflprDatabase::get_count('rejected')
        );
        $stats = RequflprDatabase::get_statistics();

        ?>
        <div class="wrap approval-plugin-wrap">
            <!-- Page Header -->
            <div class="approval-page-header">
                <div class="page-header-left">
                    <h1 class="approval-main-title">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e('Approval Requests', 'request-flow-pro'); ?>
                    </h1>
                    <p class="page-subtitle">
                        <?php esc_html_e('Manage and track all approval requests in one place', 'request-flow-pro'); ?>
                    </p>
                </div>
                <div class="page-header-right">
                    <a href="<?php echo esc_url(admin_url('admin-post.php?action=requflpr_export_csv')); ?>"
                        class="button button-secondary header-action-btn">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e('Export CSV', 'request-flow-pro'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=requflpr-settings')); ?>"
                        class="button button-secondary header-action-btn">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php esc_html_e('Settings', 'request-flow-pro'); ?>
                    </a>
                </div>
            </div>

            <!-- Statistics Dashboard -->
            <div class="approval-stats-dashboard">
                <div class="approval-stat-card approval-stat-total">
                    <div class="stat-icon">
                        <div class="icon-circle total-circle">
                            <span class="dashicons dashicons-clipboard"></span>
                        </div>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html($stats['total']); ?></div>
                        <div class="stat-label"><?php esc_html_e('TOTAL REQUESTS', 'request-flow-pro'); ?></div>
                    </div>
                </div>

                <div class="approval-stat-card approval-stat-pending">
                    <div class="stat-icon">
                        <div class="icon-circle pending-circle">
                            <span class="dashicons dashicons-clock"></span>
                        </div>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html($stats['pending']); ?></div>
                        <div class="stat-label"><?php esc_html_e('PENDING', 'request-flow-pro'); ?></div>
                    </div>
                </div>

                <div class="approval-stat-card approval-stat-approved">
                    <div class="stat-icon">
                        <div class="icon-circle approved-circle">
                            <span class="dashicons dashicons-yes"></span>
                        </div>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html($stats['approved']); ?></div>
                        <div class="stat-label"><?php esc_html_e('APPROVED', 'request-flow-pro'); ?></div>
                    </div>
                </div>

                <div class="approval-stat-card approval-stat-rejected">
                    <div class="stat-icon">
                        <div class="icon-circle rejected-circle">
                            <span class="dashicons dashicons-dismiss"></span>
                        </div>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html($stats['rejected']); ?></div>
                        <div class="stat-label"><?php esc_html_e('REJECTED', 'request-flow-pro'); ?></div>
                    </div>
                </div>

                <div class="approval-stat-card approval-stat-rate">
                    <div class="stat-icon">
                        <div class="icon-circle rate-circle">
                            <span class="dashicons dashicons-chart-line"></span>
                        </div>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html($stats['approval_rate']); ?><span
                                class="stat-unit">%</span></div>
                        <div class="stat-label"><?php esc_html_e('APPROVAL RATE', 'request-flow-pro'); ?></div>
                    </div>
                </div>

                <div class="approval-stat-card approval-stat-time">
                    <div class="stat-icon">
                        <div class="icon-circle time-circle">
                            <span class="dashicons dashicons-backup"></span>
                        </div>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html($stats['avg_response_time']); ?><span
                                class="stat-unit">h</span></div>
                        <div class="stat-label"><?php esc_html_e('AVG RESPONSE TIME', 'request-flow-pro'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="approval-filters-wrapper">
                <div class="approval-filters-section">
                    <div class="filter-tabs">
                        <a href="?page=requflpr-requests" class="filter-tab <?php echo !$status_filter ? 'active' : ''; ?>">
                            <span class="tab-label"><?php esc_html_e('All', 'request-flow-pro'); ?></span>
                            <span class="tab-count"><?php echo absint($counts['all']); ?></span>
                        </a>
                        <a href="?page=requflpr-requests&status=pending"
                            class="filter-tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                            <span class="tab-label"><?php esc_html_e('Pending', 'request-flow-pro'); ?></span>
                            <span class="tab-count"><?php echo absint($counts['pending']); ?></span>
                        </a>
                        <a href="?page=requflpr-requests&status=approved"
                            class="filter-tab <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
                            <span class="tab-label"><?php esc_html_e('Approved', 'request-flow-pro'); ?></span>
                            <span class="tab-count"><?php echo absint($counts['approved']); ?></span>
                        </a>
                        <a href="?page=requflpr-requests&status=rejected"
                            class="filter-tab <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">
                            <span class="tab-label"><?php esc_html_e('Rejected', 'request-flow-pro'); ?></span>
                            <span class="tab-count"><?php echo absint($counts['rejected']); ?></span>
                        </a>
                    </div>
                    <div class="filter-actions">
                        <input type="text" id="approval-search-input"
                            placeholder="<?php esc_attr_e('Search requests...', 'request-flow-pro'); ?>"
                            class="approval-search-box">
                    </div>
                </div>
            </div>

            <!-- Bulk Actions Form -->
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="approval-bulk-form">
                <input type="hidden" name="action" value="requflpr_bulk_action">
                <?php wp_nonce_field('requflpr_bulk_action'); ?>

                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <select name="bulk_action" id="bulk-action-selector">
                            <option value="-1"><?php esc_html_e('Bulk Actions', 'request-flow-pro'); ?></option>
                            <option value="approve"><?php esc_html_e('Approve', 'request-flow-pro'); ?></option>
                            <option value="reject"><?php esc_html_e('Reject', 'request-flow-pro'); ?></option>
                        </select>
                        <input type="submit" class="button action" value="<?php esc_attr_e('Apply', 'request-flow-pro'); ?>">
                    </div>
                </div>

                <table class="wp-list-table widefat fixed striped requflpr-requests-table">
                    <thead>
                        <tr>
                            <td class="check-column"><input type="checkbox" id="cb-select-all"></td>
                            <th><?php esc_html_e('Title', 'request-flow-pro'); ?></th>
                            <th><?php esc_html_e('Priority', 'request-flow-pro'); ?></th>
                            <th><?php esc_html_e('Category', 'request-flow-pro'); ?></th>
                            <th><?php esc_html_e('Submitter', 'request-flow-pro'); ?></th>
                            <th><?php esc_html_e('Status', 'request-flow-pro'); ?></th>
                            <th><?php esc_html_e('Date', 'request-flow-pro'); ?></th>
                            <th><?php esc_html_e('Actions', 'request-flow-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="8" class="no-requests"><?php esc_html_e('No requests found.', 'request-flow-pro'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <th class="check-column">
                                        <input type="checkbox" name="request_ids[]" value="<?php echo esc_attr($request->id); ?>">
                                    </th>
                                    <td>
                                        <strong><?php echo esc_html($request->title); ?></strong>
                                        <div class="row-actions">
                                            <span><a href="#" class="view-details"
                                                    data-id="<?php echo absint($request->id); ?>"><?php esc_html_e('View Details', 'request-flow-pro'); ?></a></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $priority = isset($request->priority) ? $request->priority : 'medium';
                                        $priority_class = 'priority-' . esc_attr($priority);
                                        ?>
                                        <span class="priority-badge <?php echo esc_attr($priority_class); ?>">
                                            <?php echo esc_html(ucfirst($priority)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo esc_html(isset($request->category) ? ucfirst($request->category) : 'General'); ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html($request->submitter_name); ?><br>
                                        <small><?php echo esc_html($request->submitter_email); ?></small>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo esc_attr($request->status); ?>">
                                            <?php echo esc_html(ucfirst($request->status)); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html(wp_date('M j, Y g:i A', strtotime($request->created_at))); ?></td>
                                    <td class="approval-actions-cell">
                                        <?php if ($request->status === 'pending'): ?>
                                            <button type="button" class="button button-primary button-small quick-approve"
                                                data-id="<?php echo absint($request->id); ?>">
                                                <?php esc_html_e('Approve', 'request-flow-pro'); ?>
                                            </button>
                                            <button type="button" class="button button-small quick-reject"
                                                data-id="<?php echo absint($request->id); ?>">
                                                <?php esc_html_e('Reject', 'request-flow-pro'); ?>
                                            </button>
                                        <?php else: ?>
                                            <span
                                                class="dashicons dashicons-<?php echo $request->status === 'approved' ? 'yes' : 'no'; ?>"></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>

            <!-- Modal for request details -->
            <div id="approval-modal" class="approval-modal" style="display: none;">
                <div class="approval-modal-content">
                    <span class="approval-close">&times;</span>
                    <div id="approval-modal-body">
                        <div class="loading-spinner">
                            <span class="dashicons dashicons-update spinning"></span>
                            <?php esc_html_e('Loading...', 'request-flow-pro'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Settings page
     */
    public function settings_page()
    {
        // Handle form submission
        if (isset($_POST['requflpr_settings_submit'])) {
            check_admin_referer('requflpr_settings_save');

            // Email Settings
            update_option('requflpr_email_notifications', isset($_POST['email_notifications']) ? 1 : 0);
            update_option('requflpr_admin_email', isset($_POST['admin_email']) ? sanitize_email(wp_unslash($_POST['admin_email'])) : '');
            update_option('requflpr_email_from_name', isset($_POST['email_from_name']) ? sanitize_text_field(wp_unslash($_POST['email_from_name'])) : '');
            update_option('requflpr_email_from_email', isset($_POST['email_from_email']) ? sanitize_email(wp_unslash($_POST['email_from_email'])) : '');

            // Email Templates
            update_option('requflpr_email_approved_subject', isset($_POST['email_approved_subject']) ? sanitize_text_field(wp_unslash($_POST['email_approved_subject'])) : '');
            update_option('requflpr_email_approved_body', isset($_POST['email_approved_body']) ? wp_kses_post(wp_unslash($_POST['email_approved_body'])) : '');
            update_option('requflpr_email_rejected_subject', isset($_POST['email_rejected_subject']) ? sanitize_text_field(wp_unslash($_POST['email_rejected_subject'])) : '');
            update_option('requflpr_email_rejected_body', isset($_POST['email_rejected_body']) ? wp_kses_post(wp_unslash($_POST['email_rejected_body'])) : '');
            update_option('requflpr_email_pending_subject', isset($_POST['email_pending_subject']) ? sanitize_text_field(wp_unslash($_POST['email_pending_subject'])) : '');
            update_option('requflpr_email_pending_body', isset($_POST['email_pending_body']) ? wp_kses_post(wp_unslash($_POST['email_pending_body'])) : '');

            // Domain Settings
            $domain_whitelist = isset($_POST['domain_whitelist']) ? sanitize_textarea_field(wp_unslash($_POST['domain_whitelist'])) : '';
            $domain_blacklist = isset($_POST['domain_blacklist']) ? sanitize_textarea_field(wp_unslash($_POST['domain_blacklist'])) : '';
            update_option('requflpr_domain_whitelist', $domain_whitelist);
            update_option('requflpr_domain_blacklist', $domain_blacklist);
            update_option('requflpr_whitelist_enabled', isset($_POST['whitelist_enabled']) ? 1 : 0);
            update_option('requflpr_blacklist_enabled', isset($_POST['blacklist_enabled']) ? 1 : 0);

            // General Settings
            update_option('requflpr_auto_delete_rejected', isset($_POST['auto_delete_rejected']) ? 1 : 0);
            update_option('requflpr_delete_after_days', isset($_POST['delete_after_days']) ? intval($_POST['delete_after_days']) : 30);
            update_option('requflpr_require_approval', isset($_POST['require_approval']) ? 1 : 0);
            update_option('requflpr_show_pending_message', isset($_POST['pending_message']) ? sanitize_textarea_field(wp_unslash($_POST['pending_message'])) : '');

            // User Approval Settings
            update_option('requflpr_enable_user_approval', isset($_POST['enable_user_approval']) ? 1 : 0);
            update_option('requflpr_user_registration_message', isset($_POST['user_registration_message']) ? sanitize_textarea_field(wp_unslash($_POST['user_registration_message'])) : '');
            update_option('requflpr_user_pending_subject', isset($_POST['user_pending_subject']) ? sanitize_text_field(wp_unslash($_POST['user_pending_subject'])) : '');
            update_option('requflpr_user_pending_body', isset($_POST['user_pending_body']) ? wp_kses_post(wp_unslash($_POST['user_pending_body'])) : '');
            update_option('requflpr_user_approved_subject', isset($_POST['user_approved_subject']) ? sanitize_text_field(wp_unslash($_POST['user_approved_subject'])) : '');
            update_option('requflpr_user_approved_body', isset($_POST['user_approved_body']) ? wp_kses_post(wp_unslash($_POST['user_approved_body'])) : '');
            update_option('requflpr_user_denied_subject', isset($_POST['user_denied_subject']) ? sanitize_text_field(wp_unslash($_POST['user_denied_subject'])) : '');
            update_option('requflpr_user_denied_body', isset($_POST['user_denied_body']) ? wp_kses_post(wp_unslash($_POST['user_denied_body'])) : '');

            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully!', 'request-flow-pro') . '</p></div>';
        }

        // Get current settings
        $email_notifications = get_option('requflpr_email_notifications', 1);
        $admin_email = get_option('requflpr_admin_email', get_option('admin_email'));
        $email_from_name = get_option('requflpr_email_from_name', get_bloginfo('name'));
        $email_from_email = get_option('requflpr_email_from_email', get_option('admin_email'));

        // Email Templates
        $email_approved_subject = get_option('requflpr_email_approved_subject', __('Your request has been approved', 'request-flow-pro'));
        $email_approved_body = get_option('requflpr_email_approved_body', $this->get_default_approved_template());
        $email_rejected_subject = get_option('requflpr_email_rejected_subject', __('Your request has been rejected', 'request-flow-pro'));
        $email_rejected_body = get_option('requflpr_email_rejected_body', $this->get_default_rejected_template());
        $email_pending_subject = get_option('requflpr_email_pending_subject', __('Your request has been received', 'request-flow-pro'));
        $email_pending_body = get_option('requflpr_email_pending_body', $this->get_default_pending_template());

        // Domain Settings
        $domain_whitelist = get_option('requflpr_domain_whitelist', '');
        $domain_blacklist = get_option('requflpr_domain_blacklist', '');
        $whitelist_enabled = get_option('requflpr_whitelist_enabled', 0);
        $blacklist_enabled = get_option('requflpr_blacklist_enabled', 0);

        // General Settings
        $auto_delete_rejected = get_option('requflpr_auto_delete_rejected', 0);
        $delete_after_days = get_option('requflpr_delete_after_days', 30);
        $require_approval = get_option('requflpr_require_approval', 1);
        $pending_message = get_option('requflpr_show_pending_message', __('Your request is pending approval. You will be notified via email once it has been reviewed.', 'request-flow-pro'));

        ?>
        <div class="wrap approval-plugin-wrap">
            <h1 class="approval-main-title">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php esc_html_e('Approval Plugin Settings', 'request-flow-pro'); ?>
            </h1>

            <form method="post" action="" class="requflpr-settings-form">
                <?php wp_nonce_field('requflpr_settings_save'); ?>

                <div class="requflpr-settings-tabs">
                    <nav class="nav-tab-wrapper">
                        <a href="#general" class="nav-tab nav-tab-active"><?php esc_html_e('General', 'request-flow-pro'); ?></a>
                        <a href="#email" class="nav-tab"><?php esc_html_e('Email Settings', 'request-flow-pro'); ?></a>
                        <a href="#templates" class="nav-tab"><?php esc_html_e('Email Templates', 'request-flow-pro'); ?></a>
                        <a href="#domains" class="nav-tab"><?php esc_html_e('Domain Management', 'request-flow-pro'); ?></a>
                        <a href="#advanced" class="nav-tab"><?php esc_html_e('Advanced', 'request-flow-pro'); ?></a>
                    </nav>

                    <!-- General Settings Tab -->
                    <div id="general" class="tab-content active">
                        <div class="settings-section">
                            <h2><?php esc_html_e('General Settings', 'request-flow-pro'); ?></h2>

                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php esc_html_e('Enable Email Notifications', 'request-flow-pro'); ?></th>
                                    <td>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="email_notifications" value="1" <?php checked($email_notifications, 1); ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                        <p class="description">
                                            <?php esc_html_e('Send email notifications to users when their request status changes', 'request-flow-pro'); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?php esc_html_e('Require Admin Approval', 'request-flow-pro'); ?></th>
                                    <td>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="require_approval" value="1" <?php checked($require_approval, 1); ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                        <p class="description">
                                            <?php esc_html_e('All requests must be manually approved by an administrator', 'request-flow-pro'); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?php esc_html_e('Pending Message', 'request-flow-pro'); ?></th>
                                    <td>
                                        <textarea name="pending_message" rows="3"
                                            class="large-text"><?php echo esc_textarea($pending_message); ?></textarea>
                                        <p class="description">
                                            <?php esc_html_e('Message shown to users after submitting a request', 'request-flow-pro'); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Email Settings Tab -->
                    <div id="email" class="tab-content">
                        <div class="settings-section">
                            <h2><?php esc_html_e('Email Configuration', 'request-flow-pro'); ?></h2>

                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php esc_html_e('Admin Email Address', 'request-flow-pro'); ?></th>
                                    <td>
                                        <input type="email" name="admin_email" value="<?php echo esc_attr($admin_email); ?>"
                                            class="regular-text">
                                        <p class="description">
                                            <?php esc_html_e('Email address to receive notifications about new requests', 'request-flow-pro'); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?php esc_html_e('From Name', 'request-flow-pro'); ?></th>
                                    <td>
                                        <input type="text" name="email_from_name"
                                            value="<?php echo esc_attr($email_from_name); ?>" class="regular-text">
                                        <p class="description">
                                            <?php esc_html_e('Name shown in email "From" field', 'request-flow-pro'); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?php esc_html_e('From Email', 'request-flow-pro'); ?></th>
                                    <td>
                                        <input type="email" name="email_from_email"
                                            value="<?php echo esc_attr($email_from_email); ?>" class="regular-text">
                                        <p class="description">
                                            <?php esc_html_e('Email address shown in "From" field', 'request-flow-pro'); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Email Templates Tab -->
                    <div id="templates" class="tab-content">
                        <div class="settings-section">
                            <h2><?php esc_html_e('Email Templates', 'request-flow-pro'); ?></h2>
                            <p class="description">
                                <?php esc_html_e('Available placeholders: {user_name}, {user_email}, {request_title}, {request_description}, {admin_notes}, {site_name}, {site_url}', 'request-flow-pro'); ?>
                            </p>

                            <h3><?php esc_html_e('Approved Email', 'request-flow-pro'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php esc_html_e('Subject', 'request-flow-pro'); ?></th>
                                    <td>
                                        <input type="text" name="email_approved_subject"
                                            value="<?php echo esc_attr($email_approved_subject); ?>" class="large-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e('Message', 'request-flow-pro'); ?></th>
                                    <td>
                                        <textarea name="email_approved_body" rows="8"
                                            class="large-text code"><?php echo esc_textarea($email_approved_body); ?></textarea>
                                    </td>
                                </tr>
                            </table>

                            <h3><?php esc_html_e('Rejected Email', 'request-flow-pro'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php esc_html_e('Subject', 'request-flow-pro'); ?></th>
                                    <td>
                                        <input type="text" name="email_rejected_subject"
                                            value="<?php echo esc_attr($email_rejected_subject); ?>" class="large-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e('Message', 'request-flow-pro'); ?></th>
                                    <td>
                                        <textarea name="email_rejected_body" rows="8"
                                            class="large-text code"><?php echo esc_textarea($email_rejected_body); ?></textarea>
                                    </td>
                                </tr>
                            </table>

                            <h3><?php esc_html_e('Pending/Confirmation Email', 'request-flow-pro'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php esc_html_e('Subject', 'request-flow-pro'); ?></th>
                                    <td>
                                        <input type="text" name="email_pending_subject"
                                            value="<?php echo esc_attr($email_pending_subject); ?>" class="large-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e('Message', 'request-flow-pro'); ?></th>
                                    <td>
                                        <textarea name="email_pending_body" rows="8"
                                            class="large-text code"><?php echo esc_textarea($email_pending_body); ?></textarea>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Domain Management Tab -->
                    <div id="domains" class="tab-content">
                        <div class="settings-section">
                            <h2><?php esc_html_e('Domain Whitelist/Blacklist', 'request-flow-pro'); ?></h2>

                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php esc_html_e('Enable Whitelist', 'request-flow-pro'); ?></th>
                                    <td>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="whitelist_enabled" value="1" <?php checked($whitelist_enabled, 1); ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                        <p class="description">
                                            <?php esc_html_e('Only allow requests from whitelisted domains', 'request-flow-pro'); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?php esc_html_e('Whitelisted Domains', 'request-flow-pro'); ?></th>
                                    <td>
                                        <textarea name="domain_whitelist" rows="5" class="large-text code"
                                            placeholder="example.com&#10;company.org"><?php echo esc_textarea($domain_whitelist); ?></textarea>
                                        <p class="description">
                                            <?php esc_html_e('Enter one domain per line (without @ or http://). Only emails from these domains will be accepted.', 'request-flow-pro'); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?php esc_html_e('Enable Blacklist', 'request-flow-pro'); ?></th>
                                    <td>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="blacklist_enabled" value="1" <?php checked($blacklist_enabled, 1); ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                        <p class="description">
                                            <?php esc_html_e('Block requests from blacklisted domains', 'request-flow-pro'); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?php esc_html_e('Blacklisted Domains', 'request-flow-pro'); ?></th>
                                    <td>
                                        <textarea name="domain_blacklist" rows="5" class="large-text code"
                                            placeholder="spam.com&#10;fake-email.net"><?php echo esc_textarea($domain_blacklist); ?></textarea>
                                        <p class="description">
                                            <?php esc_html_e('Enter one domain per line. Emails from these domains will be blocked.', 'request-flow-pro'); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Advanced Tab -->
                    <div id="advanced" class="tab-content">
                        <div class="settings-section">
                            <h2><?php esc_html_e('Advanced Settings', 'request-flow-pro'); ?></h2>

                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php esc_html_e('Auto-Delete Rejected Requests', 'request-flow-pro'); ?>
                                    </th>
                                    <td>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="auto_delete_rejected" value="1" <?php checked($auto_delete_rejected, 1); ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                        <p class="description">
                                            <?php esc_html_e('Automatically delete rejected requests after specified days', 'request-flow-pro'); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?php esc_html_e('Delete After (Days)', 'request-flow-pro'); ?></th>
                                    <td>
                                        <input type="number" name="delete_after_days"
                                            value="<?php echo esc_attr($delete_after_days); ?>" min="1" max="365"
                                            class="small-text">
                                        <p class="description">
                                            <?php esc_html_e('Number of days to keep rejected requests before auto-deletion', 'request-flow-pro'); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <h3><?php esc_html_e('Database Tools', 'request-flow-pro'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php esc_html_e('Export All Requests', 'request-flow-pro'); ?></th>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin-post.php?action=requflpr_export_csv')); ?>"
                                            class="button button-secondary">
                                            <span class="dashicons dashicons-download"></span>
                                            <?php esc_html_e('Export to CSV', 'request-flow-pro'); ?>
                                        </a>
                                        <p class="description">
                                            <?php esc_html_e('Download all requests as CSV file', 'request-flow-pro'); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <p class="submit">
                    <button type="submit" name="requflpr_settings_submit" class="button button-primary button-large">
                        <span class="dashicons dashicons-yes"></span>
                        <?php esc_html_e('Save All Settings', 'request-flow-pro'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Get default email templates
     */
    private function get_default_approved_template()
    {
        return "Hello {user_name},\n\nGreat news! Your request \"{request_title}\" has been approved.\n\n{admin_notes}\n\nThank you,\n{site_name}";
    }

    private function get_default_rejected_template()
    {
        return "Hello {user_name},\n\nWe regret to inform you that your request \"{request_title}\" has been rejected.\n\n{admin_notes}\n\nIf you have any questions, please contact us.\n\nThank you,\n{site_name}";
    }

    private function get_default_pending_template()
    {
        return "Hello {user_name},\n\nThank you for submitting your request \"{request_title}\".\n\nYour request is currently pending review. You will receive an email notification once it has been processed.\n\nThank you for your patience,\n{site_name}";
    }

    /**
     * Handle status update
     */
    public function handle_status_update()
    {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'requflpr_update_status')) {
            wp_die(esc_html__('Security check failed.', 'request-flow-pro'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'request-flow-pro'));
        }

        $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
        $new_status = isset($_POST['new_status']) ? sanitize_text_field(wp_unslash($_POST['new_status'])) : '';
        $admin_notes = isset($_POST['admin_notes']) ? sanitize_textarea_field(wp_unslash($_POST['admin_notes'])) : '';

        if (RequflprDatabase::update_status($request_id, $new_status, $admin_notes)) {
            // Send email notification if enabled
            if (get_option('requflpr_email_notifications', 1)) {
                $this->send_status_notification($request_id, $new_status);
            }

            wp_safe_redirect(add_query_arg('updated', 'true', wp_get_referer()));
        } else {
            wp_safe_redirect(add_query_arg('error', 'true', wp_get_referer()));
        }
        exit;
    }

    /**
     * Send email notification
     */
    private function send_status_notification($request_id, $status)
    {
        $request = RequflprDatabase::get_request($request_id);

        if ($request) {
            $from_name = get_option('requflpr_email_from_name', get_bloginfo('name'));
            $from_email = get_option('requflpr_email_from_email', get_option('admin_email'));

            $headers = array(
                'From: ' . $from_name . ' <' . $from_email . '>',
                'Content-Type: text/html; charset=UTF-8'
            );

            // Get appropriate template based on status
            if ($status === 'approved') {
                $subject = get_option('requflpr_email_approved_subject', __('Your request has been approved', 'request-flow-pro'));
                $body_template = get_option('requflpr_email_approved_body', "Hello {user_name},\n\nGreat news! Your request \"{request_title}\" has been approved.\n\n{admin_notes}\n\nThank you,\n{site_name}");
            } else {
                $subject = get_option('requflpr_email_rejected_subject', __('Your request has been rejected', 'request-flow-pro'));
                $body_template = get_option('requflpr_email_rejected_body', "Hello {user_name},\n\nWe regret to inform you that your request \"{request_title}\" has been rejected.\n\n{admin_notes}\n\nIf you have any questions, please contact us.\n\nThank you,\n{site_name}");
            }

            // Replace placeholders
            $subject = $this->replace_placeholders($subject, $request);
            $body = $this->replace_placeholders($body_template, $request);
            $body = nl2br($body);

            wp_mail($request->submitter_email, $subject, $body, $headers);
        }
    }

    /**
     * Replace email placeholders
     */
    private function replace_placeholders($text, $request)
    {
        $placeholders = array(
            '{user_name}' => $request->submitter_name,
            '{user_email}' => $request->submitter_email,
            '{request_title}' => $request->title,
            '{request_description}' => $request->description,
            '{admin_notes}' => isset($request->admin_notes) ? $request->admin_notes : '',
            '{site_name}' => get_bloginfo('name'),
            '{site_url}' => get_site_url(),
            '{priority}' => isset($request->priority) ? ucfirst($request->priority) : '',
            '{category}' => isset($request->category) ? ucfirst($request->category) : ''
        );

        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }

    /**
     * Export requests to CSV
     */
    public function export_csv()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'request-flow-pro'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'requflpr_requests';

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $requests = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A);

        if (empty($requests)) {
            wp_die(esc_html__('No requests to export.', 'request-flow-pro'));
        }

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=requflpr-requests-' . gmdate('Y-m-d') . '.csv');

        // Create file pointer
        $output = fopen('php://output', 'w');

        // Add CSV headers
        fputcsv($output, array('ID', 'Title', 'Description', 'Priority', 'Category', 'Submitter Name', 'Submitter Email', 'Status', 'Admin Notes', 'Created At', 'Updated At'));

        // Add data rows
        foreach ($requests as $request) {
            fputcsv($output, array(
                $request['id'],
                $request['title'],
                $request['description'],
                isset($request['priority']) ? $request['priority'] : 'medium',
                isset($request['category']) ? $request['category'] : 'general',
                $request['submitter_name'],
                $request['submitter_email'],
                $request['status'],
                $request['admin_notes'],
                $request['created_at'],
                $request['updated_at']
            ));
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
        fclose($output);
        exit;
    }

    /**
     * Handle bulk actions
     */
    public function handle_bulk_action()
    {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'requflpr_bulk_action')) {
            wp_die(esc_html__('Security check failed.', 'request-flow-pro'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'request-flow-pro'));
        }

        $bulk_action = isset($_POST['bulk_action']) ? sanitize_text_field(wp_unslash($_POST['bulk_action'])) : '';
        $request_ids = isset($_POST['request_ids']) ? array_map('intval', $_POST['request_ids']) : array();

        if (empty($request_ids) || $bulk_action === '-1') {
            wp_safe_redirect(wp_get_referer());
            exit;
        }

        $status_map = array(
            'approve' => 'approved',
            'reject' => 'rejected'
        );

        if (isset($status_map[$bulk_action])) {
            $new_status = $status_map[$bulk_action];
            RequflprDatabase::bulk_update_status($request_ids, $new_status);

            // Send notifications if enabled
            if (get_option('requflpr_email_notifications', 1)) {
                foreach ($request_ids as $request_id) {
                    $this->send_status_notification($request_id, $new_status);
                }
            }
        }

        wp_safe_redirect(add_query_arg('bulk_updated', count($request_ids), wp_get_referer()));
        exit;
    }

    /**
     * AJAX: Get request details
     */
    public function ajax_get_request_details()
    {
        check_ajax_referer('requflpr_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'request-flow-pro')));
        }

        $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
        $request = RequflprDatabase::get_request($request_id);

        if (!$request) {
            wp_send_json_error(array('message' => __('Request not found.', 'request-flow-pro')));
        }

        ob_start();
        ?>
        <div class="approval-request-details">
            <h2><?php echo esc_html($request->title); ?></h2>

            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e('Status:', 'request-flow-pro'); ?></span>
                <span class="status-badge status-<?php echo esc_attr($request->status); ?>">
                    <?php echo esc_html(ucfirst($request->status)); ?>
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e('Priority:', 'request-flow-pro'); ?></span>
                <span class="priority-badge priority-<?php echo esc_attr($request->priority); ?>">
                    <?php echo esc_html(ucfirst($request->priority)); ?>
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e('Category:', 'request-flow-pro'); ?></span>
                <span><?php echo esc_html(ucfirst($request->category)); ?></span>
            </div>

            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e('Submitter:', 'request-flow-pro'); ?></span>
                <span><?php echo esc_html($request->submitter_name); ?>
                    (<?php echo esc_html($request->submitter_email); ?>)</span>
            </div>

            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e('Submitted:', 'request-flow-pro'); ?></span>
                <span><?php echo esc_html(wp_date('F j, Y g:i A', strtotime($request->created_at))); ?></span>
            </div>

            <?php if ($request->updated_at !== $request->created_at): ?>
                <div class="detail-row">
                    <span class="detail-label"><?php esc_html_e('Last Updated:', 'request-flow-pro'); ?></span>
                    <span><?php echo esc_html(wp_date('F j, Y g:i A', strtotime($request->updated_at))); ?></span>
                </div>
            <?php endif; ?>

            <div class="detail-section">
                <h3><?php esc_html_e('Description', 'request-flow-pro'); ?></h3>
                <div class="description-content">
                    <?php echo nl2br(esc_html($request->description)); ?>
                </div>
            </div>

            <?php if ($request->admin_notes): ?>
                <div class="detail-section">
                    <h3><?php esc_html_e('Admin Notes', 'request-flow-pro'); ?></h3>
                    <div class="admin-notes-content">
                        <?php echo nl2br(esc_html($request->admin_notes)); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($request->status === 'pending'): ?>
                <div class="modal-actions">
                    <textarea id="modal-admin-notes"
                        placeholder="<?php esc_attr_e('Add admin notes (optional)...', 'request-flow-pro'); ?>" rows="3"></textarea>
                    <div class="button-group">
                        <button type="button" class="button button-primary button-large modal-approve"
                            data-id="<?php echo absint($request->id); ?>">
                            <span class="dashicons dashicons-yes"></span> <?php esc_html_e('Approve', 'request-flow-pro'); ?>
                        </button>
                        <button type="button" class="button button-large modal-reject"
                            data-id="<?php echo absint($request->id); ?>">
                            <span class="dashicons dashicons-no"></span> <?php esc_html_e('Reject', 'request-flow-pro'); ?>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX: Update request (approve/reject from modal)
     */
    public function ajax_update_request()
    {
        check_ajax_referer('requflpr_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'request-flow-pro')));
        }

        $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
        $new_status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';
        $admin_notes = isset($_POST['admin_notes']) ? sanitize_textarea_field(wp_unslash($_POST['admin_notes'])) : '';

        if (!in_array($new_status, array('approved', 'rejected'))) {
            wp_send_json_error(array('message' => __('Invalid status.', 'request-flow-pro')));
        }

        if (RequflprDatabase::update_status($request_id, $new_status, $admin_notes)) {
            // Send email notification if enabled
            if (get_option('requflpr_email_notifications', 1)) {
                $this->send_status_notification($request_id, $new_status);
            }

            wp_send_json_success(array(
                /* translators: %s: request status (approved or rejected) */
                'message' => sprintf(__('Request %s successfully!', 'request-flow-pro'), $new_status)
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to update request.', 'request-flow-pro')));
        }
    }
}