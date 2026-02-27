<?php
/**
 * Dashboard view for Natalie Auto Poster
 *
 * @package Natalie_Auto_Poster
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap nap-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-rss"></span>
        <?php esc_html_e( 'Natalie Auto Poster', 'natalie-auto-poster' ); ?>
    </h1>
    <hr class="wp-header-end">

    <!-- Stats Cards -->
    <div class="nap-stats-grid">
        <div class="nap-stat-card">
            <div class="nap-stat-number"><?php echo esc_html( $stats['total'] ); ?></div>
            <div class="nap-stat-label"><?php esc_html_e( 'Total Articles', 'natalie-auto-poster' ); ?></div>
        </div>
        <div class="nap-stat-card nap-stat-success">
            <div class="nap-stat-number"><?php echo esc_html( $stats['posted'] ); ?></div>
            <div class="nap-stat-label"><?php esc_html_e( 'Posted', 'natalie-auto-poster' ); ?></div>
        </div>
        <div class="nap-stat-card nap-stat-warning">
            <div class="nap-stat-number"><?php echo esc_html( $stats['pending'] ); ?></div>
            <div class="nap-stat-label"><?php esc_html_e( 'Pending', 'natalie-auto-poster' ); ?></div>
        </div>
        <div class="nap-stat-card nap-stat-error">
            <div class="nap-stat-number"><?php echo esc_html( $stats['error'] ); ?></div>
            <div class="nap-stat-label"><?php esc_html_e( 'Errors', 'natalie-auto-poster' ); ?></div>
        </div>
    </div>

    <div class="nap-dashboard-grid">
        <!-- Quick Actions -->
        <div class="nap-card">
            <h2><?php esc_html_e( 'Quick Actions', 'natalie-auto-poster' ); ?></h2>

            <div class="nap-action-group">
                <h3><?php esc_html_e( 'Manual Fetch', 'natalie-auto-poster' ); ?></h3>
                <p><?php esc_html_e( 'Manually trigger article fetching from all active sources.', 'natalie-auto-poster' ); ?></p>
                <select id="nap-manual-source" class="regular-text">
                    <option value=""><?php esc_html_e( 'All Active Sources', 'natalie-auto-poster' ); ?></option>
                    <?php foreach ( NAP_Fetcher::get_available_sites() as $site ) : ?>
                        <option value="<?php echo esc_attr( $site ); ?>"><?php echo esc_html( $site ); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" id="nap-manual-limit" value="5" min="1" max="20" class="small-text" />
                <span class="description"><?php esc_html_e( 'articles', 'natalie-auto-poster' ); ?></span>
                <br><br>
                <button id="nap-btn-manual-fetch" class="button button-primary">
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e( 'Fetch Now', 'natalie-auto-poster' ); ?>
                </button>
                <span id="nap-fetch-status" class="nap-status-message"></span>
            </div>

            <hr>

            <div class="nap-action-group">
                <h3><?php esc_html_e( 'Process Single Article', 'natalie-auto-poster' ); ?></h3>
                <p><?php esc_html_e( 'Enter a specific article URL to process.', 'natalie-auto-poster' ); ?></p>
                <input type="url" id="nap-single-url" placeholder="https://natalie.mu/music/news/..." class="large-text" />
                <br><br>
                <select id="nap-single-source" class="regular-text">
                    <?php foreach ( NAP_Fetcher::get_available_sites() as $site ) : ?>
                        <option value="<?php echo esc_attr( $site ); ?>"><?php echo esc_html( $site ); ?></option>
                    <?php endforeach; ?>
                </select>
                <br><br>
                <button id="nap-btn-process-single" class="button button-secondary">
                    <span class="dashicons dashicons-media-text"></span>
                    <?php esc_html_e( 'Process Article', 'natalie-auto-poster' ); ?>
                </button>
                <span id="nap-single-status" class="nap-status-message"></span>
            </div>
        </div>

        <!-- Schedule Info -->
        <div class="nap-card">
            <h2><?php esc_html_e( 'Schedule Status', 'natalie-auto-poster' ); ?></h2>

            <table class="nap-info-table">
                <tr>
                    <th><?php esc_html_e( 'Auto Fetch', 'natalie-auto-poster' ); ?></th>
                    <td>
                        <?php if ( get_option( 'nap_auto_fetch_enabled', true ) ) : ?>
                            <span class="nap-badge nap-badge-success"><?php esc_html_e( 'Enabled', 'natalie-auto-poster' ); ?></span>
                        <?php else : ?>
                            <span class="nap-badge nap-badge-error"><?php esc_html_e( 'Disabled', 'natalie-auto-poster' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Fetch Interval', 'natalie-auto-poster' ); ?></th>
                    <td><?php echo esc_html( get_option( 'nap_fetch_interval', 'hourly' ) ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Last Fetch', 'natalie-auto-poster' ); ?></th>
                    <td><?php echo esc_html( $last_fetch ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Next Fetch', 'natalie-auto-poster' ); ?></th>
                    <td><?php echo esc_html( $next_fetch ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Active Sources', 'natalie-auto-poster' ); ?></th>
                    <td>
                        <?php if ( ! empty( $active_sources ) ) : ?>
                            <?php foreach ( $active_sources as $source ) : ?>
                                <span class="nap-badge nap-badge-info"><?php echo esc_html( $source ); ?></span>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <span class="nap-badge nap-badge-warning"><?php esc_html_e( 'None configured', 'natalie-auto-poster' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Post Status', 'natalie-auto-poster' ); ?></th>
                    <td><?php echo esc_html( get_option( 'nap_default_post_status', 'draft' ) ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Image Storage', 'natalie-auto-poster' ); ?></th>
                    <td><?php echo esc_html( get_option( 'nap_image_storage', 'wordpress' ) ); ?></td>
                </tr>
            </table>

            <p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=nap-settings' ) ); ?>" class="button">
                    <?php esc_html_e( 'Configure Settings', 'natalie-auto-poster' ); ?>
                </a>
            </p>
        </div>
    </div>

    <!-- Recent Articles -->
    <div class="nap-card nap-card-full">
        <h2>
            <?php esc_html_e( 'Recent Articles', 'natalie-auto-poster' ); ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=nap-articles' ) ); ?>" class="button button-small" style="margin-left:10px;">
                <?php esc_html_e( 'View All', 'natalie-auto-poster' ); ?>
            </a>
        </h2>

        <?php if ( ! empty( $recent_articles ) ) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Title', 'natalie-auto-poster' ); ?></th>
                        <th><?php esc_html_e( 'Source', 'natalie-auto-poster' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'natalie-auto-poster' ); ?></th>
                        <th><?php esc_html_e( 'WP Post', 'natalie-auto-poster' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'natalie-auto-poster' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $recent_articles as $article ) : ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url( $article->source_url ); ?>" target="_blank">
                                    <?php echo esc_html( $article->translated_title ?: $article->original_title ); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html( $article->source_site ); ?></td>
                            <td>
                                <span class="nap-badge nap-badge-<?php echo esc_attr( $article->status === 'posted' ? 'success' : ( $article->status === 'error' ? 'error' : 'warning' ) ); ?>">
                                    <?php echo esc_html( $article->status ); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ( $article->wp_post_id ) : ?>
                                    <a href="<?php echo esc_url( get_edit_post_link( $article->wp_post_id ) ); ?>">
                                        #<?php echo esc_html( $article->wp_post_id ); ?>
                                    </a>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html( $article->created_at ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p class="nap-empty-state">
                <?php esc_html_e( 'No articles processed yet. Configure your sources and click "Fetch Now" to get started.', 'natalie-auto-poster' ); ?>
            </p>
        <?php endif; ?>
    </div>
</div>
