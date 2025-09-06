# WP User Cleaner

A WordPress plugin to clean spam users without orders/posts and manage comment spam.

## Features

### User Cleanup
- Scan for users without WooCommerce orders
- Identify users without posts or pages
- Configurable user role protection
- Bulk user deletion with safety checks
- Activity logging for audit trails

### Comment Management
- Advanced spam detection algorithm
- Automatic suspicious comment identification
- Bulk comment spam marking and deletion
- Configurable spam keywords detection
- Link-based spam detection

### Security Features
- Role-based permission checks
- Nonce verification for all actions
- Admin-only access controls
- Comprehensive logging system
- Safe deletion with confirmations

## Installation

1. Upload the plugin files to the `/wp-content/plugins/wp-user-cleaner` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Tools > User Cleaner to configure and use the plugin

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- WooCommerce (optional, for order checking)

## Usage

1. **User Cleanup**: Navigate to Tools > User Cleaner > User Cleanup tab
   - Click "Scan for Inactive Users" to find users without orders/posts
   - Review the list and select users to delete
   - Click "Delete Selected Users" with confirmation

2. **Comment Management**: Navigate to Comment Management tab
   - Click "Scan for Suspicious Comments" to identify potential spam
   - Review comments with spam scores and reasons
   - Mark as spam or delete selected comments

3. **Settings**: Configure cleanup criteria and protected user roles

4. **Activity Log**: View all actions performed by the plugin

## Security

- Administrator users are never deleted
- Configurable role protection
- All actions require proper permissions
- CSRF protection with nonces
- Comprehensive audit logging

## License

GPL v2 or later