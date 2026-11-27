# MyProperties Listing Map — Setup & Usage Guide

**MyProperties Listing Map** (`v1.0.0`) is a WordPress plugin built for **AdForest** (`myproperties.lk`). It renders an interactive **25-district Sri Lanka SVG map** alongside a searchable **Province → District → Town** explorer with live **All / Sell / Rent** listing counts and automatic district-level search expansion.

- **Requires WordPress:** 5.8+ (tested up to 6.7)
- **Requires PHP:** 7.4+
- **Shortcode:** `[myproperties_listing_map]`

---

## 1. Repository Structure

```text
.
├── GUIDE.md                         # This documentation guide
├── build_zip.py                     # Python script to package myproperties-listing-map.zip
├── assets/                          # Reference map images and source SimpleMaps lk.svg
└── myproperties-listing-map/        # WordPress plugin root
    ├── myproperties-listing-map.php # Core bootstrap, settings, SVG sanitizer, search expansion
    ├── readme.txt                   # WordPress plugin metadata & install notes
    ├── uninstall.php                # Cleanup on plugin deletion
    ├── includes/
    │   ├── class-mplm-locations.php # 9 Provinces, 25 Districts, 309 ad_country terms + SQL counts
    │   ├── class-mplm-admin.php     # WP Admin "Listing Map" page & SVG upload/revert handlers
    │   └── class-mplm-shortcode.php # [myproperties_listing_map] renderer & CSS variable injection
    └── assets/
        ├── lk.svg                   # Bundled Sri Lanka 25-district SVG (SimpleMaps)
        ├── css/
        │   ├── mplm-frontend.css
        │   └── mplm-admin.css
        └── js/
            ├── mplm-frontend.js
            └── mplm-admin.js
```

---

## 2. Building the Installable Plugin ZIP

Run the included Python script from the repository root to generate `myproperties-listing-map.zip` (automatically excluded from Git by `.gitignore`):

```powershell
python build_zip.py
```

The script packages `myproperties-listing-map/` with POSIX forward-slash paths and excludes OS/editor artifacts (`.DS_Store`, `Thumbs.db`, `desktop.ini`, `__pycache__`, `.pyc`).

---

## 3. WordPress Installation

1. Build or obtain `myproperties-listing-map.zip`.
2. In WordPress Admin, go to **Plugins → Add New → Upload Plugin**.
3. Upload `myproperties-listing-map.zip` and click **Activate**.
4. The 25-district `lk.svg` map is pre-bundled at `myproperties-listing-map/assets/lk.svg`, so the map works immediately without extra file uploads.
5. Add `[myproperties_listing_map]` to any WordPress page or Elementor **Shortcode** widget.

---

## 4. Shortcode Usage & Attributes

Basic shortcode:

```text
[myproperties_listing_map]
```

Optional attributes to override Admin defaults per placement:

| Attribute | Allowed Values | Default | Description |
| :--- | :--- | :--- | :--- |
| `default_type` | `all`, `Sell`, `Rent` | Admin `default_ad_type` (`all`) | Initial selection of the top-right listing filter |
| `title` | Any string (empty hides heading) | Admin `map_title` | Section heading above the map |
| `subtitle` | Any string (empty hides subtitle) | Admin `map_subtitle` | Subtitle text below the heading |

Example:

```text
[myproperties_listing_map default_type="Sell" title="Properties for Sale in Sri Lanka" subtitle="Select a district to explore available towns."]
```

---

## 5. Admin Settings Reference (`Listing Map`)

Open **Listing Map** in the left WordPress admin menu (requires `manage_options` capability).

### Status Cards
- **Sri Lanka SVG Map:** Shows `Ready (Bundled)` when using the built-in `assets/lk.svg`, or `Ready (Custom)` when an uploaded override is active.
- **Total Live Ads / Sell Ads / Rent Ads:** Shows live deduplicated counts across all mapped locations (`_adforest_ad_type = Sell` / `Rent`).

### Section 1: Sri Lanka SVG Map File (`lk.svg`)
- Uploading is **optional** because `lk.svg` is already bundled.
- Uploading a `.svg` file (up to 5 MB) runs `DOMDocument` allowlist sanitization (`mplm_sanitize_svg()`) and saves it to `wp-content/uploads/myproperties-listing-map/lk.svg`.
- When a custom override is active, a **Revert to Bundled lk.svg** button appears so you can delete the uploaded override and fall back to the bundled map.
- District paths are matched automatically by `id`, `data-id`, `data-code`, `name`, `data-name`, `title`, `aria-label`, or `<title>` using ISO 3166-2 codes (`LK11`–`LK92`), English district names, or SimpleMaps Sinhala romanizations.

### Section 2: Map Colors & Drop Shadow
| Setting | Default | Purpose |
| :--- | :--- | :--- |
| `fill_color` | `#36C6F4` | Base fill color of district shapes |
| `fill_opacity` | `0.15` | Fill opacity (`0.05` – `1.0`) |
| `hover_color` | `#201D56` | Hovered and selected district fill color |
| `border_color` | `#36C6F4` | District outline stroke color |
| `border_width` | `1.2` px | District outline stroke width (`0` – `5`) |
| `shadow_color` | `#36C6F4` | Outer island drop-shadow color |
| `shadow_opacity` | `0.45` | Outer drop-shadow opacity (`0` – `1`) |
| `shadow_blur` | `18` px | Drop-shadow blur radius (`0` – `60`) |
| `shadow_offset_x` / `y` | `-6` / `12` px | Drop-shadow X/Y offset (`-40` – `40`) |

> Note: The plugin clones district shapes into a background `.mplm-shadow-layer` silhouette so the drop shadow renders cleanly around the outer coastline without inner border shadow artifacts.

### Section 3: Tooltip & Side Panel Colors
- **Tooltip:** `tooltip_bg` (`#201D56`), `tooltip_text` (`#FFFFFF`), `tooltip_accent` (`#36C6F4`)
- **Side Panel:** `panel_bg` (`#FFFFFF`), `panel_primary` (`#201D56`), `panel_accent` (`#36C6F4`)
- Hover tints, pill backgrounds, borders, and legend swatches automatically derive from these colors via CSS variables (`--mplm-*-rgb`).

### Section 4: Text, Filter & Search Settings
- **Section Heading / Subtitle:** Default text rendered when shortcode attributes aren't passed.
- **Default Top-Right Filter:** `all`, `Sell`, or `Rent`.
- **When User Clicks a District on Map:**
  - `panel` *(default)*: First click opens the district and its towns in the right-side panel; second click on the same district navigates to `/search/`.
  - `redirect`: First click redirects immediately to the district search page.
- **Hide 0-Ad Locations:**
  - `hide_empty_towns` (default `on`): Hides 0-ad towns inside district detail and search results.
  - `hide_empty_districts` (default `on`): Hides 0-ad districts/provinces inside the right-side panel (all 25 districts remain visible and interactive on the map).
- **Ad Status & Search Matching:**
  - `expand_district_search` (default `on`): Expands single-district `ad_country` queries on `ad_post` searches (e.g. `/search/?country_id=492`) to include all towns belonging to that district. Town chips that share a district's term ID (e.g. **Colombo** town `492`) automatically append `&mplm_exact=1` so clicking the city chip filters strictly to that town while the district CTA searches the entire district.
  - `only_active_ads` (default `off`): Restricts counts to ads with post meta `_adforest_ad_status_ = 'active'`.
  - `nofollow_links` (default `on`): Adds `rel="nofollow"` to generated `/search/` links.
  - `search_url` (default `/search/`): Base path or full URL for AdForest search results.

### Tools
- **Refresh Live Ad Counts:** Clears `mplm_live_counts_v1_all` and `mplm_live_counts_v1_active` transients immediately. Counts also auto-refresh every 5 minutes (`300s`) and auto-invalidate on `save_post_ad_post`, `deleted_post`, `trashed_post`, `untrashed_post`, `set_object_terms`, and `ad_country` term edits.
- **Reset Colors to Defaults:** Restores all plugin options to default values.

---

## 6. Adding New Towns in WordPress

The plugin maps 9 provinces, 25 districts, and 309 baseline `ad_country` term IDs in `MPLM_Locations::get_provinces()`. If you create additional towns in WordPress under **Listings → Countries/Locations (`ad_country`)** and set their **Parent** to one of the 25 district terms (e.g. parent = Colombo `492`), both live map counts (`get_map_data()`) and district search expansion (`get_town_ids_by_district_term_id()`) automatically include those child towns.

---

## 7. Uninstalling

Deleting the plugin from **WordPress Admin → Plugins** runs `uninstall.php`, which removes `mplm_settings`, cached count transients, and any custom `lk.svg` uploaded under `wp-content/uploads/myproperties-listing-map/`. Published listings and `ad_country` taxonomy terms are untouched.
