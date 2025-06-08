<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://drashtipatel_test.com
 * @since      1.0.0
 *
 * @package    Advanced_Book_Listing
 * @subpackage Advanced_Book_Listing/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Advanced_Book_Listing
 * @subpackage Advanced_Book_Listing/includes
 * @author     Drashti Patel <drashtipatel2903@gmail.com>
 */
class Advanced_Book_Listing_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

}
