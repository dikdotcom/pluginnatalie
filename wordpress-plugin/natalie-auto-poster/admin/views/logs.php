<?php
/**
 * Logs view for Natalie Auto Poster
 *
 * @package Natalie_Auto_Poster
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap nap-wrap">
    <h1><?php esc_html_e( 'Activity Logs', 'natalie-auto-poster' ); ?></h1>

    <!-- Filter by level -->
    <div class="nap-log-filters">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=nap-logs' ) ); ?>"
           class="button <?php echo ! $level ? 'button-primary' : ''; ?>">
            <?php esc_html_e( 'All', 'natalie-auto-poster' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=nap-logs&level=info' ) ); ?>"
           class="button <?php echo $level === 'info' ? 'button-primary' : ''; ?>">
            <?php esc_html_e( 'Info', 'natalie-auto-poster' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=nap-logs&level=warning' ) ); ?>"
           class="button <?php echo $level === 'warning' ? 'button-primary' : ''; ?>">
            <?php esc_html_e( 'Warning', 'natalie-auto-poster' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=nap-logs&level=error' ) ); ?>"
           class="button <?php echo $level === 'error' ? 'button-primary' : ''; ?>">
            <?php esc_html_e( 'Error', 'natalie-auto-poster' ); ?>
        </a>
    </div>

    <?php if ( ! empty( $logs ) ) : ?>
        <table class="wp-list-table widefat fixed striped nap-logs-table">
            <thead>
                <tr>
                    <th style="width:150px;"><?php esc_html_e( 'Time', 'natalie-auto-poster' ); ?></th>
                    <th style="width:80px;"><?php esc_html_e( 'Level', 'natalie-auto-poster' ); ?></th>
                    <th style="width:80px;"><?php esc_html_e( 'Article', 'natalie-auto-poster' ); ?></th>
                    <th><?php esc_html_e( 'Message', 'natalie-auto-poster' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $logs as $log ) : ?>
                    <tr class="nap-log-<?php echo esc_attr( $log->level ); ?>">
                        <td><?php echo esc_html( $log->created_at ); ?></td>
                        <td>
                            <span class="nap-badge nap-badge-<?php echo esc_attr( $log->level === 'error' ? 'error' : ( $log->level === 'warning' ? 'warning' : 'info' ) ); ?>">
                                <?php echo esc_html( strtoupper( $log->level ) ); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ( $log->article_id ) : ?>
                                #<?php echo esc_html( $log->article_id ); ?>
                            <?php else : ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo esc_html( $log->message ); ?>
                            <?php if ( $log->context ) : ?>
                                <details>
                                    <summary><?php esc_html_e( 'Context', 'natalie-auto-poster' ); ?></summary>
                                    <pre><?php echo esc_html( $log->context ); ?></pre>
                                </details>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p class="nap-empty-state"><?php esc_html_e( 'No logs found.', 'natalie-auto-poster' ); ?></p>
    <?php endif; ?>
</div>
