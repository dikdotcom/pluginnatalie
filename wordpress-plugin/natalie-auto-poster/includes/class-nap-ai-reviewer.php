<?php
/**
 * AI Reviewer class for Natalie Auto Poster
 * Reviews and improves translated content using AI
 *
 * @package Natalie_Auto_Poster
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class NAP_AI_Reviewer
 */
class NAP_AI_Reviewer {

    /**
     * Review and improve translated article
     *
     * @param array $translated_data Translated article data
     * @param array $original_data Original article data
     * @return array|false Reviewed article data or false on failure
     */
    public static function review_article( $translated_data, $original_data ) {
        $provider = get_option( 'nap_review_provider', get_option( 'nap_translation_provider', 'openai' ) );

        NAP_Logger::info( "Reviewing translation using provider: {$provider}" );

        // Review title
        $reviewed_title = self::review_title(
            $translated_data['translated_title'],
            $original_data['title'],
            $provider
        );

        // Review content
        $reviewed_content = self::review_content(
            $translated_data['translated_content'],
            $original_data['content'],
            $provider
        );

        if ( ! $reviewed_title || ! $reviewed_content ) {
            NAP_Logger::warning( 'AI review failed, using original translation' );
            return array(
                'reviewed_title'   => $translated_data['translated_title'],
                'reviewed_content' => $translated_data['translated_content'],
                'review_notes'     => 'Review failed, using original translation',
            );
        }

        return array(
            'reviewed_title'   => $reviewed_title['title'],
            'reviewed_content' => $reviewed_content['content'],
            'review_notes'     => $reviewed_content['notes'] ?? '',
            'quality_score'    => $reviewed_content['score'] ?? null,
        );
    }

    /**
     * Review translated title
     */
    private static function review_title( $translated_title, $original_title, $provider ) {
        $prompt = self::build_title_review_prompt( $translated_title, $original_title );
        $response = self::call_ai_api( $prompt, $provider, 'title_review' );

        if ( ! $response ) {
            return array( 'title' => $translated_title );
        }

        // Parse JSON response
        $parsed = json_decode( $response, true );
        if ( json_last_error() === JSON_ERROR_NONE && isset( $parsed['title'] ) ) {
            return $parsed;
        }

        // If not JSON, use response as title directly
        return array( 'title' => trim( $response ) );
    }

    /**
     * Review translated content
     */
    private static function review_content( $translated_content, $original_content, $provider ) {
        // For very long content, only review a portion
        $max_review_length = 5000;
        $content_to_review = mb_strlen( $translated_content ) > $max_review_length
            ? mb_substr( $translated_content, 0, $max_review_length ) . '...'
            : $translated_content;

        $prompt = self::build_content_review_prompt( $content_to_review, $original_content );
        $response = self::call_ai_api( $prompt, $provider, 'content_review' );

        if ( ! $response ) {
            return array( 'content' => $translated_content, 'notes' => 'Review skipped' );
        }

        // Parse JSON response
        $parsed = json_decode( $response, true );
        if ( json_last_error() === JSON_ERROR_NONE && isset( $parsed['content'] ) ) {
            // If content was truncated for review, append the rest
            if ( mb_strlen( $translated_content ) > $max_review_length ) {
                $parsed['content'] .= mb_substr( $translated_content, $max_review_length );
            }
            return $parsed;
        }

        // If not JSON, use response as content directly
        return array( 'content' => trim( $response ), 'notes' => '' );
    }

    /**
     * Build title review prompt
     */
    private static function build_title_review_prompt( $translated_title, $original_title ) {
        return "Kamu adalah editor berita hiburan Jepang yang berpengalaman. " .
               "Periksa dan perbaiki terjemahan judul berita berikut dari Bahasa Jepang ke Bahasa Indonesia.\n\n" .
               "Judul asli (Jepang): {$original_title}\n" .
               "Terjemahan: {$translated_title}\n\n" .
               "Kriteria penilaian:\n" .
               "1. Akurasi terjemahan\n" .
               "2. Nama artis/band harus dalam bentuk asli (romaji)\n" .
               "3. Judul harus menarik dan natural dalam Bahasa Indonesia\n" .
               "4. Tidak ada kesalahan tata bahasa\n\n" .
               "Berikan respons dalam format JSON:\n" .
               '{"title": "judul yang sudah diperbaiki", "changes": "penjelasan perubahan jika ada", "score": 1-10}' .
               "\n\nJika terjemahan sudah baik, kembalikan judul yang sama.";
    }

    /**
     * Build content review prompt
     */
    private static function build_content_review_prompt( $translated_content, $original_content ) {
        // Limit original content for prompt
        $original_preview = mb_substr( wp_strip_all_tags( $original_content ), 0, 500 );

        return "Kamu adalah editor berita hiburan Jepang yang berpengalaman. " .
               "Periksa dan perbaiki terjemahan konten berita berikut dari Bahasa Jepang ke Bahasa Indonesia.\n\n" .
               "Cuplikan asli (Jepang): {$original_preview}...\n\n" .
               "Terjemahan yang perlu diperiksa:\n{$translated_content}\n\n" .
               "Kriteria penilaian:\n" .
               "1. Akurasi terjemahan\n" .
               "2. Nama artis/band/karakter harus dalam bentuk asli (romaji)\n" .
               "3. Terjemahan harus natural dan mudah dipahami pembaca Indonesia\n" .
               "4. Pertahankan tag HTML jika ada\n" .
               "5. Istilah industri hiburan Jepang harus tepat\n" .
               "6. Tidak ada kesalahan tata bahasa atau ejaan\n\n" .
               "Berikan respons dalam format JSON:\n" .
               '{"content": "konten yang sudah diperbaiki", "notes": "catatan perbaikan", "score": 1-10}' .
               "\n\nJika terjemahan sudah baik, kembalikan konten yang sama.";
    }

    /**
     * Call AI API for review
     */
    private static function call_ai_api( $prompt, $provider, $task_type ) {
        switch ( $provider ) {
            case 'openai':
                return self::call_openai( $prompt );
            case 'gemini':
                return self::call_gemini( $prompt );
            case 'claude':
                return self::call_claude( $prompt );
            default:
                return self::call_openai( $prompt );
        }
    }

    /**
     * Call OpenAI API
     */
    private static function call_openai( $prompt ) {
        $api_key = get_option( 'nap_openai_api_key' );
        if ( empty( $api_key ) ) {
            return false;
        }

        $model = get_option( 'nap_review_openai_model', get_option( 'nap_openai_model', 'gpt-4o-mini' ) );

        $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
            'timeout' => 90,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'model'    => $model,
                'messages' => array(
                    array( 'role' => 'user', 'content' => $prompt ),
                ),
                'temperature'    => 0.2,
                'max_tokens'     => 4000,
                'response_format' => array( 'type' => 'json_object' ),
            ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            NAP_Logger::error( 'OpenAI review error: ' . $response->get_error_message() );
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['choices'][0]['message']['content'] ) ) {
            return $body['choices'][0]['message']['content'];
        }

        return false;
    }

    /**
     * Call Gemini API
     */
    private static function call_gemini( $prompt ) {
        $api_key = get_option( 'nap_gemini_api_key' );
        if ( empty( $api_key ) ) {
            return false;
        }

        $model = get_option( 'nap_gemini_model', 'gemini-1.5-flash' );
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

        $response = wp_remote_post( $url, array(
            'timeout' => 90,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'contents' => array(
                    array(
                        'parts' => array(
                            array( 'text' => $prompt ),
                        ),
                    ),
                ),
                'generationConfig' => array(
                    'temperature'     => 0.2,
                    'maxOutputTokens' => 4000,
                    'responseMimeType' => 'application/json',
                ),
            ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            NAP_Logger::error( 'Gemini review error: ' . $response->get_error_message() );
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['candidates'][0]['content']['parts'][0]['text'] ) ) {
            return $body['candidates'][0]['content']['parts'][0]['text'];
        }

        return false;
    }

    /**
     * Call Claude API
     */
    private static function call_claude( $prompt ) {
        $api_key = get_option( 'nap_claude_api_key' );
        if ( empty( $api_key ) ) {
            return false;
        }

        $model = get_option( 'nap_claude_model', 'claude-3-haiku-20240307' );

        $response = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
            'timeout' => 90,
            'headers' => array(
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type'      => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'model'      => $model,
                'max_tokens' => 4000,
                'messages'   => array(
                    array( 'role' => 'user', 'content' => $prompt ),
                ),
            ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            NAP_Logger::error( 'Claude review error: ' . $response->get_error_message() );
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['content'][0]['text'] ) ) {
            return $body['content'][0]['text'];
        }

        return false;
    }
}
