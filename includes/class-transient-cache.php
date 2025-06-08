<?php
if (!defined('ABSPATH')) {die;}

/**
 * Handles transient caching for the plugin.
 *
 * @package    Advanced_Book_Listing
 * @subpackage Advanced_Book_Listing/includes
 */

class Advanced_Book_Listing_Transient_Cache {
     /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
     /**
     * Cache prefix for all plugin transients
     *
     * @var string
     */
    private static $prefix = 'advanced_books_';
    /**
         * Get cached data by key
         *
         * @param string $key The cache key
         * @return mixed|false Cached data if exists, false otherwise
         */
        public static function get_cache($key) {
            if (!is_string($key)) {
                return false;
            }

            $full_key = self::get_full_key($key);
            $value = get_transient($full_key);

            if (false === $value) {
                return false;
            }

            return maybe_unserialize($value);
        }

        /**
         * Set cache data
         *
         * @param string $key        The cache key
         * @param mixed  $value      The data to cache
         * @param int    $expiration Optional. Cache expiration in seconds. Default 0 (no expiration)
         * @return bool True if value was set, false otherwise
         */
        public static function set_cache($key, $value, $expiration = 0) {
            if (!is_string($key)) {
                return false;
            }

            $full_key = self::get_full_key($key);
            $value = maybe_serialize($value);

            return set_transient($full_key, $value, $expiration);
        }

        /**
         * Delete cached data by key
         *
         * @param string $key The cache key to delete
         * @return bool True if successful, false otherwise
         */
        public static function delete_cache($key) {
            if (!is_string($key)) {
                return false;
            }

            $full_key = self::get_full_key($key);
            return delete_transient($full_key);
        }

        /**
         * Clear all plugin-related cache
         *
         * @return int Number of cache items deleted
         */
        public static function clear_all_cache() {
            global $wpdb;

            $prefix = self::get_full_key('');
            $options = $wpdb->options;

            // Delete transients
            $transient_query = $wpdb->prepare(
                "DELETE FROM {$options} WHERE option_name LIKE %s",
                '_transient_' . $prefix . '%'
            );
            $wpdb->query($transient_query);

            // Delete transient timeouts
            $timeout_query = $wpdb->prepare(
                "DELETE FROM {$options} WHERE option_name LIKE %s",
                '_transient_timeout_' . $prefix . '%'
            );
            $wpdb->query($timeout_query);

            return $wpdb->rows_affected;
        }

        /**
         * Get the full cache key with prefix
         *
         * @param string $key The base cache key
         * @return string The full prefixed cache key
         */
        private static function get_full_key($key) {
            return self::$prefix . $key;
        }

        /**
         * Get the current cache prefix
         *
         * @return string The cache prefix
         */
        public static function get_prefix() {
            return self::$prefix;
        }

        /**
         * Set a custom cache prefix
         *
         * @param string $prefix The new prefix to use
         */
        public static function set_prefix($prefix) {
            if (is_string($prefix)) {
                self::$prefix = $prefix;
            }
        }

        /**
         * Get cache information (count, size, etc.)
         *
         * @return array Cache statistics
         */
        public static function get_cache_stats() {
            global $wpdb;

            $prefix = self::get_full_key('');
            $options = $wpdb->options;

            // Count all transients
            $count_query = $wpdb->prepare(
                "SELECT COUNT(*) FROM {$options} WHERE option_name LIKE %s",
                '_transient_' . $prefix . '%'
            );
            $count = $wpdb->get_var($count_query);

            // Get total size of transients
            $size_query = $wpdb->prepare(
                "SELECT SUM(LENGTH(option_value)) FROM {$options} WHERE option_name LIKE %s",
                '_transient_' . $prefix . '%'
            );
            $size = $wpdb->get_var($size_query);

            return array(
                'count' => (int) $count,
                'size'  => (int) $size,
                'size_human' => size_format($size),
            );
        }
}