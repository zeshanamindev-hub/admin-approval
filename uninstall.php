<?php
/**
 * Uninstall Approval Plugin
 *
 * This file is executed when the plugin is deleted via WordPress admin.
 * It removes all plugin data from the database.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('approval_plugin_version');
delete_option('approval_plugin_email_notifications');
delete_option('approval_plugin_admin_email');
delete_option('approval_plugin_email_from_name');
delete_option('approval_plugin_email_from_email');
delete_option('approval_plugin_email_approved_subject');
delete_option('approval_plugin_email_approved_body');
delete_option('approval_plugin_email_rejected_subject');
delete_option('approval_plugin_email_rejected_body');
delete_option('approval_plugin_email_pending_subject');
delete_option('approval_plugin_email_pending_body');
delete_option('approval_plugin_domain_whitelist');
delete_option('approval_plugin_domain_blacklist');
delete_option('approval_plugin_whitelist_enabled');
delete_option('approval_plugin_blacklist_enabled');
delete_option('approval_plugin_auto_delete_rejected');
delete_option('approval_plugin_delete_after_days');
delete_option('approval_plugin_require_approval');
delete_option('approval_plugin_show_pending_message');

// Delete database table
global $wpdb;
$table_name = $wpdb->prefix . 'approval_requests';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Clear any scheduled hooks
wp_clear_scheduled_hook('approval_plugin_cleanup');
