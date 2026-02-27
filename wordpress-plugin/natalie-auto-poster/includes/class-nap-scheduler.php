<?php
/**
 * Scheduler class for Natalie Auto Poster
 * Handles WP-Cron scheduling for automatic article fetching
 *
 * @package Natalie_Auto_Poster
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class NAP_Scheduler
 */
class NAP_Scheduler {

    /**
     * Cron hook names
     */
    const CRON_HOOK_FETCH   = 'nap_cron_fetch_articles';
    const CRON_HOOK_CLEANUP = 'nap_cron_cleanup_logs';

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
        add_action( self::CRON_HOOK_FETCH, array( $this, 'run_fetch_job' ) );
        add_action( self::CRON_HOOK_CLEANUP, array( $this, 'run_cleanup_job' ) );
        add_filter( 'cron_schedules', array( $this, 'add_custom_schedules' ) );
    }

    /**
     * Schedule cron events on plugin activation
     */
    public static function schedule_events() {
        $fetch_interval = get_option( 'nap_fetch_interval', 'hourly' );

        if ( ! wp_next_scheduled( self::CRON_HOOK_FETCH ) ) {
            wp_schedule_event( time(), $fetch_interval, self::CRON_HOOK_FETCH );
        }

        if ( ! wp_next_scheduled( self::CRON_HOOK_CLEANUP ) ) {
            wp_schedule_event( time(), 'daily', self::CRON_HOOK_CLEANUP );
        }
    }

    /**
     * Unschedule cron events on plugin deactivation
     */
    public static function unschedule_events() {
        $timestamp = wp_next_scheduled( self::CRON_HOOK_FETCH );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::CRON_HOOK_FETCH );
        }

        $timestamp = wp_next_scheduled( self::CRON_HOOK_CLEANUP );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::CRON_HOOK_CLEANUP );
        }
    }

    /**
     * Add custom cron schedules
     */
    public function add_custom_schedules( $schedules ) {
        $schedules['every_30_minutes'] = array(
            'interval' => 30 * MINUTE_IN_SECONDS,
            'display'  => __( 'Every 30 Minutes', 'natalie-auto-poster' ),
        );

        $schedules['every_2_hours'] = array(
            'interval' => 2 * HOUR_IN_SECONDS,
            'display'  => __( 'Every 2 Hours', 'natalie-auto-poster' ),
        );

        $schedules['every_6_hours'] = array(
            'interval' => 6 * HOUR_IN_SECONDS,
            'display'  => __( 'Every 6 Hours', 'natalie-auto-poster' ),
        );

        $schedules['every_12_hours'] = array(
            'interval' => 12 * HOUR_IN_SECONDS,
            'display'  => __( 'Every 12 Hours', 'natalie-auto-poster' ),
        );

        return $schedules;
    }

    /**
     * Run the fetch job
     */
    public function run_fetch_job() {
        $enabled = get_option( 'nap_auto_fetch_enabled', true );
        if ( ! $enabled ) {
            NAP_Logger::info( 'Auto fetch is disabled, skipping cron job' );
            return;
        }

        NAP_Logger::info( 'Starting scheduled fetch job' );

        $sources = get_option( 'nap_active_sources', array() );
        if ( empty( $sources ) ) {
            NAP_Logger::warning( 'No active sources configured' );
            return;
        }

        $articles_per_run = intval( get_option( 'nap_articles_per_run', 3 ) );
        $total_processed = 0;

        foreach ( $sources as $source_key ) {
            NAP_Logger::info( "Processing source: {$source_key}" );

            $results = NAP_Post_Creator::process_source( $source_key, $articles_per_run );
            $total_processed += $results['processed'];

            NAP_Logger::info( "Source {$source_key} results: " . wp_json_encode( $results ) );
        }

        NAP_Logger::info( "Fetch job complete. Total processed: {$total_processed}" );

        // Update last run time
        update_option( 'nap_last_fetch_time', current_time( 'mysql' ) );
        update_option( 'nap_last_fetch_count', $total_processed );
    }

    /**
     * Run the cleanup job
     */
    public function run_cleanup_job() {
        NAP_Logger::info( 'Running cleanup job' );

        $log_retention_days = intval( get_option( 'nap_log_retention_days', 30 ) );
        NAP_Database::clean_old_logs( $log_retention_days );

        NAP_Logger::info( 'Cleanup job complete' );
    }

    /**
     * Reschedule fetch job with new interval
     */
    public static function reschedule_fetch( $new_interval ) {
        $timestamp = wp_next_scheduled( self::CRON_HOOK_FETCH );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::CRON_HOOK_FETCH );
        }
        wp_schedule_event( time(), $new_interval, self::CRON_HOOK_FETCH );
    }

    /**
     * Get next scheduled time
     */
    public static function get_next_scheduled() {
        $timestamp = wp_next_scheduled( self::CRON_HOOK_FETCH );
        if ( $timestamp ) {
            return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
        }
        return __( 'Not scheduled', 'natalie-auto-poster' );
    }

    /**
     * Manually trigger fetch job
     */
    public static function trigger_manual_fetch( $source_key = null, $limit = 5 ) {
        NAP_Logger::info( 'Manual fetch triggered' . ( $source_key ? " for source: {$source_key}" : '' ) );

        if ( $source_key ) {
            return NAP_Post_Creator::process_source( $source_key, $limit );
        }

        $sources = get_option( 'nap_active_sources', array() );
        $total_results = array( 'processed' => 0, 'skipped' => 0, 'errors' => 0 );

        foreach ( $sources as $source ) {
            $results = NAP_Post_Creator::process_source( $source, $limit );
            $total_results['processed'] += $results['processed'];
            $total_results['skipped']   += $results['skipped'];
            $total_results['errors']    += $results['errors'];
        }

        return $total_results;
    }
}
