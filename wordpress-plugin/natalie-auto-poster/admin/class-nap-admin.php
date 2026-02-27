<?php
/**
 * Admin class for Natalie Auto Poster
 *
 * @package Natalie_Auto_Poster
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class NAP_Admin
 */
class NAP_Admin {

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
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_nap_manual_fetch', array( $this, 'ajax_manual_fetch' ) );
        add_action( 'wp_ajax_nap_test_connection', array( $this, 'ajax_test_connection' ) );
        add_action( 'wp_ajax_nap_process_single', array( $this, 'ajax_process_single' ) );
        add_filter( 'plugin_action_links_' . NAP_PLUGIN_BASENAME, array( $this, 'add_plugin_links' ) );
    }

    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Natalie Auto Poster', 'natalie-auto-poster' ),
            __( 'Auto Poster', 'natalie-auto-poster' ),
            'manage_options',
            'natalie-auto-poster',
            array( $this, 'render_dashboard_page' ),
            'dashicons-rss',
            30
        );

        add_submenu_page(
            'natalie-auto-poster',
            __( 'Dashboard', 'natalie-auto-poster' ),
            __( 'Dashboard', 'natalie-auto-poster' ),
            'manage_options',
            'natalie-auto-poster',
            array( $this, 'render_dashboard_page' )
        );

        add_submenu_page(
            'natalie-auto-poster',
            __( 'Articles', 'natalie-auto-poster' ),
            __( 'Articles', 'natalie-auto-poster' ),
            'manage_options',
            'nap-articles',
            array( $this, 'render_articles_page' )
        );

        add_submenu_page(
            'natalie-auto-poster',
            __( 'Settings', 'natalie-auto-poster' ),
            __( 'Settings', 'natalie-auto-poster' ),
            'manage_options',
            'nap-settings',
            array( $this, 'render_settings_page' )
        );

        add_submenu_page(
            'natalie-auto-poster',
            __( 'Logs', 'natalie-auto-poster' ),
            __( 'Logs', 'natalie-auto-poster' ),
            'manage_options',
            'nap-logs',
            array( $this, 'render_logs_page' )
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        // General settings
        register_setting( 'nap_general', 'nap_auto_fetch_enabled', array( 'type' => 'boolean', 'default' => true ) );
        register_setting( 'nap_general', 'nap_fetch_interval', array( 'type' => 'string', 'default' => 'hourly' ) );
        register_setting( 'nap_general', 'nap_articles_per_run', array( 'type' => 'integer', 'default' => 3 ) );
        register_setting( 'nap_general', 'nap_active_sources', array( 'type' => 'array', 'default' => array() ) );
        register_setting( 'nap_general', 'nap_default_post_status', array( 'type' => 'string', 'default' => 'draft' ) );
        register_setting( 'nap_general', 'nap_auto_publish', array( 'type' => 'boolean', 'default' => false ) );
        register_setting( 'nap_general', 'nap_default_post_author', array( 'type' => 'integer', 'default' => 1 ) );
        register_setting( 'nap_general', 'nap_default_category', array( 'type' => 'integer', 'default' => 0 ) );
        register_setting( 'nap_general', 'nap_default_tags', array( 'type' => 'string', 'default' => '' ) );
        register_setting( 'nap_general', 'nap_show_attribution', array( 'type' => 'boolean', 'default' => true ) );
        register_setting( 'nap_general', 'nap_attribution_template' );

        // AI / Translation settings
        register_setting( 'nap_ai', 'nap_translation_provider', array( 'type' => 'string', 'default' => 'openai' ) );
        register_setting( 'nap_ai', 'nap_openai_api_key' );
        register_setting( 'nap_ai', 'nap_openai_model', array( 'type' => 'string', 'default' => 'gpt-4o-mini' ) );
        register_setting( 'nap_ai', 'nap_gemini_api_key' );
        register_setting( 'nap_ai', 'nap_gemini_model', array( 'type' => 'string', 'default' => 'gemini-1.5-flash' ) );
        register_setting( 'nap_ai', 'nap_deepl_api_key' );
        register_setting( 'nap_ai', 'nap_claude_api_key' );
        register_setting( 'nap_ai', 'nap_claude_model', array( 'type' => 'string', 'default' => 'claude-3-haiku-20240307' ) );
        register_setting( 'nap_ai', 'nap_translation_prompt' );
        register_setting( 'nap_ai', 'nap_skip_review', array( 'type' => 'boolean', 'default' => false ) );
        register_setting( 'nap_ai', 'nap_review_provider' );

        // Image storage settings
        register_setting( 'nap_images', 'nap_image_storage', array( 'type' => 'string', 'default' => 'wordpress' ) );
        // S3
        register_setting( 'nap_images', 'nap_s3_access_key' );
        register_setting( 'nap_images', 'nap_s3_secret_key' );
        register_setting( 'nap_images', 'nap_s3_bucket' );
        register_setting( 'nap_images', 'nap_s3_region', array( 'type' => 'string', 'default' => 'us-east-1' ) );
        register_setting( 'nap_images', 'nap_s3_path_prefix', array( 'type' => 'string', 'default' => 'natalie-auto-poster/' ) );
        register_setting( 'nap_images', 'nap_s3_custom_domain' );
        // Cloudflare R2
        register_setting( 'nap_images', 'nap_r2_access_key' );
        register_setting( 'nap_images', 'nap_r2_secret_key' );
        register_setting( 'nap_images', 'nap_r2_bucket' );
        register_setting( 'nap_images', 'nap_r2_account_id' );
        register_setting( 'nap_images', 'nap_r2_custom_domain' );
        register_setting( 'nap_images', 'nap_r2_path_prefix', array( 'type' => 'string', 'default' => 'natalie-auto-poster/' ) );
        // GCS
        register_setting( 'nap_images', 'nap_gcs_service_account' );
        register_setting( 'nap_images', 'nap_gcs_bucket' );
        register_setting( 'nap_images', 'nap_gcs_path_prefix', array( 'type' => 'string', 'default' => 'natalie-auto-poster/' ) );
        register_setting( 'nap_images', 'nap_gcs_custom_domain' );
        // BunnyCDN
        register_setting( 'nap_images', 'nap_bunny_api_key' );
        register_setting( 'nap_images', 'nap_bunny_storage_zone' );
        register_setting( 'nap_images', 'nap_bunny_cdn_url' );
        register_setting( 'nap_images', 'nap_bunny_storage_region' );
        register_setting( 'nap_images', 'nap_bunny_path_prefix', array( 'type' => 'string', 'default' => 'natalie-auto-poster/' ) );

        // Maintenance settings
        register_setting( 'nap_maintenance', 'nap_log_retention_days', array( 'type' => 'integer', 'default' => 30 ) );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts( $hook ) {
        if ( strpos( $hook, 'natalie-auto-poster' ) === false && strpos( $hook, 'nap-' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'nap-admin',
            NAP_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            NAP_VERSION
        );

        wp_enqueue_script(
            'nap-admin',
            NAP_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            NAP_VERSION,
            true
        );

        wp_localize_script( 'nap-admin', 'napAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'nap_admin_nonce' ),
            'strings' => array(
                'fetching'    => __( 'Fetching articles...', 'natalie-auto-poster' ),
                'success'     => __( 'Success!', 'natalie-auto-poster' ),
                'error'       => __( 'Error occurred', 'natalie-auto-poster' ),
                'confirm'     => __( 'Are you sure?', 'natalie-auto-poster' ),
                'testing'     => __( 'Testing connection...', 'natalie-auto-poster' ),
                'processing'  => __( 'Processing...', 'natalie-auto-poster' ),
            ),
        ) );
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        $stats = array(
            'total'      => NAP_Database::count_articles_by_status(),
            'pending'    => NAP_Database::count_articles_by_status( 'pending' ),
            'posted'     => NAP_Database::count_articles_by_status( 'posted' ),
            'error'      => NAP_Database::count_articles_by_status( 'error' ),
        );

        $recent_articles = NAP_Database::get_recent_articles( 10 );
        $last_fetch = get_option( 'nap_last_fetch_time', __( 'Never', 'natalie-auto-poster' ) );
        $next_fetch = NAP_Scheduler::get_next_scheduled();
        $active_sources = get_option( 'nap_active_sources', array() );

        include NAP_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Render articles page
     */
    public function render_articles_page() {
        $page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $per_page = 20;
        $offset = ( $page - 1 ) * $per_page;

        $articles = NAP_Database::get_recent_articles( $per_page, $offset );
        $total = NAP_Database::count_articles_by_status();
        $total_pages = ceil( $total / $per_page );

        include NAP_PLUGIN_DIR . 'admin/views/articles.php';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
        include NAP_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Render logs page
     */
    public function render_logs_page() {
        $level = isset( $_GET['level'] ) ? sanitize_key( $_GET['level'] ) : null;
        $logs = NAP_Database::get_recent_logs( 200, $level );
        include NAP_PLUGIN_DIR . 'admin/views/logs.php';
    }

    /**
     * AJAX: Manual fetch
     */
    public function ajax_manual_fetch() {
        check_ajax_referer( 'nap_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ) );
        }

        $source_key = isset( $_POST['source'] ) ? sanitize_text_field( $_POST['source'] ) : null;
        $limit = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : 5;

        $results = NAP_Scheduler::trigger_manual_fetch( $source_key, $limit );

        wp_send_json_success( array(
            'message' => sprintf(
                __( 'Processed: %d, Skipped: %d, Errors: %d', 'natalie-auto-poster' ),
                $results['processed'],
                $results['skipped'],
                $results['errors']
            ),
            'results' => $results,
        ) );
    }

    /**
     * AJAX: Test API connection
     */
    public function ajax_test_connection() {
        check_ajax_referer( 'nap_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ) );
        }

        $provider = isset( $_POST['provider'] ) ? sanitize_text_field( $_POST['provider'] ) : 'openai';

        $test_text = 'こんにちは、世界！';
        $result = NAP_Translator::translate_text( $test_text, $provider );

        if ( $result ) {
            wp_send_json_success( array(
                'message'     => __( 'Connection successful!', 'natalie-auto-poster' ),
                'test_input'  => $test_text,
                'test_output' => $result,
            ) );
        } else {
            wp_send_json_error( array(
                'message' => __( 'Connection failed. Check your API key and settings.', 'natalie-auto-poster' ),
            ) );
        }
    }

    /**
     * AJAX: Process single article URL
     */
    public function ajax_process_single() {
        check_ajax_referer( 'nap_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ) );
        }

        $url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '';
        if ( empty( $url ) ) {
            wp_send_json_error( array( 'message' => __( 'URL is required', 'natalie-auto-poster' ) ) );
        }

        $source_key = isset( $_POST['source'] ) ? sanitize_text_field( $_POST['source'] ) : 'natalie.mu';

        $post_id = NAP_Post_Creator::process_article( $url, $source_key );

        if ( $post_id ) {
            wp_send_json_success( array(
                'message' => sprintf(
                    __( 'Article processed successfully! Post ID: %d', 'natalie-auto-poster' ),
                    $post_id
                ),
                'post_id'   => $post_id,
                'edit_url'  => get_edit_post_link( $post_id, 'raw' ),
                'view_url'  => get_permalink( $post_id ),
            ) );
        } else {
            wp_send_json_error( array(
                'message' => __( 'Failed to process article. Check logs for details.', 'natalie-auto-poster' ),
            ) );
        }
    }

    /**
     * Add plugin action links
     */
    public function add_plugin_links( $links ) {
        $settings_link = '<a href="' . admin_url( 'admin.php?page=nap-settings' ) . '">' . __( 'Settings', 'natalie-auto-poster' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }
}
