<?php

namespace AI_Pricing_Table;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Templates {

    public static function get_default_template_key() {
        return 'basic_blue';
    }

    public static function get_templates() {
        return [
            'basic_blue' => [
                'name'         => 'Basic Blue',
                'description'  => 'Clean SaaS cards with bright contrast and a familiar conversion-first structure.',
                'layout'       => 'cards',
                'plan'         => 'free',
                'requires_pro' => false,
                'preview'      => [
                    'bg'      => '#0f172a',
                    'surface' => '#ffffff',
                    'accent'  => '#2563eb',
                    'text'    => '#e2e8f0',
                ],
            ],
            'modern_green' => [
                'name'         => 'Modern Green',
                'description'  => 'A sharper operator-style palette for product-led and growth-oriented pricing pages.',
                'layout'       => 'cards',
                'plan'         => 'free',
                'requires_pro' => false,
                'preview'      => [
                    'bg'      => '#052e2b',
                    'surface' => '#f8fffb',
                    'accent'  => '#10b981',
                    'text'    => '#d1fae5',
                ],
            ],
            'slate_minimal' => [
                'name'         => 'Slate Minimal',
                'description'  => 'Quiet neutral styling for B2B tools that need dense information without noise.',
                'layout'       => 'cards',
                'plan'         => 'free',
                'requires_pro' => false,
                'preview'      => [
                    'bg'      => '#f3f6fa',
                    'surface' => '#ffffff',
                    'accent'  => '#334155',
                    'text'    => '#0f172a',
                ],
            ],
            'sunset_coral' => [
                'name'         => 'Sunset Coral',
                'description'  => 'Warm editorial contrast for creator tools, agencies, and product showcases.',
                'layout'       => 'cards',
                'plan'         => 'free',
                'requires_pro' => false,
                'preview'      => [
                    'bg'      => '#3f1d2e',
                    'surface' => '#fff7f6',
                    'accent'  => '#f97360',
                    'text'    => '#fde7e2',
                ],
            ],
            'midnight_cyan' => [
                'name'         => 'Midnight Cyan',
                'description'  => 'Dark presentation with cool cyan accents for modern infrastructure and AI products.',
                'layout'       => 'cards',
                'plan'         => 'free',
                'requires_pro' => false,
                'preview'      => [
                    'bg'      => '#061621',
                    'surface' => '#0f2531',
                    'accent'  => '#06b6d4',
                    'text'    => '#d7fbff',
                ],
            ],
            'dark_pro' => [
                'name'         => 'Dark Pro',
                'description'  => 'Premium dark pricing with strong emphasis for flagship plan positioning.',
                'layout'       => 'cards',
                'plan'         => 'pro',
                'requires_pro' => true,
                'preview'      => [
                    'bg'      => '#020617',
                    'surface' => '#111827',
                    'accent'  => '#f97316',
                    'text'    => '#e5eefc',
                ],
            ],
            'royal_amethyst' => [
                'name'         => 'Royal Amethyst',
                'description'  => 'High-contrast premium look for polished launches and pro-tier positioning.',
                'layout'       => 'cards',
                'plan'         => 'pro',
                'requires_pro' => true,
                'preview'      => [
                    'bg'      => '#1f1338',
                    'surface' => '#f8f4ff',
                    'accent'  => '#8b5cf6',
                    'text'    => '#efe8ff',
                ],
            ],
            'ember_gold' => [
                'name'         => 'Ember Gold',
                'description'  => 'Warm, assertive premium styling for high-ticket offers and enterprise bundles.',
                'layout'       => 'cards',
                'plan'         => 'pro',
                'requires_pro' => true,
                'preview'      => [
                    'bg'      => '#261509',
                    'surface' => '#fffaf1',
                    'accent'  => '#f59e0b',
                    'text'    => '#ffefc7',
                ],
            ],
            'glass_frost' => [
                'name'         => 'Glass Frost',
                'description'  => 'Light glassmorphism-inspired skin for polished product marketing surfaces.',
                'layout'       => 'cards',
                'plan'         => 'pro',
                'requires_pro' => true,
                'preview'      => [
                    'bg'      => '#dbeafe',
                    'surface' => '#ffffff',
                    'accent'  => '#0f766e',
                    'text'    => '#0f172a',
                ],
            ],
            'contrast_crimson' => [
                'name'         => 'Contrast Crimson',
                'description'  => 'Bold high-contrast premium styling for urgent offers and hard-sell campaigns.',
                'layout'       => 'cards',
                'plan'         => 'pro',
                'requires_pro' => true,
                'preview'      => [
                    'bg'      => '#2a0b12',
                    'surface' => '#fff5f5',
                    'accent'  => '#e11d48',
                    'text'    => '#ffe2e8',
                ],
            ],
        ];
    }

    public static function get_free_templates() {
        return array_filter(
            self::get_templates(),
            static function ( $template ) {
                return empty( $template['requires_pro'] );
            }
        );
    }

    public static function get_pro_templates() {
        return array_filter(
            self::get_templates(),
            static function ( $template ) {
                return ! empty( $template['requires_pro'] );
            }
        );
    }

    public static function is_valid_template( $key ) {
        $key = sanitize_key( (string) $key );

        return isset( self::get_templates()[ $key ] );
    }

    public static function is_pro_template( $key ) {
        $key = sanitize_key( (string) $key );

        if ( ! self::is_valid_template( $key ) ) {
            return false;
        }

        return ! empty( self::get_templates()[ $key ]['requires_pro'] );
    }

    public static function sanitize_template_key( $key, $is_pro = false ) {
        $key = sanitize_key( (string) $key );

        if ( ! self::is_valid_template( $key ) ) {
            return self::get_default_template_key();
        }

        if ( self::is_pro_template( $key ) && ! $is_pro ) {
            return self::get_default_template_key();
        }

        return $key;
    }
}
