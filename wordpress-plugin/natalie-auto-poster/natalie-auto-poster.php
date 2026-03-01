<?php
/**
 * Plugin Name: Japanese News Entertainment Post by KEiKO
 * Plugin URI: https://keiko.co.id
 * Description: Auto-post berita dari website Jepang (natalie.mu, RSS Feed, dll) yang diterjemahkan ke Bahasa Indonesia menggunakan AI, dengan review otomatis (Humanizer & Kanji Agent) dan upload foto ke cloud storage.
 * Version: 2.0.0
 * Author: Andika Setiawan
 * Author URI: https://keiko.co.id
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
define( 'NAP_VERSION', '2.0.0' );
define( 'NAP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NAP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NAP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'NAP_DB_VERSION', '1.0' );

/**
 * Main plugin class
 */
class Natalie_Auto_Poster {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

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

    private function init_hooks() {
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        add_action( 'plugins_loaded', array( $this, 'init' ) );
        add_action( 'init', array( $this, 'load_textdomain' ) );
    }

    public function activate() {
        NAP_Database::create_tables();
        NAP_Scheduler::schedule_events();
        flush_rewrite_rules();
    }

    public function deactivate() {
        NAP_Scheduler::unschedule_events();
        flush_rewrite_rules();
    }

    public function init() {
        NAP_Scheduler::get_instance();
        if ( is_admin() ) {
            NAP_Admin::get_instance();
        }
    }

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