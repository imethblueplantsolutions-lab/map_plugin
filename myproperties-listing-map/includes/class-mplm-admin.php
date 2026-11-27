<?php
/**
 * Admin Settings Page & SVG Uploader for MyProperties Listing Map.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MPLM_Admin {

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
        add_action( 'admin_init', array( __CLASS__, 'handle_actions' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_filter( 'plugin_action_links_' . plugin_basename( MPLM_PLUGIN_FILE ), array( __CLASS__, 'settings_link' ) );
    }

    public static function settings_link( $links ) {
        $url = admin_url( 'admin.php?page=myproperties-listing-map' );
        array_unshift( $links, '<a href="' . esc_url( $url ) . '">Settings</a>' );
        return $links;
    }

    public static function register_menu() {
        add_menu_page(
            'MyProperties Listing Map',
            'Listing Map',
            'manage_options',
            'myproperties-listing-map',
            array( __CLASS__, 'render_page' ),
            'dashicons-location-alt',
            58
        );
    }

    public static function enqueue_assets( $hook ) {
        if ( 'toplevel_page_myproperties-listing-map' !== $hook ) {
            return;
        }
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_style( 'mplm-admin', MPLM_PLUGIN_URL . 'assets/css/mplm-admin.css', array( 'wp-color-picker' ), MPLM_VERSION );
        wp_enqueue_script( 'mplm-admin', MPLM_PLUGIN_URL . 'assets/js/mplm-admin.js', array( 'jquery', 'wp-color-picker' ), MPLM_VERSION, true );
    }

    public static function register_settings() {
        register_setting(
            'mplm_settings_group',
            MPLM_OPTION_KEY,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
            )
        );
    }

    public static function sanitize_settings( $input ) {
        $defaults = mplm_get_default_settings();
        $clean    = array();

        $color_keys = array(
            'fill_color',
            'hover_color',
            'border_color',
            'shadow_color',
            'tooltip_bg',
            'tooltip_text',
            'tooltip_accent',
            'panel_bg',
            'panel_primary',
            'panel_accent',
        );

        foreach ( $color_keys as $k ) {
            $val = isset( $input[ $k ] ) ? sanitize_hex_color( $input[ $k ] ) : '';
            $clean[ $k ] = $val ? $val : $defaults[ $k ];
        }

        $clean['map_title']       = isset( $input['map_title'] ) ? sanitize_text_field( $input['map_title'] ) : $defaults['map_title'];
        $clean['map_subtitle']    = isset( $input['map_subtitle'] ) ? sanitize_text_field( $input['map_subtitle'] ) : $defaults['map_subtitle'];
        $clean['fill_opacity']    = isset( $input['fill_opacity'] ) ? strval( max( 0.05, min( 1, floatval( $input['fill_opacity'] ) ) ) ) : $defaults['fill_opacity'];
        $clean['border_width']    = isset( $input['border_width'] ) ? strval( max( 0, min( 5, floatval( $input['border_width'] ) ) ) ) : $defaults['border_width'];
        $clean['shadow_opacity']  = isset( $input['shadow_opacity'] ) ? strval( max( 0, min( 1, floatval( $input['shadow_opacity'] ) ) ) ) : $defaults['shadow_opacity'];
        $clean['shadow_blur']     = isset( $input['shadow_blur'] ) ? strval( max( 0, min( 60, intval( $input['shadow_blur'] ) ) ) ) : $defaults['shadow_blur'];
        $clean['shadow_offset_x'] = isset( $input['shadow_offset_x'] ) ? strval( max( -40, min( 40, intval( $input['shadow_offset_x'] ) ) ) ) : $defaults['shadow_offset_x'];
        $clean['shadow_offset_y'] = isset( $input['shadow_offset_y'] ) ? strval( max( -40, min( 40, intval( $input['shadow_offset_y'] ) ) ) ) : $defaults['shadow_offset_y'];

        $allowed_types = array( 'all', 'Sell', 'Rent' );
        $clean['default_ad_type'] = ( isset( $input['default_ad_type'] ) && in_array( $input['default_ad_type'], $allowed_types, true ) )
            ? $input['default_ad_type']
            : 'all';

        $allowed_clicks = array( 'panel', 'redirect' );
        $clean['district_click_action'] = ( isset( $input['district_click_action'] ) && in_array( $input['district_click_action'], $allowed_clicks, true ) )
            ? $input['district_click_action']
            : 'panel';

        $checkboxes = array(
            'only_active_ads',
            'hide_empty_towns',
            'hide_empty_districts',
            'expand_district_search',
            'nofollow_links',
        );
        foreach ( $checkboxes as $cb ) {
            $clean[ $cb ] = ! empty( $input[ $cb ] ) ? '1' : '0';
        }

        $search_url = isset( $input['search_url'] ) ? trim( sanitize_text_field( $input['search_url'] ) ) : '/search/';
        $clean['search_url'] = '' !== $search_url ? $search_url : '/search/';

        MPLM_Locations::clear_cache();

        return $clean;
    }

    /**
     * Store a short-lived notice and redirect (PRG pattern) to avoid duplicate POST submissions.
     */
    private static function redirect_with_notice( $code, $message, $type = 'updated' ) {
        set_transient(
            'mplm_admin_notice_' . get_current_user_id(),
            array(
                'code'    => $code,
                'message' => $message,
                'type'    => $type,
            ),
            30
        );
        wp_safe_redirect( admin_url( 'admin.php?page=myproperties-listing-map' ) );
        exit;
    }

    /**
     * Handle SVG file upload, revert to bundled SVG, reset defaults, and cache clear.
     */
    public static function handle_actions() {
        if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Handle SVG Upload
        if ( isset( $_POST['mplm_upload_svg_submit'] ) ) {
            check_admin_referer( 'mplm_upload_svg_action', 'mplm_upload_svg_nonce' );

            if ( empty( $_FILES['mplm_svg_file'] ) || ! isset( $_FILES['mplm_svg_file']['error'] ) || UPLOAD_ERR_NO_FILE === intval( $_FILES['mplm_svg_file']['error'] ) ) {
                self::redirect_with_notice( 'mplm_svg_empty', 'Please select your lk.svg file before clicking Upload.', 'error' );
            }

            if ( UPLOAD_ERR_OK !== intval( $_FILES['mplm_svg_file']['error'] ) ) {
                self::redirect_with_notice( 'mplm_svg_upload_err', 'File upload failed (error code ' . intval( $_FILES['mplm_svg_file']['error'] ) . ').', 'error' );
            }

            $tmp_name = isset( $_FILES['mplm_svg_file']['tmp_name'] ) ? $_FILES['mplm_svg_file']['tmp_name'] : '';
            if ( '' === $tmp_name || ! is_uploaded_file( $tmp_name ) ) {
                self::redirect_with_notice( 'mplm_svg_empty', 'Invalid uploaded file.', 'error' );
            }

            $size = isset( $_FILES['mplm_svg_file']['size'] ) ? intval( $_FILES['mplm_svg_file']['size'] ) : 0;
            if ( $size <= 0 || $size > 5 * 1024 * 1024 ) {
                self::redirect_with_notice( 'mplm_svg_size', 'SVG file must be between 1 byte and 5 MB.', 'error' );
            }

            $filename = isset( $_FILES['mplm_svg_file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['mplm_svg_file']['name'] ) ) : '';
            $ext      = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
            if ( 'svg' !== $ext ) {
                self::redirect_with_notice( 'mplm_svg_ext', 'Only .svg files are allowed.', 'error' );
            }

            $raw = file_get_contents( $tmp_name );
            if ( false === $raw ) {
                self::redirect_with_notice( 'mplm_svg_read', 'Could not read the uploaded SVG file.', 'error' );
            }

            $sanitized = mplm_sanitize_svg( $raw );
            if ( '' === $sanitized ) {
                self::redirect_with_notice( 'mplm_svg_invalid', 'The uploaded file does not look like a valid SVG map.', 'error' );
            }

            $upload_dir = wp_upload_dir();
            if ( ! empty( $upload_dir['error'] ) || empty( $upload_dir['basedir'] ) ) {
                self::redirect_with_notice( 'mplm_svg_dir', 'WordPress uploads directory is not available.', 'error' );
            }

            $target_dir = trailingslashit( $upload_dir['basedir'] ) . 'myproperties-listing-map';
            if ( ! file_exists( $target_dir ) ) {
                wp_mkdir_p( $target_dir );
            }

            $saved = file_put_contents( trailingslashit( $target_dir ) . 'lk.svg', $sanitized );
            if ( false === $saved ) {
                self::redirect_with_notice( 'mplm_svg_write', 'Could not save lk.svg. Please check folder permissions.', 'error' );
            }

            self::redirect_with_notice( 'mplm_svg_ok', 'Custom Sri Lanka SVG map (lk.svg) uploaded and activated successfully!', 'updated' );
        }

        // Handle Revert to Bundled SVG
        if ( isset( $_POST['mplm_remove_svg_submit'] ) ) {
            check_admin_referer( 'mplm_remove_svg_action', 'mplm_remove_svg_nonce' );
            $uploaded_svg = mplm_get_uploaded_svg_path();
            if ( '' !== $uploaded_svg && file_exists( $uploaded_svg ) ) {
                wp_delete_file( $uploaded_svg );
            }
            self::redirect_with_notice( 'mplm_svg_reverted', 'Custom SVG override removed. Using the bundled lk.svg map.', 'updated' );
        }

        // Handle Cache Clear
        if ( isset( $_POST['mplm_clear_cache_submit'] ) ) {
            check_admin_referer( 'mplm_clear_cache_action', 'mplm_clear_cache_nonce' );
            MPLM_Locations::clear_cache();
            self::redirect_with_notice( 'mplm_cache_ok', 'Live ad counts cache cleared and refreshed.', 'updated' );
        }

        // Handle Reset Defaults
        if ( isset( $_POST['mplm_reset_defaults_submit'] ) ) {
            check_admin_referer( 'mplm_reset_defaults_action', 'mplm_reset_defaults_nonce' );
            update_option( MPLM_OPTION_KEY, mplm_get_default_settings() );
            MPLM_Locations::clear_cache();
            self::redirect_with_notice( 'mplm_reset_ok', 'All colors and settings were reset to default values.', 'updated' );
        }
    }

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $notice_key = 'mplm_admin_notice_' . get_current_user_id();
        $notice     = get_transient( $notice_key );
        if ( is_array( $notice ) && ! empty( $notice['message'] ) ) {
            delete_transient( $notice_key );
            add_settings_error(
                'mplm_messages',
                isset( $notice['code'] ) ? $notice['code'] : 'mplm_notice',
                $notice['message'],
                isset( $notice['type'] ) ? $notice['type'] : 'updated'
            );
        }

        $s            = mplm_get_settings();
        $uploaded_svg = mplm_get_uploaded_svg_path();
        $svg_path     = mplm_get_svg_file_path();
        $map_data     = MPLM_Locations::get_map_data( '1' === (string) $s['only_active_ads'] );
        $totals       = $map_data['totals'];
        ?>
        <div class="wrap mplm-admin-wrap">
            <h1>MyProperties Listing Map</h1>
            <p class="mplm-admin-intro">
                Add this shortcode to any Elementor Shortcode widget or WordPress page:
                <code class="mplm-shortcode-copy">[myproperties_listing_map]</code>
            </p>

            <?php settings_errors( 'mplm_messages' ); ?>
            <?php settings_errors( MPLM_OPTION_KEY ); ?>

            <!-- Status & Live Counts Bar -->
            <div class="mplm-status-grid">
                <div class="mplm-status-card">
                    <span class="mplm-status-label">Sri Lanka SVG Map</span>
                    <?php if ( '' !== $uploaded_svg ) : ?>
                        <strong class="mplm-ok">&#10004; Ready (Custom)</strong>
                        <small>Using uploaded <code>lk.svg</code> override</small>
                    <?php elseif ( '' !== $svg_path ) : ?>
                        <strong class="mplm-ok">&#10004; Ready (Bundled)</strong>
                        <small>Using built-in <code>assets/lk.svg</code></small>
                    <?php else : ?>
                        <strong class="mplm-warn">&#9888; Upload Required</strong>
                        <small>Upload your <code>lk.svg</code> below</small>
                    <?php endif; ?>
                </div>
                <div class="mplm-status-card">
                    <span class="mplm-status-label">Total Live Ads</span>
                    <strong><?php echo intval( $totals['all'] ); ?></strong>
                    <small>Across 309 mapped locations</small>
                </div>
                <div class="mplm-status-card">
                    <span class="mplm-status-label">Sell Ads</span>
                    <strong><?php echo intval( $totals['Sell'] ); ?></strong>
                    <small><code>_adforest_ad_type = Sell</code></small>
                </div>
                <div class="mplm-status-card">
                    <span class="mplm-status-label">Rent Ads</span>
                    <strong><?php echo intval( $totals['Rent'] ); ?></strong>
                    <small><code>_adforest_ad_type = Rent</code></small>
                </div>
            </div>

            <!-- Step 1: Optional lk.svg Override -->
            <div class="mplm-admin-card">
                <h2>1. Sri Lanka SVG Map File (<code>lk.svg</code>)</h2>
                <p>
                    The 25-district Sri Lanka map (<code>lk.svg</code>) is already bundled with this plugin.
                    You can optionally upload a custom <code>lk.svg</code> below to override it without touching plugin files.
                </p>
                <div class="mplm-inline-form">
                    <form method="post" enctype="multipart/form-data" class="mplm-inline-form">
                        <?php wp_nonce_field( 'mplm_upload_svg_action', 'mplm_upload_svg_nonce' ); ?>
                        <input type="file" name="mplm_svg_file" accept=".svg" required />
                        <button type="submit" name="mplm_upload_svg_submit" class="button button-primary">Upload Custom lk.svg</button>
                    </form>
                    <?php if ( '' !== $uploaded_svg ) : ?>
                        <form method="post" onsubmit="return confirm('Remove custom lk.svg and revert to the bundled map?');">
                            <?php wp_nonce_field( 'mplm_remove_svg_action', 'mplm_remove_svg_nonce' ); ?>
                            <button type="submit" name="mplm_remove_svg_submit" class="button">Revert to Bundled lk.svg</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Step 2: Colors & Behavior -->
            <form method="post" action="options.php">
                <?php settings_fields( 'mplm_settings_group' ); ?>

                <div class="mplm-admin-card">
                    <h2>2. Map Colors &amp; Blue Drop Shadow</h2>
                    <table class="form-table">
                        <tr>
                            <th>Map Fill Color</th>
                            <td>
                                <input type="text" name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[fill_color]" value="<?php echo esc_attr( $s['fill_color'] ); ?>" class="mplm-color-picker" data-default-color="#36C6F4" />
                                <p class="description">Default: <code>#36C6F4</code></p>
                            </td>
                        </tr>
                        <tr>
                            <th>Map Fill Opacity</th>
                            <td>
                                <input type="range" min="0.05" max="1" step="0.05" name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[fill_opacity]" value="<?php echo esc_attr( $s['fill_opacity'] ); ?>" class="mplm-range" data-output="mplm_fill_opacity_val" />
                                <strong id="mplm_fill_opacity_val"><?php echo esc_html( $s['fill_opacity'] ); ?></strong>
                                <p class="description">Default: <code>0.15</code></p>
                            </td>
                        </tr>
                        <tr>
                            <th>District Hover / Active Color</th>
                            <td>
                                <input type="text" name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[hover_color]" value="<?php echo esc_attr( $s['hover_color'] ); ?>" class="mplm-color-picker" data-default-color="#201D56" />
                                <p class="description">Default: <code>#201D56</code></p>
                            </td>
                        </tr>
                        <tr>
                            <th>District Border Color &amp; Width</th>
                            <td>
                                <input type="text" name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[border_color]" value="<?php echo esc_attr( $s['border_color'] ); ?>" class="mplm-color-picker" data-default-color="#36C6F4" />
                                &nbsp;&nbsp;Width (px):
                                <input type="number" step="0.2" min="0" max="5" style="width:70px;" name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[border_width]" value="<?php echo esc_attr( $s['border_width'] ); ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th>Drop Shadow Color</th>
                            <td>
                                <input type="text" name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[shadow_color]" value="<?php echo esc_attr( $s['shadow_color'] ); ?>" class="mplm-color-picker" data-default-color="#36C6F4" />
                                <p class="description">Creates the soft floating blue shadow around Sri Lanka.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Drop Shadow Opacity</th>
                            <td>
                                <input type="range" min="0" max="1" step="0.05" name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[shadow_opacity]" value="<?php echo esc_attr( $s['shadow_opacity'] ); ?>" class="mplm-range" data-output="mplm_shadow_opacity_val" />
                                <strong id="mplm_shadow_opacity_val"><?php echo esc_html( $s['shadow_opacity'] ); ?></strong>
                            </td>
                        </tr>
                        <tr>
                            <th>Drop Shadow Blur &amp; Offset (px)</th>
                            <td>
                                Blur: <input type="number" min="0" max="60" style="width:65px;" name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[shadow_blur]" value="<?php echo esc_attr( $s['shadow_blur'] ); ?>" />
                                &nbsp; X: <input type="number" min="-40" max="40" style="width:65px;" name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[shadow_offset_x]" value="<?php echo esc_attr( $s['shadow_offset_x'] ); ?>" />
                                &nbsp; Y: <input type="number" min="-40" max="40" style="width:65px;" name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[shadow_offset_y]" value="<?php echo esc_attr( $s['shadow_offset_y'] ); ?>" />
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="mplm-admin-card">
                    <h2>3. Tooltip &amp; Side Panel Colors</h2>
                    <table class="form-table">
                        <tr>
                            <th>Tooltip Background</th>
                            <td><input type="text" name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[tooltip_bg]" value="<?php echo esc_attr( $s['tooltip_bg'] ); ?>" class="mplm-color-picker" data-default-color="#201D56" /></td>
                        </tr>
                        <tr>
                            <th>Tooltip Text Color</th>
                            <td><input type="text" name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[tooltip_text]" value="<?php echo esc_attr( $s['tooltip_text'] ); ?>" class="mplm-color-picker" data-default-color="#FFFFFF" /></td>
                        </tr>
                        <tr>
                            <th>Tooltip Accent Color</th>
                            <td><input type="text" name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[tooltip_accent]" value="<?php echo esc_attr( $s['tooltip_accent'] ); ?>" class="mplm-color-picker" data-default-color="#36C6F4" /></td>
                        </tr>
                        <tr>
                            <th>Panel Background</th>
                            <td><input type="text" name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[panel_bg]" value="<?php echo esc_attr( $s['panel_bg'] ); ?>" class="mplm-color-picker" data-default-color="#FFFFFF" /></td>
                        </tr>
                        <tr>
                            <th>Panel Primary Color</th>
                            <td><input type="text" name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[panel_primary]" value="<?php echo esc_attr( $s['panel_primary'] ); ?>" class="mplm-color-picker" data-default-color="#201D56" /></td>
                        </tr>
                        <tr>
                            <th>Panel Accent / Highlight Color</th>
                            <td><input type="text" name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[panel_accent]" value="<?php echo esc_attr( $s['panel_accent'] ); ?>" class="mplm-color-picker" data-default-color="#36C6F4" /></td>
                        </tr>
                    </table>
                </div>

                <div class="mplm-admin-card">
                    <h2>4. Text, Filter &amp; Search Settings</h2>
                    <table class="form-table">
                        <tr>
                            <th>Section Heading</th>
                            <td><input type="text" class="regular-text" name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[map_title]" value="<?php echo esc_attr( $s['map_title'] ); ?>" /></td>
                        </tr>
                        <tr>
                            <th>Section Subtitle</th>
                            <td><input type="text" class="large-text" name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[map_subtitle]" value="<?php echo esc_attr( $s['map_subtitle'] ); ?>" /></td>
                        </tr>
                        <tr>
                            <th>Default Top-Right Filter</th>
                            <td>
                                <select name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[default_ad_type]">
                                    <option value="all" <?php selected( $s['default_ad_type'], 'all' ); ?>>All (Sell + Rent)</option>
                                    <option value="Sell" <?php selected( $s['default_ad_type'], 'Sell' ); ?>>Sell</option>
                                    <option value="Rent" <?php selected( $s['default_ad_type'], 'Rent' ); ?>>Rent</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>When User Clicks a District on Map</th>
                            <td>
                                <select name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[district_click_action]">
                                    <option value="panel" <?php selected( $s['district_click_action'], 'panel' ); ?>>Open District &amp; Towns in Side Panel (2nd click opens Search)</option>
                                    <option value="redirect" <?php selected( $s['district_click_action'], 'redirect' ); ?>>Go Directly to District Search Page</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Hide 0-Ad Locations</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[hide_empty_towns]" value="1" <?php checked( $s['hide_empty_towns'], '1' ); ?> />
                                    Hide towns with 0 ads (only show towns with available ads)
                                </label>
                                <br /><br />
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[hide_empty_districts]" value="1" <?php checked( $s['hide_empty_districts'], '1' ); ?> />
                                    Hide districts with 0 ads inside the right-side panel (all 25 districts always stay visible on the map)
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th>Ad Status &amp; Search Matching</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[expand_district_search]" value="1" <?php checked( $s['expand_district_search'], '1' ); ?> />
                                    Automatically include all towns in a district when searching a district URL (e.g. <code>?country_id=492</code> shows all Colombo + town ads)
                                </label>
                                <br /><br />
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[only_active_ads]" value="1" <?php checked( $s['only_active_ads'], '1' ); ?> />
                                    Count only ads with <code>_adforest_ad_status_ = active</code> (leave unchecked to match your live <code>/search/</code> page)
                                </label>
                                <br /><br />
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[nofollow_links]" value="1" <?php checked( $s['nofollow_links'], '1' ); ?> />
                                    Add <code>rel="nofollow"</code> to <code>/search/</code> links (recommended for SEO)
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th>Search Page Path</th>
                            <td>
                                <input type="text" class="regular-text" name="<?php echo esc_attr( MPLM_OPTION_KEY ); ?>[search_url]" value="<?php echo esc_attr( $s['search_url'] ); ?>" />
                                <p class="description">Default: <code>/search/</code></p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button( 'Save Settings' ); ?>
                </div>
            </form>

            <!-- Tools -->
            <div class="mplm-admin-card mplm-tools-row">
                <form method="post" style="display:inline-block;margin-right:12px;">
                    <?php wp_nonce_field( 'mplm_clear_cache_action', 'mplm_clear_cache_nonce' ); ?>
                    <button type="submit" name="mplm_clear_cache_submit" class="button">Refresh Live Ad Counts</button>
                </form>
                <form method="post" style="display:inline-block;" onsubmit="return confirm('Reset all colors and settings back to defaults?');">
                    <?php wp_nonce_field( 'mplm_reset_defaults_action', 'mplm_reset_defaults_nonce' ); ?>
                    <button type="submit" name="mplm_reset_defaults_submit" class="button">Reset Colors to Defaults</button>
                </form>
            </div>
        </div>
        <?php
    }
}
