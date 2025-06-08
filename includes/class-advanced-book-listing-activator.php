<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Advanced_Book_Listing
 * @subpackage Advanced_Book_Listing/includes
 * @author     Drashti Patel <drashtipatel2903@gmail.com>
 */
class Advanced_Book_Listing_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		flush_rewrite_rules();
	}

}
