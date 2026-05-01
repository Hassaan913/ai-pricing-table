<?php
/**
 * Low-level sanitize helpers for manual builder IDs.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Sanitize manual builder IDs.
 *
 * @param mixed  $value Raw identifier.
 * @param string $prefix Fallback prefix.
 * @param int    $index Fallback index.
 * @return string
 */
function ai_pricing_sanitize_manual_id( $value, $prefix, $index ) {
    $value = sanitize_key( (string) $value );

    if ( '' !== $value ) {
        return $value;
    }

    return sanitize_key( $prefix . '_' . $index );
}

/**
 * Build a stable matrix key for manual tables.
 *
 * @param string $plan_id Plan identifier.
 * @param string $feature_id Feature identifier.
 * @return string
 */
function ai_pricing_manual_matrix_key( $plan_id, $feature_id ) {
    return sanitize_key( $plan_id ) . '::' . sanitize_key( $feature_id );
}

/**
 * Normalize boolean-like values.
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function ai_pricing_normalize_bool( $value ) {
    if ( is_bool( $value ) ) {
        return $value;
    }

    if ( is_numeric( $value ) ) {
        return (bool) intval( $value );
    }

    return in_array( strtolower( (string) $value ), [ '1', 'true', 'yes', 'on' ], true );
}
