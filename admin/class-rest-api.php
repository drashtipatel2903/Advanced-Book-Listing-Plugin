<?php
if (!defined('ABSPATH')) {
    die;
}

/**
 * Creates custom REST API endpoints for books.
 *
 * @package    Advanced_Book_Listing
 * @subpackage Advanced_Book_Listing/includes
 */
class Advanced_Book_Listing_REST_API {

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
     * Register all REST API routes.
     *
     * @since    1.0.0
     */
    public function register_rest_routes() {
        register_rest_route('books/v1', '/list', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_books_list'),
            'permission_callback' => '__return_true',
            'args' => array(
                'author_letter' => array(
                    'description' => __('Filter by author name starting with this letter', 'advanced-book-listing'),
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'price_range' => array(
                    'description' => __('Filter by price range (50-100, 100-150, 150-200)', 'advanced-book-listing'),
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'sort_by' => array(
                    'description' => __('Sort by newest or oldest', 'advanced-book-listing'),
                    'type' => 'string',
                    'default' => 'newest',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'page' => array(
                    'description' => __('Page number', 'advanced-book-listing'),
                    'type' => 'integer',
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                ),
                'per_page' => array(
                    'description' => __('Number of items per page', 'advanced-book-listing'),
                    'type' => 'integer',
                    'default' => 10,
                    'sanitize_callback' => 'absint',
                ),
            ),
        ));
    }

    /**
     * Get list of books with filtering options.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    The REST request object
     * @return   WP_REST_Response
     */
    public function get_books_list(WP_REST_Request $request) {
        $params = $request->get_params();

        // Build meta query
        $meta_query = array('relation' => 'AND');

        // Author name filter
        if (!empty($params['author_letter'])) {
            $meta_query[] = array(
                'key' => '_book_author_name',
                'value' => '^' . $params['author_letter'],
                'compare' => 'REGEXP'
            );
        }

        // Price range filter
        if (!empty($params['price_range'])) {
            $price_ranges = array(
                '50-100' => array(50, 100),
                '100-150' => array(100, 150),
                '150-200' => array(150, 200)
            );

            if (isset($price_ranges[$params['price_range']])) {
                $meta_query[] = array(
                    'key' => '_book_price',
                    'value' => $price_ranges[$params['price_range']],
                    'type' => 'numeric',
                    'compare' => 'BETWEEN'
                );
            }
        }

        // Order by
        $order = 'DESC';
        if ('oldest' === $params['sort_by']) {
            $order = 'ASC';
        }

        // Query arguments
        $args = array(
            'post_type' => 'book',
            'posts_per_page' => $params['per_page'],
            'paged' => $params['page'],
            'meta_query' => $meta_query,
             'orderby' => array(
                '_book_publish_date' => $order,
            ),
            'meta_key' => '_book_publish_date',
        );

        // The query
        $books_query = new WP_Query($args);

        // Prepare response
        $books = array();
        
        if ($books_query->have_posts()) {
            while ($books_query->have_posts()) {
                $books_query->the_post();
                
                $book_id = get_the_ID();
                $author_name = get_post_meta($book_id, '_book_author_name', true);
                $price = get_post_meta($book_id, '_book_price', true);
                $publish_date = get_post_meta($book_id, '_book_publish_date', true);
                
                $books[] = array(
                    'id' => $book_id,
                    'title' => get_the_title(),
                    'permalink' => get_permalink(),
                    'thumbnail' => get_the_post_thumbnail_url($book_id, 'medium'),
                    'excerpt' => get_the_excerpt(),
                    'author_name' => $author_name,
                    'price' => $price,
                    'publish_date' => $publish_date,
                    'formatted_price' => $price ? '$' . number_format($price, 2) : '',
                    'formatted_date' => $publish_date ? date_i18n(get_option('date_format'), strtotime($publish_date)) : ''
                );
            }
        }
        
        wp_reset_postdata();

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'books' => $books,
                'total' => $books_query->found_posts,
                'pages' => $books_query->max_num_pages,
                'current_page' => $params['page']
            )
        ), 200);
    }
}