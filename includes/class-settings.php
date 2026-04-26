<?php
namespace AI_Pricing_Table;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Settings Page Class
 */
class Settings {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Register settings fields
     */
    public function register_settings() {
        register_setting( 'ai_pricing_table_settings', 'ai_pricing_gemini_key', [ $this, 'sanitize_api_key' ] );
    }

    /**
     * Sanitize API key
     *
     * @param string $value API key.
     * @return string
     */
    public function sanitize_api_key( $value ) {
        return sanitize_text_field( trim( $value ) );
    }

    /**
     * Render Settings Page
     */
    public function settings_page() {
        $gemini_key = get_option( 'ai_pricing_gemini_key', '' );
        $business   = $_POST['test_business'] ?? 'AI Content Writer Pro';
        $audience   = $_POST['test_audience'] ?? 'Freelancers and bloggers';
        $features   = $_POST['test_features'] ?? 'AI article writing, SEO optimization, Plagiarism checker, Team collaboration';
        ?>
        <div class="wrap">
            <h1>AI Pricing Table Settings</h1>

            <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
                <h2>1. Google Gemini API Key</h2>
                <p>Get your free API key from: <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener noreferrer">Google AI Studio</a></p>

                <form method="post" action="options.php">
                    <?php settings_fields( 'ai_pricing_table_settings' ); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Gemini API Key</th>
                            <td>
                                <input
                                    type="password"
                                    name="ai_pricing_gemini_key"
                                    value="<?php echo esc_attr( $gemini_key ); ?>"
                                    class="regular-text"
                                    placeholder="AIzaSy..."
                                />
                                <p class="description">Leave empty to use only Ollama locally.</p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button( 'Save API Key' ); ?>
                </form>
            </div>

            <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
                <h2>2. Test AI Generation</h2>
                <form method="post" action="">
                    <?php wp_nonce_field( 'ai_test_nonce', 'ai_test_nonce' ); ?>

                    <p>
                        <strong>Business Name:</strong><br>
                        <input type="text" name="test_business" value="<?php echo esc_attr( $business ); ?>" class="regular-text" />
                    </p>

                    <p>
                        <strong>Target Audience:</strong><br>
                        <input type="text" name="test_audience" value="<?php echo esc_attr( $audience ); ?>" class="regular-text" />
                    </p>

                    <p>
                        <strong>Main Features:</strong><br>
                        <textarea name="test_features" rows="3" class="large-text"><?php echo esc_textarea( $features ); ?></textarea>
                    </p>

                    <input type="hidden" name="ai_test_action" value="1" />
                    <?php submit_button( 'Test AI Generation', 'primary', 'submit', false ); ?>
                </form>
            </div>

            <?php
            if ( isset( $_POST['ai_test_action'] ) && check_admin_referer( 'ai_test_nonce', 'ai_test_nonce' ) ) {
                $this->handle_test();
            }
            ?>
        </div>
        <?php
    }

    /**
     * Handle AI Test
     */
    private function handle_test() {
        $business_info = [
            'business_name' => sanitize_text_field( $_POST['test_business'] ?? '' ),
            'audience'      => sanitize_text_field( $_POST['test_audience'] ?? '' ),
            'features'      => sanitize_textarea_field( $_POST['test_features'] ?? '' ),
            'type'          => 'SaaS',
        ];

        $ai     = new AI();
        $result = $ai->generate_pricing( $business_info );

        echo '<div class="card" style="max-width:800px; margin-top:30px; padding:20px; background:#f8f9fa;">';
        echo '<h2>Test Result</h2>';

        if ( is_wp_error( $result ) ) {
            echo '<div style="color:red;"><strong>Error:</strong> ' . esc_html( $result->get_error_message() ) . '</div>';
            echo '<p><strong>Tip:</strong> Add a Gemini API key or start Ollama locally.</p>';
        } else {
            echo '<pre style="background:#fff; padding:15px; border:1px solid #ddd; overflow:auto;">';
            print_r( $result );
            echo '</pre>';

            echo '<p style="color:green;"><strong>Success:</strong> AI returned structured pricing data.</p>';

            $post_id = wp_insert_post( [
                'post_type'   => 'ai_pricing_table',
                'post_title'  => $business_info['business_name'] . ' Pricing',
                'post_status' => 'publish',
            ] );

            if ( $post_id && ! is_wp_error( $post_id ) ) {
                update_post_meta( $post_id, '_ai_pricing_json', wp_json_encode( $result ) );
                update_post_meta( $post_id, '_ai_pricing_mode', 'ai' );
                update_post_meta( $post_id, '_ai_template', 'basic_blue' );

                echo '<p style="color:green;"><strong>Saved to hidden storage successfully.</strong></p>';
                echo '<p>Post ID: ' . intval( $post_id ) . '</p>';
            } else {
                echo '<p style="color:red;"><strong>Failed to save hidden pricing table record.</strong></p>';
            }
        }

        echo '</div>';
    }
}
