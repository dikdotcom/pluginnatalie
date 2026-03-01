<?php
/**
 * Settings view for Japanese News Entertainment Post by KEiKO
 *
 * @package Natalie_Auto_Poster
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$tabs = array(
    'general'     => __( 'General', 'natalie-auto-poster' ),
    'ai'          => __( 'AI & Translation', 'natalie-auto-poster' ),
    'images'      => __( 'Image Storage', 'natalie-auto-poster' ),
    'maintenance' => __( 'Maintenance', 'natalie-auto-poster' ),
);
?>
<div class="wrap nap-wrap">
    <h1><?php esc_html_e( 'Japanese News Entertainment Post Settings', 'natalie-auto-poster' ); ?></h1>

    <nav class="nav-tab-wrapper">
        <?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=nap-settings&tab=' . $tab_key ) ); ?>"
               class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html( $tab_label ); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <form method="post" action="options.php">
        <?php
        switch ( $active_tab ) {
            case 'ai':
                settings_fields( 'nap_ai' );
                ?>
                <div class="nap-settings-section">
                    <h2><?php esc_html_e( 'Translation Settings', 'natalie-auto-poster' ); ?></h2>

                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'Translation Provider', 'natalie-auto-poster' ); ?></th>
                            <td>
                                <select name="nap_translation_provider" id="nap_translation_provider">
                                    <option value="gemini" <?php selected( get_option( 'nap_translation_provider' ), 'gemini' ); ?>>Google Gemini</option>
                                    <option value="openai" <?php selected( get_option( 'nap_translation_provider' ), 'openai' ); ?>>OpenAI (GPT)</option>
                                    <option value="claude" <?php selected( get_option( 'nap_translation_provider' ), 'claude' ); ?>>Anthropic Claude</option>
                                    <option value="groq" <?php selected( get_option( 'nap_translation_provider' ), 'groq' ); ?>>Groq (Free/Fast Llama)</option>
                                    <option value="cohere" <?php selected( get_option( 'nap_translation_provider' ), 'cohere' ); ?>>Cohere (Free Tier)</option>
                                    <option value="deepl" <?php selected( get_option( 'nap_translation_provider' ), 'deepl' ); ?>>DeepL</option>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <div class="nap-provider-settings" id="settings-openai">
                        <h3>OpenAI Settings</h3>
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e( 'API Key', 'natalie-auto-poster' ); ?></th>
                                <td>
                                    <input type="password" name="nap_openai_api_key" value="<?php echo esc_attr( get_option( 'nap_openai_api_key' ) ); ?>" class="regular-text" autocomplete="new-password" />
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Model', 'natalie-auto-poster' ); ?></th>
                                <td>
                                    <select name="nap_openai_model">
                                        <option value="gpt-4o-mini" <?php selected( get_option( 'nap_openai_model', 'gpt-4o-mini' ), 'gpt-4o-mini' ); ?>>GPT-4o Mini</option>
                                        <option value="gpt-4o" <?php selected( get_option( 'nap_openai_model' ), 'gpt-4o' ); ?>>GPT-4o</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="nap-provider-settings" id="settings-gemini">
                        <h3>Google Gemini Settings</h3>
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e( 'API Key', 'natalie-auto-poster' ); ?></th>
                                <td>
                                    <input type="password" name="nap_gemini_api_key" value="<?php echo esc_attr( get_option( 'nap_gemini_api_key' ) ); ?>" class="regular-text" autocomplete="new-password" />
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Model', 'natalie-auto-poster' ); ?></th>
                                <td>
                                    <select name="nap_gemini_model">
                                        <option value="gemini-2.0-flash" <?php selected( get_option( 'nap_gemini_model', 'gemini-2.0-flash' ), 'gemini-2.0-flash' ); ?>>Gemini 2.0 Flash (Latest)</option>
                                        <option value="gemini-2.0-pro" <?php selected( get_option( 'nap_gemini_model' ), 'gemini-2.0-pro' ); ?>>Gemini 2.0 Pro</option>
                                        <option value="gemini-1.5-pro" <?php selected( get_option( 'nap_gemini_model' ), 'gemini-1.5-pro' ); ?>>Gemini 1.5 Pro</option>
                                        <option value="gemini-1.5-flash" <?php selected( get_option( 'nap_gemini_model' ), 'gemini-1.5-flash' ); ?>>Gemini 1.5 Flash</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="nap-provider-settings" id="settings-groq">
                        <h3>Groq Settings (Free Llama Models)</h3>
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e( 'API Key', 'natalie-auto-poster' ); ?></th>
                                <td>
                                    <input type="password" name="nap_groq_api_key" value="<?php echo esc_attr( get_option( 'nap_groq_api_key' ) ); ?>" class="regular-text" autocomplete="new-password" />
                                    <p class="description">Dapatkan API Key gratis di <a href="https://console.groq.com" target="_blank">console.groq.com</a></p>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Model', 'natalie-auto-poster' ); ?></th>
                                <td>
                                    <select name="nap_groq_model">
                                        <option value="llama3-70b-8192" <?php selected( get_option( 'nap_groq_model', 'llama3-70b-8192' ), 'llama3-70b-8192' ); ?>>Llama 3 70B</option>
                                        <option value="llama3-8b-8192" <?php selected( get_option( 'nap_groq_model' ), 'llama3-8b-8192' ); ?>>Llama 3 8B</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="nap-provider-settings" id="settings-cohere">
                        <h3>Cohere Settings</h3>
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e( 'API Key', 'natalie-auto-poster' ); ?></th>
                                <td>
                                    <input type="password" name="nap_cohere_api_key" value="<?php echo esc_attr( get_option( 'nap_cohere_api_key' ) ); ?>" class="regular-text" autocomplete="new-password" />
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Model', 'natalie-auto-poster' ); ?></th>
                                <td>
                                    <select name="nap_cohere_model">
                                        <option value="command-r-plus" <?php selected( get_option( 'nap_cohere_model', 'command-r-plus' ), 'command-r-plus' ); ?>>Command R+</option>
                                        <option value="command-r" <?php selected( get_option( 'nap_cohere_model' ), 'command-r' ); ?>>Command R</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="nap-provider-settings" id="settings-claude">
                        <h3>Anthropic Claude Settings</h3>
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e( 'API Key', 'natalie-auto-poster' ); ?></th>
                                <td>
                                    <input type="password" name="nap_claude_api_key" value="<?php echo esc_attr( get_option( 'nap_claude_api_key' ) ); ?>" class="regular-text" autocomplete="new-password" />
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Model', 'natalie-auto-poster' ); ?></th>
                                <td>
                                    <select name="nap_claude_model">
                                        <option value="claude-3-haiku-20240307" <?php selected( get_option( 'nap_claude_model', 'claude-3-haiku-20240307' ), 'claude-3-haiku-20240307' ); ?>>Claude 3 Haiku</option>
                                        <option value="claude-3-5-sonnet-20241022" <?php selected( get_option( 'nap_claude_model' ), 'claude-3-5-sonnet-20241022' ); ?>>Claude 3.5 Sonnet</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="nap-provider-settings" id="settings-deepl">
                        <h3>DeepL Settings (Free / Pro)</h3>
                        <p class="description" style="color:#0073aa; font-weight:bold;">
                            DeepL menyediakan API Gratis (Free Tier) untuk terjemahan hingga 500.000 karakter per bulan.
                        </p>
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e( 'API Key', 'natalie-auto-poster' ); ?></th>
                                <td>
                                    <input type="password" name="nap_deepl_api_key" value="<?php echo esc_attr( get_option( 'nap_deepl_api_key' ) ); ?>" class="regular-text" autocomplete="new-password" />
                                    <p class="description" style="color:#d63638;">
                                        <?php esc_html_e( 'Penting: Jika Anda menggunakan DeepL versi GRATIS (Free Tier), pastikan API Key Anda diakhiri dengan ":fx". Plugin otomatis mengarah ke server gratis.', 'natalie-auto-poster' ); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="nap-settings-section">
                    <h2><?php esc_html_e( 'AI Review & Humanizer Settings', 'natalie-auto-poster' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'Agen Pengecek Kanji', 'natalie-auto-poster' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="nap_enable_kanji_agent" value="1" <?php checked( get_option( 'nap_enable_kanji_agent', 1 ), 1 ); ?> />
                                    <strong><?php esc_html_e( 'Aktifkan Agen AI Khusus Verifikasi Nama (Nanori)', 'natalie-auto-poster' ); ?></strong>
                                </label>
                                <p class="description">Menghindari salah baca kanji pada nama aktor/idol saat diterjemahkan.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Humanize Tone', 'natalie-auto-poster' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="nap_humanize_tone" value="1" <?php checked( get_option( 'nap_humanize_tone', 1 ), 1 ); ?> />
                                    <?php esc_html_e( 'Gunakan gaya bahasa santai/jurnalistik agar tidak terdeteksi AI', 'natalie-auto-poster' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Skip AI Review', 'natalie-auto-poster' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="nap_skip_review" value="1" <?php checked( get_option( 'nap_skip_review' ), 1 ); ?> />
                                    <?php esc_html_e( 'Lewati proses AI Review/Humanizer (Lebih cepat, tapi gaya bahasa mungkin kaku)', 'natalie-auto-poster' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Review Provider', 'natalie-auto-poster' ); ?></th>
                            <td>
                                <select name="nap_review_provider">
                                    <option value="" <?php selected( get_option( 'nap_review_provider' ), '' ); ?>><?php esc_html_e( 'Sama dengan Translation Provider', 'natalie-auto-poster' ); ?></option>
                                    <option value="gemini" <?php selected( get_option( 'nap_review_provider' ), 'gemini' ); ?>>Google Gemini</option>
                                    <option value="groq" <?php selected( get_option( 'nap_review_provider' ), 'groq' ); ?>>Groq (Llama)</option>
                                    <option value="cohere" <?php selected( get_option( 'nap_review_provider' ), 'cohere' ); ?>>Cohere</option>
                                    <option value="openai" <?php selected( get_option( 'nap_review_provider' ), 'openai' ); ?>>OpenAI (GPT)</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                <?php
                break;

            case 'images':
                settings_fields( 'nap_images' );
                // Bagian ini sama persis dengan yang asli, agar tidak terlalu panjang saya sertakan struktur basic WordPress Media
                ?>
                <div class="nap-settings-section">
                    <h2><?php esc_html_e( 'Image Storage Settings', 'natalie-auto-poster' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'Storage Provider', 'natalie-auto-poster' ); ?></th>
                            <td>
                                <select name="nap_image_storage" id="nap_image_storage">
                                    <option value="wordpress" <?php selected( get_option( 'nap_image_storage', 'wordpress' ), 'wordpress' ); ?>><?php esc_html_e( 'WordPress Media Library', 'natalie-auto-poster' ); ?></option>
                                    <option value="s3" <?php selected( get_option( 'nap_image_storage' ), 's3' ); ?>>Amazon S3</option>
                                    <option value="r2" <?php selected( get_option( 'nap_image_storage' ), 'r2' ); ?>>Cloudflare R2</option>
                                    <option value="bunny" <?php selected( get_option( 'nap_image_storage' ), 'bunny' ); ?>>BunnyCDN</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <div class="nap-storage-settings" id="storage-s3">
                        <table class="form-table">
                            <tr><th><?php esc_html_e( 'Access Key ID', 'natalie-auto-poster' ); ?></th>
                            <td><input type="text" name="nap_s3_access_key" value="<?php echo esc_attr( get_option( 'nap_s3_access_key' ) ); ?>" class="regular-text" /></td></tr>
                            <tr><th><?php esc_html_e( 'Secret Access Key', 'natalie-auto-poster' ); ?></th>
                            <td><input type="password" name="nap_s3_secret_key" value="<?php echo esc_attr( get_option( 'nap_s3_secret_key' ) ); ?>" class="regular-text" /></td></tr>
                            <tr><th><?php esc_html_e( 'Bucket Name', 'natalie-auto-poster' ); ?></th>
                            <td><input type="text" name="nap_s3_bucket" value="<?php echo esc_attr( get_option( 'nap_s3_bucket' ) ); ?>" class="regular-text" /></td></tr>
                        </table>
                    </div>
                </div>
                <?php
                break;

            case 'maintenance':
                settings_fields( 'nap_maintenance' );
                ?>
                <div class="nap-settings-section">
                    <h2><?php esc_html_e( 'Maintenance Settings', 'natalie-auto-poster' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'Log Retention (days)', 'natalie-auto-poster' ); ?></th>
                            <td>
                                <input type="number" name="nap_log_retention_days" value="<?php echo esc_attr( get_option( 'nap_log_retention_days', 30 ) ); ?>" min="1" max="365" class="small-text" />
                            </td>
                        </tr>
                    </table>
                </div>
                <?php
                break;

            default: // general
                settings_fields( 'nap_general' );
                ?>
                <div class="nap-settings-section">
                    <h2><?php esc_html_e( 'Source Configuration', 'natalie-auto-poster' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'Active Sources (Default)', 'natalie-auto-poster' ); ?></th>
                            <td>
                                <?php
                                $active_sources = get_option( 'nap_active_sources', array() );
                                $available_sites = NAP_Fetcher::get_available_sites();
                                foreach ( $available_sites as $site ) :
                                ?>
                                    <label style="display:block; margin-bottom:5px;">
                                        <input type="checkbox" name="nap_active_sources[]" value="<?php echo esc_attr( $site ); ?>"
                                               <?php checked( in_array( $site, $active_sources ) ); ?> />
                                        <?php echo esc_html( $site ); ?>
                                    </label>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Custom Scraping Sources (RSS/Feed)', 'natalie-auto-poster' ); ?></th>
                            <td>
                                <textarea name="nap_custom_sources" rows="5" class="large-text" placeholder="https://news.yahoo.co.jp/rss/topics/entertainment.xml&#10;https://www.oricon.co.jp/rss/news/"><?php echo esc_textarea( get_option( 'nap_custom_sources' ) ); ?></textarea>
                                <p class="description" style="color:#0073aa; font-weight:600;">
                                    <?php esc_html_e( 'Masukkan URL RSS Feed (Satu URL per baris). Plugin akan otomatis melakukan scraping artikel penuh dari web aslinya.', 'natalie-auto-poster' ); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="nap-settings-section">
                    <h2><?php esc_html_e( 'Fetch & Post Settings', 'natalie-auto-poster' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'Enable Auto Fetch', 'natalie-auto-poster' ); ?></th>
                            <td><input type="checkbox" name="nap_auto_fetch_enabled" value="1" <?php checked( get_option( 'nap_auto_fetch_enabled', 1 ), 1 ); ?> /></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Articles Per Run', 'natalie-auto-poster' ); ?></th>
                            <td><input type="number" name="nap_articles_per_run" value="<?php echo esc_attr( get_option( 'nap_articles_per_run', 3 ) ); ?>" class="small-text" /></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Auto Publish', 'natalie-auto-poster' ); ?></th>
                            <td><input type="checkbox" name="nap_auto_publish" value="1" <?php checked( get_option( 'nap_auto_publish' ), 1 ); ?> /></td>
                        </tr>
                    </table>
                </div>
                <?php
                break;
        }
        ?>
        <?php submit_button(); ?>
    </form>
</div>