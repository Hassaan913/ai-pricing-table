<?php
/**
 * Uninstall handler for AI Pricing Table.
 *
 * Deletes plugin option(s) and hidden pricing table posts.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Plugin option(s).
delete_option( 'ai_pricing_gemini_key' );

// Delete hidden CPT posts (and their postmeta).
$tables = get_posts( [
    'post_type'      => 'ai_pricing_table',
    'post_status'    => 'any',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'no_found_rows'  => true,
] );

foreach ( $tables as $table_id ) {
    wp_delete_post( $table_id, true );
}

