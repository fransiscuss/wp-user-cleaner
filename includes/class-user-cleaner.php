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
     * Get users without orders or posts using direct SQL query
     */
    public function get_inactive_users($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'sort_by' => 'registered',
            'sort_order' => 'ASC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Use the provided SQL query to find users without orders and posts
        $sql = "SELECT U.ID, U.user_login, U.user_email, U.display_name, U.user_registered
                FROM {$wpdb->users} AS U
                JOIN {$wpdb->usermeta} AS UM ON U.ID = UM.user_id
                LEFT JOIN {$wpdb->postmeta} AS PM ON U.ID = PM.meta_value AND PM.meta_key = '_customer_user'
                LEFT JOIN {$wpdb->posts} AS P ON U.ID = P.post_author
                WHERE UM.meta_key = '{$wpdb->prefix}capabilities'
                AND UM.meta_value LIKE '%subscriber%'
                AND PM.meta_value IS NULL
                AND P.post_author IS NULL
                GROUP BY U.ID";
        
        // Add sorting
        if ($args['sort_by'] === 'registered') {
            $sql .= " ORDER BY U.user_registered " . ($args['sort_order'] === 'DESC' ? 'DESC' : 'ASC');
        } elseif ($args['sort_by'] === 'login') {
            $sql .= " ORDER BY U.user_login " . ($args['sort_order'] === 'DESC' ? 'DESC' : 'ASC');
        } elseif ($args['sort_by'] === 'email') {
            $sql .= " ORDER BY U.user_email " . ($args['sort_order'] === 'DESC' ? 'DESC' : 'ASC');
        }
        
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        $inactive_users = array();
        if ($results) {
            foreach ($results as $user) {
                // Get user roles
                $user_roles = get_userdata($user['ID'])->roles;
                
                $inactive_users[] = array(
                    'ID' => $user['ID'],
                    'user_login' => $user['user_login'],
                    'user_email' => $user['user_email'],
                    'display_name' => $user['display_name'],
                    'user_registered' => $user['user_registered'],
                    'roles' => $user_roles
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
        
        $sort_by = isset($_POST['sort_by']) ? sanitize_text_field($_POST['sort_by']) : 'registered';
        $sort_order = isset($_POST['sort_order']) ? sanitize_text_field($_POST['sort_order']) : 'ASC';
        
        $args = array(
            'sort_by' => $sort_by,
            'sort_order' => $sort_order
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