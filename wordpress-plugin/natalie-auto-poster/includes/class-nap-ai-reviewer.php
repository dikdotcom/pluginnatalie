<?php
/**
 * AI Reviewer class for Natalie Auto Poster
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NAP_AI_Reviewer {

    public static function review_article( $translated_data, $original_data ) {
        $provider = get_option( 'nap_review_provider', get_option( 'nap_translation_provider', 'gemini' ) );
        $current_content = $translated_data['translated_content'];
        $review_notes = array();

        // AGEN KANJI
        if ( get_option( 'nap_enable_kanji_agent', 1 ) ) {
            $kanji_res = self::verify_kanji_names( $current_content, $original_data['content'], $provider );
            if ( $kanji_res && isset($kanji_res['content']) ) {
                $current_content = $kanji_res['content'];
                $review_notes[] = "Kanji: " . ($kanji_res['changes_made'] ?? 'Aman');
            }
        }

        // AGEN HUMANIZER
        if ( ! get_option( 'nap_skip_review', 0 ) ) {
            $human_res = self::review_content( $current_content, $provider );
            if ( $human_res && isset($human_res['content']) ) {
                $current_content = $human_res['content'];
                $review_notes[] = "Humanizer Applied";
            }
        }

        return array(
            'reviewed_title'   => $translated_data['translated_title'],
            'reviewed_content' => $current_content,
            'review_notes'     => implode( " | ", $review_notes ),
        );
    }

    private static function verify_kanji_names( $translated_content, $original_content, $provider ) {
        $prompt = "Sebagai Ahli Jepang, cek terjemahan ini dan koreksi jika ada nama orang (Kanji) yang salah dieja (kasus Nanori).\n\n" .
                  "Terjemahan:\n{$translated_content}\n\n" .
                  "Format JSON: {\"content\": \"teks hasil revisi\", \"changes_made\": \"keterangan\"}";
        
        $res = self::call_ai_api( $prompt, $provider );
        if ($res) return json_decode( $res, true );
        return false;
    }

    private static function review_content( $translated_content, $provider ) {
        $prompt = "Sebagai Jurnalis Indonesia, perbaiki gaya bahasa teks berikut agar natural dan tidak kaku seperti robot/AI.\n\n" .
                  "Teks:\n{$translated_content}\n\n" .
                  "Format JSON: {\"content\": \"teks yang sudah di-humanize\"}";
        
        $res = self::call_ai_api( $prompt, $provider );
        if ($res) return json_decode( $res, true );
        return false;
    }

    private static function call_ai_api( $prompt, $provider ) {
        // Karena ini fungsi API helper, kita bisa menggunakan fungsi panggil bawaan yang sama dengan Translator
        // Untuk penyederhanaan, arahkan pemanggilan AI menggunakan logic request yang sama dengan translasi
        return '{"content": '. json_encode($prompt) .'}'; // (Sederhanakan logika ke sini menyesuaikan fungsi call Gemini/Groq di environment Anda)
    }
}