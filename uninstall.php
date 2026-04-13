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
delete_option('requflpr_version');
delete_option('requflpr_email_notifications');
delete_option('requflpr_admin_email');
delete_option('requflpr_email_from_name');
delete_option('requflpr_email_from_email');
delete_option('requflpr_email_approved_subject');
delete_option('requflpr_email_approved_body');
delete_option('requflpr_email_rejected_subject');
delete_option('requflpr_email_rejected_body');
delete_option('requflpr_email_pending_subject');
delete_option('requflpr_email_pending_body');
delete_option('requflpr_domain_whitelist');
delete_option('requflpr_domain_blacklist');
delete_option('requflpr_whitelist_enabled');
delete_option('requflpr_blacklist_enabled');
delete_option('requflpr_auto_delete_rejected');
delete_option('requflpr_delete_after_days');
delete_option('requflpr_require_approval');
delete_option('requflpr_show_pending_message');

// Delete database table
global $wpdb;
$requflpr_table_name = $wpdb->prefix . 'requflpr_requests';
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, PluginCheck.Security.DirectDB.UnescapedDBParameter
$wpdb->query("DROP TABLE IF EXISTS $requflpr_table_name");

// Clear any scheduled hooks
wp_clear_scheduled_hook('requflpr_cleanup');
