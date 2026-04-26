<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$table_count = wp_count_posts( 'ai_pricing_table' );
$published   = isset( $table_count->publish ) ? intval( $table_count->publish ) : 0;

$content = '
    <h1>Overview</h1>
    <p>Manage pricing tables from a custom plugin workflow. The WordPress post editor is no longer part of the main experience.</p>
    <div class="ai-admin-card">
        <h2>Current Status</h2>
        <p><strong>Saved Tables:</strong> ' . esc_html( $published ) . '</p>
        <p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=ai_pricing_add_new' ) ) . '">Create New Table</a></p>
        <p><a class="button" href="' . esc_url( admin_url( 'admin.php?page=ai_pricing_tables' ) ) . '">View All Tables</a></p>
    </div>
';

include __DIR__ . '/layout.php';
