<?php
/**
 * Plugin Name: Natalie Auto Poster
 * Plugin URI: https://github.com/your-repo/natalie-auto-poster
 * Description: Auto-post berita dari website Jepang (natalie.mu dan sejenisnya) yang diterjemahkan ke Bahasa Indonesia menggunakan AI, dengan review otomatis dan upload foto ke S3/cloud storage.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: natalie-auto-poster
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'NAP_VERSION', '1.0.0' );
define( 'NAP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NAP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NAP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'NAP_DB_VERSION', '1.0' );

/**
 * Main plugin class
 */
class Natalie_Auto_Poster {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Get single instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once NAP_PLUGIN_DIR . 'includes/class-nap-database.php';
        require_once NAP_PLUGIN_DIR . 'includes/class-nap-fetcher.php';
        require_once NAP_PLUGIN_DIR . 'includes/class-nap-translator.php';
        require_once NAP_PLUGIN_DIR . 'includes/class-nap-ai-reviewer.php';
        require_once NAP_PLUGIN_DIR . 'includes/class-nap-image-uploader.php';
        require_once NAP_PLUGIN_DIR . 'includes/class-nap-post-creator.php';
        require_once NAP_PLUGIN_DIR . 'includes/class-nap-scheduler.php';
        require_once NAP_PLUGIN_DIR . 'includes/class-nap-logger.php';

        if ( is_admin() ) {
            require_once NAP_PLUGIN_DIR . 'admin/class-nap-admin.php';
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        add_action( 'plugins_loaded', array( $this, 'init' ) );
        add_action( 'init', array( $this, 'load_textdomain' ) );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        NAP_Database::create_tables();
        NAP_Scheduler::schedule_events();
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        NAP_Scheduler::unschedule_events();
        flush_rewrite_rules();
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize components
        NAP_Scheduler::get_instance();

        if ( is_admin() ) {
            NAP_Admin::get_instance();
        }
    }

    /**
     * Load text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'natalie-auto-poster',
            false,
            dirname( NAP_PLUGIN_BASENAME ) . '/languages/'
        );
    }
}

// Initialize plugin
Natalie_Auto_Poster::get_instance();
