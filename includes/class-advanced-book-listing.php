<?php
/**
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Advanced_Book_Listing
 * @subpackage Advanced_Book_Listing/includes
 * @author     Drashti Patel <drashtipatel2903@gmail.com>
 */
class Advanced_Book_Listing {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Advanced_Book_Listing_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'ADVANCED_BOOK_LISTING_VERSION' ) ) {
			$this->version = ADVANCED_BOOK_LISTING_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'advanced-book-listing';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Advanced_Book_Listing_Loader. Orchestrates the hooks of the plugin.
	 * - Advanced_Book_Listing_i18n. Defines internationalization functionality.
	 * - Advanced_Book_Listing_Admin. Defines all hooks for the admin area.
	 * - Advanced_Book_Listing_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once ADVANCED_BOOK_LISTING_PLUGIN_DIR . 'includes/class-advanced-book-listing-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once ADVANCED_BOOK_LISTING_PLUGIN_DIR . 'includes/class-advanced-book-listing-i18n.php';
		require_once ADVANCED_BOOK_LISTING_PLUGIN_DIR . 'includes/class-book-shortcode-handler.php';
		require_once ADVANCED_BOOK_LISTING_PLUGIN_DIR . 'includes/class-ajax-handler.php';
		require_once ADVANCED_BOOK_LISTING_PLUGIN_DIR . 'includes/class-transient-cache.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once ADVANCED_BOOK_LISTING_PLUGIN_DIR . 'admin/class-advanced-book-listing-admin.php';
		require_once ADVANCED_BOOK_LISTING_PLUGIN_DIR . 'admin/class-advanced-book-cpt.php';
        require_once ADVANCED_BOOK_LISTING_PLUGIN_DIR . 'admin/class-rest-api.php';
		$this->loader = new Advanced_Book_Listing_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Advanced_Book_Listing_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Advanced_Book_Listing_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$this->loader = new Advanced_Book_Listing_Loader();

		$shortcode_public = new Advanced_Book_Shortcode( $this->get_plugin_name(), $this->get_version() );
		add_shortcode( 'advanced_books', [$shortcode_public, 'render_shortcode'] );
		
		$book_short = new Advanced_Book_Shortcode( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'wp_enqueue_scripts', $book_short, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $book_short, 'enqueue_scripts' );
		
		$book_ajax = new Advanced_Book_Listing_Ajax( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action('wp_ajax_load_more_books',  $book_ajax,'handle_load_more_books');
		$this->loader->add_action('wp_ajax_nopriv_load_more_books', $book_ajax,'handle_load_more_books');
		
		$admin_cpt = new Advanced_Book_Listing_Admin( $this->get_plugin_name(), $this->get_version() );

		// Initialize CPT
        $cpt = new Advanced_Book_Listing_CPT( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action('init', $cpt, 'register_book_cpt',10);
        $this->loader->add_action('add_meta_boxes', $cpt, 'add_book_meta_boxes');
        $this->loader->add_action('save_post_book', $cpt, 'save_book_meta');

        // Initialize REST API
        $rest_api = new Advanced_Book_Listing_REST_API( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action('rest_api_init', $rest_api, 'register_rest_routes');

        // Admin styles and scripts
        $this->loader->add_action('admin_enqueue_scripts', $admin_cpt, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $admin_cpt, 'enqueue_scripts');

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Advanced_Book_Listing_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
