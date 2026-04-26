<?php
namespace AI_Pricing_Table;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CPT {

    public function __construct() {
        add_action( 'wp_ajax_ai_generate_pricing', [ $this, 'ajax_generate_pricing' ] );
    }

    public function ajax_generate_pricing() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized request.' ], 403 );
        }

        check_ajax_referer( 'ai_pricing_generate', 'nonce' );

        $ai = new AI();

        $business = sanitize_text_field( $_POST['business'] ?? '' );
        $audience = sanitize_text_field( $_POST['audience'] ?? '' );
        $features = sanitize_textarea_field( $_POST['features'] ?? '' );

        if ( '' === trim( $business ) && '' === trim( $audience ) && '' === trim( $features ) ) {
            wp_send_json_error(
                [ 'message' => 'Please fill at least one field (Business Name, Target Audience, or Main Features) before generating.' ],
                400
            );
        }

        $info = [
            'business_name' => $business,
            'audience'      => $audience,
            'features'      => $features,
            'type'          => 'SaaS',
        ];

        $result = $ai->generate_pricing( $info );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error(
                [ 'message' => $result->get_error_message() ],
                400
            );
        }

        wp_send_json_success(
            [
                'pricing' => $result,
                'json'    => wp_json_encode( $result ),
            ]
        );
    }
}
