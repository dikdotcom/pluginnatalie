<?php
/**
 * Translator class for Natalie Auto Poster
 * Handles Japanese to Indonesian translation using AI APIs
 *
 * @package Natalie_Auto_Poster
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class NAP_Translator
 */
class NAP_Translator {

    /**
     * Supported translation providers
     */
    const PROVIDER_OPENAI    = 'openai';
    const PROVIDER_GEMINI    = 'gemini';
    const PROVIDER_DEEPL     = 'deepl';
    const PROVIDER_CLAUDE    = 'claude';

    /**
     * Translate article from Japanese to Indonesian
     *
     * @param array $article Article data with title and content
     * @return array|false Translated article data or false on failure
     */
    public static function translate_article( $article ) {
        $provider = get_option( 'nap_translation_provider', self::PROVIDER_OPENAI );

        NAP_Logger::info( "Translating article using provider: {$provider}", null, array( 'title' => $article['title'] ) );

        // Translate title
        $translated_title = self::translate_text( $article['title'], $provider );
        if ( ! $translated_title ) {
            NAP_Logger::error( 'Failed to translate article title' );
            return false;
        }

        // Translate content (may need chunking for long content)
        $translated_content = self::translate_content( $article['content'], $provider );
        if ( ! $translated_content ) {
            NAP_Logger::error( 'Failed to translate article content' );
            return false;
        }

        return array(
            'original_title'    => $article['title'],
            'translated_title'  => $translated_title,
            'original_content'  => $article['content'],
            'translated_content' => $translated_content,
        );
    }

    /**
     * Translate a single text string
     *
     * @param string $text Text to translate
     * @param string $provider Translation provider
     * @return string|false Translated text or false on failure
     */
    public static function translate_text( $text, $provider = null ) {
        if ( ! $provider ) {
            $provider = get_option( 'nap_translation_provider', self::PROVIDER_OPENAI );
        }

        switch ( $provider ) {
            case self::PROVIDER_OPENAI:
                return self::translate_with_openai( $text );
            case self::PROVIDER_GEMINI:
                return self::translate_with_gemini( $text );
            case self::PROVIDER_DEEPL:
                return self::translate_with_deepl( $text );
            case self::PROVIDER_CLAUDE:
                return self::translate_with_claude( $text );
            default:
                NAP_Logger::error( "Unknown translation provider: {$provider}" );
                return false;
        }
    }

    /**
     * Translate long content by chunking
     */
    private static function translate_content( $content, $provider ) {
        // Strip HTML for translation, then re-apply
        $text_content = wp_strip_all_tags( $content );

        // If content is short enough, translate directly
        $max_chars = 3000;
        if ( mb_strlen( $text_content ) <= $max_chars ) {
            return self::translate_text( $content, $provider );
        }

        // Split into paragraphs and translate in chunks
        $paragraphs = preg_split( '/(<p[^>]*>.*?<\/p>)/si', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
        $translated_parts = array();
        $current_chunk = '';
        $current_chunk_html = '';

        foreach ( $paragraphs as $part ) {
            $part_text = wp_strip_all_tags( $part );
            if ( mb_strlen( $current_chunk . $part_text ) > $max_chars && ! empty( $current_chunk ) ) {
                // Translate current chunk
                $translated = self::translate_text( $current_chunk_html, $provider );
                if ( $translated ) {
                    $translated_parts[] = $translated;
                }
                $current_chunk = $part_text;
                $current_chunk_html = $part;
            } else {
                $current_chunk .= $part_text;
                $current_chunk_html .= $part;
            }
        }

        // Translate remaining chunk
        if ( ! empty( $current_chunk_html ) ) {
            $translated = self::translate_text( $current_chunk_html, $provider );
            if ( $translated ) {
                $translated_parts[] = $translated;
            }
        }

        return implode( '', $translated_parts );
    }

    /**
     * Translate using OpenAI GPT
     */
    private static function translate_with_openai( $text ) {
        $api_key = get_option( 'nap_openai_api_key' );
        if ( empty( $api_key ) ) {
            NAP_Logger::error( 'OpenAI API key not configured' );
            return false;
        }

        $model = get_option( 'nap_openai_model', 'gpt-4o-mini' );

        $system_prompt = self::get_translation_system_prompt();
        $user_prompt = "Terjemahkan teks berikut dari Bahasa Jepang ke Bahasa Indonesia:\n\n" . $text;

        $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'model'    => $model,
                'messages' => array(
                    array( 'role' => 'system', 'content' => $system_prompt ),
                    array( 'role' => 'user', 'content' => $user_prompt ),
                ),
                'temperature' => 0.3,
                'max_tokens'  => 4000,
            ) ),
        ) );

        return self::parse_openai_response( $response );
    }

    /**
     * Translate using Google Gemini
     */
    private static function translate_with_gemini( $text ) {
        $api_key = get_option( 'nap_gemini_api_key' );
        if ( empty( $api_key ) ) {
            NAP_Logger::error( 'Gemini API key not configured' );
            return false;
        }

        $model = get_option( 'nap_gemini_model', 'gemini-1.5-flash' );
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

        $system_prompt = self::get_translation_system_prompt();
        $user_prompt = $system_prompt . "\n\nTerjemahkan teks berikut dari Bahasa Jepang ke Bahasa Indonesia:\n\n" . $text;

        $response = wp_remote_post( $url, array(
            'timeout' => 60,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'contents' => array(
                    array(
                        'parts' => array(
                            array( 'text' => $user_prompt ),
                        ),
                    ),
                ),
                'generationConfig' => array(
                    'temperature'     => 0.3,
                    'maxOutputTokens' => 4000,
                ),
            ) ),
        ) );

        return self::parse_gemini_response( $response );
    }

    /**
     * Translate using DeepL API
     */
    private static function translate_with_deepl( $text ) {
        $api_key = get_option( 'nap_deepl_api_key' );
        if ( empty( $api_key ) ) {
            NAP_Logger::error( 'DeepL API key not configured' );
            return false;
        }

        // Determine if using free or pro API
        $api_url = strpos( $api_key, ':fx' ) !== false
            ? 'https://api-free.deepl.com/v2/translate'
            : 'https://api.deepl.com/v2/translate';

        // DeepL doesn't support HTML translation well, strip tags first
        $is_html = ( $text !== strip_tags( $text ) );
        $text_to_translate = $is_html ? wp_strip_all_tags( $text ) : $text;

        $response = wp_remote_post( $api_url, array(
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'DeepL-Auth-Key ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'text'        => array( $text_to_translate ),
                'source_lang' => 'JA',
                'target_lang' => 'ID',
            ) ),
        ) );

        return self::parse_deepl_response( $response );
    }

    /**
     * Translate using Anthropic Claude
     */
    private static function translate_with_claude( $text ) {
        $api_key = get_option( 'nap_claude_api_key' );
        if ( empty( $api_key ) ) {
            NAP_Logger::error( 'Claude API key not configured' );
            return false;
        }

        $model = get_option( 'nap_claude_model', 'claude-3-haiku-20240307' );
        $system_prompt = self::get_translation_system_prompt();
        $user_prompt = "Terjemahkan teks berikut dari Bahasa Jepang ke Bahasa Indonesia:\n\n" . $text;

        $response = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
            'timeout' => 60,
            'headers' => array(
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type'      => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'model'      => $model,
                'max_tokens' => 4000,
                'system'     => $system_prompt,
                'messages'   => array(
                    array( 'role' => 'user', 'content' => $user_prompt ),
                ),
            ) ),
        ) );

        return self::parse_claude_response( $response );
    }

    /**
     * Get translation system prompt
     */
    private static function get_translation_system_prompt() {
        $custom_prompt = get_option( 'nap_translation_prompt' );
        if ( ! empty( $custom_prompt ) ) {
            return $custom_prompt;
        }

        return 'Kamu adalah penerjemah profesional yang ahli dalam menerjemahkan berita hiburan Jepang ke Bahasa Indonesia. ' .
               'Terjemahkan dengan akurat dan natural, pertahankan nama artis/band Jepang dalam bentuk aslinya (romaji), ' .
               'terjemahkan judul lagu/album jika ada terjemahan yang umum digunakan, ' .
               'gunakan istilah yang tepat untuk industri musik/hiburan Jepang, ' .
               'dan pastikan terjemahan mudah dipahami oleh pembaca Indonesia. ' .
               'Jika ada HTML dalam teks, pertahankan tag HTML tersebut dalam terjemahan. ' .
               'Hanya berikan hasil terjemahan tanpa penjelasan tambahan.';
    }

    /**
     * Parse OpenAI API response
     */
    private static function parse_openai_response( $response ) {
        if ( is_wp_error( $response ) ) {
            NAP_Logger::error( 'OpenAI API error: ' . $response->get_error_message() );
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['error'] ) ) {
            NAP_Logger::error( 'OpenAI API error: ' . $body['error']['message'] );
            return false;
        }

        if ( isset( $body['choices'][0]['message']['content'] ) ) {
            return trim( $body['choices'][0]['message']['content'] );
        }

        NAP_Logger::error( 'Unexpected OpenAI response format' );
        return false;
    }

    /**
     * Parse Gemini API response
     */
    private static function parse_gemini_response( $response ) {
        if ( is_wp_error( $response ) ) {
            NAP_Logger::error( 'Gemini API error: ' . $response->get_error_message() );
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['error'] ) ) {
            NAP_Logger::error( 'Gemini API error: ' . $body['error']['message'] );
            return false;
        }

        if ( isset( $body['candidates'][0]['content']['parts'][0]['text'] ) ) {
            return trim( $body['candidates'][0]['content']['parts'][0]['text'] );
        }

        NAP_Logger::error( 'Unexpected Gemini response format' );
        return false;
    }

    /**
     * Parse DeepL API response
     */
    private static function parse_deepl_response( $response ) {
        if ( is_wp_error( $response ) ) {
            NAP_Logger::error( 'DeepL API error: ' . $response->get_error_message() );
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['message'] ) ) {
            NAP_Logger::error( 'DeepL API error: ' . $body['message'] );
            return false;
        }

        if ( isset( $body['translations'][0]['text'] ) ) {
            return trim( $body['translations'][0]['text'] );
        }

        NAP_Logger::error( 'Unexpected DeepL response format' );
        return false;
    }

    /**
     * Parse Claude API response
     */
    private static function parse_claude_response( $response ) {
        if ( is_wp_error( $response ) ) {
            NAP_Logger::error( 'Claude API error: ' . $response->get_error_message() );
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['error'] ) ) {
            NAP_Logger::error( 'Claude API error: ' . $body['error']['message'] );
            return false;
        }

        if ( isset( $body['content'][0]['text'] ) ) {
            return trim( $body['content'][0]['text'] );
        }

        NAP_Logger::error( 'Unexpected Claude response format' );
        return false;
    }
}
