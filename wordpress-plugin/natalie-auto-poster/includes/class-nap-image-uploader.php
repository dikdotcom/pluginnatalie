<?php
/**
 * Image Uploader class for Natalie Auto Poster
 * Handles downloading and uploading images to S3/cloud storage or WordPress media library
 *
 * @package Natalie_Auto_Poster
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class NAP_Image_Uploader
 */
class NAP_Image_Uploader {

    /**
     * Storage providers
     */
    const STORAGE_WORDPRESS = 'wordpress';
    const STORAGE_S3        = 's3';
    const STORAGE_R2        = 'r2';       // Cloudflare R2
    const STORAGE_GCS       = 'gcs';      // Google Cloud Storage
    const STORAGE_BUNNY     = 'bunny';    // BunnyCDN

    /**
     * Process images for an article
     *
     * @param array $images Array of image data from fetcher
     * @param int   $article_id NAP article ID
     * @param int   $wp_post_id WordPress post ID (optional)
     * @return array Processed images with new URLs
     */
    public static function process_images( $images, $article_id, $wp_post_id = null ) {
        if ( empty( $images ) ) {
            return array();
        }

        $storage_provider = get_option( 'nap_image_storage', self::STORAGE_WORDPRESS );
        $processed_images = array();

        foreach ( $images as $index => $image ) {
            NAP_Logger::info( "Processing image {$index}: {$image['url']}", $article_id );

            $result = self::process_single_image( $image, $storage_provider, $wp_post_id );

            if ( $result ) {
                $processed_images[] = array_merge( $image, $result );
                NAP_Logger::info( "Image processed successfully: {$result['new_url']}", $article_id );
            } else {
                NAP_Logger::warning( "Failed to process image: {$image['url']}", $article_id );
                // Keep original URL as fallback
                $processed_images[] = array_merge( $image, array( 'new_url' => $image['url'] ) );
            }
        }

        return $processed_images;
    }

    /**
     * Process a single image
     */
    private static function process_single_image( $image, $storage_provider, $wp_post_id = null ) {
        // Download image to temp file
        $temp_file = self::download_image( $image['url'] );
        if ( ! $temp_file ) {
            return false;
        }

        $result = false;

        switch ( $storage_provider ) {
            case self::STORAGE_S3:
                $result = self::upload_to_s3( $temp_file, $image );
                break;
            case self::STORAGE_R2:
                $result = self::upload_to_r2( $temp_file, $image );
                break;
            case self::STORAGE_GCS:
                $result = self::upload_to_gcs( $temp_file, $image );
                break;
            case self::STORAGE_BUNNY:
                $result = self::upload_to_bunny( $temp_file, $image );
                break;
            case self::STORAGE_WORDPRESS:
            default:
                $result = self::upload_to_wordpress( $temp_file, $image, $wp_post_id );
                break;
        }

        // Clean up temp file
        if ( file_exists( $temp_file['path'] ) ) {
            @unlink( $temp_file['path'] );
        }

        return $result;
    }

    /**
     * Download image to temp file
     */
    private static function download_image( $url ) {
        $response = wp_remote_get( $url, array(
            'timeout'   => 30,
            'stream'    => true,
            'filename'  => wp_tempnam(),
        ) );

        if ( is_wp_error( $response ) ) {
            NAP_Logger::error( 'Failed to download image: ' . $response->get_error_message() );
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        if ( $status_code !== 200 ) {
            NAP_Logger::error( "HTTP {$status_code} downloading image: {$url}" );
            return false;
        }

        $temp_path = $response['filename'];
        $content_type = wp_remote_retrieve_header( $response, 'content-type' );

        // Determine file extension from content type
        $ext = self::get_extension_from_content_type( $content_type );
        if ( ! $ext ) {
            $ext = pathinfo( parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION );
            $ext = strtolower( $ext );
        }

        if ( ! in_array( $ext, array( 'jpg', 'jpeg', 'png', 'gif', 'webp' ) ) ) {
            $ext = 'jpg';
        }

        return array(
            'path'         => $temp_path,
            'extension'    => $ext,
            'content_type' => $content_type ?: 'image/jpeg',
            'filename'     => self::generate_filename( $url, $ext ),
        );
    }

    /**
     * Upload to WordPress media library
     */
    private static function upload_to_wordpress( $temp_file, $image, $wp_post_id = null ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $upload_dir = wp_upload_dir();
        $filename = $temp_file['filename'];
        $upload_path = $upload_dir['path'] . '/' . $filename;

        // Move temp file to uploads
        if ( ! copy( $temp_file['path'], $upload_path ) ) {
            NAP_Logger::error( 'Failed to copy image to uploads directory' );
            return false;
        }

        // Create attachment
        $attachment = array(
            'guid'           => $upload_dir['url'] . '/' . $filename,
            'post_mime_type' => $temp_file['content_type'],
            'post_title'     => sanitize_file_name( $image['alt'] ?: $filename ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        $attachment_id = wp_insert_attachment( $attachment, $upload_path, $wp_post_id );

        if ( is_wp_error( $attachment_id ) ) {
            NAP_Logger::error( 'Failed to create attachment: ' . $attachment_id->get_error_message() );
            @unlink( $upload_path );
            return false;
        }

        // Generate image metadata
        $attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_path );
        wp_update_attachment_metadata( $attachment_id, $attachment_data );

        return array(
            'new_url'       => wp_get_attachment_url( $attachment_id ),
            'attachment_id' => $attachment_id,
            'storage'       => self::STORAGE_WORDPRESS,
        );
    }

    /**
     * Upload to Amazon S3
     */
    private static function upload_to_s3( $temp_file, $image ) {
        $access_key    = get_option( 'nap_s3_access_key' );
        $secret_key    = get_option( 'nap_s3_secret_key' );
        $bucket        = get_option( 'nap_s3_bucket' );
        $region        = get_option( 'nap_s3_region', 'us-east-1' );
        $path_prefix   = get_option( 'nap_s3_path_prefix', 'natalie-auto-poster/' );
        $custom_domain = get_option( 'nap_s3_custom_domain' );

        if ( empty( $access_key ) || empty( $secret_key ) || empty( $bucket ) ) {
            NAP_Logger::error( 'S3 credentials not configured' );
            return false;
        }

        $filename = $path_prefix . date( 'Y/m/' ) . $temp_file['filename'];
        $file_content = file_get_contents( $temp_file['path'] );

        if ( $file_content === false ) {
            NAP_Logger::error( 'Failed to read temp file for S3 upload' );
            return false;
        }

        // Build S3 request
        $host = "{$bucket}.s3.{$region}.amazonaws.com";
        $url = "https://{$host}/{$filename}";
        $date = gmdate( 'D, d M Y H:i:s T' );
        $content_md5 = base64_encode( md5( $file_content, true ) );
        $content_type = $temp_file['content_type'];

        // Build signature
        $string_to_sign = "PUT\n{$content_md5}\n{$content_type}\n{$date}\n/{$bucket}/{$filename}";
        $signature = base64_encode( hash_hmac( 'sha1', $string_to_sign, $secret_key, true ) );

        $response = wp_remote_request( $url, array(
            'method'  => 'PUT',
            'timeout' => 60,
            'headers' => array(
                'Authorization' => "AWS {$access_key}:{$signature}",
                'Content-Type'  => $content_type,
                'Content-MD5'   => $content_md5,
                'Date'          => $date,
                'x-amz-acl'     => 'public-read',
            ),
            'body' => $file_content,
        ) );

        if ( is_wp_error( $response ) ) {
            NAP_Logger::error( 'S3 upload error: ' . $response->get_error_message() );
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        if ( $status_code !== 200 ) {
            NAP_Logger::error( "S3 upload failed with HTTP {$status_code}" );
            return false;
        }

        // Build public URL
        if ( $custom_domain ) {
            $public_url = rtrim( $custom_domain, '/' ) . '/' . $filename;
        } else {
            $public_url = "https://{$host}/{$filename}";
        }

        return array(
            'new_url' => $public_url,
            'storage' => self::STORAGE_S3,
            'key'     => $filename,
        );
    }

    /**
     * Upload to Cloudflare R2
     */
    private static function upload_to_r2( $temp_file, $image ) {
        $access_key    = get_option( 'nap_r2_access_key' );
        $secret_key    = get_option( 'nap_r2_secret_key' );
        $bucket        = get_option( 'nap_r2_bucket' );
        $account_id    = get_option( 'nap_r2_account_id' );
        $custom_domain = get_option( 'nap_r2_custom_domain' );
        $path_prefix   = get_option( 'nap_r2_path_prefix', 'natalie-auto-poster/' );

        if ( empty( $access_key ) || empty( $secret_key ) || empty( $bucket ) || empty( $account_id ) ) {
            NAP_Logger::error( 'Cloudflare R2 credentials not configured' );
            return false;
        }

        $filename = $path_prefix . date( 'Y/m/' ) . $temp_file['filename'];
        $file_content = file_get_contents( $temp_file['path'] );

        if ( $file_content === false ) {
            return false;
        }

        // R2 uses S3-compatible API
        $endpoint = "https://{$account_id}.r2.cloudflarestorage.com";
        $url = "{$endpoint}/{$bucket}/{$filename}";

        // AWS Signature V4 for R2
        $signed_headers = self::build_aws_v4_headers(
            'PUT',
            $url,
            $file_content,
            $temp_file['content_type'],
            $access_key,
            $secret_key,
            'auto',
            's3'
        );

        $response = wp_remote_request( $url, array(
            'method'  => 'PUT',
            'timeout' => 60,
            'headers' => $signed_headers,
            'body'    => $file_content,
        ) );

        if ( is_wp_error( $response ) ) {
            NAP_Logger::error( 'R2 upload error: ' . $response->get_error_message() );
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        if ( $status_code !== 200 ) {
            NAP_Logger::error( "R2 upload failed with HTTP {$status_code}" );
            return false;
        }

        if ( $custom_domain ) {
            $public_url = rtrim( $custom_domain, '/' ) . '/' . $filename;
        } else {
            $public_url = "{$endpoint}/{$bucket}/{$filename}";
        }

        return array(
            'new_url' => $public_url,
            'storage' => self::STORAGE_R2,
            'key'     => $filename,
        );
    }

    /**
     * Upload to Google Cloud Storage
     */
    private static function upload_to_gcs( $temp_file, $image ) {
        $service_account_json = get_option( 'nap_gcs_service_account' );
        $bucket               = get_option( 'nap_gcs_bucket' );
        $path_prefix          = get_option( 'nap_gcs_path_prefix', 'natalie-auto-poster/' );
        $custom_domain        = get_option( 'nap_gcs_custom_domain' );

        if ( empty( $service_account_json ) || empty( $bucket ) ) {
            NAP_Logger::error( 'GCS credentials not configured' );
            return false;
        }

        $service_account = json_decode( $service_account_json, true );
        if ( ! $service_account ) {
            NAP_Logger::error( 'Invalid GCS service account JSON' );
            return false;
        }

        // Get access token
        $access_token = self::get_gcs_access_token( $service_account );
        if ( ! $access_token ) {
            return false;
        }

        $filename = $path_prefix . date( 'Y/m/' ) . $temp_file['filename'];
        $file_content = file_get_contents( $temp_file['path'] );

        $url = "https://storage.googleapis.com/upload/storage/v1/b/{$bucket}/o?uploadType=media&name=" . urlencode( $filename );

        $response = wp_remote_post( $url, array(
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => $temp_file['content_type'],
            ),
            'body' => $file_content,
        ) );

        if ( is_wp_error( $response ) ) {
            NAP_Logger::error( 'GCS upload error: ' . $response->get_error_message() );
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        if ( $status_code !== 200 ) {
            NAP_Logger::error( "GCS upload failed with HTTP {$status_code}" );
            return false;
        }

        // Make object public
        self::make_gcs_object_public( $bucket, $filename, $access_token );

        if ( $custom_domain ) {
            $public_url = rtrim( $custom_domain, '/' ) . '/' . $filename;
        } else {
            $public_url = "https://storage.googleapis.com/{$bucket}/{$filename}";
        }

        return array(
            'new_url' => $public_url,
            'storage' => self::STORAGE_GCS,
            'key'     => $filename,
        );
    }

    /**
     * Upload to BunnyCDN Storage
     */
    private static function upload_to_bunny( $temp_file, $image ) {
        $api_key       = get_option( 'nap_bunny_api_key' );
        $storage_zone  = get_option( 'nap_bunny_storage_zone' );
        $cdn_url       = get_option( 'nap_bunny_cdn_url' );
        $path_prefix   = get_option( 'nap_bunny_path_prefix', 'natalie-auto-poster/' );
        $storage_region = get_option( 'nap_bunny_storage_region', '' );

        if ( empty( $api_key ) || empty( $storage_zone ) ) {
            NAP_Logger::error( 'BunnyCDN credentials not configured' );
            return false;
        }

        $filename = $path_prefix . date( 'Y/m/' ) . $temp_file['filename'];
        $file_content = file_get_contents( $temp_file['path'] );

        // Determine storage endpoint based on region
        $storage_host = empty( $storage_region )
            ? 'storage.bunnycdn.com'
            : "{$storage_region}.storage.bunnycdn.com";

        $url = "https://{$storage_host}/{$storage_zone}/{$filename}";

        $response = wp_remote_request( $url, array(
            'method'  => 'PUT',
            'timeout' => 60,
            'headers' => array(
                'AccessKey'    => $api_key,
                'Content-Type' => $temp_file['content_type'],
            ),
            'body' => $file_content,
        ) );

        if ( is_wp_error( $response ) ) {
            NAP_Logger::error( 'BunnyCDN upload error: ' . $response->get_error_message() );
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        if ( $status_code !== 201 ) {
            NAP_Logger::error( "BunnyCDN upload failed with HTTP {$status_code}" );
            return false;
        }

        if ( $cdn_url ) {
            $public_url = rtrim( $cdn_url, '/' ) . '/' . $filename;
        } else {
            $public_url = "https://{$storage_zone}.b-cdn.net/{$filename}";
        }

        return array(
            'new_url' => $public_url,
            'storage' => self::STORAGE_BUNNY,
            'key'     => $filename,
        );
    }

    /**
     * Get GCS access token using service account
     */
    private static function get_gcs_access_token( $service_account ) {
        $now = time();
        $header = base64_encode( json_encode( array( 'alg' => 'RS256', 'typ' => 'JWT' ) ) );
        $payload = base64_encode( json_encode( array(
            'iss'   => $service_account['client_email'],
            'scope' => 'https://www.googleapis.com/auth/devstorage.read_write',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'exp'   => $now + 3600,
            'iat'   => $now,
        ) ) );

        $signing_input = $header . '.' . $payload;

        // Sign with private key
        $private_key = openssl_pkey_get_private( $service_account['private_key'] );
        if ( ! $private_key ) {
            NAP_Logger::error( 'Invalid GCS private key' );
            return false;
        }

        $signature = '';
        openssl_sign( $signing_input, $signature, $private_key, OPENSSL_ALGO_SHA256 );
        $jwt = $signing_input . '.' . base64_encode( $signature );

        $response = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
            'timeout' => 30,
            'body'    => array(
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return $body['access_token'] ?? false;
    }

    /**
     * Make GCS object publicly accessible
     */
    private static function make_gcs_object_public( $bucket, $filename, $access_token ) {
        $url = "https://storage.googleapis.com/storage/v1/b/{$bucket}/o/" . urlencode( $filename ) . '/iam';

        wp_remote_post( $url, array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'bindings' => array(
                    array(
                        'role'    => 'roles/storage.objectViewer',
                        'members' => array( 'allUsers' ),
                    ),
                ),
            ) ),
        ) );
    }

    /**
     * Build AWS Signature V4 headers
     */
    private static function build_aws_v4_headers( $method, $url, $body, $content_type, $access_key, $secret_key, $region, $service ) {
        $parsed_url = parse_url( $url );
        $host = $parsed_url['host'];
        $path = $parsed_url['path'] ?? '/';

        $datetime = gmdate( 'Ymd\THis\Z' );
        $date = gmdate( 'Ymd' );

        $payload_hash = hash( 'sha256', $body );

        $canonical_headers = "content-type:{$content_type}\nhost:{$host}\nx-amz-content-sha256:{$payload_hash}\nx-amz-date:{$datetime}\n";
        $signed_headers = 'content-type;host;x-amz-content-sha256;x-amz-date';

        $canonical_request = implode( "\n", array(
            $method,
            $path,
            '',
            $canonical_headers,
            $signed_headers,
            $payload_hash,
        ) );

        $credential_scope = "{$date}/{$region}/{$service}/aws4_request";
        $string_to_sign = implode( "\n", array(
            'AWS4-HMAC-SHA256',
            $datetime,
            $credential_scope,
            hash( 'sha256', $canonical_request ),
        ) );

        $signing_key = hash_hmac( 'sha256', 'aws4_request',
            hash_hmac( 'sha256', $service,
                hash_hmac( 'sha256', $region,
                    hash_hmac( 'sha256', $date, 'AWS4' . $secret_key, true ),
                    true
                ),
                true
            ),
            true
        );

        $signature = hash_hmac( 'sha256', $string_to_sign, $signing_key );

        return array(
            'Content-Type'         => $content_type,
            'x-amz-date'           => $datetime,
            'x-amz-content-sha256' => $payload_hash,
            'Authorization'        => "AWS4-HMAC-SHA256 Credential={$access_key}/{$credential_scope}, SignedHeaders={$signed_headers}, Signature={$signature}",
        );
    }

    /**
     * Generate filename for uploaded image
     */
    private static function generate_filename( $original_url, $ext ) {
        $url_hash = substr( md5( $original_url ), 0, 8 );
        return date( 'Ymd' ) . '-' . $url_hash . '.' . $ext;
    }

    /**
     * Get extension from content type
     */
    private static function get_extension_from_content_type( $content_type ) {
        $map = array(
            'image/jpeg' => 'jpg',
            'image/jpg'  => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        );

        $content_type = strtolower( trim( explode( ';', $content_type )[0] ) );
        return $map[ $content_type ] ?? null;
    }
}
