<?php

/**
 * Plugin Name:     Schema Pro - Genesis
 * Plugin URI:      https://github.com/bizbudding/wp-schema-pro-genesis
 * Description:     Automatically disables specific Genesis schema when Schema Pro is outputting JSON-LD data.
 * Version:         0.2.0
 *
 * Author:          Mike Hemberger
 * Author URI:      https://bizbudding.com
 * Text Domain:     'wp-schema-pro-genesis'
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main Schema_Pro_Genesis_Setup Class.
 *
 * @access  private
 * @since   0.1.0
 */
final class Schema_Pro_Genesis_Setup {

	/**
	 * @var Schema_Pro_Genesis_Setup The one true Schema_Pro_Genesis_Setup
	 * @since 0.1.0
	 */
	private static $instance;

	/**
	 * Main Schema_Pro_Genesis_Setup Instance.
	 *
	 * Insures that only one instance of Schema_Pro_Genesis_Setup exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since   0.1.0
	 * @static  var array $instance
	 * @return  object | Schema_Pro_Genesis_Setup The one true Schema_Pro_Genesis_Setup
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup
			self::$instance = new Schema_Pro_Genesis_Setup;
			// Methods
			self::$instance->setup_constants();
			// self::$instance->includes();
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since   0.1.0
	 * @access  protected
	 * @return  void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wp-schema-pro-genesis' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @since   0.1.0
	 * @access  protected
	 * @return  void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wp-schema-pro-genesis' ), '1.0' );
	}

	/**
	 * Setup plugin constants.
	 *
	 * @access  private
	 * @since   0.1.0
	 * @return  void
	 */
	private function setup_constants() {

		// Plugin version.
		if ( ! defined( 'SCHEMA_PRO_GENESIS_VERSION' ) ) {
			define( 'SCHEMA_PRO_GENESIS_VERSION', '0.2.0' );
		}

		// Plugin Folder Path.
		if ( ! defined( 'SCHEMA_PRO_GENESIS_PLUGIN_DIR' ) ) {
			define( 'SCHEMA_PRO_GENESIS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin Includes Path
		if ( ! defined( 'SCHEMA_PRO_GENESIS_INCLUDES_DIR' ) ) {
			define( 'SCHEMA_PRO_GENESIS_INCLUDES_DIR', SCHEMA_PRO_GENESIS_PLUGIN_DIR . 'includes/' );
		}

		// Plugin Folder URL.
		if ( ! defined( 'SCHEMA_PRO_GENESIS_PLUGIN_URL' ) ) {
			define( 'SCHEMA_PRO_GENESIS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		// Plugin Root File.
		if ( ! defined( 'SCHEMA_PRO_GENESIS_PLUGIN_FILE' ) ) {
			define( 'SCHEMA_PRO_GENESIS_PLUGIN_FILE', __FILE__ );
		}

		// Plugin Base Name
		if ( ! defined( 'SCHEMA_PRO_GENESIS_BASENAME' ) ) {
			define( 'SCHEMA_PRO_GENESIS_BASENAME', dirname( plugin_basename( __FILE__ ) ) );
		}
	}

	/**
	 * Include required files.
	 *
	 * @access  private
	 * @since   0.1.0
	 * @return  void
	 */
	private function includes() {
		foreach ( glob( SCHEMA_PRO_GENESIS_INCLUDES_DIR . '*.php' ) as $file ) { include $file; }
	}

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'updater' ) );
		add_action( 'wp',         array( $this, 'run' ), 99 );
	}

	/**
	 * Setup the updater.
	 *
	 * @uses    https://github.com/YahnisElsts/plugin-update-checker/
	 *
	 * @return  void
	 */
	public function updater() {
		if ( ! class_exists( 'Puc_v4_Factory' ) ) {
			require_once SCHEMA_PRO_GENESIS_INCLUDES_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php'; // 4.4
		}
		$updater = Puc_v4_Factory::buildUpdateChecker( 'https://github.com/bizbudding/wp-schema-pro-genesis', __FILE__, 'wp-schema-pro-genesis' );
	}

	/**
	 * Remove Genesis schema if WP Schema Pro is outputting schema on the current page.
	 * A lot of the conditional code was taken directly from:
	 * /plugins/wp-schema-pro/classes/class-bsf-aiosrs-pro-markup.php
	 *
	 * @uses    Genesis.
	 * @uses    Schema Pro.
	 *
	 * @return  void
	 */
	public function run() {

		// Bail if not on the front end.
		if ( is_admin() ) {
			return;
		}

		// Bail if Genesis is not the parent theme.
		if ( 'genesis'!== get_template() ) {
			return;
		}

		// Bail if Schema Pro is not active.
		if ( ! class_exists( 'BSF_AIOSRS_Pro' ) ) {
			return;
		}

		// Get schema settings.
		$general_settings = BSF_AIOSRS_Pro_Admin::get_options( 'wp-schema-pro-general-settings' );
		$global_settings  = BSF_AIOSRS_Pro_Admin::get_options( 'wp-schema-pro-global-schemas' );
		$posts            = BSF_AIOSRS_Pro_Markup::get_schema_posts();

		// Body.
		if ( ! empty( $general_settings['site-represent'] ) ) {
			add_filter( 'genesis_attr_body', array( $this, 'remove_attributes' ), 20 );
		}

		// Breadcrumbs.
		if ( '1' === $global_settings['breadcrumb'] ) {
			add_filter( 'genesis_attr_breadcrumb',           array( $this, 'remove_attributes' ), 20 );
			add_filter( 'genesis_attr_breadcrumb-link-wrap', array( $this, 'remove_attributes' ), 20 );
		}

		// Content.
		if ( is_array( $posts ) && ! empty( $posts ) ) {
			add_filter( 'genesis_attr_content', array( $this, 'remove_attributes' ), 20 );
			add_filter( 'genesis_attr_entry',   array( $this, 'remove_attributes' ), 20 );
		}

		// Bail if not a single page/post/cpt.
		if ( ! is_singular() ) {
			return;
		}

		// Set variables.
		$post_id = get_the_ID();
		$about   = (int)$global_settings['about-page'];
		$contact = (int)$global_settings['contact-page'];

		// Specific items.
		if ( in_array( $post_id, array( $about, $contact ) ) ) {
			add_filter( 'genesis_attr_content', array( $this, 'remove_attributes' ), 20 );
			add_filter( 'genesis_attr_entry',   array( $this, 'remove_attributes' ), 20 );
		}
	}

	/**
	 * Clear schema from a Genesis element.
	 *
	 * @param   array  $attributes  The attributes of the element.
	 *
	 * @return  array  The modified attirbutes.
	 */
	public function remove_attributes( $attributes ) {
		$attributes['itemprop']  = '';
		$attributes['itemscope'] = '';
		$attributes['itemtype']  = '';
		return $attributes;
	}

}

/**
 * The main function for that returns Schema_Pro_Genesis_Setup
 *
 * The main function responsible for returning the one true Schema_Pro_Genesis_Setup
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $plugin = Schema_Pro_Genesis(); ?>
 *
 * @since 0.1.0
 *
 * @return object|Schema_Pro_Genesis_Setup The one true Schema_Pro_Genesis_Setup Instance.
 */
function Schema_Pro_Genesis() {
	return Schema_Pro_Genesis_Setup::instance();
}

// Get Schema_Pro_Genesis Running.
Schema_Pro_Genesis();
