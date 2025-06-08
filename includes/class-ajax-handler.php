<?php
/**
 * AJAX Request Handler for Advanced Book Listing
 *
 * Handles all AJAX requests for loading more books with filters and pagination.
 *
 * @package    Advanced_Book_Listing
 * @subpackage AJAX
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
class Advanced_Book_Listing_Ajax {
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
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }
    /**
     * Handle the load more books AJAX request.
     */
    public function handle_load_more_books() {
        try {
            // Verify nonce first
            $this->verify_request();

            // Validate and sanitize input parameters
            $params = $this->validate_ajax_parameters();

            // Build query args
            $args = $this->build_query_args($params);

            // Execute query and get results
            $results = $this->get_books_results($args);

            // Send successful response
            $this->send_success_response($results, $params['page']);

        } catch (Exception $e) {
            $this->send_error_response($e->getMessage());
        }
    }

    /**
     * Verify the AJAX request is valid.
     *
     * @throws Exception If nonce verification fails
     */
    private function verify_request() {
        if (!check_ajax_referer('advanced_book_listing_nonce', 'nonce', false)) {
            throw new Exception(__('Security check failed. Please refresh the page and try again.', 'advanced-book-listing'));
        }
    }

    /**
     * Validate and sanitize AJAX parameters.
     *
     * @return array Sanitized parameters
     * @throws Exception If required parameters are missing
     */
    private function validate_ajax_parameters() {
        $params = array(
            'page'           => isset($_POST['page']) ? absint($_POST['page']) : 1,
            'author_letter'  => isset($_POST['author_letter']) ? sanitize_text_field($_POST['author_letter']) : '',
            'price_range'    => isset($_POST['price_range']) ? $this->sanitize_price_range($_POST['price_range']) : '',
            'sort_by'        => isset($_POST['sort_by']) ? $this->sanitize_sort_by($_POST['sort_by']) : 'newest',
            'posts_per_page' => isset($_POST['posts_per_page']) ? absint($_POST['posts_per_page']) : 6,
        );

        if ($params['page'] < 1) {
            throw new Exception(__('Invalid page number.', 'advanced-book-listing'));
        }

        return $params;
    }

    /**
     * Sanitize price range parameter.
     *
     * @param string $price_range
     * @return string Sanitized price range
     */
    private function sanitize_price_range($price_range) {
        $allowed_ranges = array('50-100', '100-150', '150-200', '');
        return in_array($price_range, $allowed_ranges) ? $price_range : '';
    }

    /**
     * Sanitize sort by parameter.
     *
     * @param string $sort_by
     * @return string Sanitized sort by
     */
    private function sanitize_sort_by($sort_by) {
        $allowed_values = array('newest', 'oldest');
        return in_array($sort_by, $allowed_values) ? $sort_by : 'newest';
    }

    /**
     * Build query arguments based on parameters.
     *
     * @param array $params
     * @return array WP_Query arguments
     */
    private function build_query_args($params) {
        $meta_query = array();

        // Author name filter
        if (!empty($params['author_letter'])) {
            $meta_query[] = array(
                'key'     => '_book_author_name',
                'value'   => '^' . $params['author_letter'],
                'compare' => 'REGEXP'
            );
        }

        // Price range filter
        if (!empty($params['price_range'])) {
            $price_ranges = array(
                '50-100'  => array(50, 100),
                '100-150' => array(100, 150),
                '150-200' => array(150, 200)
            );

            if (isset($price_ranges[$params['price_range']])) {
                $meta_query[] = array(
                    'key'     => '_book_price',
                    'value'   => $price_ranges[$params['price_range']],
                    'type'    => 'numeric',
                    'compare' => 'BETWEEN'
                );
            }
        }

        // Order by
        $orderby = array();
        $order = ('oldest' === $params['sort_by']) ? 'ASC' : 'DESC';
        return array(
            'post_type'      => 'book',
            'posts_per_page' => $params['posts_per_page'],
            'paged'          => $params['page'],
            'meta_query'     => $meta_query,
            'orderby' => array(
                '_book_publish_date' => $order,
            ),
            'meta_key' => '_book_publish_date',
            'no_found_rows'  => false
        );
    }

    /**
     * Execute query and get results.
     *
     * @param array $args WP_Query arguments
     * @return array Query results
     */
    private function get_books_results($args) {
        $books_query = new WP_Query($args);

        ob_start();
        
        if ($books_query->have_posts()) :
            while ($books_query->have_posts()) : 
                $books_query->the_post();
                Advanced_Book_Shortcode::instance()->render_book_item();
            endwhile;
        endif;
        
        wp_reset_postdata();

        return array(
            'html'       => ob_get_clean(),
            'max_pages'  => $books_query->max_num_pages,
            'found'      => $books_query->found_posts,
            'post_count' => $books_query->post_count
        );
    }

    /**
     * Send successful AJAX response.
     *
     * @param array $results
     * @param int $current_page
     */
    private function send_success_response($results, $current_page) {
        wp_send_json_success(array(
            'html'         => $results['html'],
            'max_pages'    => $results['max_pages'],
            'current_page' => $current_page,
            'found'       => $results['found'],
            'post_count'   => $results['post_count'],
            'message'     => __('Books loaded successfully.', 'advanced-book-listing')
        ));
    }

    /**
     * Send error AJAX response.
     *
     * @param string $message
     */
    private function send_error_response($message) {
        wp_send_json_error(array(
            'message' => $message
        ));
    }
}