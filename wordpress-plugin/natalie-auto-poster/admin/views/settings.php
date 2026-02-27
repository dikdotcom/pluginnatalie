<?php
/**
 * Settings view for Natalie Auto Poster
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
    <h1><?php esc_html_e( 'Natalie Auto Poster Settings', 'natalie-auto-poster' ); ?></h1>

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
                                    <option value="openai" <?php selected( get_option( 'nap_translation_provider' ), 'openai' ); ?>>OpenAI (GPT)</option>
                                    <option value="gemini" <?php selected( get_option( 'nap_translation_provider' ), 'gemini' ); ?>>Google Gemini</option>
                                    <option value="claude" <?php selected( get_option( 'nap_translation_provider' ), 'claude' ); ?>>Anthropic Claude</option>
                                    <option value="deepl" <?php selected( get_option( 'nap_translation_provider' ), 'deepl' ); ?>>DeepL</option>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <!-- OpenAI Settings -->
                    <div class="nap-provider-settings" id="settings-openai">
                        <h3>OpenAI Settings</h3>
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e( 'API Key', 'natalie-auto-poster' ); ?></th>
                                <td>
                                    <input type="password" name="nap_openai_api_key" value="<?php echo esc_attr( get_option( 'nap_openai_api_key' ) ); ?>" class="regular-text" autocomplete="new-password" />
                                    <button type="button" class="button nap-test-btn" data-provider="openai">
                                        <?php esc_html_e( 'Test Connection', 'natalie-auto-poster' ); ?>
                                    </button>
                                    <span class="nap-test-result"></span>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Model', 'natalie-auto-poster' ); ?></th>
                                <td>
                                    <select name="nap_openai_model">
                                        <option value="gpt-4o-mini" <?php selected( get_option( 'nap_openai_model', 'gpt-4o-mini' ), 'gpt-4o-mini' ); ?>>GPT-4o Mini (Recommended)</option>
                                        <option value="gpt-4o" <?php selected( get_option( 'nap_openai_model' ), 'gpt-4o' ); ?>>GPT-4o</option>
                                        <option value="gpt-4-turbo" <?php selected( get_option( 'nap_openai_model' ), 'gpt-4-turbo' ); ?>>GPT-4 Turbo</option>
                                        <option value="gpt-3.5-turbo" <?php selected( get_option( 'nap_openai_model' ), 'gpt-3.5-turbo' ); ?>>GPT-3.5 Turbo</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Gemini Settings -->
                    <div class="nap-provider-settings" id="settings-gemini">
                        <h3>Google Gemini Settings</h3>
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e( 'API Key', 'natalie-auto-poster' ); ?></th>
                                <td>
                                    <input type="password" name="nap_gemini_api_key" value="<?php echo esc_attr( get_option( 'nap_gemini_api_key' ) ); ?>" class="regular-text" autocomplete="new-password" />
                                    <button type="button" class="button nap-test-btn" data-provider="gemini">
                                        <?php esc_html_e( 'Test Connection', 'natalie-auto-poster' ); ?>
                                    </button>
                                    <span class="nap-test-result"></span>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Model', 'natalie-auto-poster' ); ?></th>
                                <td>
                                    <select name="nap_gemini_model">
                                        <option value="gemini-1.5-flash" <?php selected( get_option( 'nap_gemini_model', 'gemini-1.5-flash' ), 'gemini-1.5-flash' ); ?>>Gemini 1.5 Flash (Recommended)</option>
                                        <option value="gemini-1.5-pro" <?php selected( get_option( 'nap_gemini_model' ), 'gemini-1.5-pro' ); ?>>Gemini 1.5 Pro</option>
                                        <option value="gemini-2.0-flash" <?php selected( get_option( 'nap_gemini_model' ), 'gemini-2.0-flash' ); ?>>Gemini 2.0 Flash</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Claude Settings -->
                    <div class="nap-provider-settings" id="settings-claude">
                        <h3>Anthropic Claude Settings</h3>
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e( 'API Key', 'natalie-auto-poster' ); ?></th>
                                <td>
                                    <input type="password" name="nap_claude_api_key" value="<?php echo esc_attr( get_option( 'nap_claude_api_key' ) ); ?>" class="regular-text" autocomplete="new-password" />
                                    <button type="button" class="button nap-test-btn" data-provider="claude">
                                        <?php esc_html_e( 'Test Connection', 'natalie-auto-poster' ); ?>
                                    </button>
                                    <span class="nap-test-result"></span>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Model', 'natalie-auto-poster' ); ?></th>
                                <td>
                                    <select name="nap_claude_model">
                                        <option value="claude-3-haiku-20240307" <?php selected( get_option( 'nap_claude_model', 'claude-3-haiku-20240307' ), 'claude-3-haiku-20240307' ); ?>>Claude 3 Haiku (Fast & Cheap)</option>
                                        <option value="claude-3-5-sonnet-20241022" <?php selected( get_option( 'nap_claude_model' ), 'claude-3-5-sonnet-20241022' ); ?>>Claude 3.5 Sonnet</option>
                                        <option value="claude-3-opus-20240229" <?php selected( get_option( 'nap_claude_model' ), 'claude-3-opus-20240229' ); ?>>Claude 3 Opus</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- DeepL Settings -->
                    <div class="nap-provider-settings" id="settings-deepl">
                        <h3>DeepL Settings</h3>
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e( 'API Key', 'natalie-auto-poster' ); ?></th>
                                <td>
                                    <input type="password" name="nap_deepl_api_key" value="<?php echo esc_attr( get_option( 'nap_deepl_api_key' ) ); ?>" class="regular-text" autocomplete="new-password" />
                                    <button type="button" class="button nap-test-btn" data-provider="deepl">
                                        <?php esc_html_e( 'Test Connection', 'natalie-auto-poster' ); ?>
                                    </button>
                                    <span class="nap-test-result"></span>
                                    <p class="description"><?php esc_html_e( 'Use key ending in :fx for free tier, or without :fx for pro tier.', 'natalie-auto-poster' ); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="nap-settings-section">
                    <h2><?php esc_html_e( 'AI Review Settings', 'natalie-auto-poster' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'Skip AI Review', 'natalie-auto-poster' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="nap_skip_review" value="1" <?php checked( get_option( 'nap_skip_review' ), 1 ); ?> />
                                    <?php esc_html_e( 'Skip AI review step (faster but lower quality)', 'natalie-auto-poster' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Review Provider', 'natalie-auto-poster' ); ?></th>
                            <td>
                                <select name="nap_review_provider">
                                    <option value="" <?php selected( get_option( 'nap_review_provider' ), '' ); ?>><?php esc_html_e( 'Same as Translation Provider', 'natalie-auto-poster' ); ?></option>
                                    <option value="openai" <?php selected( get_option( 'nap_review_provider' ), 'openai' ); ?>>OpenAI (GPT)</option>
                                    <option value="gemini" <?php selected( get_option( 'nap_review_provider' ), 'gemini' ); ?>>Google Gemini</option>
                                    <option value="claude" <?php selected( get_option( 'nap_review_provider' ), 'claude' ); ?>>Anthropic Claude</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Custom Translation Prompt', 'natalie-auto-poster' ); ?></th>
                            <td>
                                <textarea name="nap_translation_prompt" rows="6" class="large-text"><?php echo esc_textarea( get_option( 'nap_translation_prompt' ) ); ?></textarea>
                                <p class="description"><?php esc_html_e( 'Leave empty to use the default prompt optimized for Japanese entertainment news.', 'natalie-auto-poster' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                <?php
                break;

            case 'images':
                settings_fields( 'nap_images' );
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
                                    <option value="gcs" <?php selected( get_option( 'nap_image_storage' ), 'gcs' ); ?>>Google Cloud Storage</option>
                                    <option value="bunny" <?php selected( get_option( 'nap_image_storage' ), 'bunny' ); ?>>BunnyCDN</option>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <!-- Amazon S3 -->
                    <div class="nap-storage-settings" id="storage-s3">
                        <h3>Amazon S3 Settings</h3>
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e( 'Access Key ID', 'natalie-auto-poster' ); ?></th>
                                <td><input type="text" name="nap_s3_access_key" value="<?php echo esc_attr( get_option( 'nap_s3_access_key' ) ); ?>" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Secret Access Key', 'natalie-auto-poster' ); ?></th>
                                <td><input type="password" name="nap_s3_secret_key" value="<?php echo esc_attr( get_option( 'nap_s3_secret_key' ) ); ?>" class="regular-text" autocomplete="new-password" /></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Bucket Name', 'natalie-auto-poster' ); ?></th>
                                <td><input type="text" name="nap_s3_bucket" value="<?php echo esc_attr( get_option( 'nap_s3_bucket' ) ); ?>" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Region', 'natalie-auto-poster' ); ?></th>
                                <td>
                                    <input type="text" name="nap_s3_region" value="<?php echo esc_attr( get_option( 'nap_s3_region', 'us-east-1' ) ); ?>" class="regular-text" />
                                    <p class="description"><?php esc_html_e( 'e.g., us-east-1, ap-southeast-1', 'natalie-auto-poster' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Path Prefix', 'natalie-auto-poster' ); ?></th>
                                <td><input type="text" name="nap_s3_path_prefix" value="<?php echo esc_attr( get_option( 'nap_s3_path_prefix', 'natalie-auto-poster/' ) ); ?>" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Custom Domain (CDN)', 'natalie-auto-poster' ); ?></th>
                                <td>
                                    <input type="url" name="nap_s3_custom_domain" value="<?php echo esc_attr( get_option( 'nap_s3_custom_domain' ) ); ?>" class="regular-text" placeholder="https://cdn.yourdomain.com" />
                                    <p class="description"><?php esc_html_e( 'Optional. Use if you have CloudFront or custom domain.', 'natalie-auto-poster' ); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Cloudflare R2 -->
                    <div class="nap-storage-settings" id="storage-r2">
                        <h3>Cloudflare R2 Settings</h3>
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e( 'Account ID', 'natalie-auto-poster' ); ?></th>
                                <td><input type="text" name="nap_r2_account_id" value="<?php echo esc_attr( get_option( 'nap_r2_account_id' ) ); ?>" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Access Key ID', 'natalie-auto-poster' ); ?></th>
                                <td><input type="text" name="nap_r2_access_key" value="<?php echo esc_attr( get_option( 'nap_r2_access_key' ) ); ?>" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Secret Access Key', 'natalie-auto-poster' ); ?></th>
                                <td><input type="password" name="nap_r2_secret_key" value="<?php echo esc_attr( get_option( 'nap_r2_secret_key' ) ); ?>" class="regular-text" autocomplete="new-password" /></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Bucket Name', 'natalie-auto-poster' ); ?></th>
                                <td><input type="text" name="nap_r2_bucket" value="<?php echo esc_attr( get_option( 'nap_r2_bucket' ) ); ?>" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Custom Domain', 'natalie-auto-poster' ); ?></th>
                                <td>
                                    <input type="url" name="nap_r2_custom_domain" value="<?php echo esc_attr( get_option( 'nap_r2_custom_domain' ) ); ?>" class="regular-text" placeholder="https://pub-xxx.r2.dev" />
                                    <p class="description"><?php esc_html_e( 'Your R2 public URL or custom domain.', 'natalie-auto-poster' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Path Prefix', 'natalie-auto-poster' ); ?></th>
                                <td><input type="text" name="nap_r2_path_prefix" value="<?php echo esc_attr( get_option( 'nap_r2_path_prefix', 'natalie-auto-poster/' ) ); ?>" class="regular-text" /></td>
                            </tr>
                        </table>
                    </div>

                    <!-- Google Cloud Storage -->
                    <div class="nap-storage-settings" id="storage-gcs">
                        <h3>Google Cloud Storage Settings</h3>
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e( 'Service Account JSON', 'natalie-auto-poster' ); ?></th>
                                <td>
                                    <textarea name="nap_gcs_service_account" rows="8" class="large-text" placeholder='{"type": "service_account", ...}'><?php echo esc_textarea( get_option( 'nap_gcs_service_account' ) ); ?></textarea>
                                    <p class="description"><?php esc_html_e( 'Paste the full service account JSON key here.', 'natalie-auto-poster' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Bucket Name', 'natalie-auto-poster' ); ?></th>
                                <td><input type="text" name="nap_gcs_bucket" value="<?php echo esc_attr( get_option( 'nap_gcs_bucket' ) ); ?>" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Custom Domain', 'natalie-auto-poster' ); ?></th>
                                <td><input type="url" name="nap_gcs_custom_domain" value="<?php echo esc_attr( get_option( 'nap_gcs_custom_domain' ) ); ?>" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Path Prefix', 'natalie-auto-poster' ); ?></th>
                                <td><input type="text" name="nap_gcs_path_prefix" value="<?php echo esc_attr( get_option( 'nap_gcs_path_prefix', 'natalie-auto-poster/' ) ); ?>" class="regular-text" /></td>
                            </tr>
                        </table>
                    </div>

                    <!-- BunnyCDN -->
                    <div class="nap-storage-settings" id="storage-bunny">
                        <h3>BunnyCDN Storage Settings</h3>
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e( 'Storage API Key', 'natalie-auto-poster' ); ?></th>
                                <td><input type="password" name="nap_bunny_api_key" value="<?php echo esc_attr( get_option( 'nap_bunny_api_key' ) ); ?>" class="regular-text" autocomplete="new-password" /></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Storage Zone Name', 'natalie-auto-poster' ); ?></th>
                                <td><input type="text" name="nap_bunny_storage_zone" value="<?php echo esc_attr( get_option( 'nap_bunny_storage_zone' ) ); ?>" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'CDN URL', 'natalie-auto-poster' ); ?></th>
                                <td>
                                    <input type="url" name="nap_bunny_cdn_url" value="<?php echo esc_attr( get_option( 'nap_bunny_cdn_url' ) ); ?>" class="regular-text" placeholder="https://yourzone.b-cdn.net" />
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Storage Region', 'natalie-auto-poster' ); ?></th>
                                <td>
                                    <select name="nap_bunny_storage_region">
                                        <option value="" <?php selected( get_option( 'nap_bunny_storage_region' ), '' ); ?>><?php esc_html_e( 'Default (Falkenstein)', 'natalie-auto-poster' ); ?></option>
                                        <option value="ny" <?php selected( get_option( 'nap_bunny_storage_region' ), 'ny' ); ?>>New York</option>
                                        <option value="la" <?php selected( get_option( 'nap_bunny_storage_region' ), 'la' ); ?>>Los Angeles</option>
                                        <option value="sg" <?php selected( get_option( 'nap_bunny_storage_region' ), 'sg' ); ?>>Singapore</option>
                                        <option value="syd" <?php selected( get_option( 'nap_bunny_storage_region' ), 'syd' ); ?>>Sydney</option>
                                        <option value="uk" <?php selected( get_option( 'nap_bunny_storage_region' ), 'uk' ); ?>>London</option>
                                        <option value="se" <?php selected( get_option( 'nap_bunny_storage_region' ), 'se' ); ?>>Stockholm</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Path Prefix', 'natalie-auto-poster' ); ?></th>
                                <td><input type="text" name="nap_bunny_path_prefix" value="<?php echo esc_attr( get_option( 'nap_bunny_path_prefix', 'natalie-auto-poster/' ) ); ?>" class="regular-text" /></td>
                            </tr>
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
                                <p class="description"><?php esc_html_e( 'Logs older than this will be automatically deleted.', 'natalie-auto-poster' ); ?></p>
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
                            <th><?php esc_html_e( 'Active Sources', 'natalie-auto-poster' ); ?></th>
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
                                <p class="description"><?php esc_html_e( 'Select which sources to fetch articles from.', 'natalie-auto-poster' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="nap-settings-section">
                    <h2><?php esc_html_e( 'Fetch Schedule', 'natalie-auto-poster' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'Enable Auto Fetch', 'natalie-auto-poster' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="nap_auto_fetch_enabled" value="1" <?php checked( get_option( 'nap_auto_fetch_enabled', 1 ), 1 ); ?> />
                                    <?php esc_html_e( 'Automatically fetch new articles on schedule', 'natalie-auto-poster' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Fetch Interval', 'natalie-auto-poster' ); ?></th>
                            <td>
                                <select name="nap_fetch_interval">
                                    <option value="every_30_minutes" <?php selected( get_option( 'nap_fetch_interval' ), 'every_30_minutes' ); ?>><?php esc_html_e( 'Every 30 Minutes', 'natalie-auto-poster' ); ?></option>
                                    <option value="hourly" <?php selected( get_option( 'nap_fetch_interval', 'hourly' ), 'hourly' ); ?>><?php esc_html_e( 'Hourly', 'natalie-auto-poster' ); ?></option>
                                    <option value="every_2_hours" <?php selected( get_option( 'nap_fetch_interval' ), 'every_2_hours' ); ?>><?php esc_html_e( 'Every 2 Hours', 'natalie-auto-poster' ); ?></option>
                                    <option value="every_6_hours" <?php selected( get_option( 'nap_fetch_interval' ), 'every_6_hours' ); ?>><?php esc_html_e( 'Every 6 Hours', 'natalie-auto-poster' ); ?></option>
                                    <option value="every_12_hours" <?php selected( get_option( 'nap_fetch_interval' ), 'every_12_hours' ); ?>><?php esc_html_e( 'Every 12 Hours', 'natalie-auto-poster' ); ?></option>
                                    <option value="daily" <?php selected( get_option( 'nap_fetch_interval' ), 'daily' ); ?>><?php esc_html_e( 'Daily', 'natalie-auto-poster' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Articles Per Run', 'natalie-auto-poster' ); ?></th>
                            <td>
                                <input type="number" name="nap_articles_per_run" value="<?php echo esc_attr( get_option( 'nap_articles_per_run', 3 ) ); ?>" min="1" max="20" class="small-text" />
                                <p class="description"><?php esc_html_e( 'Maximum new articles to process per source per run.', 'natalie-auto-poster' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="nap-settings-section">
                    <h2><?php esc_html_e( 'Post Settings', 'natalie-auto-poster' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'Default Post Status', 'natalie-auto-poster' ); ?></th>
                            <td>
                                <select name="nap_default_post_status">
                                    <option value="draft" <?php selected( get_option( 'nap_default_post_status', 'draft' ), 'draft' ); ?>><?php esc_html_e( 'Draft', 'natalie-auto-poster' ); ?></option>
                                    <option value="publish" <?php selected( get_option( 'nap_default_post_status' ), 'publish' ); ?>><?php esc_html_e( 'Published', 'natalie-auto-poster' ); ?></option>
                                    <option value="pending" <?php selected( get_option( 'nap_default_post_status' ), 'pending' ); ?>><?php esc_html_e( 'Pending Review', 'natalie-auto-poster' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Auto Publish', 'natalie-auto-poster' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="nap_auto_publish" value="1" <?php checked( get_option( 'nap_auto_publish' ), 1 ); ?> />
                                    <?php esc_html_e( 'Automatically publish posts after processing', 'natalie-auto-poster' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Default Author', 'natalie-auto-poster' ); ?></th>
                            <td>
                                <?php
                                wp_dropdown_users( array(
                                    'name'     => 'nap_default_post_author',
                                    'selected' => get_option( 'nap_default_post_author', 1 ),
                                    'show_option_none' => false,
                                ) );
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Default Category', 'natalie-auto-poster' ); ?></th>
                            <td>
                                <?php
                                wp_dropdown_categories( array(
                                    'name'             => 'nap_default_category',
                                    'selected'         => get_option( 'nap_default_category', 0 ),
                                    'show_option_none' => __( '— Select Category —', 'natalie-auto-poster' ),
                                    'option_none_value' => 0,
                                ) );
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Default Tags', 'natalie-auto-poster' ); ?></th>
                            <td>
                                <input type="text" name="nap_default_tags" value="<?php echo esc_attr( get_option( 'nap_default_tags' ) ); ?>" class="regular-text" placeholder="jepang, musik, hiburan" />
                                <p class="description"><?php esc_html_e( 'Comma-separated tags to add to all posts.', 'natalie-auto-poster' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Show Source Attribution', 'natalie-auto-poster' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="nap_show_attribution" value="1" <?php checked( get_option( 'nap_show_attribution', 1 ), 1 ); ?> />
                                    <?php esc_html_e( 'Add source link at the bottom of each post', 'natalie-auto-poster' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Attribution Template', 'natalie-auto-poster' ); ?></th>
                            <td>
                                <input type="text" name="nap_attribution_template"
                                       value="<?php echo esc_attr( get_option( 'nap_attribution_template', '<p class="nap-source"><em>Sumber: <a href="{url}" target="_blank" rel="noopener noreferrer">{site}</a></em></p>' ) ); ?>"
                                       class="large-text" />
                                <p class="description"><?php esc_html_e( 'Use {url}, {site}, {title} as placeholders.', 'natalie-auto-poster' ); ?></p>
                            </td>
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
