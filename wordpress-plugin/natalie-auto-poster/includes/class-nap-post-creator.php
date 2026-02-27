<?php
/**
 * Post Creator class for Natalie Auto Poster
 * Handles creating WordPress posts from processed articles
 *
 * @package Natalie_Auto_Poster
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class NAP_Post_Creator
 */
class NAP_Post_Creator {

    /**
     * Main pipeline: fetch, translate, review, upload images, and create post
     *
     * @param string $article_url URL of the article to process
     * @param string $source_key  Source site key
     * @return int|false WordPress post ID or false on failure
     */
    public static function process_article( $article_url, $source_key ) {
        // Check if already processed
        if ( NAP_Database::article_exists( $article_url ) ) {
            NAP_Logger::info( "Article already processed: {$article_url}" );
            return false;
        }

        // Insert initial record
        $article_id = NAP_Database::insert_article( array(
            'source_url'  => $article_url,
            'source_site' => $source_key,
            'status'      => 'fetching',
        ) );

        if ( ! $article_id ) {
            NAP_Logger::error( "Failed to insert article record for: {$article_url}" );
            return false;
        }

        NAP_Logger::info( "Starting article processing pipeline", $article_id );

        try {
            // Step 1: Fetch article
            $article_data = self::step_fetch( $article_url, $source_key, $article_id );
            if ( ! $article_data ) {
                return false;
            }

            // Step 2: Translate
            $translated_data = self::step_translate( $article_data, $article_id );
            if ( ! $translated_data ) {
                return false;
            }

            // Step 3: AI Review
            $reviewed_data = self::step_review( $translated_data, $article_data, $article_id );

            // Step 4: Create draft post (before image upload to get post ID)
            $wp_post_id = self::step_create_draft( $reviewed_data, $article_data, $article_id );
            if ( ! $wp_post_id ) {
                return false;
            }

            // Step 5: Process and upload images
            $processed_images = self::step_process_images( $article_data['images'], $article_id, $wp_post_id );

            // Step 6: Update post with final content (images replaced)
            $final_post_id = self::step_finalize_post( $wp_post_id, $reviewed_data, $processed_images, $article_id );

            // Update article record
            NAP_Database::update_article( $article_id, array(
                'wp_post_id' => $final_post_id,
                'status'     => 'posted',
                'posted_at'  => current_time( 'mysql' ),
            ) );

            NAP_Logger::info( "Article successfully posted as WP post #{$final_post_id}", $article_id );
            return $final_post_id;

        } catch ( Exception $e ) {
            NAP_Logger::error( 'Pipeline exception: ' . $e->getMessage(), $article_id );
            NAP_Database::update_article( $article_id, array(
                'status'        => 'error',
                'error_message' => $e->getMessage(),
            ) );
            return false;
        }
    }

    /**
     * Step 1: Fetch article
     */
    private static function step_fetch( $url, $source_key, $article_id ) {
        NAP_Logger::info( 'Step 1: Fetching article', $article_id );

        $article_data = NAP_Fetcher::fetch_article( $url, $source_key );

        if ( ! $article_data ) {
            NAP_Database::update_article( $article_id, array(
                'status'        => 'error',
                'error_message' => 'Failed to fetch article',
            ) );
            return false;
        }

        NAP_Database::update_article( $article_id, array(
            'original_title'   => $article_data['title'],
            'original_content' => $article_data['content'],
            'images_data'      => wp_json_encode( $article_data['images'] ),
            'status'           => 'fetched',
            'fetched_at'       => current_time( 'mysql' ),
        ) );

        NAP_Logger::info( 'Article fetched: ' . $article_data['title'], $article_id );
        return $article_data;
    }

    /**
     * Step 2: Translate article
     */
    private static function step_translate( $article_data, $article_id ) {
        NAP_Logger::info( 'Step 2: Translating article', $article_id );

        NAP_Database::update_article( $article_id, array( 'status' => 'translating' ) );

        $translated = NAP_Translator::translate_article( $article_data );

        if ( ! $translated ) {
            NAP_Database::update_article( $article_id, array(
                'status'        => 'error',
                'error_message' => 'Translation failed',
            ) );
            return false;
        }

        NAP_Database::update_article( $article_id, array(
            'translated_title'   => $translated['translated_title'],
            'translated_content' => $translated['translated_content'],
            'status'             => 'translated',
            'translated_at'      => current_time( 'mysql' ),
        ) );

        NAP_Logger::info( 'Translation complete: ' . $translated['translated_title'], $article_id );
        return $translated;
    }

    /**
     * Step 3: AI Review
     */
    private static function step_review( $translated_data, $original_data, $article_id ) {
        $skip_review = get_option( 'nap_skip_review', false );

        if ( $skip_review ) {
            NAP_Logger::info( 'Step 3: Skipping AI review (disabled in settings)', $article_id );
            return array(
                'reviewed_title'   => $translated_data['translated_title'],
                'reviewed_content' => $translated_data['translated_content'],
            );
        }

        NAP_Logger::info( 'Step 3: AI reviewing translation', $article_id );
        NAP_Database::update_article( $article_id, array( 'status' => 'reviewing' ) );

        $reviewed = NAP_AI_Reviewer::review_article( $translated_data, $original_data );

        if ( ! $reviewed ) {
            NAP_Logger::warning( 'AI review failed, using original translation', $article_id );
            $reviewed = array(
                'reviewed_title'   => $translated_data['translated_title'],
                'reviewed_content' => $translated_data['translated_content'],
            );
        }

        NAP_Database::update_article( $article_id, array(
            'reviewed_content' => $reviewed['reviewed_content'],
            'status'           => 'reviewed',
            'reviewed_at'      => current_time( 'mysql' ),
        ) );

        NAP_Logger::info( 'AI review complete', $article_id );
        return $reviewed;
    }

    /**
     * Step 4: Create draft post
     */
    private static function step_create_draft( $reviewed_data, $original_data, $article_id ) {
        NAP_Logger::info( 'Step 4: Creating draft post', $article_id );

        $post_status = get_option( 'nap_default_post_status', 'draft' );
        $post_author = get_option( 'nap_default_post_author', 1 );
        $post_category = get_option( 'nap_default_category', 0 );
        $post_tags = get_option( 'nap_default_tags', '' );

        $title = $reviewed_data['reviewed_title'] ?? $reviewed_data['translated_title'] ?? $original_data['title'];
        $content = $reviewed_data['reviewed_content'] ?? $reviewed_data['translated_content'] ?? $original_data['content'];

        // Add source attribution
        $attribution = self::build_attribution( $original_data );
        $content .= $attribution;

        $post_data = array(
            'post_title'   => sanitize_text_field( $title ),
            'post_content' => wp_kses_post( $content ),
            'post_status'  => $post_status,
            'post_author'  => intval( $post_author ),
            'post_type'    => 'post',
            'meta_input'   => array(
                '_nap_source_url'  => $original_data['url'],
                '_nap_article_id'  => $article_id,
                '_nap_source_site' => $original_data['url'],
            ),
        );

        // Set category
        if ( $post_category ) {
            $post_data['post_category'] = array( intval( $post_category ) );
        }

        $wp_post_id = wp_insert_post( $post_data, true );

        if ( is_wp_error( $wp_post_id ) ) {
            NAP_Logger::error( 'Failed to create post: ' . $wp_post_id->get_error_message(), $article_id );
            NAP_Database::update_article( $article_id, array(
                'status'        => 'error',
                'error_message' => 'Failed to create WordPress post: ' . $wp_post_id->get_error_message(),
            ) );
            return false;
        }

        // Add tags
        if ( ! empty( $post_tags ) ) {
            $tags = array_map( 'trim', explode( ',', $post_tags ) );
            wp_set_post_tags( $wp_post_id, $tags, true );
        }

        NAP_Logger::info( "Draft post created: #{$wp_post_id}", $article_id );
        return $wp_post_id;
    }

    /**
     * Step 5: Process images
     */
    private static function step_process_images( $images, $article_id, $wp_post_id ) {
        if ( empty( $images ) ) {
            NAP_Logger::info( 'Step 5: No images to process', $article_id );
            return array();
        }

        NAP_Logger::info( 'Step 5: Processing ' . count( $images ) . ' images', $article_id );

        $processed = NAP_Image_Uploader::process_images( $images, $article_id, $wp_post_id );

        // Set featured image (first image)
        if ( ! empty( $processed ) ) {
            foreach ( $processed as $img ) {
                if ( ! empty( $img['is_main'] ) && ! empty( $img['attachment_id'] ) ) {
                    set_post_thumbnail( $wp_post_id, $img['attachment_id'] );
                    NAP_Logger::info( 'Featured image set', $article_id );
                    break;
                }
            }
        }

        return $processed;
    }

    /**
     * Step 6: Finalize post with processed images
     */
    private static function step_finalize_post( $wp_post_id, $reviewed_data, $processed_images, $article_id ) {
        NAP_Logger::info( 'Step 6: Finalizing post', $article_id );

        $content = $reviewed_data['reviewed_content'] ?? $reviewed_data['translated_content'] ?? '';

        // Replace original image URLs with new URLs in content
        if ( ! empty( $processed_images ) ) {
            foreach ( $processed_images as $img ) {
                if ( ! empty( $img['url'] ) && ! empty( $img['new_url'] ) && $img['url'] !== $img['new_url'] ) {
                    $content = str_replace( $img['url'], $img['new_url'], $content );
                }
            }
        }

        // Update post with final content
        wp_update_post( array(
            'ID'           => $wp_post_id,
            'post_content' => wp_kses_post( $content ),
        ) );

        // Update post status if auto-publish is enabled
        $auto_publish = get_option( 'nap_auto_publish', false );
        if ( $auto_publish ) {
            wp_update_post( array(
                'ID'          => $wp_post_id,
                'post_status' => 'publish',
            ) );
            NAP_Logger::info( "Post #{$wp_post_id} published", $article_id );
        }

        return $wp_post_id;
    }

    /**
     * Build source attribution HTML
     */
    private static function build_attribution( $original_data ) {
        $show_attribution = get_option( 'nap_show_attribution', true );
        if ( ! $show_attribution ) {
            return '';
        }

        $attribution_template = get_option(
            'nap_attribution_template',
            '<p class="nap-source"><em>Sumber: <a href="{url}" target="_blank" rel="noopener noreferrer">{site}</a></em></p>'
        );

        $parsed_url = parse_url( $original_data['url'] );
        $site_name = $parsed_url['host'] ?? $original_data['url'];

        $attribution = str_replace(
            array( '{url}', '{site}', '{title}' ),
            array( esc_url( $original_data['url'] ), esc_html( $site_name ), esc_html( $original_data['title'] ) ),
            $attribution_template
        );

        return "\n\n" . $attribution;
    }

    /**
     * Process multiple articles from a source
     *
     * @param string $source_key Source site key
     * @param int    $limit      Maximum articles to process
     * @return array Results
     */
    public static function process_source( $source_key, $limit = 5 ) {
        NAP_Logger::info( "Processing source: {$source_key}, limit: {$limit}" );

        $article_urls = NAP_Fetcher::fetch_article_list( $source_key );

        if ( empty( $article_urls ) ) {
            NAP_Logger::warning( "No articles found for source: {$source_key}" );
            return array( 'processed' => 0, 'skipped' => 0, 'errors' => 0 );
        }

        $results = array( 'processed' => 0, 'skipped' => 0, 'errors' => 0 );
        $count = 0;

        foreach ( $article_urls as $url ) {
            if ( $count >= $limit ) {
                break;
            }

            // Skip already processed
            if ( NAP_Database::article_exists( $url ) ) {
                $results['skipped']++;
                continue;
            }

            $post_id = self::process_article( $url, $source_key );

            if ( $post_id ) {
                $results['processed']++;
            } else {
                $results['errors']++;
            }

            $count++;

            // Small delay between articles to be respectful
            sleep( 2 );
        }

        NAP_Logger::info( "Source processing complete: " . wp_json_encode( $results ) );
        return $results;
    }
}
