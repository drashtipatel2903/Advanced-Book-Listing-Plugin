<?php
/**
 * Plugin Name:       Advanced Book Listing
 * Plugin URI:        https://wordpress.org/plugins/advanced-book-listing
 * Description:       A plugin for managing and displaying books with advanced filtering and AJAX pagination shortcode is [advanced_books].
 * Version:           1.0.0
 * Author:            Drashti Patel
 * Author URI:        https://drashtipatel_test.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       advanced-book-listing
 * Domain Path:       /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ADVANCED_BOOK_LISTING_VERSION', '1.0.0');
define('ADVANCED_BOOK_LISTING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ADVANCED_BOOK_LISTING_PLUGIN_URL', plugin_dir_url(__FILE__));


require_once ADVANCED_BOOK_LISTING_PLUGIN_DIR . 'includes/class-advanced-book-listing.php';

function activate_advanced_book_listing() {
	require_once ADVANCED_BOOK_LISTING_PLUGIN_DIR . 'includes/class-advanced-book-listing-activator.php';
	Advanced_Book_Listing_Activator::activate();
}

function deactivate_advanced_book_listing() {
	require_once ADVANCED_BOOK_LISTING_PLUGIN_DIR . 'includes/class-advanced-book-listing-deactivator.php';
	Advanced_Book_Listing_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_advanced_book_listing' );
register_deactivation_hook( __FILE__, 'deactivate_advanced_book_listing' );

function run_advanced_book_listing() {

	$plugin = new Advanced_Book_Listing();
	$plugin->run();

}
run_advanced_book_listing();