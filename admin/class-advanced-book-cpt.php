<?php
if (!defined('ABSPATH')) {die;}

/**
 * Handles the registration of the Books custom post type and its custom fields.
 *
 * @package    Advanced_Book_Listing
 * @subpackage Advanced_Book_Listing/includes
 */
class Advanced_Book_Listing_CPT {

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
     * Register the custom post type.
     *
     * @since    1.0.0
     */
    public function register_book_cpt() {
        $labels = array(
            'name'               => _x('Books', 'post type general name', 'advanced-book-listing'),
            'singular_name'      => _x('Book', 'post type singular name', 'advanced-book-listing'),
            'menu_name'          => __('Books', 'advanced-book-listing'),
            'name_admin_bar'     => __('Book', 'advanced-book-listing'),
            'add_new'           => __('Add New', 'advanced-book-listing'),
            'add_new_item'       => __('Add New Book', 'advanced-book-listing'),
            'new_item'           => __('New Book', 'advanced-book-listing'),
            'edit_item'          => __('Edit Book', 'advanced-book-listing'),
            'view_item'         => __('View Book', 'advanced-book-listing'),
            'all_items'          => __('All Books', 'advanced-book-listing'),
            'search_items'      => __('Search Books', 'advanced-book-listing'),
            'parent_item_colon'  => __('Parent Books:', 'advanced-book-listing'),
            'not_found'          => __('No books found.', 'advanced-book-listing'),
            'not_found_in_trash' => __('No books found in Trash.', 'advanced-book-listing')
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'           => true,
            'show_in_menu'      => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'books'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor', 'thumbnail'),
            'show_in_rest'       => true,
        );

        register_post_type('book', $args);
    }

    /**
     * Add meta boxes for the book post type.
     *
     * @since    1.0.0
     */
    public function add_book_meta_boxes() {
        add_meta_box(
            'book_meta',
            __('Book Details', 'advanced-book-listing'),
            array($this, 'render_book_meta_box'),
            'book',
            'normal',
            'high'
        );
    }

    /**
     * Render the book meta box content.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_book_meta_box($post) {
        wp_nonce_field('book_meta_box', 'book_meta_box_nonce');
        
        $author_name  = get_post_meta($post->ID, '_book_author_name', true);
        $price        = get_post_meta($post->ID, '_book_price', true);
        $publish_date = get_post_meta($post->ID, '_book_publish_date', true);
        
        echo '<div class="book-meta-fields">';
        echo '<p>';
        echo '<label for="book_author_name">' . esc_html__('Author Name', 'advanced-book-listing') . '</label>';
        echo '<input type="text" id="book_author_name" name="book_author_name" value="' . esc_attr($author_name) . '" />';
        echo '</p>';
        echo '<p>';
        echo '<label for="book_price">' . esc_html__('Price ($)', 'advanced-book-listing') . '</label>';
        echo '<input type="number" id="book_price" name="book_price" value="' . esc_attr($price) . '" min="0" />';
        echo '</p>';
        echo '<p>';
        echo '<label for="book_publish_date">' . esc_html__('Publish Date', 'advanced-book-listing') . '</label>';
        echo '<input type="date" id="book_publish_date" name="book_publish_date" value="' . esc_attr($publish_date) . '" />';
        echo '</p>';        
        echo '</div>';
    }

    /**
     * Save the book meta data.
     *
     * @since    1.0.0
     * @param    int    $post_id    The post ID.
     */
    public function save_book_meta($post_id) {
        if (!isset($_POST['book_meta_box_nonce']) || !wp_verify_nonce($_POST['book_meta_box_nonce'], 'book_meta_box')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save author name
        if (isset($_POST['book_author_name'])) {
            update_post_meta(
                $post_id,
                '_book_author_name',
                sanitize_text_field($_POST['book_author_name'])
            );
        }

        // Save price
        if (isset($_POST['book_price'])) {
            update_post_meta(
                $post_id,
                '_book_price',
                floatval($_POST['book_price'])
            );
        }

        // Save publish date
        if (isset($_POST['book_publish_date'])) {
            update_post_meta(
                $post_id,
                '_book_publish_date',
                sanitize_text_field($_POST['book_publish_date'])
            );
        }
    }
}