<?php
namespace AI_Pricing_Table;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI {

    /**
     * Main entry
     */
    public function generate_pricing( $business_info ) {

        $prompt = $this->build_prompt( $business_info );

        // Try Gemini first
        $response = $this->call_gemini( $prompt );

        if ( is_wp_error( $response ) ) {
            // fallback to Ollama
            $response = $this->call_ollama( $prompt );
        }

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $parsed = $this->parse_ai_response( $response );

        // 🔁 Retry once if failed
        if ( is_wp_error( $parsed ) ) {
            $retry_prompt = $prompt . "\n\nIMPORTANT: Return ONLY COMPLETE valid JSON. No truncation.";

            $response = $this->call_gemini( $retry_prompt );

            if ( ! is_wp_error( $response ) ) {
                $parsed = $this->parse_ai_response( $response );
            }
        }

        return $parsed;
    }

    /**
     * Prompt Builder (Optimized)
     */
    private function build_prompt( $info ) {

        return "You are a professional SaaS pricing strategist.

Business Name: " . ($info['business_name'] ?? 'My Product') . "
Target Audience: " . ($info['audience'] ?? 'General users') . "
Main Features: " . ($info['features'] ?? 'AI-powered tool') . "
Business Type: " . ($info['type'] ?? 'SaaS') . "

Generate a pricing table with EXACTLY 3 or 4 tiers.

STRICT RULES:
- Return ONLY valid JSON
- No explanations
- No markdown
- Ensure JSON is COMPLETE (no truncation)
- Max 5 features per tier

FORMAT:
{
  \"tiers\": [
    {
      \"name\": \"Free\",
      \"price_monthly\": 0,
      \"price_yearly\": 0,
      \"billing_text\": \"Forever free\",
      \"highlight\": false,
      \"features\": [\"Feature 1\", \"Feature 2\"],
      \"button_text\": \"Get Started\",
      \"button_url\": \"#\"
    }
  ],
  \"recommended_tier\": \"Pro\",
  \"currency\": \"USD\"
}";
    }

    /**
     * Gemini API
     */
    private function call_gemini( $prompt ) {

        $api_key = get_option( 'ai_pricing_gemini_key', '' );

        if ( empty( $api_key ) ) {
            return new \WP_Error( 'no_key', 'Gemini API key not configured.' );
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $api_key;

        $body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature'      => 0.4,
                'maxOutputTokens'  => 3000,
                'responseMimeType' => 'application/json'
            ]
        ];

        $response = wp_remote_post( $url, [
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( $body ),
            'timeout' => 30
        ]);

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body_content = wp_remote_retrieve_body( $response );
        $data = json_decode( $body_content, true );

        return $data['candidates'][0]['content']['parts'][0]['text']
            ?? new \WP_Error( 'gemini_error', 'Invalid Gemini response.' );
    }

    /**
     * Ollama API (Local)
     */
    private function call_ollama( $prompt ) {

        $url = 'http://localhost:11434/api/generate';

        $body = [
            'model'  => 'llama3.2',
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'temperature' => 0.6,
                'num_predict' => 2000 // 🔥 prevents truncation
            ]
        ];

        $response = wp_remote_post( $url, [
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( $body ),
            'timeout' => 45
        ]);

        if ( is_wp_error( $response ) ) {
            return new \WP_Error( 'ollama_error', 'Ollama not running.' );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        return $data['response']
            ?? new \WP_Error( 'ollama_error', 'Empty Ollama response.' );
    }

    /**
     * 🔥 ADVANCED PARSER (Bulletproof)
     */
    private function parse_ai_response( $ai_text ) {

        if ( empty( $ai_text ) || ! is_string( $ai_text ) ) {
            return new \WP_Error( 'empty', 'Empty AI response.' );
        }

        // Remove markdown
        $ai_text = preg_replace( '/```(?:json)?/i', '', $ai_text );
        $ai_text = str_replace( '```', '', $ai_text );
        $ai_text = trim( $ai_text );

        // Extract JSON block
        if ( preg_match( '/(\{[\s\S]*\})/', $ai_text, $matches ) ) {
            $json_str = $matches[1];
        } else {
            $json_str = $ai_text;
        }

        // Fix trailing commas
        $json_str = preg_replace( '/,\s*([\]}])/m', '$1', $json_str );

        // 🔍 Check balance
        if (
            substr_count($json_str, '{') !== substr_count($json_str, '}') ||
            substr_count($json_str, '[') !== substr_count($json_str, ']')
        ) {
            return new \WP_Error(
                'incomplete_json',
                'AI response incomplete. Please retry.'
            );
        }

        $json = json_decode( $json_str, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new \WP_Error(
                'json_error',
                'JSON Error: ' . json_last_error_msg()
            );
        }

        // Validate structure
        if ( ! isset( $json['tiers'] ) || ! is_array( $json['tiers'] ) ) {
            return new \WP_Error(
                'invalid_structure',
                'Invalid pricing format returned.'
            );
        }

        return $json;
    }

}