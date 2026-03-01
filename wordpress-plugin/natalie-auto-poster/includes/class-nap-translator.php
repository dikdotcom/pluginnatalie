<?php
/**
 * Translator class for Natalie Auto Poster
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NAP_Translator {

    const PROVIDER_OPENAI    = 'openai';
    const PROVIDER_GEMINI    = 'gemini';
    const PROVIDER_DEEPL     = 'deepl';
    const PROVIDER_CLAUDE    = 'claude';
    const PROVIDER_GROQ      = 'groq';
    const PROVIDER_COHERE    = 'cohere';

    public static function translate_article( $article ) {
        $provider = get_option( 'nap_translation_provider', self::PROVIDER_GEMINI );
        $translated_title = self::translate_text( $article['title'], $provider );
        $translated_content = self::translate_text( wp_strip_all_tags($article['content']), $provider );

        if ( ! $translated_title || ! $translated_content ) return false;

        return array(
            'original_title'    => $article['title'],
            'translated_title'  => $translated_title,
            'original_content'  => $article['content'],
            'translated_content' => $translated_content,
        );
    }

    public static function translate_text( $text, $provider = null ) {
        if ( ! $provider ) $provider = get_option( 'nap_translation_provider', self::PROVIDER_GEMINI );

        switch ( $provider ) {
            case self::PROVIDER_OPENAI: return self::translate_with_openai( $text );
            case self::PROVIDER_GEMINI: return self::translate_with_gemini( $text );
            case self::PROVIDER_DEEPL:  return self::translate_with_deepl( $text );
            case self::PROVIDER_GROQ:   return self::translate_with_groq( $text );
            case self::PROVIDER_COHERE: return self::translate_with_cohere( $text );
            default: return false;
        }
    }

    private static function translate_with_gemini( $text ) {
        $api_key = get_option( 'nap_gemini_api_key' );
        if ( empty( $api_key ) ) return false;

        $model = get_option( 'nap_gemini_model', 'gemini-2.0-flash' );
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

        $system_prompt = self::get_translation_system_prompt();
        $response = wp_remote_post( $url, array(
            'timeout' => 60,
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body' => wp_json_encode( array(
                'contents' => array( array( 'parts' => array( array( 'text' => $system_prompt . "\n\nTeks: " . $text ) ) ) ),
            ) ),
        ) );

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return isset($body['candidates'][0]['content']['parts'][0]['text']) ? trim($body['candidates'][0]['content']['parts'][0]['text']) : false;
    }

    private static function translate_with_groq( $text ) {
        $api_key = get_option( 'nap_groq_api_key' );
        if ( empty( $api_key ) ) return false;

        $model = get_option( 'nap_groq_model', 'llama3-70b-8192' );
        $response = wp_remote_post( 'https://api.groq.com/openai/v1/chat/completions', array(
            'timeout' => 60,
            'headers' => array( 'Authorization' => 'Bearer ' . $api_key, 'Content-Type'  => 'application/json' ),
            'body' => wp_json_encode( array(
                'model'    => $model,
                'messages' => array(
                    array( 'role' => 'system', 'content' => self::get_translation_system_prompt() ),
                    array( 'role' => 'user', 'content' => $text ),
                )
            ) ),
        ) );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return isset($body['choices'][0]['message']['content']) ? trim($body['choices'][0]['message']['content']) : false;
    }

    private static function translate_with_cohere( $text ) {
        $api_key = get_option( 'nap_cohere_api_key' );
        if ( empty( $api_key ) ) return false;

        $model = get_option( 'nap_cohere_model', 'command-r' );
        $response = wp_remote_post( 'https://api.cohere.ai/v1/chat', array(
            'timeout' => 60,
            'headers' => array( 'Authorization' => 'Bearer ' . $api_key, 'Content-Type'  => 'application/json' ),
            'body' => wp_json_encode( array(
                'model'   => $model,
                'message' => self::get_translation_system_prompt() . "\n\nTeks: " . $text,
            ) ),
        ) );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return isset($body['text']) ? trim($body['text']) : false;
    }

    private static function translate_with_deepl( $text ) {
        $api_key = get_option( 'nap_deepl_api_key' );
        if ( empty( $api_key ) ) return false;

        $api_url = strpos( $api_key, ':fx' ) !== false ? 'https://api-free.deepl.com/v2/translate' : 'https://api.deepl.com/v2/translate';
        
        $response = wp_remote_post( $api_url, array(
            'timeout' => 60,
            'headers' => array( 'Authorization' => 'DeepL-Auth-Key ' . $api_key, 'Content-Type' => 'application/json' ),
            'body' => wp_json_encode( array(
                'text'        => array( $text ),
                'source_lang' => 'JA',
                'target_lang' => 'ID',
                'tag_handling'=> 'html',
            ) ),
        ) );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return isset($body['translations'][0]['text']) ? trim($body['translations'][0]['text']) : false;
    }

    private static function get_translation_system_prompt() {
        return 'Kamu adalah penerjemah profesional berita Jepang ke Bahasa Indonesia. Terjemahkan dengan akurat.';
    }
}