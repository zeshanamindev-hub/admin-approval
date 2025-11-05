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
                <?php _e('User Approvals', 'approval-plugin'); ?>
            </h1>
            <p class="page-subtitle"><?php _e('Manage user registration approvals and access control', 'approval-plugin'); ?></p>
        </div>
        <div class="page-header-right">
            <a href="<?php echo admin_url('admin.php?page=approval-settings&tab=users'); ?>" class="button button-secondary header-action-btn">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php _e('User Settings', 'approval-plugin'); ?>
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
                <div class="stat-label"><?php _e('TOTAL USERS', 'approval-plugin'); ?></div>
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
                <div class="stat-label"><?php _e('PENDING APPROVAL', 'approval-plugin'); ?></div>
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
                <div class="stat-number"><?php echo esc_html($counts['denied']); ?></div>
                <div class="stat-label"><?php _e('DENIED', 'approval-plugin'); ?></div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="approval-filters-wrapper">
        <div class="approval-filters-section">
            <div class="filter-tabs">
                <a href="?page=approval-users" class="filter-tab <?php echo !$status_filter ? 'active' : ''; ?>">
                    <span class="tab-label"><?php _e('All Users', 'approval-plugin'); ?></span>
                    <span class="tab-count"><?php echo $counts['all']; ?></span>
                </a>
                <a href="?page=approval-users&user_status=pending" class="filter-tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                    <span class="tab-label"><?php _e('Pending', 'approval-plugin'); ?></span>
                    <span class="tab-count"><?php echo $counts['pending']; ?></span>
                </a>
                <a href="?page=approval-users&user_status=approved" class="filter-tab <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
                    <span class="tab-label"><?php _e('Approved', 'approval-plugin'); ?></span>
                    <span class="tab-count"><?php echo $counts['approved']; ?></span>
                </a>
                <a href="?page=approval-users&user_status=denied" class="filter-tab <?php echo $status_filter === 'denied' ? 'active' : ''; ?>">
                    <span class="tab-label"><?php _e('Denied', 'approval-plugin'); ?></span>
                    <span class="tab-count"><?php echo $counts['denied']; ?></span>
                </a>
            </div>
            <div class="filter-actions">
                <input type="text" id="user-search-input" placeholder="<?php _e('Search users...', 'approval-plugin'); ?>" class="approval-search-box">
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="approval-users-form">
        <input type="hidden" name="action" value="approval_bulk_user_action">
        <?php wp_nonce_field('approval_bulk_user_action'); ?>

        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <select name="bulk_action" id="bulk-action-selector-users">
                    <option value="-1"><?php _e('Bulk Actions', 'approval-plugin'); ?></option>
                    <option value="approve"><?php _e('Approve', 'approval-plugin'); ?></option>
                    <option value="deny"><?php _e('Deny', 'approval-plugin'); ?></option>
                </select>
                <input type="submit" class="button action" value="<?php _e('Apply', 'approval-plugin'); ?>">
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped approval-requests-table users-table">
            <thead>
                <tr>
                    <td class="check-column"><input type="checkbox" id="cb-select-all-users"></td>
                    <th><?php _e('Username', 'approval-plugin'); ?></th>
                    <th><?php _e('Name', 'approval-plugin'); ?></th>
                    <th><?php _e('Email', 'approval-plugin'); ?></th>
                    <th><?php _e('Role', 'approval-plugin'); ?></th>
                    <th><?php _e('Status', 'approval-plugin'); ?></th>
                    <th><?php _e('Registered', 'approval-plugin'); ?></th>
                    <th><?php _e('Actions', 'approval-plugin'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" class="no-requests"><?php _e('No users found.', 'approval-plugin'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user):
                        $status = get_user_meta($user->ID, 'approval_status', true);
                        if (empty($status)) {
                            $status = 'approved'; // Existing users are approved by default
                        }
                        $user_data = get_userdata($user->ID);
                        $roles = !empty($user_data->roles) ? implode(', ', $user_data->roles) : __('No role', 'approval-plugin');
                    ?>
                        <tr>
                            <th class="check-column">
                                <input type="checkbox" name="user_ids[]" value="<?php echo esc_attr($user->ID); ?>">
                            </th>
                            <td>
                                <strong><?php echo esc_html($user_data->user_login); ?></strong>
                                <div class="row-actions">
                                    <span><a href="<?php echo get_edit_user_link($user->ID); ?>"><?php _e('Edit', 'approval-plugin'); ?></a></span>
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
                                    <button type="button" class="button button-primary button-small user-quick-approve" data-user-id="<?php echo $user->ID; ?>">
                                        <?php _e('Approve', 'approval-plugin'); ?>
                                    </button>
                                    <button type="button" class="button button-small user-quick-deny" data-user-id="<?php echo $user->ID; ?>">
                                        <?php _e('Deny', 'approval-plugin'); ?>
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

<script>
jQuery(document).ready(function($) {
    // Select all checkbox
    $('#cb-select-all-users').on('change', function() {
        var checked = $(this).prop('checked');
        $('input[name="user_ids[]"]').prop('checked', checked);
    });

    // Form validation
    $('#approval-users-form').on('submit', function(e) {
        var action = $('#bulk-action-selector-users').val();
        var checkedCount = $('input[name="user_ids[]"]:checked').length;

        if (action === '-1') {
            e.preventDefault();
            alert('<?php _e('Please select an action.', 'approval-plugin'); ?>');
            return false;
        }

        if (checkedCount === 0) {
            e.preventDefault();
            alert('<?php _e('Please select at least one user.', 'approval-plugin'); ?>');
            return false;
        }

        var actionText = action === 'approve' ? '<?php _e('approve', 'approval-plugin'); ?>' : '<?php _e('deny', 'approval-plugin'); ?>';
        if (!confirm('<?php _e('Are you sure you want to', 'approval-plugin'); ?> ' + actionText + ' ' + checkedCount + ' <?php _e('user(s)?', 'approval-plugin'); ?>')) {
            e.preventDefault();
            return false;
        }
    });

    // Quick approve/deny
    $('.user-quick-approve, .user-quick-deny').on('click', function(e) {
        e.preventDefault();

        var button = $(this);
        var userId = button.data('user-id');
        var isApprove = button.hasClass('user-quick-approve');
        var status = isApprove ? 'approved' : 'denied';
        var action = isApprove ? '<?php _e('approve', 'approval-plugin'); ?>' : '<?php _e('deny', 'approval-plugin'); ?>';

        if (!confirm('<?php _e('Are you sure you want to', 'approval-plugin'); ?> ' + action + ' <?php _e('this user?', 'approval-plugin'); ?>')) {
            return;
        }

        button.prop('disabled', true).text(isApprove ? '<?php _e('Approving...', 'approval-plugin'); ?>' : '<?php _e('Denying...', 'approval-plugin'); ?>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'approval_update_user_status',
                user_id: userId,
                status: status,
                admin_notes: '',
                nonce: '<?php echo wp_create_nonce('approval_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                    button.prop('disabled', false).text(isApprove ? '<?php _e('Approve', 'approval-plugin'); ?>' : '<?php _e('Deny', 'approval-plugin'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('An error occurred. Please try again.', 'approval-plugin'); ?>');
                button.prop('disabled', false).text(isApprove ? '<?php _e('Approve', 'approval-plugin'); ?>' : '<?php _e('Deny', 'approval-plugin'); ?>');
            }
        });
    });

    // Search functionality
    $('#user-search-input').on('keyup', function() {
        var searchTerm = $(this).val().toLowerCase();

        $('.users-table tbody tr').each(function() {
            if ($(this).hasClass('no-search-results')) {
                return;
            }

            var rowText = $(this).text().toLowerCase();

            if (rowText.indexOf(searchTerm) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});
</script>
