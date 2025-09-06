<?php
/**
 * User Cleaner Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPUserCleanerUsers {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_wp_user_cleaner_scan_users', array($this, 'ajax_scan_users'));
        add_action('wp_ajax_wp_user_cleaner_delete_users', array($this, 'ajax_delete_users'));
    }
    
    /**
     * Get users without orders or posts
     */
    public function get_inactive_users($args = array()) {
        $defaults = array(
            'exclude_roles' => array('administrator', 'editor', 'author'),
            'check_orders' => true,
            'check_posts' => true,
            'limit' => -1
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $user_query_args = array(
            'fields' => 'all',
            'number' => $args['limit'],
            'role__not_in' => $args['exclude_roles']
        );
        
        $users = get_users($user_query_args);
        $inactive_users = array();
        
        foreach ($users as $user) {
            $is_inactive = true;
            
            // Check if user has posts
            if ($args['check_posts']) {
                $post_count = count_user_posts($user->ID);
                if ($post_count > 0) {
                    $is_inactive = false;
                }
            }
            
            // Check if user has WooCommerce orders
            if ($is_inactive && $args['check_orders'] && function_exists('wc_get_orders')) {
                $orders = wc_get_orders(array(
                    'customer_id' => $user->ID,
                    'limit' => 1,
                    'status' => 'any'
                ));
                
                if (!empty($orders)) {
                    $is_inactive = false;
                }
            }
            
            if ($is_inactive) {
                $inactive_users[] = array(
                    'ID' => $user->ID,
                    'user_login' => $user->user_login,
                    'user_email' => $user->user_email,
                    'display_name' => $user->display_name,
                    'user_registered' => $user->user_registered,
                    'roles' => $user->roles
                );
            }
        }
        
        return $inactive_users;
    }
    
    /**
     * Delete inactive users
     */
    public function delete_users($user_ids) {
        if (!current_user_can('delete_users')) {
            return new WP_Error('permission_denied', __('You do not have permission to delete users.', 'wp-user-cleaner'));
        }
        
        $deleted_count = 0;
        $errors = array();
        
        foreach ($user_ids as $user_id) {
            $user_id = intval($user_id);
            
            // Additional safety checks
            $user = get_user_by('ID', $user_id);
            if (!$user) {
                $errors[] = sprintf(__('User ID %d not found.', 'wp-user-cleaner'), $user_id);
                continue;
            }
            
            // Don't delete administrators
            if (in_array('administrator', $user->roles)) {
                $errors[] = sprintf(__('Cannot delete administrator user: %s', 'wp-user-cleaner'), $user->user_login);
                continue;
            }
            
            // Log the deletion
            $this->log_action('user_deleted', $user_id, array(
                'user_login' => $user->user_login,
                'user_email' => $user->user_email
            ));
            
            // Delete the user
            if (wp_delete_user($user_id)) {
                $deleted_count++;
            } else {
                $errors[] = sprintf(__('Failed to delete user: %s', 'wp-user-cleaner'), $user->user_login);
            }
        }
        
        return array(
            'deleted' => $deleted_count,
            'errors' => $errors
        );
    }
    
    /**
     * AJAX handler for scanning users
     */
    public function ajax_scan_users() {
        check_ajax_referer('wp_user_cleaner_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'wp-user-cleaner'));
        }
        
        $settings = get_option('wp_user_cleaner_settings', array());
        
        $args = array(
            'check_orders' => isset($settings['delete_users_without_orders']) ? $settings['delete_users_without_orders'] : true,
            'check_posts' => isset($settings['delete_users_without_posts']) ? $settings['delete_users_without_posts'] : true,
            'exclude_roles' => isset($settings['exclude_roles']) ? $settings['exclude_roles'] : array('administrator', 'editor', 'author')
        );
        
        $inactive_users = $this->get_inactive_users($args);
        
        wp_send_json_success(array(
            'users' => $inactive_users,
            'count' => count($inactive_users)
        ));
    }
    
    /**
     * AJAX handler for deleting users
     */
    public function ajax_delete_users() {
        check_ajax_referer('wp_user_cleaner_nonce', 'nonce');
        
        if (!current_user_can('delete_users')) {
            wp_die(__('Insufficient permissions.', 'wp-user-cleaner'));
        }
        
        $user_ids = isset($_POST['user_ids']) ? array_map('intval', $_POST['user_ids']) : array();
        
        if (empty($user_ids)) {
            wp_send_json_error(__('No users selected.', 'wp-user-cleaner'));
        }
        
        $result = $this->delete_users($user_ids);
        
        if (!empty($result['errors'])) {
            wp_send_json_error(array(
                'message' => __('Some users could not be deleted.', 'wp-user-cleaner'),
                'deleted' => $result['deleted'],
                'errors' => $result['errors']
            ));
        } else {
            wp_send_json_success(array(
                'message' => sprintf(__('%d users deleted successfully.', 'wp-user-cleaner'), $result['deleted']),
                'deleted' => $result['deleted']
            ));
        }
    }
    
    /**
     * Log actions for audit trail
     */
    private function log_action($action_type, $user_id = null, $details = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'user_cleaner_log';
        
        $wpdb->insert(
            $table_name,
            array(
                'action_type' => $action_type,
                'user_id' => $user_id,
                'details' => json_encode($details),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s')
        );
    }
}

// Initialize the class
WPUserCleanerUsers::get_instance();