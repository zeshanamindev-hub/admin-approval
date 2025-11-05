<?php
/**
 * Admin interface for the Approval Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class ApprovalAdmin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_post_approval_update_status', array($this, 'handle_status_update'));
        add_action('admin_post_approval_bulk_action', array($this, 'handle_bulk_action'));
        add_action('admin_post_approval_export_csv', array($this, 'export_csv'));
        add_action('wp_ajax_approval_get_request_details', array($this, 'ajax_get_request_details'));
        add_action('wp_ajax_approval_update_request', array($this, 'ajax_update_request'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Approval Requests', 'approval-plugin'),
            __('Approvals', 'approval-plugin'),
            'manage_options',
            'approval-requests',
            array($this, 'admin_page'),
            'dashicons-yes-alt',
            30
        );
        
        add_submenu_page(
            'approval-requests',
            __('All Requests', 'approval-plugin'),
            __('All Requests', 'approval-plugin'),
            'manage_options',
            'approval-requests',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'approval-requests',
            __('Settings', 'approval-plugin'),
            __('Settings', 'approval-plugin'),
            'manage_options',
            'approval-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'approval') !== false) {
            wp_enqueue_style('approval-admin-css', APPROVAL_PLUGIN_URL . 'css/admin-style.css', array(), APPROVAL_PLUGIN_VERSION);
            wp_enqueue_script('approval-admin-js', APPROVAL_PLUGIN_URL . 'js/admin-script.js', array('jquery'), APPROVAL_PLUGIN_VERSION, true);

            wp_localize_script('approval-admin-js', 'approval_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('approval_nonce')
            ));
        }
    }
    
    /**
     * Main admin page
     */
    public function admin_page() {
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : null;
        $requests = ApprovalDatabase::get_requests($status_filter);
        $counts = array(
            'all' => ApprovalDatabase::get_count(),
            'pending' => ApprovalDatabase::get_count('pending'),
            'approved' => ApprovalDatabase::get_count('approved'),
            'rejected' => ApprovalDatabase::get_count('rejected')
        );
        $stats = ApprovalDatabase::get_statistics();

        ?>
        <div class="wrap approval-plugin-wrap">
            <!-- Page Header -->
            <div class="approval-page-header">
                <div class="page-header-left">
                    <h1 class="approval-main-title">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('Approval Requests', 'approval-plugin'); ?>
                    </h1>
                    <p class="page-subtitle"><?php _e('Manage and track all approval requests in one place', 'approval-plugin'); ?></p>
                </div>
                <div class="page-header-right">
                    <a href="<?php echo admin_url('admin-post.php?action=approval_export_csv'); ?>" class="button button-secondary header-action-btn">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export CSV', 'approval-plugin'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=approval-settings'); ?>" class="button button-secondary header-action-btn">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php _e('Settings', 'approval-plugin'); ?>
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
                        <div class="stat-label"><?php _e('TOTAL REQUESTS', 'approval-plugin'); ?></div>
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
                        <div class="stat-label"><?php _e('PENDING', 'approval-plugin'); ?></div>
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
                        <div class="stat-label"><?php _e('APPROVED', 'approval-plugin'); ?></div>
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
                        <div class="stat-label"><?php _e('REJECTED', 'approval-plugin'); ?></div>
                    </div>
                </div>

                <div class="approval-stat-card approval-stat-rate">
                    <div class="stat-icon">
                        <div class="icon-circle rate-circle">
                            <span class="dashicons dashicons-chart-line"></span>
                        </div>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html($stats['approval_rate']); ?><span class="stat-unit">%</span></div>
                        <div class="stat-label"><?php _e('APPROVAL RATE', 'approval-plugin'); ?></div>
                    </div>
                </div>

                <div class="approval-stat-card approval-stat-time">
                    <div class="stat-icon">
                        <div class="icon-circle time-circle">
                            <span class="dashicons dashicons-backup"></span>
                        </div>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html($stats['avg_response_time']); ?><span class="stat-unit">h</span></div>
                        <div class="stat-label"><?php _e('AVG RESPONSE TIME', 'approval-plugin'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="approval-filters-wrapper">
                <div class="approval-filters-section">
                    <div class="filter-tabs">
                        <a href="?page=approval-requests" class="filter-tab <?php echo !$status_filter ? 'active' : ''; ?>">
                            <span class="tab-label"><?php _e('All', 'approval-plugin'); ?></span>
                            <span class="tab-count"><?php echo $counts['all']; ?></span>
                        </a>
                        <a href="?page=approval-requests&status=pending" class="filter-tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                            <span class="tab-label"><?php _e('Pending', 'approval-plugin'); ?></span>
                            <span class="tab-count"><?php echo $counts['pending']; ?></span>
                        </a>
                        <a href="?page=approval-requests&status=approved" class="filter-tab <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
                            <span class="tab-label"><?php _e('Approved', 'approval-plugin'); ?></span>
                            <span class="tab-count"><?php echo $counts['approved']; ?></span>
                        </a>
                        <a href="?page=approval-requests&status=rejected" class="filter-tab <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">
                            <span class="tab-label"><?php _e('Rejected', 'approval-plugin'); ?></span>
                            <span class="tab-count"><?php echo $counts['rejected']; ?></span>
                        </a>
                    </div>
                    <div class="filter-actions">
                        <input type="text" id="approval-search-input" placeholder="<?php _e('Search requests...', 'approval-plugin'); ?>" class="approval-search-box">
                    </div>
                </div>
            </div>

            <!-- Bulk Actions Form -->
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="approval-bulk-form">
                <input type="hidden" name="action" value="approval_bulk_action">
                <?php wp_nonce_field('approval_bulk_action'); ?>

                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <select name="bulk_action" id="bulk-action-selector">
                            <option value="-1"><?php _e('Bulk Actions', 'approval-plugin'); ?></option>
                            <option value="approve"><?php _e('Approve', 'approval-plugin'); ?></option>
                            <option value="reject"><?php _e('Reject', 'approval-plugin'); ?></option>
                        </select>
                        <input type="submit" class="button action" value="<?php _e('Apply', 'approval-plugin'); ?>">
                    </div>
                </div>

                <table class="wp-list-table widefat fixed striped approval-requests-table">
                    <thead>
                        <tr>
                            <td class="check-column"><input type="checkbox" id="cb-select-all"></td>
                            <th><?php _e('Title', 'approval-plugin'); ?></th>
                            <th><?php _e('Priority', 'approval-plugin'); ?></th>
                            <th><?php _e('Category', 'approval-plugin'); ?></th>
                            <th><?php _e('Submitter', 'approval-plugin'); ?></th>
                            <th><?php _e('Status', 'approval-plugin'); ?></th>
                            <th><?php _e('Date', 'approval-plugin'); ?></th>
                            <th><?php _e('Actions', 'approval-plugin'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="8" class="no-requests"><?php _e('No requests found.', 'approval-plugin'); ?></td>
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
                                            <span><a href="#" class="view-details" data-id="<?php echo $request->id; ?>"><?php _e('View Details', 'approval-plugin'); ?></a></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $priority = isset($request->priority) ? $request->priority : 'medium';
                                        $priority_class = 'priority-' . esc_attr($priority);
                                        ?>
                                        <span class="priority-badge <?php echo $priority_class; ?>">
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
                                    <td><?php echo date('M j, Y g:i A', strtotime($request->created_at)); ?></td>
                                    <td class="approval-actions-cell">
                                        <?php if ($request->status === 'pending'): ?>
                                            <button type="button" class="button button-primary button-small quick-approve" data-id="<?php echo $request->id; ?>">
                                                <?php _e('Approve', 'approval-plugin'); ?>
                                            </button>
                                            <button type="button" class="button button-small quick-reject" data-id="<?php echo $request->id; ?>">
                                                <?php _e('Reject', 'approval-plugin'); ?>
                                            </button>
                                        <?php else: ?>
                                            <span class="dashicons dashicons-<?php echo $request->status === 'approved' ? 'yes' : 'no'; ?>"></span>
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
                            <?php _e('Loading...', 'approval-plugin'); ?>
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
    public function settings_page() {
        // Handle form submission
        if (isset($_POST['approval_settings_submit'])) {
            check_admin_referer('approval_settings_save');

            // Email Settings
            update_option('approval_plugin_email_notifications', isset($_POST['email_notifications']) ? 1 : 0);
            update_option('approval_plugin_admin_email', sanitize_email($_POST['admin_email']));
            update_option('approval_plugin_email_from_name', sanitize_text_field($_POST['email_from_name']));
            update_option('approval_plugin_email_from_email', sanitize_email($_POST['email_from_email']));

            // Email Templates
            update_option('approval_plugin_email_approved_subject', sanitize_text_field($_POST['email_approved_subject']));
            update_option('approval_plugin_email_approved_body', wp_kses_post($_POST['email_approved_body']));
            update_option('approval_plugin_email_rejected_subject', sanitize_text_field($_POST['email_rejected_subject']));
            update_option('approval_plugin_email_rejected_body', wp_kses_post($_POST['email_rejected_body']));
            update_option('approval_plugin_email_pending_subject', sanitize_text_field($_POST['email_pending_subject']));
            update_option('approval_plugin_email_pending_body', wp_kses_post($_POST['email_pending_body']));

            // Domain Settings
            $domain_whitelist = sanitize_textarea_field($_POST['domain_whitelist']);
            $domain_blacklist = sanitize_textarea_field($_POST['domain_blacklist']);
            update_option('approval_plugin_domain_whitelist', $domain_whitelist);
            update_option('approval_plugin_domain_blacklist', $domain_blacklist);
            update_option('approval_plugin_whitelist_enabled', isset($_POST['whitelist_enabled']) ? 1 : 0);
            update_option('approval_plugin_blacklist_enabled', isset($_POST['blacklist_enabled']) ? 1 : 0);

            // General Settings
            update_option('approval_plugin_auto_delete_rejected', isset($_POST['auto_delete_rejected']) ? 1 : 0);
            update_option('approval_plugin_delete_after_days', intval($_POST['delete_after_days']));
            update_option('approval_plugin_require_approval', isset($_POST['require_approval']) ? 1 : 0);
            update_option('approval_plugin_show_pending_message', sanitize_textarea_field($_POST['pending_message']));

            // User Approval Settings
            update_option('approval_plugin_enable_user_approval', isset($_POST['enable_user_approval']) ? 1 : 0);
            update_option('approval_plugin_user_registration_message', sanitize_textarea_field($_POST['user_registration_message']));
            update_option('approval_plugin_user_pending_subject', sanitize_text_field($_POST['user_pending_subject']));
            update_option('approval_plugin_user_pending_body', wp_kses_post($_POST['user_pending_body']));
            update_option('approval_plugin_user_approved_subject', sanitize_text_field($_POST['user_approved_subject']));
            update_option('approval_plugin_user_approved_body', wp_kses_post($_POST['user_approved_body']));
            update_option('approval_plugin_user_denied_subject', sanitize_text_field($_POST['user_denied_subject']));
            update_option('approval_plugin_user_denied_body', wp_kses_post($_POST['user_denied_body']));

            echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'approval-plugin') . '</p></div>';
        }

        // Get current settings
        $email_notifications = get_option('approval_plugin_email_notifications', 1);
        $admin_email = get_option('approval_plugin_admin_email', get_option('admin_email'));
        $email_from_name = get_option('approval_plugin_email_from_name', get_bloginfo('name'));
        $email_from_email = get_option('approval_plugin_email_from_email', get_option('admin_email'));

        // Email Templates
        $email_approved_subject = get_option('approval_plugin_email_approved_subject', __('Your request has been approved', 'approval-plugin'));
        $email_approved_body = get_option('approval_plugin_email_approved_body', $this->get_default_approved_template());
        $email_rejected_subject = get_option('approval_plugin_email_rejected_subject', __('Your request has been rejected', 'approval-plugin'));
        $email_rejected_body = get_option('approval_plugin_email_rejected_body', $this->get_default_rejected_template());
        $email_pending_subject = get_option('approval_plugin_email_pending_subject', __('Your request has been received', 'approval-plugin'));
        $email_pending_body = get_option('approval_plugin_email_pending_body', $this->get_default_pending_template());

        // Domain Settings
        $domain_whitelist = get_option('approval_plugin_domain_whitelist', '');
        $domain_blacklist = get_option('approval_plugin_domain_blacklist', '');
        $whitelist_enabled = get_option('approval_plugin_whitelist_enabled', 0);
        $blacklist_enabled = get_option('approval_plugin_blacklist_enabled', 0);

        // General Settings
        $auto_delete_rejected = get_option('approval_plugin_auto_delete_rejected', 0);
        $delete_after_days = get_option('approval_plugin_delete_after_days', 30);
        $require_approval = get_option('approval_plugin_require_approval', 1);
        $pending_message = get_option('approval_plugin_show_pending_message', __('Your request is pending approval. You will be notified via email once it has been reviewed.', 'approval-plugin'));

        ?>
        <div class="wrap approval-plugin-wrap">
            <h1 class="approval-main-title">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php _e('Approval Plugin Settings', 'approval-plugin'); ?>
            </h1>

            <form method="post" action="" class="approval-settings-form">
                <?php wp_nonce_field('approval_settings_save'); ?>

                <div class="approval-settings-tabs">
                    <nav class="nav-tab-wrapper">
                        <a href="#general" class="nav-tab nav-tab-active"><?php _e('General', 'approval-plugin'); ?></a>
                        <a href="#email" class="nav-tab"><?php _e('Email Settings', 'approval-plugin'); ?></a>
                        <a href="#templates" class="nav-tab"><?php _e('Email Templates', 'approval-plugin'); ?></a>
                        <a href="#domains" class="nav-tab"><?php _e('Domain Management', 'approval-plugin'); ?></a>
                        <a href="#advanced" class="nav-tab"><?php _e('Advanced', 'approval-plugin'); ?></a>
                    </nav>

                    <!-- General Settings Tab -->
                    <div id="general" class="tab-content active">
                        <div class="settings-section">
                            <h2><?php _e('General Settings', 'approval-plugin'); ?></h2>

                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Enable Email Notifications', 'approval-plugin'); ?></th>
                                    <td>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="email_notifications" value="1" <?php checked($email_notifications, 1); ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                        <p class="description"><?php _e('Send email notifications to users when their request status changes', 'approval-plugin'); ?></p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?php _e('Require Admin Approval', 'approval-plugin'); ?></th>
                                    <td>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="require_approval" value="1" <?php checked($require_approval, 1); ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                        <p class="description"><?php _e('All requests must be manually approved by an administrator', 'approval-plugin'); ?></p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?php _e('Pending Message', 'approval-plugin'); ?></th>
                                    <td>
                                        <textarea name="pending_message" rows="3" class="large-text"><?php echo esc_textarea($pending_message); ?></textarea>
                                        <p class="description"><?php _e('Message shown to users after submitting a request', 'approval-plugin'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Email Settings Tab -->
                    <div id="email" class="tab-content">
                        <div class="settings-section">
                            <h2><?php _e('Email Configuration', 'approval-plugin'); ?></h2>

                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Admin Email Address', 'approval-plugin'); ?></th>
                                    <td>
                                        <input type="email" name="admin_email" value="<?php echo esc_attr($admin_email); ?>" class="regular-text">
                                        <p class="description"><?php _e('Email address to receive notifications about new requests', 'approval-plugin'); ?></p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?php _e('From Name', 'approval-plugin'); ?></th>
                                    <td>
                                        <input type="text" name="email_from_name" value="<?php echo esc_attr($email_from_name); ?>" class="regular-text">
                                        <p class="description"><?php _e('Name shown in email "From" field', 'approval-plugin'); ?></p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?php _e('From Email', 'approval-plugin'); ?></th>
                                    <td>
                                        <input type="email" name="email_from_email" value="<?php echo esc_attr($email_from_email); ?>" class="regular-text">
                                        <p class="description"><?php _e('Email address shown in "From" field', 'approval-plugin'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Email Templates Tab -->
                    <div id="templates" class="tab-content">
                        <div class="settings-section">
                            <h2><?php _e('Email Templates', 'approval-plugin'); ?></h2>
                            <p class="description"><?php _e('Available placeholders: {user_name}, {user_email}, {request_title}, {request_description}, {admin_notes}, {site_name}, {site_url}', 'approval-plugin'); ?></p>

                            <h3><?php _e('Approved Email', 'approval-plugin'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Subject', 'approval-plugin'); ?></th>
                                    <td>
                                        <input type="text" name="email_approved_subject" value="<?php echo esc_attr($email_approved_subject); ?>" class="large-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Message', 'approval-plugin'); ?></th>
                                    <td>
                                        <textarea name="email_approved_body" rows="8" class="large-text code"><?php echo esc_textarea($email_approved_body); ?></textarea>
                                    </td>
                                </tr>
                            </table>

                            <h3><?php _e('Rejected Email', 'approval-plugin'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Subject', 'approval-plugin'); ?></th>
                                    <td>
                                        <input type="text" name="email_rejected_subject" value="<?php echo esc_attr($email_rejected_subject); ?>" class="large-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Message', 'approval-plugin'); ?></th>
                                    <td>
                                        <textarea name="email_rejected_body" rows="8" class="large-text code"><?php echo esc_textarea($email_rejected_body); ?></textarea>
                                    </td>
                                </tr>
                            </table>

                            <h3><?php _e('Pending/Confirmation Email', 'approval-plugin'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Subject', 'approval-plugin'); ?></th>
                                    <td>
                                        <input type="text" name="email_pending_subject" value="<?php echo esc_attr($email_pending_subject); ?>" class="large-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Message', 'approval-plugin'); ?></th>
                                    <td>
                                        <textarea name="email_pending_body" rows="8" class="large-text code"><?php echo esc_textarea($email_pending_body); ?></textarea>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Domain Management Tab -->
                    <div id="domains" class="tab-content">
                        <div class="settings-section">
                            <h2><?php _e('Domain Whitelist/Blacklist', 'approval-plugin'); ?></h2>

                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Enable Whitelist', 'approval-plugin'); ?></th>
                                    <td>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="whitelist_enabled" value="1" <?php checked($whitelist_enabled, 1); ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                        <p class="description"><?php _e('Only allow requests from whitelisted domains', 'approval-plugin'); ?></p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?php _e('Whitelisted Domains', 'approval-plugin'); ?></th>
                                    <td>
                                        <textarea name="domain_whitelist" rows="5" class="large-text code" placeholder="example.com&#10;company.org"><?php echo esc_textarea($domain_whitelist); ?></textarea>
                                        <p class="description"><?php _e('Enter one domain per line (without @ or http://). Only emails from these domains will be accepted.', 'approval-plugin'); ?></p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?php _e('Enable Blacklist', 'approval-plugin'); ?></th>
                                    <td>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="blacklist_enabled" value="1" <?php checked($blacklist_enabled, 1); ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                        <p class="description"><?php _e('Block requests from blacklisted domains', 'approval-plugin'); ?></p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?php _e('Blacklisted Domains', 'approval-plugin'); ?></th>
                                    <td>
                                        <textarea name="domain_blacklist" rows="5" class="large-text code" placeholder="spam.com&#10;fake-email.net"><?php echo esc_textarea($domain_blacklist); ?></textarea>
                                        <p class="description"><?php _e('Enter one domain per line. Emails from these domains will be blocked.', 'approval-plugin'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Advanced Tab -->
                    <div id="advanced" class="tab-content">
                        <div class="settings-section">
                            <h2><?php _e('Advanced Settings', 'approval-plugin'); ?></h2>

                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Auto-Delete Rejected Requests', 'approval-plugin'); ?></th>
                                    <td>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="auto_delete_rejected" value="1" <?php checked($auto_delete_rejected, 1); ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                        <p class="description"><?php _e('Automatically delete rejected requests after specified days', 'approval-plugin'); ?></p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?php _e('Delete After (Days)', 'approval-plugin'); ?></th>
                                    <td>
                                        <input type="number" name="delete_after_days" value="<?php echo esc_attr($delete_after_days); ?>" min="1" max="365" class="small-text">
                                        <p class="description"><?php _e('Number of days to keep rejected requests before auto-deletion', 'approval-plugin'); ?></p>
                                    </td>
                                </tr>
                            </table>

                            <h3><?php _e('Database Tools', 'approval-plugin'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Export All Requests', 'approval-plugin'); ?></th>
                                    <td>
                                        <a href="<?php echo admin_url('admin-post.php?action=approval_export_csv'); ?>" class="button button-secondary">
                                            <span class="dashicons dashicons-download"></span> <?php _e('Export to CSV', 'approval-plugin'); ?>
                                        </a>
                                        <p class="description"><?php _e('Download all requests as CSV file', 'approval-plugin'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <p class="submit">
                    <button type="submit" name="approval_settings_submit" class="button button-primary button-large">
                        <span class="dashicons dashicons-yes"></span> <?php _e('Save All Settings', 'approval-plugin'); ?>
                    </button>
                </p>
            </form>
        </div>

        <style>
        .approval-settings-tabs {
            background: white;
            margin-top: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .nav-tab-wrapper {
            border-bottom: 1px solid #ccc;
            padding: 0 20px;
            margin: 0;
        }

        .nav-tab {
            border: none;
            padding: 15px 20px;
            margin: 0;
            background: none;
            border-bottom: 3px solid transparent;
        }

        .nav-tab-active {
            border-bottom-color: #667eea;
            color: #667eea;
            font-weight: 600;
        }

        .tab-content {
            display: none;
            padding: 30px;
        }

        .tab-content.active {
            display: block;
        }

        .settings-section h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f1;
        }

        .settings-section h3 {
            margin-top: 30px;
            color: #667eea;
        }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: #10b981;
        }

        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }

        .form-table th {
            font-weight: 600;
            color: #1f2937;
        }

        .approval-settings-form .button-primary {
            padding: 10px 30px;
            height: auto;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Tab switching
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');

                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');

                $('.tab-content').removeClass('active');
                $(target).addClass('active');
            });
        });
        </script>
        <?php
    }

    /**
     * Get default email templates
     */
    private function get_default_approved_template() {
        return "Hello {user_name},\n\nGreat news! Your request \"{request_title}\" has been approved.\n\n{admin_notes}\n\nThank you,\n{site_name}";
    }

    private function get_default_rejected_template() {
        return "Hello {user_name},\n\nWe regret to inform you that your request \"{request_title}\" has been rejected.\n\n{admin_notes}\n\nIf you have any questions, please contact us.\n\nThank you,\n{site_name}";
    }

    private function get_default_pending_template() {
        return "Hello {user_name},\n\nThank you for submitting your request \"{request_title}\".\n\nYour request is currently pending review. You will receive an email notification once it has been processed.\n\nThank you for your patience,\n{site_name}";
    }
    
    /**
     * Handle status update
     */
    public function handle_status_update() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'approval_update_status')) {
            wp_die(__('Security check failed.', 'approval-plugin'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'approval-plugin'));
        }
        
        $request_id = intval($_POST['request_id']);
        $new_status = sanitize_text_field($_POST['new_status']);
        $admin_notes = isset($_POST['admin_notes']) ? sanitize_textarea_field($_POST['admin_notes']) : '';
        
        if (ApprovalDatabase::update_status($request_id, $new_status, $admin_notes)) {
            // Send email notification if enabled
            if (get_option('approval_plugin_email_notifications', 1)) {
                $this->send_status_notification($request_id, $new_status);
            }
            
            wp_redirect(add_query_arg('updated', 'true', wp_get_referer()));
        } else {
            wp_redirect(add_query_arg('error', 'true', wp_get_referer()));
        }
        exit;
    }
    
    /**
     * Send email notification
     */
    private function send_status_notification($request_id, $status) {
        $request = ApprovalDatabase::get_request($request_id);

        if ($request) {
            $from_name = get_option('approval_plugin_email_from_name', get_bloginfo('name'));
            $from_email = get_option('approval_plugin_email_from_email', get_option('admin_email'));

            $headers = array(
                'From: ' . $from_name . ' <' . $from_email . '>',
                'Content-Type: text/html; charset=UTF-8'
            );

            // Get appropriate template based on status
            if ($status === 'approved') {
                $subject = get_option('approval_plugin_email_approved_subject', __('Your request has been approved', 'approval-plugin'));
                $body_template = get_option('approval_plugin_email_approved_body', "Hello {user_name},\n\nGreat news! Your request \"{request_title}\" has been approved.\n\n{admin_notes}\n\nThank you,\n{site_name}");
            } else {
                $subject = get_option('approval_plugin_email_rejected_subject', __('Your request has been rejected', 'approval-plugin'));
                $body_template = get_option('approval_plugin_email_rejected_body', "Hello {user_name},\n\nWe regret to inform you that your request \"{request_title}\" has been rejected.\n\n{admin_notes}\n\nIf you have any questions, please contact us.\n\nThank you,\n{site_name}");
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
    private function replace_placeholders($text, $request) {
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
    public function export_csv() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'approval-plugin'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'approval_requests';

        $requests = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A);

        if (empty($requests)) {
            wp_die(__('No requests to export.', 'approval-plugin'));
        }

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=approval-requests-' . date('Y-m-d') . '.csv');

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

        fclose($output);
        exit;
    }

    /**
     * Handle bulk actions
     */
    public function handle_bulk_action() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'approval_bulk_action')) {
            wp_die(__('Security check failed.', 'approval-plugin'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'approval-plugin'));
        }

        $bulk_action = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : '';
        $request_ids = isset($_POST['request_ids']) ? array_map('intval', $_POST['request_ids']) : array();

        if (empty($request_ids) || $bulk_action === '-1') {
            wp_redirect(wp_get_referer());
            exit;
        }

        $status_map = array(
            'approve' => 'approved',
            'reject' => 'rejected'
        );

        if (isset($status_map[$bulk_action])) {
            $new_status = $status_map[$bulk_action];
            ApprovalDatabase::bulk_update_status($request_ids, $new_status);

            // Send notifications if enabled
            if (get_option('approval_plugin_email_notifications', 1)) {
                foreach ($request_ids as $request_id) {
                    $this->send_status_notification($request_id, $new_status);
                }
            }
        }

        wp_redirect(add_query_arg('bulk_updated', count($request_ids), wp_get_referer()));
        exit;
    }

    /**
     * AJAX: Get request details
     */
    public function ajax_get_request_details() {
        check_ajax_referer('approval_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'approval-plugin')));
        }

        $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
        $request = ApprovalDatabase::get_request($request_id);

        if (!$request) {
            wp_send_json_error(array('message' => __('Request not found.', 'approval-plugin')));
        }

        ob_start();
        ?>
        <div class="approval-request-details">
            <h2><?php echo esc_html($request->title); ?></h2>

            <div class="detail-row">
                <span class="detail-label"><?php _e('Status:', 'approval-plugin'); ?></span>
                <span class="status-badge status-<?php echo esc_attr($request->status); ?>">
                    <?php echo esc_html(ucfirst($request->status)); ?>
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label"><?php _e('Priority:', 'approval-plugin'); ?></span>
                <span class="priority-badge priority-<?php echo esc_attr($request->priority); ?>">
                    <?php echo esc_html(ucfirst($request->priority)); ?>
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label"><?php _e('Category:', 'approval-plugin'); ?></span>
                <span><?php echo esc_html(ucfirst($request->category)); ?></span>
            </div>

            <div class="detail-row">
                <span class="detail-label"><?php _e('Submitter:', 'approval-plugin'); ?></span>
                <span><?php echo esc_html($request->submitter_name); ?> (<?php echo esc_html($request->submitter_email); ?>)</span>
            </div>

            <div class="detail-row">
                <span class="detail-label"><?php _e('Submitted:', 'approval-plugin'); ?></span>
                <span><?php echo date('F j, Y g:i A', strtotime($request->created_at)); ?></span>
            </div>

            <?php if ($request->updated_at !== $request->created_at): ?>
            <div class="detail-row">
                <span class="detail-label"><?php _e('Last Updated:', 'approval-plugin'); ?></span>
                <span><?php echo date('F j, Y g:i A', strtotime($request->updated_at)); ?></span>
            </div>
            <?php endif; ?>

            <div class="detail-section">
                <h3><?php _e('Description', 'approval-plugin'); ?></h3>
                <div class="description-content">
                    <?php echo nl2br(esc_html($request->description)); ?>
                </div>
            </div>

            <?php if ($request->admin_notes): ?>
            <div class="detail-section">
                <h3><?php _e('Admin Notes', 'approval-plugin'); ?></h3>
                <div class="admin-notes-content">
                    <?php echo nl2br(esc_html($request->admin_notes)); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($request->status === 'pending'): ?>
            <div class="modal-actions">
                <textarea id="modal-admin-notes" placeholder="<?php _e('Add admin notes (optional)...', 'approval-plugin'); ?>" rows="3"></textarea>
                <div class="button-group">
                    <button type="button" class="button button-primary button-large modal-approve" data-id="<?php echo $request->id; ?>">
                        <span class="dashicons dashicons-yes"></span> <?php _e('Approve', 'approval-plugin'); ?>
                    </button>
                    <button type="button" class="button button-large modal-reject" data-id="<?php echo $request->id; ?>">
                        <span class="dashicons dashicons-no"></span> <?php _e('Reject', 'approval-plugin'); ?>
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
    public function ajax_update_request() {
        check_ajax_referer('approval_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'approval-plugin')));
        }

        $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
        $new_status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $admin_notes = isset($_POST['admin_notes']) ? sanitize_textarea_field($_POST['admin_notes']) : '';

        if (!in_array($new_status, array('approved', 'rejected'))) {
            wp_send_json_error(array('message' => __('Invalid status.', 'approval-plugin')));
        }

        if (ApprovalDatabase::update_status($request_id, $new_status, $admin_notes)) {
            // Send email notification if enabled
            if (get_option('approval_plugin_email_notifications', 1)) {
                $this->send_status_notification($request_id, $new_status);
            }

            wp_send_json_success(array(
                'message' => sprintf(__('Request %s successfully!', 'approval-plugin'), $new_status)
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to update request.', 'approval-plugin')));
        }
    }
}