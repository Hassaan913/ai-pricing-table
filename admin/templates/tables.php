<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

ob_start();
?>
<h1>Show All Tables</h1>
<p>Manage the hidden pricing table records that power your shortcodes.</p>

<div class="ai-admin-card">
    <?php if ( empty( $tables ) ) : ?>
        <p>No pricing tables found yet.</p>
        <p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=ai_pricing_add_new' ) ); ?>">Create Your First Table</a></p>
    <?php else : ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Shortcode</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $tables as $table ) : ?>
                    <?php
                    $edit_url   = add_query_arg(
                        [
                            'page'     => 'ai_pricing_add_new',
                            'table_id' => $table->ID,
                        ],
                        admin_url( 'admin.php' )
                    );
                    $delete_url = wp_nonce_url(
                        add_query_arg(
                            [
                                'page'              => 'ai_pricing_tables',
                                'ai_pricing_action' => 'delete_table',
                                'table_id'          => $table->ID,
                            ],
                            admin_url( 'admin.php' )
                        ),
                        'ai_pricing_delete_' . $table->ID
                    );
                    $duplicate_url = wp_nonce_url(
                        add_query_arg(
                            [
                                'page'              => 'ai_pricing_tables',
                                'ai_pricing_action' => 'duplicate_table',
                                'table_id'          => $table->ID,
                            ],
                            admin_url( 'admin.php' )
                        ),
                        'ai_pricing_duplicate_' . $table->ID
                    );
                    $shortcode = '[ai_pricing_table id="' . $table->ID . '"]';
                    ?>
                    <tr>
                        <td><?php echo esc_html( $table->post_title ); ?></td>
                        <td>
                            <code><?php echo esc_html( $shortcode ); ?></code>
                            <button
                                type="button"
                                class="button button-small ai-copy-shortcode"
                                data-shortcode="<?php echo esc_attr( $shortcode ); ?>"
                            >Copy</button>
                            <div><small>Paste this shortcode into any page, post, or widget.</small></div>
                        </td>
                        <td><?php echo esc_html( get_the_date( '', $table ) ); ?></td>
                        <td>
                            <a class="button button-small" href="<?php echo esc_url( $edit_url ); ?>">Edit</a>
                            <a class="button button-small" href="<?php echo esc_url( $duplicate_url ); ?>">Duplicate</a>
                            <a class="button button-small" href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('Delete this pricing table?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<script>
(function () {
    function copyText(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }
        const textarea = document.createElement("textarea");
        textarea.value = text;
        textarea.setAttribute("readonly", "");
        textarea.style.position = "absolute";
        textarea.style.left = "-9999px";
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand("copy");
        } catch (e) {}
        document.body.removeChild(textarea);
        return Promise.resolve();
    }

    document.addEventListener("click", function (event) {
        const button = event.target && event.target.closest ? event.target.closest(".ai-copy-shortcode") : null;
        if (!button) return;
        const shortcode = button.getAttribute("data-shortcode") || "";
        if (!shortcode) return;

        const original = button.textContent;
        button.disabled = true;
        button.textContent = "Copying...";
        copyText(shortcode).then(function () {
            button.textContent = "Copied";
            setTimeout(function () {
                button.disabled = false;
                button.textContent = original;
            }, 900);
        }).catch(function () {
            button.disabled = false;
            button.textContent = original;
        });
    });
})();
</script>
<?php
$content = ob_get_clean();

include __DIR__ . '/layout.php';
