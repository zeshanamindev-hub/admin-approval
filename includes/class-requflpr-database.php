<?php
/**
 * Database operations for the Approval Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class RequflprDatabase
{

    private static $table_name = 'requflpr_requests';

    /**
     * Create the approval requests table
     */
    public static function create_table()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text NOT NULL,
            submitter_name varchar(100) NOT NULL,
            submitter_email varchar(100) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            priority varchar(20) DEFAULT 'medium',
            category varchar(100) DEFAULT 'general',
            admin_notes text,
            assigned_to bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status_idx (status),
            KEY priority_idx (priority),
            KEY category_idx (category)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Insert a new approval request
     */
    public static function insert_request($data)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        $insert_data = array(
            'title' => sanitize_text_field($data['title']),
            'description' => sanitize_textarea_field($data['description']),
            'submitter_name' => sanitize_text_field($data['submitter_name']),
            'submitter_email' => sanitize_email($data['submitter_email']),
            'status' => 'pending',
            'priority' => isset($data['priority']) ? sanitize_text_field($data['priority']) : 'medium',
            'category' => isset($data['category']) ? sanitize_text_field($data['category']) : 'general'
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $result = $wpdb->insert(
            $table_name,
            $insert_data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        return $result !== false ? $wpdb->insert_id : false;
    }

    /**
     * Get all approval requests
     */
    public static function get_requests($status = null, $limit = 20, $offset = 0)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        $where = '';
        if ($status) {
            $where = $wpdb->prepare(" WHERE status = %s", $status);
        }

        $sql = "SELECT * FROM $table_name $where ORDER BY created_at DESC LIMIT %d OFFSET %d";

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $wpdb->get_results($wpdb->prepare($sql, $limit, $offset));
    }

    /**
     * Get a single request by ID
     */
    public static function get_request($id)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    }

    /**
     * Update request status
     */
    public static function update_status($id, $status, $admin_notes = '')
    {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->update(
            $table_name,
            array(
                'status' => $status,
                'admin_notes' => sanitize_textarea_field($admin_notes)
            ),
            array('id' => $id),
            array('%s', '%s'),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Get request count by status
     */
    public static function get_count($status = null)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        if ($status) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
            return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE status = %s", $status));
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
            return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        }
    }

    /**
     * Get statistics for dashboard
     */
    public static function get_statistics()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        $stats = array(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name"),
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
            'pending' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE status = %s", 'pending')),
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
            'approved' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE status = %s", 'approved')),
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
            'rejected' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE status = %s", 'rejected')),
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
            'today' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = %s", current_time('Y-m-d'))),
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
            'this_week' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE YEARWEEK(created_at) = YEARWEEK(%s)", current_time('mysql'))),
        );

        // Calculate approval rate
        if ($stats['total'] > 0) {
            $stats['approval_rate'] = round(($stats['approved'] / $stats['total']) * 100, 1);
        } else {
            $stats['approval_rate'] = 0;
        }

        // Get average response time (in hours)
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $avg_time = $wpdb->get_var("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) FROM $table_name WHERE status != 'pending'");
        $stats['avg_response_time'] = $avg_time ? round($avg_time, 1) : 0;

        return $stats;
    }

    /**
     * Get requests by category
     */
    public static function get_count_by_category()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $wpdb->get_results("SELECT category, COUNT(*) as count FROM $table_name GROUP BY category");
    }

    /**
     * Get requests by priority
     */
    public static function get_count_by_priority()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $wpdb->get_results("SELECT priority, COUNT(*) as count FROM $table_name GROUP BY priority");
    }

    /**
     * Bulk update status
     */
    public static function bulk_update_status($ids, $status)
    {
        global $wpdb;

        if (empty($ids) || !is_array($ids)) {
            return false;
        }

        $table_name = $wpdb->prefix . self::$table_name;
        $ids_placeholder = implode(',', array_fill(0, count($ids), '%d'));

        $query = $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "UPDATE $table_name SET status = %s WHERE id IN ($ids_placeholder)",
            array_merge(array($status), $ids)
        );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $wpdb->query($query);
    }
}