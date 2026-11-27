<?php
/**
 * Shortcode [myproperties_listing_map] renderer (Option A: Map + Side Panel).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MPLM_Shortcode {

    private static $svg_cache = array();

    public static function init() {
        add_shortcode( 'myproperties_listing_map', array( __CLASS__, 'render' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
    }

    public static function register_assets() {
        wp_register_style(
            'mplm-frontend',
            MPLM_PLUGIN_URL . 'assets/css/mplm-frontend.css',
            array(),
            MPLM_VERSION
        );
        wp_register_script(
            'mplm-frontend',
            MPLM_PLUGIN_URL . 'assets/js/mplm-frontend.js',
            array(),
            MPLM_VERSION,
            true
        );
    }

    /**
     * Format initial ad count label to match frontend JS formatAdLabel().
     */
    private static function format_ad_label( $count, $ad_type ) {
        $count      = intval( $count );
        $type_label = ( 'Sell' === $ad_type || 'Rent' === $ad_type ) ? ( $ad_type . ' ' ) : '';
        if ( 0 === $count ) {
            return 'No ' . $type_label . 'Ads Available';
        }
        return $count . ' ' . $type_label . ( 1 === $count ? 'Ad Available' : 'Ads Available' );
    }

    public static function render( $atts = array() ) {
        $s = mplm_get_settings();

        $atts = shortcode_atts(
            array(
                'default_type' => $s['default_ad_type'],
                'title'        => $s['map_title'],
                'subtitle'     => $s['map_subtitle'],
            ),
            $atts,
            'myproperties_listing_map'
        );

        $default_type = in_array( $atts['default_type'], array( 'all', 'Sell', 'Rent' ), true )
            ? $atts['default_type']
            : 'all';

        wp_enqueue_style( 'mplm-frontend' );
        wp_enqueue_script( 'mplm-frontend' );

        $svg_path = mplm_get_svg_file_path();
        $svg_html = '';
        if ( '' !== $svg_path ) {
            if ( isset( self::$svg_cache[ $svg_path ] ) ) {
                $svg_html = self::$svg_cache[ $svg_path ];
            } else {
                $raw_svg = file_get_contents( $svg_path );
                if ( false !== $raw_svg ) {
                    $svg_html = mplm_sanitize_svg( $raw_svg );
                }
                self::$svg_cache[ $svg_path ] = $svg_html;
            }
        }

        $map_data   = MPLM_Locations::get_map_data( '1' === (string) $s['only_active_ads'] );
        $search_url = $s['search_url'];
        if ( 0 !== strpos( $search_url, 'http://' ) && 0 !== strpos( $search_url, 'https://' ) ) {
            $search_url = home_url( '/' . ltrim( $search_url, '/' ) );
        }

        $instance_id = wp_unique_id( 'mplm-' );

        $payload = array(
            'instanceId'           => $instance_id,
            'defaultType'          => $default_type,
            'searchUrl'            => esc_url_raw( $search_url ),
            'hideEmptyTowns'       => ( '1' === (string) $s['hide_empty_towns'] ),
            'hideEmptyDistricts'   => ( '1' === (string) $s['hide_empty_districts'] ),
            'expandDistrictSearch' => ( '1' === (string) $s['expand_district_search'] ),
            'nofollowLinks'        => ( '1' === (string) $s['nofollow_links'] ),
            'districtClickAction'  => $s['district_click_action'],
            'data'                 => $map_data,
        );

        // CSS Custom Properties from Admin Settings
        $shadow_rgba        = mplm_hex_to_rgba( $s['shadow_color'], $s['shadow_opacity'] );
        $fill_rgb           = mplm_hex_to_rgb_triplet( $s['fill_color'], '36C6F4' );
        $primary_rgb        = mplm_hex_to_rgb_triplet( $s['panel_primary'], '201D56' );
        $accent_rgb         = mplm_hex_to_rgb_triplet( $s['panel_accent'], '36C6F4' );
        $tooltip_accent_rgb = mplm_hex_to_rgb_triplet( $s['tooltip_accent'], '36C6F4' );
        $inline_vars        = sprintf(
            '--mplm-fill:%s;--mplm-fill-rgb:%s;--mplm-fill-opacity:%s;--mplm-hover:%s;--mplm-border:%s;--mplm-border-width:%spx;--mplm-shadow-rgba:%s;--mplm-shadow-blur:%spx;--mplm-shadow-x:%spx;--mplm-shadow-y:%spx;--mplm-tooltip-bg:%s;--mplm-tooltip-text:%s;--mplm-tooltip-accent:%s;--mplm-tooltip-accent-rgb:%s;--mplm-panel-bg:%s;--mplm-panel-primary:%s;--mplm-primary-rgb:%s;--mplm-panel-accent:%s;--mplm-accent-rgb:%s;',
            esc_attr( $s['fill_color'] ),
            esc_attr( $fill_rgb ),
            esc_attr( $s['fill_opacity'] ),
            esc_attr( $s['hover_color'] ),
            esc_attr( $s['border_color'] ),
            esc_attr( $s['border_width'] ),
            esc_attr( $shadow_rgba ),
            esc_attr( $s['shadow_blur'] ),
            esc_attr( $s['shadow_offset_x'] ),
            esc_attr( $s['shadow_offset_y'] ),
            esc_attr( $s['tooltip_bg'] ),
            esc_attr( $s['tooltip_text'] ),
            esc_attr( $s['tooltip_accent'] ),
            esc_attr( $tooltip_accent_rgb ),
            esc_attr( $s['panel_bg'] ),
            esc_attr( $s['panel_primary'] ),
            esc_attr( $primary_rgb ),
            esc_attr( $s['panel_accent'] ),
            esc_attr( $accent_rgb )
        );

        ob_start();
        ?>
        <section
            id="<?php echo esc_attr( $instance_id ); ?>"
            class="mplm-wrapper"
            style="<?php echo esc_attr( $inline_vars ); ?>"
            data-mplm-config="<?php echo esc_attr( wp_json_encode( $payload ) ); ?>"
        >
            <!-- Top Bar: Title + Dynamic All / Sell / Rent Dropdown -->
            <div class="mplm-header">
                <div class="mplm-header-left">
                    <?php if ( '' !== trim( $atts['title'] ) ) : ?>
                        <h2 class="mplm-title"><?php echo esc_html( $atts['title'] ); ?></h2>
                    <?php endif; ?>
                    <?php if ( '' !== trim( $atts['subtitle'] ) ) : ?>
                        <p class="mplm-subtitle"><?php echo esc_html( $atts['subtitle'] ); ?></p>
                    <?php endif; ?>
                </div>

                <div class="mplm-header-right">
                    <span class="mplm-total-pill" data-mplm-total>
                        <?php echo esc_html( self::format_ad_label( $map_data['totals'][ $default_type ], $default_type ) ); ?>
                    </span>
                    <div class="mplm-filter-wrap">
                        <label for="<?php echo esc_attr( $instance_id ); ?>-type" class="mplm-sr-only">Filter listing type</label>
                        <select id="<?php echo esc_attr( $instance_id ); ?>-type" class="mplm-type-select" data-mplm-type-select>
                            <option value="all" <?php selected( $default_type, 'all' ); ?>>All (Sell &amp; Rent)</option>
                            <option value="Sell" <?php selected( $default_type, 'Sell' ); ?>>For Sale (Sell)</option>
                            <option value="Rent" <?php selected( $default_type, 'Rent' ); ?>>For Rent (Rent)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Option A Body: Map Left + Province/District/Town Panel Right -->
            <div class="mplm-grid">
                <!-- Left: Interactive Sri Lanka Map -->
                <div class="mplm-map-column">
                    <div class="mplm-map-stage" data-mplm-map-stage>
                        <?php if ( '' !== $svg_html ) : ?>
                            <div class="mplm-svg-holder" data-mplm-svg-holder>
                                <?php echo $svg_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized via mplm_sanitize_svg(). ?>
                            </div>
                        <?php else : ?>
                            <div class="mplm-missing-svg">
                                <strong>Sri Lanka SVG map not uploaded yet.</strong>
                                <p>Go to <em>WordPress Admin &rarr; Listing Map</em> and upload your <code>lk.svg</code> file.</p>
                            </div>
                        <?php endif; ?>

                        <!-- Dynamic Hover / Tap Tooltip -->
                        <div class="mplm-tooltip" data-mplm-tooltip role="tooltip" aria-hidden="true">
                            <div class="mplm-tooltip-district" data-mplm-tt-district></div>
                            <div class="mplm-tooltip-province" data-mplm-tt-province></div>
                            <div class="mplm-tooltip-count" data-mplm-tt-count></div>
                        </div>
                    </div>

                    <div class="mplm-map-legend">
                        <span class="mplm-legend-item">
                            <span class="mplm-legend-swatch mplm-swatch-default"></span> District
                        </span>
                        <span class="mplm-legend-item">
                            <span class="mplm-legend-swatch mplm-swatch-active"></span> Hovered / Selected
                        </span>
                    </div>
                </div>

                <!-- Right: Province -> District -> Town Panel -->
                <div class="mplm-panel-column">
                    <div class="mplm-panel-top">
                        <div class="mplm-search-box">
                            <input
                                type="search"
                                class="mplm-search-input"
                                placeholder="Search district or town (e.g. Bambalapitiya)..."
                                data-mplm-search
                                autocomplete="off"
                            />
                        </div>
                    </div>

                    <div class="mplm-panel-body" data-mplm-panel-body>
                        <!-- Populated dynamically by mplm-frontend.js -->
                    </div>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}
