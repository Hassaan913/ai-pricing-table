<?php
namespace AI_Pricing_Table;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Shortcode {

    public function __construct() {
        add_shortcode( 'ai_pricing_table', [ $this, 'render' ] );
    }

    public function render( $atts ) {
        $atts = shortcode_atts( [
            'id' => 0,
        ], $atts );

        $post_id = empty( $atts['id'] ) ? $this->get_latest_table_id() : intval( $atts['id'] );

        if ( empty( $post_id ) ) {
            return 'No pricing tables found.';
        }

        $manual   = get_post_meta( $post_id, '_ai_pricing_data', true );
        $ai       = get_post_meta( $post_id, '_ai_pricing_json', true );
        $template = get_post_meta( $post_id, '_ai_template', true );
        $mode     = get_post_meta( $post_id, '_ai_pricing_mode', true );

        if ( empty( $template ) ) {
            $template = 'basic_blue';
        }

        if ( ! in_array( $mode, [ 'ai', 'manual' ], true ) ) {
            $mode = ! empty( $manual ) ? 'manual' : 'ai';
        }

        if ( 'manual' === $mode ) {
            $rendered = \ai_pricing_render_manual_table( $manual, $template );

            if ( 'Invalid manual data.' !== $rendered || empty( $ai ) ) {
                return $rendered;
            }
        }

        return \ai_pricing_render_ai_table( $ai, $template );
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
