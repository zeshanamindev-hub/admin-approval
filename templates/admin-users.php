<?php
/**
 * User Approvals Admin Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap approval-plugin-wrap">
    <!-- Page Header -->
    <div class="approval-page-header">
        <div class="page-header-left">
            <h1 class="approval-main-title">
                <span class="dashicons dashicons-admin-users"></span>
                <?php esc_html_e('User Approvals', 'request-flow-pro'); ?>
            </h1>
            <p class="page-subtitle">
                <?php esc_html_e('Manage user registration approvals and access control', 'request-flow-pro'); ?>
            </p>
        </div>
        <div class="page-header-right">
            <a href="<?php echo admin_url('admin.php?page=approval-settings&tab=users'); ?>"
                class="button button-secondary header-action-btn">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php esc_html_e('User Settings', 'request-flow-pro'); ?>
            </a>
        </div>
    </div>

    <!-- Statistics Cards for Users -->
    <div class="approval-stats-dashboard">
        <div class="approval-stat-card approval-stat-total">
            <div class="stat-icon">
                <div class="icon-circle total-circle">
                    <span class="dashicons dashicons-groups"></span>
                </div>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo esc_html($counts['all']); ?></div>
                <div class="stat-label"><?php esc_html_e('TOTAL USERS', 'request-flow-pro'); ?></div>
            </div>
        </div>

        <div class="approval-stat-card approval-stat-pending">
            <div class="stat-icon">
                <div class="icon-circle pending-circle">
                    <span class="dashicons dashicons-hourglass"></span>
                </div>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo esc_html($counts['pending']); ?></div>
                <div class="stat-label"><?php esc_html_e('PENDING APPROVAL', 'request-flow-pro'); ?></div>
            </div>
        </div>

        <div class="approval-stat-card approval-stat-approved">
            <div class="stat-icon">
                <div class="icon-circle approved-circle">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo esc_html($counts['approved']); ?></div>
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
                <div class="stat-number"><?php echo esc_html($counts['denied']); ?></div>
                <div class="stat-label"><?php esc_html_e('DENIED', 'request-flow-pro'); ?></div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="approval-filters-wrapper">
        <div class="approval-filters-section">
            <div class="filter-tabs">
                <a href="?page=approval-users" class="filter-tab <?php echo !$status_filter ? 'active' : ''; ?>">
                    <span class="tab-label"><?php esc_html_e('All Users', 'request-flow-pro'); ?></span>
                    <span class="tab-count"><?php echo esc_html($counts['all']); ?></span>
                </a>
                <a href="?page=approval-users&user_status=pending"
                    class="filter-tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                    <span class="tab-label"><?php esc_html_e('Pending', 'request-flow-pro'); ?></span>
                    <span class="tab-count"><?php echo esc_html($counts['pending']); ?></span>
                </a>
                <a href="?page=approval-users&user_status=approved"
                    class="filter-tab <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
                    <span class="tab-label"><?php esc_html_e('Approved', 'request-flow-pro'); ?></span>
                    <span class="tab-count"><?php echo esc_html($counts['approved']); ?></span>
                </a>
                <a href="?page=approval-users&user_status=denied"
                    class="filter-tab <?php echo $status_filter === 'denied' ? 'active' : ''; ?>">
                    <span class="tab-label"><?php esc_html_e('Denied', 'request-flow-pro'); ?></span>
                    <span class="tab-count"><?php echo esc_html($counts['denied']); ?></span>
                </a>
            </div>
            <div class="filter-actions">
                <input type="text" id="user-search-input"
                    placeholder="<?php esc_attr_e('Search users...', 'request-flow-pro'); ?>"
                    class="approval-search-box">
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="approval-users-form">
        <input type="hidden" name="action" value="approval_bulk_user_action">
        <?php wp_nonce_field('approval_bulk_user_action'); ?>

        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <select name="bulk_action" id="bulk-action-selector-users">
                    <option value="-1"><?php esc_html_e('Bulk Actions', 'request-flow-pro'); ?></option>
                    <option value="approve"><?php esc_html_e('Approve', 'request-flow-pro'); ?></option>
                    <option value="deny"><?php esc_html_e('Deny', 'request-flow-pro'); ?></option>
                </select>
                <input type="submit" class="button action" value="<?php esc_attr_e('Apply', 'request-flow-pro'); ?>">
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped approval-requests-table users-table">
            <thead>
                <tr>
                    <td class="check-column"><input type="checkbox" id="cb-select-all-users"></td>
                    <th><?php esc_html_e('Username', 'request-flow-pro'); ?></th>
                    <th><?php esc_html_e('Name', 'request-flow-pro'); ?></th>
                    <th><?php esc_html_e('Email', 'request-flow-pro'); ?></th>
                    <th><?php esc_html_e('Role', 'request-flow-pro'); ?></th>
                    <th><?php esc_html_e('Status', 'request-flow-pro'); ?></th>
                    <th><?php esc_html_e('Registered', 'request-flow-pro'); ?></th>
                    <th><?php esc_html_e('Actions', 'request-flow-pro'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" class="no-requests"><?php esc_html_e('No users found.', 'request-flow-pro'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user):
                        $status = get_user_meta($user->ID, 'approval_status', true);
                        if (empty($status)) {
                            $status = 'approved'; // Existing users are approved by default
                        }
                        $user_data = get_userdata($user->ID);
                        $roles = !empty($user_data->roles) ? implode(', ', $user_data->roles) : esc_html__('No role', 'request-flow-pro');
                        ?>
                        <tr>
                            <th class="check-column">
                                <input type="checkbox" name="user_ids[]" value="<?php echo esc_attr($user->ID); ?>">
                            </th>
                            <td>
                                <strong><?php echo esc_html($user_data->user_login); ?></strong>
                                <div class="row-actions">
                                    <span><a
                                            href="<?php echo get_edit_user_link($user->ID); ?>"><?php esc_html_e('Edit', 'request-flow-pro'); ?></a></span>
                                </div>
                            </td>
                            <td><?php echo esc_html($user_data->display_name); ?></td>
                            <td><?php echo esc_html($user_data->user_email); ?></td>
                            <td><?php echo esc_html(ucfirst($roles)); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($status); ?>">
                                    <?php echo esc_html(ucfirst($status)); ?>
                                </span>
                            </td>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($user_data->user_registered)); ?></td>
                            <td class="approval-actions-cell">
                                <?php if ($status === 'pending'): ?>
                                    <button type="button" class="button button-primary button-small user-quick-approve"
                                        data-user-id="<?php echo intval($user->ID); ?>">
                                        <?php esc_html_e('Approve', 'request-flow-pro'); ?>
                                    </button>
                                    <button type="button" class="button button-small user-quick-deny"
                                        data-user-id="<?php echo intval($user->ID); ?>">
                                        <?php esc_html_e('Deny', 'request-flow-pro'); ?>
                                    </button>
                                <?php else: ?>
                                    <span class="dashicons dashicons-<?php echo $status === 'approved' ? 'yes' : 'no'; ?>"></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
</div>