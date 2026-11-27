<?php
/**
 * Plugin Name: MyProperties Listing Map
 * Plugin URI:  https://myproperties.lk
 * Description: Interactive Sri Lanka 25-district listing map for AdForest with live All/Sell/Rent ad counts, customizable colors, blue drop shadow, Province -> District -> Town panel, and automatic district search expansion.
 * Version:     1.0.0
 * Author:      MyProperties.lk
 * Text Domain: myproperties-listing-map
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MPLM_VERSION', '1.0.0' );
define( 'MPLM_PLUGIN_FILE', __FILE__ );
define( 'MPLM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MPLM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MPLM_OPTION_KEY', 'mplm_settings' );

require_once MPLM_PLUGIN_DIR . 'includes/class-mplm-locations.php';
require_once MPLM_PLUGIN_DIR . 'includes/class-mplm-admin.php';
require_once MPLM_PLUGIN_DIR . 'includes/class-mplm-shortcode.php';

/**
 * Default plugin settings.
 */
function mplm_get_default_settings() {
    return array(
        'map_title'              => 'Explore Properties by District',
        'map_subtitle'           => 'Hover a district to see live ads, or click to browse available towns.',
        'fill_color'             => '#36C6F4',
        'fill_opacity'           => '0.15',
        'hover_color'            => '#201D56',
        'border_color'           => '#36C6F4',
        'border_width'           => '1.2',
        'shadow_color'           => '#36C6F4',
        'shadow_opacity'         => '0.45',
        'shadow_blur'            => '18',
        'shadow_offset_x'        => '-6',
        'shadow_offset_y'        => '12',
        'tooltip_bg'             => '#201D56',
        'tooltip_text'           => '#FFFFFF',
        'tooltip_accent'         => '#36C6F4',
        'panel_bg'               => '#FFFFFF',
        'panel_primary'          => '#201D56',
        'panel_accent'           => '#36C6F4',
        'default_ad_type'        => 'all',
        'only_active_ads'        => '0',
        'hide_empty_towns'       => '1',
        'hide_empty_districts'   => '1',
        'expand_district_search' => '1',
        'nofollow_links'         => '1',
        'district_click_action'  => 'panel', // 'panel' or 'redirect'
        'search_url'             => '/search/',
    );
}

/**
 * Retrieve merged settings.
 */
function mplm_get_settings() {
    $defaults = mplm_get_default_settings();
    $saved    = get_option( MPLM_OPTION_KEY, array() );
    if ( ! is_array( $saved ) ) {
        $saved = array();
    }
    return wp_parse_args( $saved, $defaults );
}

/**
 * Parse a HEX color into an [r, g, b] integer array.
 */
function mplm_parse_hex_rgb( $hex, $fallback = '36C6F4' ) {
    $hex = ltrim( trim( (string) $hex ), '#' );
    if ( 3 === strlen( $hex ) ) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
        $hex = $fallback;
    }
    return array(
        hexdec( substr( $hex, 0, 2 ) ),
        hexdec( substr( $hex, 2, 2 ) ),
        hexdec( substr( $hex, 4, 2 ) ),
    );
}

/**
 * Convert HEX color + opacity into rgba().
 */
function mplm_hex_to_rgba( $hex, $opacity = 1 ) {
    list( $r, $g, $b ) = mplm_parse_hex_rgb( $hex );
    $a = max( 0, min( 1, floatval( $opacity ) ) );
    return sprintf( 'rgba(%d, %d, %d, %.2f)', $r, $g, $b, $a );
}

/**
 * Convert HEX color into comma-separated RGB triplet for CSS rgba(var(...), alpha).
 */
function mplm_hex_to_rgb_triplet( $hex, $fallback = '36C6F4' ) {
    list( $r, $g, $b ) = mplm_parse_hex_rgb( $hex, $fallback );
    return sprintf( '%d, %d, %d', $r, $g, $b );
}

/**
 * Get path to custom uploaded SVG override if present.
 */
function mplm_get_uploaded_svg_path() {
    $upload_dir = wp_upload_dir();
    if ( ! empty( $upload_dir['basedir'] ) ) {
        $uploaded_svg = trailingslashit( $upload_dir['basedir'] ) . 'myproperties-listing-map/lk.svg';
        if ( file_exists( $uploaded_svg ) && is_readable( $uploaded_svg ) ) {
            return $uploaded_svg;
        }
    }
    return '';
}

/**
 * Resolve the path to the Sri Lanka SVG file (uploaded first, then bundled assets/lk.svg).
 */
function mplm_get_svg_file_path() {
    $uploaded_svg = mplm_get_uploaded_svg_path();
    if ( '' !== $uploaded_svg ) {
        return $uploaded_svg;
    }

    $bundled_svg = MPLM_PLUGIN_DIR . 'assets/lk.svg';
    if ( file_exists( $bundled_svg ) && is_readable( $bundled_svg ) ) {
        return $bundled_svg;
    }

    return '';
}

/**
 * Sanitize SVG markup via DOMDocument allowlisting (with regex fallback).
 */
function mplm_sanitize_svg( $svg_content ) {
    if ( ! is_string( $svg_content ) || stripos( $svg_content, '<svg' ) === false ) {
        return '';
    }

    // Strip XML declarations, DOCTYPE (including internal subsets), entity declarations, and comments before parsing.
    $svg_content = preg_replace( '/<\?xml[^>]*\?>/i', '', $svg_content );
    $svg_content = preg_replace( '/<!DOCTYPE\b[^>\[]*(?:\[.*?\])?\s*>/is', '', $svg_content );
    $svg_content = preg_replace( '/<!(?:ENTITY|ELEMENT|ATTLIST|NOTATION)[^>]*>/is', '', $svg_content );
    $svg_content = preg_replace( '/<!--.*?-->/s', '', $svg_content );

    if ( ! class_exists( 'DOMDocument' ) ) {
        $svg_content = preg_replace( '/<script\b[^>]*>(?:.*?<\/script>)?/is', '', $svg_content );
        $svg_content = preg_replace( '/<foreignObject\b[^>]*>(?:.*?<\/foreignObject>)?/is', '', $svg_content );
        $svg_content = preg_replace( '/<(?:style|iframe|object|embed|use|image|animate|animateTransform|animateMotion|set|handler)\b[^>]*>(?:.*?<\/(?:style|iframe|object|embed|use|image|animate|animateTransform|animateMotion|set|handler)>)?/is', '', $svg_content );
        $svg_content = preg_replace( '/\s+on[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $svg_content );
        $svg_content = preg_replace( '/(?:href|xlink:href)\s*=\s*(?:"\s*(?:javascript|vbscript|data):[^"]*"|\'\s*(?:javascript|vbscript|data):[^\']*\')/i', '', $svg_content );
        return trim( $svg_content );
    }

    $prev_errors = libxml_use_internal_errors( true );
    $prev_loader = null;
    if ( PHP_VERSION_ID < 80000 && function_exists( 'libxml_disable_entity_loader' ) ) {
        $prev_loader = libxml_disable_entity_loader( true );
    }

    $src_dom = new DOMDocument();
    $loaded  = $src_dom->loadXML(
        '<?xml version="1.0" encoding="UTF-8"?>' . trim( $svg_content ),
        LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
    );

    if ( null !== $prev_loader && function_exists( 'libxml_disable_entity_loader' ) ) {
        libxml_disable_entity_loader( $prev_loader );
    }
    libxml_clear_errors();
    libxml_use_internal_errors( $prev_errors );

    if ( ! $loaded || ! $src_dom->documentElement || 'svg' !== strtolower( $src_dom->documentElement->localName ) ) {
        return '';
    }

    $allowed_tags = array(
        'svg'      => 'svg',
        'g'        => 'g',
        'path'     => 'path',
        'polygon'  => 'polygon',
        'polyline' => 'polyline',
        'rect'     => 'rect',
        'circle'   => 'circle',
        'ellipse'  => 'ellipse',
        'line'     => 'line',
        'title'    => 'title',
        'desc'     => 'desc',
        'defs'     => 'defs',
        'clippath' => 'clipPath',
        'symbol'   => 'symbol',
        'metadata' => 'metadata',
    );

    $allowed_attrs = array(
        'id'                  => 'id',
        'class'               => 'class',
        'viewbox'             => 'viewBox',
        'width'               => 'width',
        'height'              => 'height',
        'x'                   => 'x',
        'y'                   => 'y',
        'x1'                  => 'x1',
        'y1'                  => 'y1',
        'x2'                  => 'x2',
        'y2'                  => 'y2',
        'cx'                  => 'cx',
        'cy'                  => 'cy',
        'r'                   => 'r',
        'rx'                  => 'rx',
        'ry'                  => 'ry',
        'd'                   => 'd',
        'points'              => 'points',
        'fill'                => 'fill',
        'fill-opacity'        => 'fill-opacity',
        'fill-rule'           => 'fill-rule',
        'stroke'              => 'stroke',
        'stroke-width'        => 'stroke-width',
        'stroke-linecap'      => 'stroke-linecap',
        'stroke-linejoin'     => 'stroke-linejoin',
        'stroke-opacity'      => 'stroke-opacity',
        'opacity'             => 'opacity',
        'transform'           => 'transform',
        'version'             => 'version',
        'baseprofile'         => 'baseProfile',
        'name'                => 'name',
        'title'               => 'title',
        'aria-label'          => 'aria-label',
        'role'                => 'role',
        'preserveaspectratio' => 'preserveAspectRatio',
    );

    $clean_dom = new DOMDocument( '1.0', 'UTF-8' );
    $clean_svg = mplm_sanitize_svg_node( $src_dom->documentElement, $clean_dom, $allowed_tags, $allowed_attrs );
    if ( ! $clean_svg ) {
        return '';
    }

    if ( ! $clean_svg->hasAttribute( 'xmlns' ) ) {
        $clean_svg->setAttribute( 'xmlns', 'http://www.w3.org/2000/svg' );
    }

    $clean_dom->appendChild( $clean_svg );
    $output = $clean_dom->saveXML( $clean_svg );

    return is_string( $output ) ? trim( $output ) : '';
}

/**
 * Recursively clone allowlisted SVG elements and attributes into a clean DOMDocument.
 */
function mplm_sanitize_svg_node( $node, $clean_dom, $allowed_tags, $allowed_attrs ) {
    if ( ! ( $node instanceof DOMElement ) ) {
        return null;
    }

    $tag_lower = strtolower( $node->localName );
    if ( ! isset( $allowed_tags[ $tag_lower ] ) ) {
        return null;
    }

    $clean_el = $clean_dom->createElement( $allowed_tags[ $tag_lower ] );

    if ( $node->hasAttributes() ) {
        foreach ( $node->attributes as $attr ) {
            $attr_lower = strtolower( $attr->nodeName );
            if ( 0 === strpos( $attr_lower, 'on' ) ) {
                continue;
            }

            $is_data_attr = ( 0 === strpos( $attr_lower, 'data-' ) && preg_match( '/^data-[a-z0-9\-_]+$/', $attr_lower ) );
            if ( ! isset( $allowed_attrs[ $attr_lower ] ) && ! $is_data_attr ) {
                continue;
            }

            $val = trim( (string) $attr->nodeValue );
            if ( preg_match( '/(?:javascript|vbscript|data\s*:|expression\s*\(|url\s*\()/i', $val ) ) {
                continue;
            }

            $target_attr = $is_data_attr ? $attr_lower : $allowed_attrs[ $attr_lower ];
            $clean_el->setAttribute( $target_attr, $val );
        }
    }

    if ( 'title' === $tag_lower || 'desc' === $tag_lower ) {
        $text = trim( (string) $node->textContent );
        if ( '' !== $text ) {
            $clean_el->appendChild( $clean_dom->createTextNode( $text ) );
        }
        return $clean_el;
    }

    foreach ( $node->childNodes as $child ) {
        if ( $child instanceof DOMElement ) {
            $clean_child = mplm_sanitize_svg_node( $child, $clean_dom, $allowed_tags, $allowed_attrs );
            if ( $clean_child ) {
                $clean_el->appendChild( $clean_child );
            }
        }
    }

    return $clean_el;
}

/**
 * Activation hook: seed defaults.
 */
register_activation_hook( __FILE__, function () {
    if ( false === get_option( MPLM_OPTION_KEY ) ) {
        add_option( MPLM_OPTION_KEY, mplm_get_default_settings() );
    }
    MPLM_Locations::clear_cache();
} );

/**
 * Initialize plugin modules.
 */
add_action( 'plugins_loaded', function () {
    MPLM_Admin::init();
    MPLM_Shortcode::init();
} );

/**
 * Auto-clear cached ad counts whenever ad_post changes.
 */
add_action( 'save_post_ad_post', array( 'MPLM_Locations', 'clear_cache' ) );
add_action( 'deleted_post', function ( $post_id ) {
    if ( get_post_type( $post_id ) === 'ad_post' ) {
        MPLM_Locations::clear_cache();
    }
} );
add_action( 'trashed_post', function ( $post_id ) {
    if ( get_post_type( $post_id ) === 'ad_post' ) {
        MPLM_Locations::clear_cache();
    }
} );
add_action( 'untrashed_post', function ( $post_id ) {
    if ( get_post_type( $post_id ) === 'ad_post' ) {
        MPLM_Locations::clear_cache();
    }
} );
add_action( 'set_object_terms', function ( $object_id, $terms, $tt_ids, $taxonomy ) {
    if ( 'ad_country' === $taxonomy || 'ad_type' === $taxonomy ) {
        MPLM_Locations::clear_cache();
    }
}, 10, 4 );
add_action( 'created_ad_country', array( 'MPLM_Locations', 'clear_cache' ) );
add_action( 'edited_ad_country', array( 'MPLM_Locations', 'clear_cache' ) );
add_action( 'delete_ad_country', array( 'MPLM_Locations', 'clear_cache' ) );

/**
 * Expand district-level `ad_country` search queries so clicking a district
 * (e.g. /search/?country_id=492) automatically includes all flat town IDs
 * belonging to that district without modifying theme files or term parents.
 */
add_action( 'pre_get_posts', 'mplm_expand_district_search_query', 20 );

function mplm_expand_district_search_query( $query ) {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return;
    }

    // Allow exact main-city town chip links (e.g. "Colombo" town vs "Colombo District") to opt out of expansion.
    if ( isset( $_GET['mplm_exact'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['mplm_exact'] ) ) ) {
        return;
    }

    $settings = mplm_get_settings();
    if ( empty( $settings['expand_district_search'] ) || '1' !== (string) $settings['expand_district_search'] ) {
        return;
    }

    $post_type = $query->get( 'post_type' );
    $is_ad_post = ( 'ad_post' === $post_type ) || ( is_array( $post_type ) && in_array( 'ad_post', $post_type, true ) );
    if ( ! $is_ad_post ) {
        return;
    }

    $tax_query = $query->get( 'tax_query' );
    if ( empty( $tax_query ) || ! is_array( $tax_query ) ) {
        return;
    }

    $modified  = false;
    $tax_query = mplm_expand_tax_query_recursive( $tax_query, $modified );

    if ( $modified ) {
        $query->set( 'tax_query', $tax_query );
    }
}

/**
 * Recursively inspect a WP_Query tax_query array and expand any single district term
 * inside `ad_country` into its full list of town term IDs.
 */
function mplm_expand_tax_query_recursive( $tax_query, &$modified ) {
    foreach ( $tax_query as $key => $clause ) {
        if ( ! is_array( $clause ) ) {
            continue;
        }

        if ( isset( $clause['taxonomy'] ) && 'ad_country' === $clause['taxonomy'] && isset( $clause['terms'] ) ) {
            $field = isset( $clause['field'] ) ? $clause['field'] : 'term_id';
            $terms = $clause['terms'];

            // Only expand when a single district term is queried.
            $single_term = null;
            if ( is_scalar( $terms ) && '' !== (string) $terms ) {
                $single_term = $terms;
            } elseif ( is_array( $terms ) && 1 === count( $terms ) ) {
                $first_term = reset( $terms );
                if ( is_scalar( $first_term ) && '' !== (string) $first_term ) {
                    $single_term = $first_term;
                }
            }

            if ( null !== $single_term ) {
                $expanded_ids = array();

                if ( 'term_id' === $field || 'id' === $field ) {
                    $expanded_ids = MPLM_Locations::get_town_ids_by_district_term_id( intval( $single_term ) );
                } elseif ( 'slug' === $field ) {
                    $expanded_ids = MPLM_Locations::get_town_ids_by_district_slug( sanitize_title( (string) $single_term ) );
                }

                if ( ! empty( $expanded_ids ) ) {
                    $clause['field']    = 'term_id';
                    $clause['terms']    = $expanded_ids;
                    $clause['operator'] = 'IN';
                    $tax_query[ $key ]  = $clause;
                    $modified           = true;
                }
            }
        } else {
            $tax_query[ $key ] = mplm_expand_tax_query_recursive( $clause, $modified );
        }
    }

    return $tax_query;
}
