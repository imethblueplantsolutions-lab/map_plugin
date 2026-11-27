<?php
/**
 * Uninstall cleanup for MyProperties Listing Map.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'mplm_settings' );
delete_transient( 'mplm_live_counts_v1_all' );
delete_transient( 'mplm_live_counts_v1_active' );

$upload_dir = wp_upload_dir();
if ( ! empty( $upload_dir['basedir'] ) ) {
    $plugin_upload_dir = trailingslashit( $upload_dir['basedir'] ) . 'myproperties-listing-map';
    $uploaded_svg      = trailingslashit( $plugin_upload_dir ) . 'lk.svg';
    if ( file_exists( $uploaded_svg ) ) {
        wp_delete_file( $uploaded_svg );
    }
    if ( is_dir( $plugin_upload_dir ) ) {
        @rmdir( $plugin_upload_dir );
    }
}
