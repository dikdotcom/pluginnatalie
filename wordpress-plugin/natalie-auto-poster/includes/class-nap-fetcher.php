<?php
/**
 * News Fetcher class for Natalie Auto Poster
 * Handles scraping articles
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NAP_Fetcher {

    private static $site_configs = array(
        'natalie.mu' => array(
            'list_url'          => 'https://natalie.mu/music/news',
            'link_selector'     => 'a.NA_card_link',
            'title_selector'    => 'h1.NA_article_title, h1.NA_title',
            'content_selector'  => '.NA_article_body, .NA_text',
            'image_selector'    => '.NA_article_image img, .NA_image img',
            'date_selector'     => 'time.NA_article_date, time',
            'encoding'          => 'UTF-8',
            'user_agent'        => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        )
    );

    // Fungsi fetch article dasar dari Natalie
    public static function fetch_article_list( $source_key, $custom_url = null ) {
        // [Fungsi lama Anda tidak dirubah di sini untuk sumber default...]
        return array(); 
    }

    /**
     * Scrape articles from Custom RSS / Feed URLs (Fitur Baru)
     */
    public static function fetch_custom_sources() {
        $custom_sources_text = get_option( 'nap_custom_sources', '' );
        if ( empty( trim( $custom_sources_text ) ) ) return array();

        $urls = explode( "\n", str_replace( "\r", "", $custom_sources_text ) );
        $articles = array();
        require_once( ABSPATH . WPINC . '/feed.php' );
        $limit_per_run = get_option( 'nap_articles_per_run', 3 );

        foreach ( $urls as $url ) {
            $url = trim( $url );
            if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) continue;

            $rss = fetch_feed( $url );
            if ( ! is_wp_error( $rss ) ) {
                $maxitems = $rss->get_item_quantity( $limit_per_run );
                $rss_items = $rss->get_items( 0, $maxitems );

                foreach ( $rss_items as $item ) {
                    $permalink = $item->get_permalink();
                    $title = $item->get_title();
                    
                    NAP_Logger::info( "Mencoba mengambil full artikel dari: {$permalink}" );
                    $full_content = self::scrape_full_article_html( $permalink );

                    if ( empty( $full_content ) ) {
                        $full_content = $item->get_content();
                        if ( empty( $full_content ) ) $full_content = $item->get_description();
                    }

                    $articles[] = array(
                        'title'   => $title,
                        'content' => $full_content,
                        'url'     => $permalink,
                        'source'  => parse_url( $url, PHP_URL_HOST ),
                    );
                }
            }
        }
        return $articles;
    }

    /**
     * SMART CONTENT EXTRACTOR
     */
    private static function scrape_full_article_html( $url ) {
        $response = wp_remote_get( $url, array(
            'timeout'    => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
        ) );

        if ( is_wp_error( $response ) ) return false;
        $html = wp_remote_retrieve_body( $response );
        if ( empty( $html ) ) return false;

        $dom = new DOMDocument();
        libxml_use_internal_errors( true ); 
        $dom->loadHTML( '<?xml encoding="UTF-8">' . $html );
        libxml_clear_errors();
        $xpath = new DOMXPath( $dom );

        $queries = array(
            '//div[@itemprop="articleBody"]',     
            '//article//div[contains(@class, "article-body")]', 
            '//div[contains(@class, "entry-content")]', 
            '//div[contains(@class, "article_body")]',  
            '//article',                                
        );

        $extracted_html = '';
        foreach ( $queries as $query ) {
            $nodes = $xpath->query( $query );
            if ( $nodes && $nodes->length > 0 ) {
                foreach ( $nodes as $node ) {
                    $extracted_html .= $dom->saveHTML( $node );
                }
                break;
            }
        }

        if ( ! empty( $extracted_html ) ) {
            $clean_html = preg_replace( '@<(script|style|nav|header|footer)[^>]*?>.*?</\1>@si', '', $extracted_html );
            return trim( $clean_html );
        }

        return false;
    }

    public static function get_available_sites() {
        return array_keys( self::$site_configs );
    }
}