<?php

namespace AI_Pricing_Table;

if (!defined('ABSPATH')) exit;

class Templates {

    public static function get_templates() {
        return [
            'basic_blue' => [
                'name'  => 'Basic Blue',
                'image' => plugin_dir_url(__FILE__) . '../assets/templates/basic-blue.png',
                'pro'   => false,
            ],
            'modern_green' => [
                'name'  => 'Modern Green',
                'image' => plugin_dir_url(__FILE__) . '../assets/templates/modern-green.png',
                'pro'   => false,
            ],
            'dark_pro' => [
                'name'  => 'Dark Pro',
                'image' => plugin_dir_url(__FILE__) . '../assets/templates/dark-pro.png',
                'pro'   => true, // locked
            ],
        ];
    }
}