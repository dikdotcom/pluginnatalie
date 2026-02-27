<?php
/**
 * Articles list view for Natalie Auto Poster
 *
 * @package Natalie_Auto_Poster
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap nap-wrap">
    <h1><?php esc_html_e( 'Processed Articles', 'natalie-auto-poster' ); ?></h1>

    <?php if ( ! empty( $articles ) ) : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:40px;">ID</th>
                    <th><?php esc_html_e( 'Title', 'natalie-auto-poster' ); ?></th>
                    <th style="width:120px;"><?php esc_html_e( 'Source', 'natalie-auto-poster' ); ?></th>
                    <th style="width:100px;"><?php esc_html_e( 'Status', 'natalie-auto-poster' ); ?></th>
                    <th style="width:80px;"><?php esc_html_e( 'WP Post', 'natalie-auto-poster' ); ?></th>
                    <th style="width:150px;"><?php esc_html_e( 'Date', 'natalie-auto-poster' ); ?></th>
                    <th style="width:100px;"><?php esc_html_e( 'Actions', 'natalie-auto-poster' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $articles as $article ) : ?>
                    <tr>
                        <td><?php echo esc_html( $article->id ); ?></td>
                        <td>
                            <strong>
                                <a href="<?php echo esc_url( $article->source_url ); ?>" target="_blank" rel="noopener">
                                    <?php echo esc_html( $article->translated_title ?: $article->original_title ); ?>
                                </a>
                            </strong>
                            <?php if ( $article->error_message ) : ?>
                                <br><small class="nap-error-text"><?php echo esc_html( $article->error_message ); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html( $article->source_site ); ?></td>
                        <td>
                            <?php
                            $status_class = array(
                                'posted'     => 'success',
                                'error'      => 'error',
                                'pending'    => 'warning',
                                'fetching'   => 'info',
                                'translating' => 'info',
                                'reviewing'  => 'info',
                                'fetched'    => 'info',
                                'translated' => 'info',
                                'reviewed'   => 'info',
                            );
                            $class = $status_class[ $article->status ] ?? 'warning';
                            ?>
                            <span class="nap-badge nap-badge-<?php echo esc_attr( $class ); ?>">
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
                        <td>
                            <?php if ( $article->status === 'error' ) : ?>
                                <button class="button button-small nap-retry-btn"
                                        data-url="<?php echo esc_attr( $article->source_url ); ?>"
                                        data-source="<?php echo esc_attr( $article->source_site ); ?>">
                                    <?php esc_html_e( 'Retry', 'natalie-auto-poster' ); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ( $total_pages > 1 ) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links( array(
                        'base'      => add_query_arg( 'paged', '%#%' ),
                        'format'    => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total'     => $total_pages,
                        'current'   => $page,
                    ) );
                    ?>
                </div>
            </div>
        <?php endif; ?>

    <?php else : ?>
        <p class="nap-empty-state">
            <?php esc_html_e( 'No articles found.', 'natalie-auto-poster' ); ?>
        </p>
    <?php endif; ?>
</div>
