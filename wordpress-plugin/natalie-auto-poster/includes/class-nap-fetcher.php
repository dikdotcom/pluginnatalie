<?php
/**
 * News Fetcher class for Natalie Auto Poster
 * Handles scraping articles from natalie.mu and similar Japanese news sites
 *
 * @package Natalie_Auto_Poster
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class NAP_Fetcher
 */
class NAP_Fetcher {

    /**
     * Supported source sites configuration
     */
    private static $site_configs = array(
        'natalie.mu' => array(
            'list_url'          => 'https://natalie.mu/music/news',
            'article_selector'  => '.NA_card',
            'link_selector'     => 'a.NA_card_link',
            'title_selector'    => 'h1.NA_article_title, h1.NA_title',
            'content_selector'  => '.NA_article_body, .NA_text',
            'image_selector'    => '.NA_article_image img, .NA_image img',
            'date_selector'     => 'time.NA_article_date, time',
            'encoding'          => 'UTF-8',
            'user_agent'        => 'Mozilla/5.0 (compatible; WordPress/6.0; +https://wordpress.org)',
        ),
        'natalie.mu/comic' => array(
            'list_url'          => 'https://natalie.mu/comic/news',
            'article_selector'  => '.NA_card',
            'link_selector'     => 'a.NA_card_link',
            'title_selector'    => 'h1.NA_article_title, h1.NA_title',
            'content_selector'  => '.NA_article_body, .NA_text',
            'image_selector'    => '.NA_article_image img, .NA_image img',
            'date_selector'     => 'time.NA_article_date, time',
            'encoding'          => 'UTF-8',
            'user_agent'        => 'Mozilla/5.0 (compatible; WordPress/6.0; +https://wordpress.org)',
        ),
        'natalie.mu/eiga' => array(
            'list_url'          => 'https://natalie.mu/eiga/news',
            'article_selector'  => '.NA_card',
            'link_selector'     => 'a.NA_card_link',
            'title_selector'    => 'h1.NA_article_title, h1.NA_title',
            'content_selector'  => '.NA_article_body, .NA_text',
            'image_selector'    => '.NA_article_image img, .NA_image img',
            'date_selector'     => 'time.NA_article_date, time',
            'encoding'          => 'UTF-8',
            'user_agent'        => 'Mozilla/5.0 (compatible; WordPress/6.0; +https://wordpress.org)',
        ),
    );

    /**
     * Fetch article list from a source site
     *
     * @param string $source_key Source site key
     * @param string $custom_url Optional custom URL override
     * @return array List of article URLs
     */
    public static function fetch_article_list( $source_key, $custom_url = null ) {
        $config = self::get_site_config( $source_key );
        if ( ! $config ) {
            NAP_Logger::error( "Unknown source site: {$source_key}" );
            return array();
        }

        $url = $custom_url ?: $config['list_url'];
        NAP_Logger::info( "Fetching article list from: {$url}" );

        $response = wp_remote_get( $url, array(
            'timeout'    => 30,
            'user-agent' => $config['user_agent'],
            'headers'    => array(
                'Accept'          => 'text/html,application/xhtml+xml',
                'Accept-Language' => 'ja,en;q=0.9',
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            NAP_Logger::error( 'Failed to fetch article list: ' . $response->get_error_message() );
            return array();
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        if ( $status_code !== 200 ) {
            NAP_Logger::error( "HTTP error {$status_code} fetching article list from {$url}" );
            return array();
        }

        $html = wp_remote_retrieve_body( $response );
        return self::parse_article_links( $html, $config, $url );
    }

    /**
     * Parse article links from HTML
     */
    private static function parse_article_links( $html, $config, $base_url ) {
        $links = array();

        // Use DOMDocument to parse HTML
        $dom = new DOMDocument();
        libxml_use_internal_errors( true );
        $dom->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) );
        libxml_clear_errors();

        $xpath = new DOMXPath( $dom );

        // Convert CSS selector to XPath (simplified)
        $link_xpath = self::css_to_xpath( $config['link_selector'] );
        $nodes = $xpath->query( $link_xpath );

        if ( $nodes ) {
            foreach ( $nodes as $node ) {
                $href = $node->getAttribute( 'href' );
                if ( $href ) {
                    // Make absolute URL
                    if ( strpos( $href, 'http' ) !== 0 ) {
                        $parsed = parse_url( $base_url );
                        $href = $parsed['scheme'] . '://' . $parsed['host'] . $href;
                    }
                    $links[] = $href;
                }
            }
        }

        // Fallback: regex-based link extraction for natalie.mu
        if ( empty( $links ) ) {
            preg_match_all( '/href="(https:\/\/natalie\.mu\/[^"]+\/news\/\d+)"/', $html, $matches );
            if ( ! empty( $matches[1] ) ) {
                $links = array_unique( $matches[1] );
            }
        }

        NAP_Logger::info( 'Found ' . count( $links ) . ' article links' );
        return array_unique( $links );
    }

    /**
     * Fetch and parse a single article
     *
     * @param string $url Article URL
     * @param string $source_key Source site key
     * @return array|false Article data or false on failure
     */
    public static function fetch_article( $url, $source_key ) {
        $config = self::get_site_config( $source_key );
        if ( ! $config ) {
            // Try to auto-detect config from URL
            $config = self::detect_config_from_url( $url );
        }

        if ( ! $config ) {
            NAP_Logger::error( "Cannot determine config for URL: {$url}" );
            return false;
        }

        NAP_Logger::info( "Fetching article: {$url}" );

        $response = wp_remote_get( $url, array(
            'timeout'    => 30,
            'user-agent' => $config['user_agent'],
            'headers'    => array(
                'Accept'          => 'text/html,application/xhtml+xml',
                'Accept-Language' => 'ja,en;q=0.9',
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            NAP_Logger::error( 'Failed to fetch article: ' . $response->get_error_message() );
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        if ( $status_code !== 200 ) {
            NAP_Logger::error( "HTTP error {$status_code} fetching article: {$url}" );
            return false;
        }

        $html = wp_remote_retrieve_body( $response );
        return self::parse_article( $html, $config, $url );
    }

    /**
     * Parse article content from HTML
     */
    private static function parse_article( $html, $config, $url ) {
        $dom = new DOMDocument();
        libxml_use_internal_errors( true );
        $dom->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) );
        libxml_clear_errors();

        $xpath = new DOMXPath( $dom );

        // Extract title
        $title = self::extract_text( $xpath, $config['title_selector'] );
        if ( empty( $title ) ) {
            // Fallback to og:title
            $og_title = $xpath->query( '//meta[@property="og:title"]/@content' );
            if ( $og_title && $og_title->length > 0 ) {
                $title = $og_title->item(0)->nodeValue;
            }
        }

        // Extract content
        $content = self::extract_html_content( $xpath, $dom, $config['content_selector'] );

        // Extract images
        $images = self::extract_images( $xpath, $config['image_selector'], $url );

        // Also check og:image
        $og_image = $xpath->query( '//meta[@property="og:image"]/@content' );
        if ( $og_image && $og_image->length > 0 ) {
            $og_image_url = $og_image->item(0)->nodeValue;
            if ( ! in_array( $og_image_url, array_column( $images, 'url' ) ) ) {
                array_unshift( $images, array(
                    'url'     => $og_image_url,
                    'alt'     => $title,
                    'is_main' => true,
                ) );
            }
        }

        // Extract date
        $date = self::extract_date( $xpath, $config['date_selector'] );

        if ( empty( $title ) || empty( $content ) ) {
            NAP_Logger::warning( "Could not extract title or content from: {$url}" );
            return false;
        }

        return array(
            'url'     => $url,
            'title'   => trim( $title ),
            'content' => $content,
            'images'  => $images,
            'date'    => $date,
        );
    }

    /**
     * Extract text from XPath query
     */
    private static function extract_text( $xpath, $selector ) {
        $selectors = explode( ',', $selector );
        foreach ( $selectors as $sel ) {
            $xp = self::css_to_xpath( trim( $sel ) );
            $nodes = $xpath->query( $xp );
            if ( $nodes && $nodes->length > 0 ) {
                return trim( $nodes->item(0)->textContent );
            }
        }
        return '';
    }

    /**
     * Extract HTML content from XPath query
     */
    private static function extract_html_content( $xpath, $dom, $selector ) {
        $selectors = explode( ',', $selector );
        foreach ( $selectors as $sel ) {
            $xp = self::css_to_xpath( trim( $sel ) );
            $nodes = $xpath->query( $xp );
            if ( $nodes && $nodes->length > 0 ) {
                $node = $nodes->item(0);
                $html = '';
                foreach ( $node->childNodes as $child ) {
                    $html .= $dom->saveHTML( $child );
                }
                // Clean up HTML
                $html = self::clean_html( $html );
                return $html;
            }
        }
        return '';
    }

    /**
     * Extract images from article
     */
    private static function extract_images( $xpath, $selector, $base_url ) {
        $images = array();
        $selectors = explode( ',', $selector );

        foreach ( $selectors as $sel ) {
            $xp = self::css_to_xpath( trim( $sel ) );
            $nodes = $xpath->query( $xp );
            if ( $nodes ) {
                foreach ( $nodes as $node ) {
                    $src = $node->getAttribute( 'src' );
                    if ( empty( $src ) ) {
                        $src = $node->getAttribute( 'data-src' );
                    }
                    if ( $src ) {
                        // Make absolute URL
                        if ( strpos( $src, 'http' ) !== 0 ) {
                            $parsed = parse_url( $base_url );
                            $src = $parsed['scheme'] . '://' . $parsed['host'] . $src;
                        }
                        $images[] = array(
                            'url'     => $src,
                            'alt'     => $node->getAttribute( 'alt' ),
                            'is_main' => count( $images ) === 0,
                        );
                    }
                }
            }
        }

        return $images;
    }

    /**
     * Extract date from article
     */
    private static function extract_date( $xpath, $selector ) {
        $selectors = explode( ',', $selector );
        foreach ( $selectors as $sel ) {
            $xp = self::css_to_xpath( trim( $sel ) );
            $nodes = $xpath->query( $xp );
            if ( $nodes && $nodes->length > 0 ) {
                $node = $nodes->item(0);
                // Try datetime attribute first
                $datetime = $node->getAttribute( 'datetime' );
                if ( $datetime ) {
                    return $datetime;
                }
                return trim( $node->textContent );
            }
        }
        return current_time( 'mysql' );
    }

    /**
     * Clean HTML content
     */
    private static function clean_html( $html ) {
        // Remove script and style tags
        $html = preg_replace( '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', '', $html );
        $html = preg_replace( '/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/i', '', $html );

        // Remove inline event handlers
        $html = preg_replace( '/\s+on\w+="[^"]*"/i', '', $html );

        // Remove empty tags
        $html = preg_replace( '/<[^>]+>\s*<\/[^>]+>/', '', $html );

        // Normalize whitespace
        $html = preg_replace( '/\s+/', ' ', $html );

        return trim( $html );
    }

    /**
     * Simple CSS selector to XPath converter
     */
    private static function css_to_xpath( $selector ) {
        // Handle class selectors
        if ( preg_match( '/^\.(.+)$/', $selector, $m ) ) {
            return "//*[contains(@class, '{$m[1]}')]";
        }

        // Handle element.class
        if ( preg_match( '/^(\w+)\.(.+)$/', $selector, $m ) ) {
            return "//{$m[1]}[contains(@class, '{$m[2]}')]";
        }

        // Handle element#id
        if ( preg_match( '/^(\w+)#(.+)$/', $selector, $m ) ) {
            return "//{$m[1]}[@id='{$m[2]}']";
        }

        // Handle #id
        if ( preg_match( '/^#(.+)$/', $selector, $m ) ) {
            return "//*[@id='{$m[1]}']";
        }

        // Handle element
        if ( preg_match( '/^\w+$/', $selector ) ) {
            return "//{$selector}";
        }

        // Handle element > child
        if ( strpos( $selector, ' > ' ) !== false ) {
            $parts = explode( ' > ', $selector );
            $xpath = '';
            foreach ( $parts as $i => $part ) {
                if ( $i === 0 ) {
                    $xpath = '//' . $part;
                } else {
                    $xpath .= '/' . $part;
                }
            }
            return $xpath;
        }

        // Default: treat as element
        return "//{$selector}";
    }

    /**
     * Get site config by key
     */
    public static function get_site_config( $key ) {
        // Check built-in configs
        if ( isset( self::$site_configs[ $key ] ) ) {
            return self::$site_configs[ $key ];
        }

        // Check custom configs from settings
        $custom_sites = get_option( 'nap_custom_sites', array() );
        if ( isset( $custom_sites[ $key ] ) ) {
            return $custom_sites[ $key ];
        }

        return null;
    }

    /**
     * Auto-detect config from URL
     */
    private static function detect_config_from_url( $url ) {
        $parsed = parse_url( $url );
        $host = $parsed['host'] ?? '';

        foreach ( self::$site_configs as $key => $config ) {
            if ( strpos( $url, $key ) !== false ) {
                return $config;
            }
        }

        // Generic fallback config
        return array(
            'title_selector'   => 'h1, .article-title, .entry-title',
            'content_selector' => 'article, .article-body, .entry-content, .post-content',
            'image_selector'   => 'article img, .article-body img',
            'date_selector'    => 'time, .date, .published',
            'encoding'         => 'UTF-8',
            'user_agent'       => 'Mozilla/5.0 (compatible; WordPress/6.0; +https://wordpress.org)',
        );
    }

    /**
     * Get all available source sites
     */
    public static function get_available_sites() {
        $sites = array_keys( self::$site_configs );
        $custom_sites = get_option( 'nap_custom_sites', array() );
        return array_merge( $sites, array_keys( $custom_sites ) );
    }
}
