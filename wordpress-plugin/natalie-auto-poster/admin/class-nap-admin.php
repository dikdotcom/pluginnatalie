<?php
/**
 * Admin class for Natalie Auto Poster
 *
 * @package Natalie_Auto_Poster
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NAP_Admin {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_filter( 'plugin_action_links_' . NAP_PLUGIN_BASENAME, array( $this, 'add_plugin_links' ) );
    }

    public function add_admin_menu() {
        add_menu_page( __( 'Auto Poster', 'natalie-auto-poster' ), __( 'Auto Poster', 'natalie-auto-poster' ), 'manage_options', 'natalie-auto-poster', array( $this, 'render_dashboard_page' ), 'dashicons-rss', 30 );
        add_submenu_page( 'natalie-auto-poster', __( 'Settings', 'natalie-auto-poster' ), __( 'Settings', 'natalie-auto-poster' ), 'manage_options', 'nap-settings', array( $this, 'render_settings_page' ) );
    }

    public function register_settings() {
        // General settings
        register_setting( 'nap_general', 'nap_auto_fetch_enabled', array( 'type' => 'boolean', 'default' => true ) );
        register_setting( 'nap_general', 'nap_articles_per_run', array( 'type' => 'integer', 'default' => 3 ) );
        register_setting( 'nap_general', 'nap_active_sources', array( 'type' => 'array', 'default' => array() ) );
        register_setting( 'nap_general', 'nap_custom_sources', 'sanitize_textarea_field' );
        register_setting( 'nap_general', 'nap_auto_publish', array( 'type' => 'boolean', 'default' => false ) );

        // AI Settings
        register_setting( 'nap_ai', 'nap_translation_provider', array( 'type' => 'string', 'default' => 'gemini' ) );
        register_setting( 'nap_ai', 'nap_openai_api_key' );
        register_setting( 'nap_ai', 'nap_openai_model', array( 'type' => 'string', 'default' => 'gpt-4o-mini' ) );
        register_setting( 'nap_ai', 'nap_gemini_api_key' );
        register_setting( 'nap_ai', 'nap_gemini_model', array( 'type' => 'string', 'default' => 'gemini-2.0-flash' ) );
        register_setting( 'nap_ai', 'nap_deepl_api_key' );
        register_setting( 'nap_ai', 'nap_claude_api_key' );
        register_setting( 'nap_ai', 'nap_claude_model' );
        register_setting( 'nap_ai', 'nap_groq_api_key' );
        register_setting( 'nap_ai', 'nap_groq_model', array( 'type' => 'string', 'default' => 'llama3-70b-8192' ) );
        register_setting( 'nap_ai', 'nap_cohere_api_key' );
        register_setting( 'nap_ai', 'nap_cohere_model', array( 'type' => 'string', 'default' => 'command-r' ) );
        
        // Review Settings
        register_setting( 'nap_ai', 'nap_skip_review', array( 'type' => 'boolean', 'default' => false ) );
        register_setting( 'nap_ai', 'nap_review_provider' );
        register_setting( 'nap_ai', 'nap_humanize_tone', array( 'type' => 'boolean', 'default' => true ) );
        register_setting( 'nap_ai', 'nap_enable_kanji_agent', array( 'type' => 'boolean', 'default' => true ) );

        // Storage & Maintenance
        register_setting( 'nap_images', 'nap_image_storage', array( 'type' => 'string', 'default' => 'wordpress' ) );
        register_setting( 'nap_images', 'nap_s3_access_key' );
        register_setting( 'nap_images', 'nap_s3_secret_key' );
        register_setting( 'nap_images', 'nap_s3_bucket' );
        register_setting( 'nap_maintenance', 'nap_log_retention_days', array( 'type' => 'integer', 'default' => 30 ) );
    }

    public function enqueue_scripts( $hook ) {
        if ( strpos( $hook, 'nap-' ) === false && strpos( $hook, 'natalie-auto-poster' ) === false ) return;
        wp_enqueue_style( 'nap-admin', NAP_PLUGIN_URL . 'assets/css/admin.css', array(), NAP_VERSION );
        wp_enqueue_script( 'nap-admin', NAP_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), NAP_VERSION, true );
    }

    public function render_dashboard_page() { include NAP_PLUGIN_DIR . 'admin/views/dashboard.php'; }
    public function render_settings_page() { 
        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
        include NAP_PLUGIN_DIR . 'admin/views/settings.php'; 
    }
    public function add_plugin_links( $links ) {
        array_unshift( $links, '<a href="' . admin_url( 'admin.php?page=nap-settings' ) . '">' . __( 'Settings', 'natalie-auto-poster' ) . '</a>' );
        return $links;
    }
}