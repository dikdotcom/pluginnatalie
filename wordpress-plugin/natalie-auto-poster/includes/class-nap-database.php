<?php
/**
 * Database handler for Natalie Auto Poster
 *
 * @package Natalie_Auto_Poster
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class NAP_Database
 * Handles database table creation and queries
 */
class NAP_Database {

    /**
     * Table names
     */
    public static function get_articles_table() {
        global $wpdb;
        return $wpdb->prefix . 'nap_articles';
    }

    public static function get_logs_table() {
        global $wpdb;
        return $wpdb->prefix . 'nap_logs';
    }

    /**
     * Create plugin database tables
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Articles tracking table
        $articles_table = self::get_articles_table();
        $sql_articles = "CREATE TABLE IF NOT EXISTS {$articles_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            source_url varchar(500) NOT NULL,
            source_site varchar(100) NOT NULL,
            original_title text NOT NULL,
            translated_title text,
            original_content longtext,
            translated_content longtext,
            reviewed_content longtext,
            wp_post_id bigint(20) DEFAULT NULL,
            status varchar(50) DEFAULT 'pending',
            images_data longtext,
            fetched_at datetime DEFAULT NULL,
            translated_at datetime DEFAULT NULL,
            reviewed_at datetime DEFAULT NULL,
            posted_at datetime DEFAULT NULL,
            error_message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY source_url (source_url(255)),
            KEY status (status),
            KEY source_site (source_site),
            KEY wp_post_id (wp_post_id)
        ) {$charset_collate};";

        // Logs table
        $logs_table = self::get_logs_table();
        $sql_logs = "CREATE TABLE IF NOT EXISTS {$logs_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            article_id bigint(20) DEFAULT NULL,
            level varchar(20) NOT NULL DEFAULT 'info',
            message text NOT NULL,
            context longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY article_id (article_id),
            KEY level (level),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_articles );
        dbDelta( $sql_logs );

        update_option( 'nap_db_version', NAP_DB_VERSION );
    }

    /**
     * Check if article URL already exists
     */
    public static function article_exists( $url ) {
        global $wpdb;
        $table = self::get_articles_table();
        $count = $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE source_url = %s", $url )
        );
        return $count > 0;
    }

    /**
     * Insert new article record
     */
    public static function insert_article( $data ) {
        global $wpdb;
        $table = self::get_articles_table();

        $defaults = array(
            'status'      => 'pending',
            'fetched_at'  => current_time( 'mysql' ),
            'created_at'  => current_time( 'mysql' ),
            'updated_at'  => current_time( 'mysql' ),
        );

        $data = wp_parse_args( $data, $defaults );

        $wpdb->insert( $table, $data );
        return $wpdb->insert_id;
    }

    /**
     * Update article record
     */
    public static function update_article( $id, $data ) {
        global $wpdb;
        $table = self::get_articles_table();

        $data['updated_at'] = current_time( 'mysql' );

        return $wpdb->update(
            $table,
            $data,
            array( 'id' => $id )
        );
    }

    /**
     * Get article by ID
     */
    public static function get_article( $id ) {
        global $wpdb;
        $table = self::get_articles_table();
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id )
        );
    }

    /**
     * Get articles by status
     */
    public static function get_articles_by_status( $status, $limit = 10 ) {
        global $wpdb;
        $table = self::get_articles_table();
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE status = %s ORDER BY created_at DESC LIMIT %d",
                $status,
                $limit
            )
        );
    }

    /**
     * Get recent articles for admin display
     */
    public static function get_recent_articles( $limit = 50, $offset = 0 ) {
        global $wpdb;
        $table = self::get_articles_table();
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            )
        );
    }

    /**
     * Count articles by status
     */
    public static function count_articles_by_status( $status = null ) {
        global $wpdb;
        $table = self::get_articles_table();

        if ( $status ) {
            return $wpdb->get_var(
                $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", $status )
            );
        }

        return $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
    }

    /**
     * Insert log entry
     */
    public static function insert_log( $level, $message, $article_id = null, $context = null ) {
        global $wpdb;
        $table = self::get_logs_table();

        $wpdb->insert( $table, array(
            'article_id' => $article_id,
            'level'      => $level,
            'message'    => $message,
            'context'    => $context ? wp_json_encode( $context ) : null,
            'created_at' => current_time( 'mysql' ),
        ) );
    }

    /**
     * Get recent logs
     */
    public static function get_recent_logs( $limit = 100, $level = null ) {
        global $wpdb;
        $table = self::get_logs_table();

        if ( $level ) {
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE level = %s ORDER BY created_at DESC LIMIT %d",
                    $level,
                    $limit
                )
            );
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d",
                $limit
            )
        );
    }

    /**
     * Clean old logs
     */
    public static function clean_old_logs( $days = 30 ) {
        global $wpdb;
        $table = self::get_logs_table();
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );
    }
}
