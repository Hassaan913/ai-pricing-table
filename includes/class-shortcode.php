<?php
namespace AI_Pricing_Table;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Shortcode {

    private const SHORTCODE_TAG = 'ai_pricing_table';

    public function __construct() {
        add_shortcode( self::SHORTCODE_TAG, [ $this, 'render' ] );
    }

    public function render( $atts ) {
        $atts = shortcode_atts( [
            'id' => 0,
        ], $atts );

        $post_id = empty( $atts['id'] ) ? $this->get_latest_table_id() : intval( $atts['id'] );

        if ( empty( $post_id ) ) {
            return 'No pricing tables found. Create one in AI Pricing and embed it with [ai_pricing_table id="123"].';
        }

        $post = get_post( $post_id );

        if ( ! $post || 'ai_pricing_table' !== $post->post_type || 'publish' !== $post->post_status ) {
            return 'Pricing table not found. Check the shortcode ID and try again.';
        }

        $manual   = get_post_meta( $post_id, '_ai_pricing_data', true );
        $ai       = get_post_meta( $post_id, '_ai_pricing_json', true );
        $template = get_post_meta( $post_id, '_ai_template', true );
        $mode     = get_post_meta( $post_id, '_ai_pricing_mode', true );
        $is_pro   = function_exists( 'ai_pricing_table_is_pro' ) ? (bool) ai_pricing_table_is_pro() : false;

        $template = Templates::sanitize_template_key( $template, $is_pro );

        if ( ! in_array( $mode, [ 'ai', 'manual' ], true ) ) {
            $mode = ! empty( $manual ) ? 'manual' : 'ai';
        }

        if ( 'manual' === $mode ) {
            $rendered = \ai_pricing_render_manual_table( $manual, $template );

            if ( 'Invalid manual data.' !== $rendered || empty( $ai ) ) {
                return $rendered;
            }
        }

        $rendered = \ai_pricing_render_ai_table( $ai, $template );

        return 'No pricing data found.' === $rendered
            ? 'Pricing table data is incomplete. Edit and save the table again in AI Pricing.'
            : $rendered;
    }

    private function get_latest_table_id() {
        $query = new \WP_Query( [
            'post_type'      => 'ai_pricing_table',
            'posts_per_page' => 1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => 'publish',
        ] );

        if ( ! $query->have_posts() ) {
            return 0;
        }

        $query->the_post();
        $post_id = get_the_ID();
        wp_reset_postdata();

        return $post_id;
    }
}
