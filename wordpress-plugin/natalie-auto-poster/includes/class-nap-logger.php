<?php
/**
 * Logger class for Natalie Auto Poster
 *
 * @package Natalie_Auto_Poster
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class NAP_Logger
 */
class NAP_Logger {

    const LEVEL_DEBUG   = 'debug';
    const LEVEL_INFO    = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR   = 'error';

    /**
     * Log a debug message
     */
    public static function debug( $message, $article_id = null, $context = null ) {
        self::log( self::LEVEL_DEBUG, $message, $article_id, $context );
    }

    /**
     * Log an info message
     */
    public static function info( $message, $article_id = null, $context = null ) {
        self::log( self::LEVEL_INFO, $message, $article_id, $context );
    }

    /**
     * Log a warning message
     */
    public static function warning( $message, $article_id = null, $context = null ) {
        self::log( self::LEVEL_WARNING, $message, $article_id, $context );
    }

    /**
     * Log an error message
     */
    public static function error( $message, $article_id = null, $context = null ) {
        self::log( self::LEVEL_ERROR, $message, $article_id, $context );
    }

    /**
     * Core log method
     */
    private static function log( $level, $message, $article_id = null, $context = null ) {
        NAP_Database::insert_log( $level, $message, $article_id, $context );

        // Also write to WP debug log if enabled
        if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            $log_message = sprintf(
                '[NAP][%s] %s',
                strtoupper( $level ),
                $message
            );
            if ( $article_id ) {
                $log_message .= ' (Article ID: ' . $article_id . ')';
            }
            error_log( $log_message );
        }
    }
}
