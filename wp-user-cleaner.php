<?php
/**
 * Plugin Name: WP User Cleaner
 * Plugin URI: https://github.com/fransiscuss/wp-user-cleaner
 * Description: Clean and delete spam users without orders/posts and manage comment spam in WordPress and WooCommerce.
 * Version: 1.3.0
 * Author: Fransiscus Setiawan
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-user-cleaner
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_USER_CLEANER_VERSION', '1.3.0');
define('WP_USER_CLEANER_PATH', plugin_dir_path(__FILE__));
define('WP_USER_CLEANER_URL', plugin_dir_url(__FILE__));

// Main plugin class
class WPUserCleaner {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('wp-user-cleaner', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        
        // Initialize admin interface
        if (is_admin()) {
            $this->init_admin();
        }
        
        // Load includes
        $this->load_includes();
    }
    
    private function load_includes() {
        require_once WP_USER_CLEANER_PATH . 'includes/class-user-cleaner.php';
        require_once WP_USER_CLEANER_PATH . 'includes/class-comment-manager.php';
        require_once WP_USER_CLEANER_PATH . 'includes/class-admin.php';
    }
    
    private function init_admin() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function add_admin_menu() {
        add_management_page(
            __('WP User Cleaner', 'wp-user-cleaner'),
            __('User Cleaner', 'wp-user-cleaner'),
            'manage_options',
            'wp-user-cleaner',
            array('WPUserCleanerAdmin', 'render_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'tools_page_wp-user-cleaner') {
            return;
        }
        
        wp_enqueue_style(
            'wp-user-cleaner-admin',
            WP_USER_CLEANER_URL . 'css/admin.css',
            array(),
            WP_USER_CLEANER_VERSION
        );
        
        wp_enqueue_script(
            'wp-user-cleaner-admin',
            WP_USER_CLEANER_URL . 'js/admin.js',
            array('jquery'),
            WP_USER_CLEANER_VERSION,
            true
        );
        
        wp_localize_script('wp-user-cleaner-admin', 'wpUserCleaner', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_user_cleaner_nonce'),
            'confirmDelete' => __('Are you sure you want to delete these users? This action cannot be undone.', 'wp-user-cleaner'),
        ));
    }
    
    public function activate() {
        // Create database tables if needed
        $this->create_tables();
        
        // Set default options
        add_option('wp_user_cleaner_settings', array(
            'delete_users_without_orders' => false,
            'delete_users_without_posts' => false,
            'exclude_roles' => array('administrator', 'editor', 'author'),
            'auto_spam_comments' => false,
            'delete_spam_comments_days' => 30,
            'suspicious_domain_keywords' => 'tempmail,10minutemail,guerrillamail,mailinator,throwaway',
            'excluded_domains' => 'members.ebay.com,kogan.com.au,members.ebay.com.au,amazon.com.au,gmail.com,yahoo.com,hotmail.com,outlook.com'
        ));
    }
    
    public function deactivate() {
        // Cleanup scheduled events if any
        wp_clear_scheduled_hook('wp_user_cleaner_daily_cleanup');
    }
    
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'user_cleaner_log';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            action_type varchar(50) NOT NULL,
            user_id bigint(20),
            comment_id bigint(20),
            details text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize the plugin
WPUserCleaner::get_instance();