<?php
if (!defined('ABSPATH')) {die;}

/**
 * Handles [advanced_books] shortcode and filter functionality.
 *
 * @package    Advanced_Book_Listing
 * @subpackage Advanced_Book_Listing/includes
 */
class Advanced_Book_Shortcode {
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
    public $post_perpage;    
    private static $instance = null;
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
        $this->post_perpage = 3;
        $this->transient_cache = new Advanced_Book_Listing_Transient_Cache();

    }

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self('advanced-book-listing',ADVANCED_BOOK_LISTING_VERSION);
        }
        return self::$instance;
    }

    /**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Advanced_Book_Listing_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Advanced_Book_Listing_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( 'abl-custom-css', ADVANCED_BOOK_LISTING_PLUGIN_URL . 'assets/css/abl-custom.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Advanced_Book_Listing_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Advanced_Book_Listing_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( 'abl-custom-js', ADVANCED_BOOK_LISTING_PLUGIN_URL . 'assets/js/abl-custom.js', array( 'jquery' ), $this->version, false );
         // Localize script for AJAX
         wp_localize_script(
            'abl-custom-js',
            'advanced_book_listing_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('advanced_book_listing_nonce')
            )
        );
	}
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'posts_per_page' => $this->post_perpage,
        ), $atts, 'advanced_books');

        // Get current filter values from request
        $author_letter = isset($_GET['author_letter']) ? sanitize_text_field($_GET['author_letter']) : '';
        $price_range = isset($_GET['price_range']) ? sanitize_text_field($_GET['price_range']) : '';
        $sort_by = isset($_GET['sort_by']) ? sanitize_text_field($_GET['sort_by']) : 'newest';

        // Generate cache key based on filters
        $cache_key = 'advanced_books_' . md5(serialize(array(
            'author_letter' => $author_letter,
            'price_range' => $price_range,
            'sort_by' => $sort_by,
            'page' => get_query_var('paged', 1),
            'posts_per_page' => $atts['posts_per_page']
        )));

        // Try to get cached output
        $output =  $this->transient_cache->get_cache($cache_key);

        if (false === $output) {
            ob_start();
            $this->render_book_listing($atts, $author_letter, $price_range, $sort_by);
            $output = ob_get_clean();
             $this->transient_cache->set_cache($cache_key, $output, HOUR_IN_SECONDS);
        }

        return $output;
    }

    private function render_book_listing($atts, $author_letter, $price_range, $sort_by) {
        $meta_query = array();

        // Author name filter
        if (!empty($author_letter)) {
            $meta_query[] = array(
                'key' => '_book_author_name',
                'value' => '^' . $author_letter,
                'compare' => 'REGEXP'
            );
        }

        // Price range filter
        if (!empty($price_range)) {
            $price_ranges = array(
                '50-100' => array(50, 100),
                '100-150' => array(100, 150),
                '150-200' => array(150, 200)
            );

            if (isset($price_ranges[$price_range])) {
                $meta_query[] = array(
                    'key' => '_book_price',
                    'value' => $price_ranges[$price_range],
                    'type' => 'numeric',
                    'compare' => 'BETWEEN'
                );
            }
        }

        // Order by
        $orderby = array();
        $order = 'DESC';

        if ('oldest' === $sort_by) {
            $order = 'ASC';
        }

        $orderby['meta_value'] = $order;
        $orderby['meta_key'] = '_book_publish_date';

        // Query arguments
        $args = array(
            'post_type' => 'book',
            'posts_per_page' => $atts['posts_per_page'],
            'paged' => max(1, get_query_var('paged')),
            'orderby' => array(
                '_book_publish_date' => $order,
            ),
            'meta_key' => '_book_publish_date',
            'meta_query' => $meta_query,
        );

       

        // The query
        $books_query = new WP_Query($args);

        // Display filters
        $this->render_filters($author_letter, $price_range, $sort_by);

        // Display books
        if ($books_query->have_posts()) :
            echo '<div class="book-listing">';
            echo '<div class="book-list" id="book-list">';
            
            while ($books_query->have_posts()) : $books_query->the_post();
                $this->render_book_item();
            endwhile;
            
            echo '</div>';

            // Pagination
            $this->render_pagination($books_query->max_num_pages);
            
            echo '</div>';
            
            wp_reset_postdata();
        else :
            echo '<p>' . __('No books found.', 'advanced-book-listing') . '</p>';
        endif;
    }

    private function render_filters($current_author_letter, $current_price_range, $current_sort_by) {
        ?>
        <div class="book-filters">
            <form id="book-filters-form" method="get" action="<?php echo esc_url(remove_query_arg('paged')); ?>">
                <!-- Author Filter -->
                <div class="filter-group">
                    <label for="author_letter"><?php _e('Filter by Author:', 'advanced-book-listing'); ?></label>
                    <select name="author_letter" id="author_letter">
                        <option value=""><?php _e('All Authors', 'advanced-book-listing'); ?></option>
                        <?php foreach (range('A', 'Z') as $letter) : ?>
                            <option value="<?php echo esc_attr($letter); ?>" <?php selected($current_author_letter, $letter); ?>>
                                <?php echo esc_html($letter); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Price Filter -->
                <div class="filter-group">
                    <label for="price_range"><?php _e('Price Range:', 'advanced-book-listing'); ?></label>
                    <select name="price_range" id="price_range">
                        <option value=""><?php _e('All Prices', 'advanced-book-listing'); ?></option>
                        <option value="50-100" <?php selected($current_price_range, '50-100'); ?>><?php _e('$50 - $100', 'advanced-book-listing'); ?></option>
                        <option value="100-150" <?php selected($current_price_range, '100-150'); ?>><?php _e('$100 - $150', 'advanced-book-listing'); ?></option>
                        <option value="150-200" <?php selected($current_price_range, '150-200'); ?>><?php _e('$150 - $200', 'advanced-book-listing'); ?></option>
                    </select>
                </div>
                
                <!-- Sort Filter -->
                <div class="filter-group">
                    <label for="sort_by"><?php _e('Sort by:', 'advanced-book-listing'); ?></label>
                    <select name="sort_by" id="sort_by">
                        <option value="newest" <?php selected($current_sort_by, 'newest'); ?>><?php _e('Newest First', 'advanced-book-listing'); ?></option>
                        <option value="oldest" <?php selected($current_sort_by, 'oldest'); ?>><?php _e('Oldest First', 'advanced-book-listing'); ?></option>
                    </select>
                </div>
                
                <button type="submit" class="filter-button"><?php _e('Apply Filters', 'advanced-book-listing'); ?></button>
                <?php if ($current_author_letter || $current_price_range || 'newest' !== $current_sort_by) : ?>
                    <a href="<?php echo esc_url(remove_query_arg(array('author_letter', 'price_range', 'sort_by', 'paged'))); ?>" class="reset-filters">
                        <?php _e('Reset Filters', 'advanced-book-listing'); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>
        <?php
    }

    public function render_book_item() {
        $author_name = get_post_meta(get_the_ID(), '_book_author_name', true);
        $price = get_post_meta(get_the_ID(), '_book_price', true);
        $publish_date = get_post_meta(get_the_ID(), '_book_publish_date', true);
        
        $formatted_date = $publish_date ? date_i18n(get_option('date_format'), strtotime($publish_date)) : '';
        $formatted_price = $price ? '$' . number_format($price, 2) : '';
        ?>
        <div class="book-item">
            <div class="book-thumbnail">
                <?php if (has_post_thumbnail()) : ?>
                    <a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('medium'); ?></a>
                <?php endif; ?>
            </div>
            <div class="book-details">
                <h3 class="book-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                <?php if ($author_name) : ?>
                    <div class="book-author"><?php echo esc_html__('Author:', 'advanced-book-listing') . ' ' . esc_html($author_name); ?></div>
                <?php endif; ?>
                <?php if ($price) : ?>
                    <div class="book-price"><?php echo esc_html__('Price:', 'advanced-book-listing') . ' ' . esc_html($formatted_price); ?></div>
                <?php endif; ?>
                <?php if ($publish_date) : ?>
                    <div class="book-publish-date"><?php echo esc_html__('Published:', 'advanced-book-listing') . ' ' . esc_html($formatted_date); ?></div>
                <?php endif; ?>
                <div class="book-excerpt"><?php the_excerpt(); ?></div>
            </div>
        </div>
        <?php
    }

    private function render_pagination($max_num_pages) {
        if ($max_num_pages <= 1) {
            return;
        }        
        // AJAX load more button
        echo '<div class="load-more-container">';
        echo '<button id="load-more-books" class="load-more-button" data-page="1" data-posts-per-page='.$this->post_perpage.' data-max-pages="' . esc_attr($max_num_pages) . '">';
        echo __('Load More', 'advanced-book-listing');
        echo '</button>';
        echo '</div>';
    }
}