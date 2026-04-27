<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$current_page   = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
$current_post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : '';
?>
<div class="ai-admin-wrapper">

    <div class="ai-admin-sidebar">
        <ul>
            <li class="<?php echo ($current_page == 'ai_pricing_main') ? 'active' : ''; ?>">
                <a href="<?php echo admin_url('admin.php?page=ai_pricing_main'); ?>">Overview</a>
            </li>

            <li class="<?php echo ($current_page == 'ai_pricing_add_new') ? 'active' : ''; ?>">
                <a href="<?php echo admin_url('admin.php?page=ai_pricing_add_new'); ?>">Add New Table</a>
            </li>

            <li class="<?php echo ($current_page == 'ai_pricing_tables') ? 'active' : ''; ?>">
                <a href="<?php echo admin_url('admin.php?page=ai_pricing_tables'); ?>">Show All Tables</a>
            </li>

            <li class="<?php echo ($current_page == 'ai_pricing_settings') ? 'active' : ''; ?>">
                <a href="<?php echo admin_url('admin.php?page=ai_pricing_settings'); ?>">Settings</a>
            </li>

            <li class="<?php echo ($current_page == 'ai_pricing_import_export') ? 'active' : ''; ?>">
                <a href="<?php echo admin_url('admin.php?page=ai_pricing_import_export'); ?>">Import / Export</a>
            </li>
        </ul>
    </div>

    <div class="ai-admin-content">
        <?php echo $content; ?>
    </div>

</div>
