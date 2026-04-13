<?php
/**
 * Plugin Name: Request Flow Pro
 * Plugin URI: https://wordpress.org/plugins/request-flow-pro/
 * Description: A powerful WordPress solution for managing approval workflows with modern UI, email notifications, priority levels, and comprehensive request tracking.
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Author: Zeshan Amin
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
define('REQUFLPR_VERSION', '1.0.0');
define('REQUFLPR_PATH', plugin_dir_path(__FILE__));
define('REQUFLPR_URL', plugin_dir_url(__FILE__));

/**
 * Main Request Flow Pro Plugin Class
 */
class RequflprPlugin
{

    public function __construct()
    {
        // Load dependencies early to avoid class availability issues on init.
        $this->load_dependencies();

        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init()
    {
        // Ensure classes are available before instantiation.
        $this->load_dependencies();

        // Initialize components
        if (is_admin() && class_exists('RequflprAdmin')) {
            new RequflprAdmin();
        }
        if (class_exists('RequflprShortcodes')) {
            new RequflprShortcodes();
        }
        if (class_exists('RequflprUsers')) {
            new RequflprUsers();
        }
    }

    private function load_dependencies()
    {
        $dependency_files = array(
            REQUFLPR_PATH . 'includes/class-requflpr-database.php',
            REQUFLPR_PATH . 'includes/class-requflpr-admin.php',
            REQUFLPR_PATH . 'includes/class-requflpr-shortcodes.php',
            REQUFLPR_PATH . 'includes/class-requflpr-users.php',
        );

        foreach ($dependency_files as $dependency_file) {
            if (file_exists($dependency_file)) {
                require_once $dependency_file;
            }
        }

        // Backward compatibility for mixed class names during upgrade transitions.
        if (!class_exists('RequflprAdmin') && class_exists('ApprovalAdmin')) {
            class_alias('ApprovalAdmin', 'RequflprAdmin');
        }
        if (!class_exists('RequflprUsers') && class_exists('ApprovalUsers')) {
            class_alias('ApprovalUsers', 'RequflprUsers');
        }
        if (!class_exists('RequflprDatabase') && class_exists('ApprovalDatabase')) {
            class_alias('ApprovalDatabase', 'RequflprDatabase');
        }
        if (!class_exists('RequflprShortcodes') && class_exists('ApprovalShortcodes')) {
            class_alias('ApprovalShortcodes', 'RequflprShortcodes');
        }
    }

    public function activate()
    {
        // Load dependencies for activation
        require_once REQUFLPR_PATH . 'includes/class-requflpr-database.php';

        // Create database table
        RequflprDatabase::create_table();

        // Set default options
        add_option('requflpr_version', REQUFLPR_VERSION);
        add_option('requflpr_email_notifications', 1);
    }

    public function deactivate()
    {
        // Clean up scheduled events if any
        wp_clear_scheduled_hook('requflpr_cleanup');
    }
}

// Initialize the plugin
new RequflprPlugin();