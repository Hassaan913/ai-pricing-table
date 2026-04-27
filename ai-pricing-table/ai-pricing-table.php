<?php
/**
 * Plugin Name:       AI Pricing Table
 * Plugin URI:        http://localhost/ai-pricing-table
 * Description:       Smart AI-powered pricing tables for WordPress. Generate beautiful, conversion-optimized pricing tables using free AI (Google Gemini + Ollama).
 * Version:           1.0.0
 * Author:            Hassaan
 * Author URI:        http://localhost/ai-pricing-table
 * License:           GPL-2.0+
 * Text Domain:       ai-pricing-table
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      8.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants (global, outside namespace)
define( 'AI_PRICING_TABLE_VERSION', '1.0.0' );
define( 'AI_PRICING_TABLE_FILE', __FILE__ );
define( 'AI_PRICING_TABLE_PATH', plugin_dir_path( __FILE__ ) );
define( 'AI_PRICING_TABLE_URL', plugin_dir_url( __FILE__ ) );
define( 'AI_PRICING_TABLE_BASENAME', plugin_basename( __FILE__ ) );

// Load Composer autoloader if it exists
if ( file_exists( AI_PRICING_TABLE_PATH . 'vendor/autoload.php' ) ) {
    require_once AI_PRICING_TABLE_PATH . 'vendor/autoload.php';
}

// Load main class file
require_once AI_PRICING_TABLE_PATH . 'includes/class-main.php';

// Use global function (outside any namespace) to avoid the callback error
function ai_pricing_table_init() {
    if ( class_exists( '\AI_Pricing_Table\Main' ) ) {
        \AI_Pricing_Table\Main::get_instance();
    } else {
        // Safety fallback notice
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible"><p><strong>AI Pricing Table Error:</strong> Main class not found. Please check includes/class-main.php</p></div>';
        });
    }
}
add_action( 'plugins_loaded', 'ai_pricing_table_init' );