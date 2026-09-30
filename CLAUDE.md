# citcom-rebuild (WordPress theme)

Rebuild of citcom.co.uk from ACF Flexible Content to ACF Blocks. The site must
look pixel-identical to the current one; the win is maintainability and
performance. Read `docs/00-discovery.md` first, then the phase brief you are on.

## Ground rules

- This repo IS the theme. It deploys to `wp-content/themes/citcom-rebuild/`
  (Pressable git deploy to staging site 1771004). Nothing else lives here.
- The old theme is read-only reference at
  `C:\Users\gaz_c\OneDrive\Documents\Working Files\citcom-reference\citcom`
  (templates in `templates/flexFunctions/*.php`, SCSS in `assets/_dev/scss/`,
  field groups in `acf-json/`). Copy markup and class names faithfully; do not
  copy its architecture.
- Never commit `node_modules`, build caches, screenshots (`.baseline/`) or
  anything from the reference theme.
- Hybrid theme: PHP templates for header/footer/archives/singles,
  `theme.json` for tokens, one ACF Block per old flexible layout.
- Requirements on the target site: ACF Pro 6.8+, ACF Extended Pro (image
  selector, code editor, column, post types field types), plus one mu-plugin
  that Pressable provides at account level and the field JSON relies on:
  acf-swatch (nickforddev/acf-swatch, field type `swatch`). A local site needs
  it in wp-content/mu-plugins. The focal point image field is the theme's own
  `citcom_focuspoint` type (inc/class-citcom-field-focuspoint.php), which
  replaced the acf-focuspoint mu-plugin with the same {id, top, left} value.
  Reviews come from Trustindex (decided 2026-09-30; the old Elfsight embeds are
  gone): citcom/trustindex, the diner reviews block and [google_reviews] all go
  through `citcom_trustindex_embed()` in inc/shortcodes.php, which needs the
  Trustindex plugin for its shortcodes. PHP 8.5, WP 7.1+.
- UK English in all copy and comments. No em dashes in prose.

## Block conventions

- Namespace `citcom/`. One folder per block under `blocks/<name>/` containing
  `block.json`, `render.php`, `style.scss` (compiled to `style.css`),
  optional `editor.scss`, optional `view.js`, and `fields.json` (the ACF field
  group for that block, loaded via `acf/settings/load_json`).
- Register with `register_block_type( __DIR__ . '/blocks/<name>' )` from a
  single loader in `inc/blocks.php`. ACF Blocks v3 (`acf.blockVersion: 3`),
  `apiVersion: 3`, `supports.anchor: true`, `supports.jsx: true` only where
  InnerBlocks is used.
- Front-end markup must match the old `flexXxx()` output: same wrapper
  `<section class="section-padding flex-<layout> ...">`, same inner structure
  and classes, so the existing SCSS applies unchanged.
- The old per-layout "Section settings" (padding, background colour/pattern,
  gradient, text colour, anchor) become a shared field group applied to every
  citcom/* block via a `block` location rule, rendered by a shared helper
  `citcom_section_attrs( $block, $fields )` that returns the class string and
  anchor id exactly as `layoutSettings()` did.
- Block name mapping (old layout -> block): editor -> citcom/editor (InnerBlocks
  wrapped in the section markup), page_header -> citcom/page-header,
  media_text -> citcom/media-text, cta -> citcom/cta, display_posts ->
  citcom/display-posts, icons -> citcom/icons, gallery -> citcom/gallery,
  quote -> citcom/quote, shortcode -> citcom/shortcode, sub_services ->
  citcom/sub-services, google_reviews -> citcom/trustindex, stats ->
  citcom/stats, video -> citcom/video, swiper -> citcom/swiper, contact_map ->
  citcom/contact-map, services_showcase -> citcom/services-showcase,
  citdot_cards -> citcom/citdot-cards, diner_* -> citcom/diner-* (all eight).
  The old sidebar blocks acf/latest-posts, acf/newsletter-signup,
  acf/posts-search, acf/related-posts and acf/top-blog-posts are citcom/* with
  the same slugs.
- Keep field names identical to the old sub-field names wherever possible; the
  migration script maps old row data onto block attributes by name.

## Build

- `@wordpress/scripts` (`wp-scripts build` / `start`). Entry points: one
  global stylesheet (`src/theme.scss`, the old `assets/_dev/scss` ported), and
  per-block styles via `block.json` `style`/`editorStyle`/`viewScript` so a
  page only loads what it uses.
- Compiled output goes to `build/` (git-ignored on main). GitHub Actions
  (`.github/workflows/deploy.yml`) builds on every push to main and publishes
  source plus `build/` as one force-pushed commit on the `deploy` branch under
  `wp-content/themes/citcom-rebuild/`; Pressable deploys that branch.

## Verification

- Visual: `node tools/visual-baseline.mjs capture|diff` (see
  `docs/00-discovery.md`). Pixel-identical is the bar; any diff is a bug.
- PHP: `composer phpcs` (WordPress-Extra + PHPCompatibility 8.5) and
  `composer phpstan` once configured.
- Local dev site: https://citcom-rebuild.test/ (Laragon, this repo is its active theme).
- Never test against live (site 1572392). Staging is 1771004.
