=== WP User Cleaner ===
Contributors: fransiscuss
Tags: users, cleanup, spam, comments, woocommerce
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Clean and delete spam users without orders/posts and manage comment spam in WordPress and WooCommerce.

== Description ==

WP User Cleaner is a comprehensive WordPress plugin designed to help administrators maintain clean user databases by automatically identifying and removing inactive users. The plugin integrates seamlessly with WooCommerce to detect users who have never placed orders and combines this with post/page creation analysis to identify truly inactive accounts.

**Key Features:**

* **Smart User Detection**: Identifies users without WooCommerce orders AND without posts/pages
* **WooCommerce Integration**: Deep integration with WooCommerce order system for accurate detection
* **Comment Spam Management**: Advanced spam detection with scoring system for suspicious comments
* **Domain Protection**: Configurable domain exclusion to protect legitimate user accounts
* **Role-Based Protection**: Exclude specific user roles from cleanup (administrators, editors, etc.)
* **Activity Logging**: Complete audit trail of all cleanup actions
* **Sorting & Filtering**: Sort users by registration date, username, or email address
* **Safe Deletion Process**: Multiple confirmation steps and safety checks

**Comment Spam Features:**

* Automatic detection of suspicious comments based on keyword analysis
* Link count analysis to identify spam patterns
* Suspicious email domain detection (temporary email services)
* Configurable spam score thresholds
* Bulk spam management (mark as spam or delete)

**Security & Safety:**

* Comprehensive permission checks
* Multiple confirmation dialogs for destructive actions
* Activity logging for audit trails
* Domain exclusion system to protect legitimate users
* Role-based user protection

**Perfect for:**

* WooCommerce store owners dealing with fake user registrations
* Blog administrators managing comment spam
* Sites with user registration spam problems
* Administrators wanting to maintain clean user databases

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/wp-user-cleaner/` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to Tools → User Cleaner to access the plugin interface
4. Configure settings in the Settings tab according to your needs
5. Use the User Cleanup tab to scan for inactive users
6. Use the Comment Management tab to handle comment spam

== Frequently Asked Questions ==

= Does this plugin work with WooCommerce? =

Yes! This plugin is specifically designed to integrate with WooCommerce. It checks if users have placed orders before marking them as inactive. The plugin uses multiple detection methods to ensure accurate order detection.

= Will administrator accounts be deleted? =

No, administrator accounts are protected by default. You can configure which user roles to exclude from cleanup in the settings.

= Can I exclude specific email domains? =

Yes, you can specify domains to exclude from user cleanup. For example, you might want to exclude legitimate domains like gmail.com or specific business domains.

= What happens to user data when a user is deleted? =

The plugin uses WordPress's built-in user deletion function, which handles user data according to WordPress standards. You can choose to reassign posts to other users during the deletion process.

= Does the plugin log its actions? =

Yes, all cleanup actions are logged in the Activity Log tab, providing a complete audit trail of deletions and modifications.

== Screenshots ==

1. User cleanup interface showing inactive users with sorting options
2. Comment management with spam scoring and detection
3. Settings configuration page
4. Activity log showing all actions

== Changelog ==

= 1.2.0 =
* Fixed critical WooCommerce order detection issue
* Enhanced order detection with multiple fallback methods
* Added comprehensive debug information for troubleshooting
* Improved guest order detection via email matching
* Better error handling for various WooCommerce configurations

= 1.1.0 =
* Updated plugin author to Fransiscus Setiawan
* Added sorting functionality for users by registration date, username, or email
* Added sorting controls to admin interface (newest/oldest first)
* Improved domain exclusion system for user cleanup
* Added configurable suspicious domain keywords for comment spam detection
* Enhanced settings page with email domain configuration
* Better domain handling to exclude legitimate sites like eBay, Amazon, etc.
* Fixed comment spam detection to be more accurate

= 1.0.0 =
* Initial release
* User cleanup functionality
* Comment spam detection
* Settings page
* Activity logging

== Upgrade Notice ==

= 1.2.0 =
Important update fixing critical WooCommerce order detection issues. Users with orders were incorrectly being marked for cleanup in previous versions.

= 1.1.0 =
Enhanced user sorting and domain exclusion features. Better protection for legitimate user accounts.

= 1.0.0 =
Initial release of WP User Cleaner.