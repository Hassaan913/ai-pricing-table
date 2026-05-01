<?php
namespace AI_Pricing_Table;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Post_Type {

    public function hooks() {
        add_action( 'init', [ $this, 'register' ] );
    }

    public function register() {
        $labels = [
            'name'          => 'Pricing Tables',
            'singular_name' => 'Pricing Table',
        ];

        register_post_type(
            'ai_pricing_table',
            [
                'labels'              => $labels,
                'public'              => false,
                'show_ui'             => false,
                'show_in_menu'        => false,
                'show_in_admin_bar'   => false,
                'show_in_nav_menus'   => false,
                'publicly_queryable'  => false,
                'exclude_from_search' => true,
                'supports'            => [ 'title' ],
                'has_archive'         => false,
                'rewrite'             => false,
            ]
        );
    }
}

