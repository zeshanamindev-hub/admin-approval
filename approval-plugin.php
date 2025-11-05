<?php
/**
 * Plugin Name: Request Flow Pro
 * Plugin URI: https://wordpress.org/plugins/request-flow-pro/
 * Description: A powerful WordPress solution for managing approval workflows with modern UI, email notifications, priority levels, and comprehensive request tracking.
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Author: Your Name
 * Author URI: https://profiles.wordpress.org/zeshanamin/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: request-flow-pro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('APPROVAL_PLUGIN_VERSION', '1.0.0');
define('APPROVAL_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('APPROVAL_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main Approval Plugin Class
 */
class ApprovalPlugin {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load plugin files
        $this->load_dependencies();
        
        // Initialize components
        if (is_admin()) {
            new ApprovalAdmin();
        }
        new ApprovalShortcodes();
        new ApprovalUsers();
    }
    
    private function load_dependencies() {
        require_once APPROVAL_PLUGIN_PATH . 'includes/class-approval-database.php';
        require_once APPROVAL_PLUGIN_PATH . 'includes/class-approval-admin.php';
        require_once APPROVAL_PLUGIN_PATH . 'includes/class-approval-shortcodes.php';
        require_once APPROVAL_PLUGIN_PATH . 'includes/class-approval-users.php';
    }
    
    public function activate() {
        // Load dependencies for activation
        require_once APPROVAL_PLUGIN_PATH . 'includes/class-approval-database.php';

        // Create database table
        ApprovalDatabase::create_table();

        // Set default options
        add_option('approval_plugin_version', APPROVAL_PLUGIN_VERSION);
        add_option('approval_plugin_email_notifications', 1);
    }
    
    public function deactivate() {
        // Clean up scheduled events if any
        wp_clear_scheduled_hook('approval_plugin_cleanup');
    }
}

// Initialize the plugin
new ApprovalPlugin();