# Citcom rebuild: Phase 0 discovery

Date: 2026-09-28. Source of truth: live site (Pressable site 1572392) and the
`citcom` theme snapshot taken the same day (kept outside this repo as read-only
reference at `Working Files/citcom-reference/citcom`).

## Decisions taken

- Hybrid theme: PHP templates for header, footer, archives and singles;
  `theme.json` for design tokens; one ACF Block per current flexible layout.
- Development locally in Laragon (`citcom-rebuild` site), full-content testing
  on Pressable staging (site 1771004, `citcomstaging.mystagingwebsite.com`) via
  git deploy from `GarethCitcom/citcom-rebuild`. Live untouched until launch.
- Content moves by an automated migration command, not by hand.
- Target is pixel-identical against a screenshot baseline of the current site.

## Environment

- WordPress 7.1.2, PHP 8.5, Pressable AMS, edge cache on.
- Theme `citcom` v1.0.1 (THEME_VERSION 1.2.13), Bootstrap 5.3 based, SCSS built
  with Prepros (`prepros.config`) to `assets/css/styles.min.css` and
  `assets/js/main.min.js`. jQuery 3.7.1 from CDN, Font Awesome kit, Adobe Fonts
  (Typekit `dom1odt`), AOS, Swiper 11, lozad, simplebar, vlitejs, video.js.
- Active plugins of note: ACF Pro 6.8.10, ACF Extended Pro 0.9.2.7,
  acf-to-content, ACF Font Awesome, ASE Pro, Forminator, Jetpack Boost,
  ShortPixel, FileBird Pro, Fluent SMTP, RealFaviconGenerator, miniOrange SAML.
- mu-plugins: acf-audio-video-player, acf-autosize, acf-clone-repeater,
  acf-dimensions, acf-focuspoint, acf-getallobjects, acf-swatch, cbxwpwritelog,
  citcomsuite, sqlite-database-integration (present; MySQL is in use),
  wp-migrate-db-pro-compatibility, email-template.php, hosting.php.

## Content model

Post types using the flexible content field `blocks` (field
`field_66f9a66637bf6`, group `group_66f9a66681625`): page, service, case-study,
landing-page, template.

Counts (publish + draft + private) with at least one block row:
page 20, service 40, case-study 37, landing-page 11, template 6. 114 posts,
769 block rows in total. Also 81 blog posts (not flexible content).

### How the data is stored (important for migration)

- ACF Extended "single meta" mode is on: each post has ONE `acf` meta row
  holding a serialized array of all field values (the home page's is 18 KB).
  There are no per-field `blocks_0_title` rows in postmeta.
- The `acf-getallobjects` mu-plugin additionally writes the fully formatted
  field values (images expanded to arrays etc.) as JSON into
  `wp_options.acfAllObjects_{post_id}` on every save (368 rows, 2.1 MB,
  autoload off). The front end reads THIS via `ACFAllObj::get()`, not postmeta.
  `functions/lib/acf-cache-disabled-layouts.php` exists to patch a bug where
  disabled rows never reach that cache.
- Migration source: read `acfAllObjects_{id}` JSON (already formatted, closest
  to what the templates receive). Fall back to unserialising `acf` meta and
  formatting through ACF if an option row is missing.

### Layout usage across all 114 posts (rows)

| Layout | Rows | Used on |
|---|---|---|
| editor | 210 | everywhere |
| media_text | 134 | services, case studies, pages |
| page_header | 103 | every page type |
| cta | 83 | services, case studies, pages |
| display_posts | 68 | case studies, services, templates |
| icons | 48 | services, pages |
| gallery | 39 | services, case studies |
| quote | 26 | case studies mainly |
| shortcode | 17 | pages, landing pages |
| sub_services | 7 | services, templates |
| google_reviews | 6 | pages, landing page |
| stats | 5 | case studies |
| video | 3 | case studies, page |
| swiper | 3 | pages |
| contact_map | 2 | pages |
| diner_* (8 layouts) | 14 | Diner landing page + current home page |
| services_showcase | 1 | home page |
| citdot_cards | 1 | about page |

Per post type: services use page_header 40, cta 39, editor 34, icons 34,
media_text 30, gallery 27, display_posts 26, sub_services 6. Case studies use
page_header 37, editor 36, display_posts 31, cta 31, quote 22, media_text 13,
gallery 8, stats 5, video 2. Pages use page_header 17, editor 14, shortcode 8,
cta 7, media_text 7, icons 6, display_posts 5, google_reviews 5, gallery 4,
quote 3, swiper 2, contact_map 2, video 1, services_showcase 1, citdot_cards 1.

Build order for blocks therefore: editor, page_header, media_text, cta,
display_posts, icons, gallery, quote, shortcode, then the long tail. The 8
diner_* layouts are event-specific (registered in PHP in
`functions/acf/diner-layouts.php`, templates `templates/flexFunctions/diner_*`)
and ARE being ported as blocks: the Diner campaign runs into the new year.

### Field groups (acf-json, 23 files)

Flexible Content (Blocks); Section settings and Section settings (simple)
(both located on `block == all`: these are the per-layout settings clone,
providing section_padding, background_colour/color, shape_pattern,
gradient_with_pattern, text_colour/color, anchor_name); Flex Block Editor
(an `acfe_block_editor` field, i.e. Gutenberg inside a flex row, used by the
`editor` layout via clone `field_66017a69d6a0a`); Company Logo's, Footer
Accreditations, Service Shapes, Our Socials, Templates (options page
`theme-settings`); Service Details (service); Post image (post, case-study);
User (post); Menu ID (nav_menu_item); Landing page setting (landing-page);
Top Blog Posts, Newsletter Signup, Latest Posts (existing ACF blocks in
`blocks/`). Plus ACF-registered CPTs: case-study, service, landing-page,
template; taxonomy case-study-tag; options page Site Settings.

Third-party field types in use that the new blocks must keep or replace:
focuspoint (image + focal point), swatch (colour), acfe_image_selector,
acfe_column, acfe_code_editor, acfe_post_types, acfe_block_editor,
audio_video_player, font-awesome, range, clone.

### Existing ACF blocks (already Gutenberg)

`blocks/latest-posts.php`, `newsletter-signup.php`, `posts-search.php`,
`related-posts.php`, `top-blog-posts.php`. These prove blocks already work
on this stack and can be ported into the new block folder structure.

## Rendering pipeline today

`page.php` -> `templates/flexible-acf.php` -> loops `blocks` ->
`templates/acf-getFlexLayout.php` (switch on `acf_fc_layout`) -> one
`flexXxx($data)` function per layout in `templates/flexFunctions/*.php`.
Each renders `<section class="section-padding flex-{layout} {settings classes}">`
with a `container-xl` inside; `layoutSettings()` in
`functions/acf/flexible-content.php` turns Section settings into classes
(`bg-*`, `text-*`, `pattern pattern-opac`, padding class, anchor id).
Content passes through `citdotLists()`. SCSS per layout lives in
`assets/_dev/scss/flexContent/`.

Note: `page.php` calls `get_header()` and then includes `flexible-acf.php`,
which itself calls `get_header()` / `get_footer()` again. Check whether the
header is duplicated or guarded before assuming that markup as the baseline.

## Performance and hygiene findings (baseline issues to fix in the rebuild)

1. `node_modules` (8,975 files) and `working-folder` (215 MB) are deployed in
   the production theme directory. The theme itself is ~5 MB.
2. `functions/lib/get-logos.php` builds the uploads path from the WP core
   directory and throws `file_get_contents` warnings under WP-CLI (and will do
   under any non-web context). Logos should be read via `wp_get_upload_dir()`.
3. Something in the stack emits a UTF-8 BOM at the start of every WP-CLI
   response (a PHP file saved with a BOM). Find and fix; it breaks scripting.
4. Double storage of all ACF data (single `acf` meta + `acfAllObjects_*`
   options) plus `acf-to-content` copying field text into post_content for
   search. With blocks in `post_content` all three become unnecessary.
5. `PERFORMANCE_ANALYSIS.md` in the old theme already lists: repeated
   `get_field('option')` calls per request (partly addressed by
   `acf-options-cache.php`), related-posts query on every request, duplicate
   `custom_excerpt()`, and more. Treat as a checklist.
6. Assets: one global `styles.min.css` (231 KB dir) and `main.min.js` for every
   page; jQuery and Font Awesome kit from third-party CDNs; Adobe Fonts CSS.
   Rebuild target: per-block CSS/JS registered through `block.json` so only
   what a page uses loads; self-hosted or subset fonts where licensing allows;
   drop jQuery dependency where the new block JS allows.
7. Inline `<style>` blocks are generated per section for spacing/background
   overrides (`flexLayoutSettings()`); prefer utility classes / CSS custom
   properties from theme.json.

## Baseline capture

- `docs/urls.csv` lists every public URL to compare (all pages, services,
  case studies, landing pages, archives, a sample of posts, search, 404).
- `tools/visual-baseline.mjs` captures full-page desktop + mobile PNGs with
  animations/AOS/lazy-load neutralised, and diffs two capture sets. Neither
  the Cowork cloud workspace nor the local VM can reach citcom.co.uk (egress
  allowlist), so it must run natively on a dev machine (e.g. from Claude Code):
  `npm i -D playwright pixelmatch pngjs && npx playwright install chromium`,
  then `node tools/visual-baseline.mjs capture --base https://citcomstaging.mystagingwebsite.com --out .baseline/old`.
  Capture the baseline BEFORE the new theme is activated on staging.
- Note: the current home page is the Diner landing page (post 219996,
  "Home — Diner"); the regular home is `/home/`.

## Baseline capture result (2026-09-28)

Captured from staging before any theme change: 246 shots (123 URLs at desktop
and mobile), only the deliberate 404 page returned 404. Stored in
`.baseline/old/` (585 MB, git-ignored) with `manifest.json`.

Re-captured on 2026-09-29 after two staging changes: the Font Awesome kit now
allows the staging host (the first capture had no icons, so footer and card
icons showed as bullets and empty boxes) and the cookie banner was removed.
One URL timed out on the first pass and was re-captured separately.

Notes on the tool:

- `--insecure` accepts Laragon's self-signed certificate; `--only slug,slug`
  captures a subset of urls.csv, and an entry prefixed with `=` matches the
  exact path (run that form from PowerShell, Git Bash rewrites `=/path`).
- `/` is saved as `root.*.png`; `/home/` as `home.*.png` (the first capture
  wrote both to the same file, so the Diner home page had no shot).

## Findings added in Phase 1

- The templates nested `<main>` twice (page.php and flexible-acf.php both open
  one; get_header/get_footer are require_once so the header is not duplicated).
  The new theme outputs one `<main>`; the only selector that depended on the
  nesting (`body > main > main` for the modal blur) is now `body > main`.
- `focuspoint` and `swatch` were NOT ACF Extended field types but mu-plugins
  on the Pressable sites (ooksanen/acf-focuspoint 1.2.0, nickforddev/acf-swatch
  1.0.7). Decided 2026-09-29: swatch stays (local installs need it in
  wp-content/mu-plugins); focuspoint is replaced by the theme's own
  `citcom_focuspoint` field type, which stores the same `{id, top, left}`
  array, so field names and stored values are unchanged and the mu-plugin can
  be removed from Pressable once the new theme is live. Empty values must be
  arrays, never strings (the migration must write `["id" => "", "top" => "",
  "left" => ""]` or omit the key).
- The ACF UI post types, taxonomy and options page live in the database as
  `acf-post-type`, `acf-taxonomy` and `acf-ui-options-page` posts. The theme
  registers the same keys in PHP at init priority 5. Deactivating (or deleting)
  those ACF UI entries is the FIRST step of the Phase 3 staging run, in the same
  session the new theme is activated on staging, or the slugs collide.
- ACF Extended performance mode ("ultra", single `acf` meta row) is kept on in
  `inc/acf.php` so existing option and post-meta values stay readable; Phase 3
  decides whether to turn it off and convert.
- `page_header` tested `$data['type'] == 'service'` but the field stores
  `services`, so the `service-{name}` class never rendered. The block keeps
  that behaviour (with a comment) because the baseline was captured with it.
- Blog posts link to `/blog/{category}/{slug}` via a `post_link` filter and
  custom rewrite rules, while the permalink structure is
  `/%category%/%postname%/` with category base `blog-category`; both URL forms
  resolve on staging. Ported as is in `inc/setup.php`.
- The old bundle shipped bootstrap-select, jquery.mousewheel, anchorScroll,
  findOverflows and clusterMap without calling them; they are not ported (see
  `docs/jquery-usage.md`).
- Compiled assets: `build/` is git-ignored and never committed to main. GitHub
  Actions (`.github/workflows/deploy.yml`) builds on every push to main and
  force-pushes a single "Build <sha>" commit to the `deploy` branch, laid out as
  `wp-content/themes/citcom-rebuild/` (source plus `build/`, without node_modules,
  .baseline, docs, tools, .github, package files, webpack config, .editorconfig
  and logs) for Pressable's wp-content git integration. Point the staging site's
  git deploy at the `deploy` branch; decided 2026-09-28.

## Block data format (for the Phase 3 migration)

Learned while building the local fixture (tools/local-fixture.php is a working
example):

- Block attributes go through `serialize_block_attributes()`, not plain
  `wp_json_encode()`, so quotes, `<`, `>` and `--` inside field HTML cannot
  break the block comment.
- Content written with `wp_insert_post()` / `wp_update_post()` must be
  `wp_slash()`ed, or the JSON escapes in the block comment are stripped and
  the block loses its attributes.
- Repeaters are stored flattened, as in postmeta: `name` holds the row count,
  each sub-value is `name_{i}_{sub}`, and every key has a `_name...` twin with
  the field key. Nested arrays are ignored by ACF.
- Focal point fields (`citcom_focuspoint`) are always arrays `{id, top, left}`,
  also when empty.
- Fields hidden by conditional logic must be OMITTED from the block data (for
  example `header_image` on a pattern page header, `form` on a button CTA,
  `posts` on an archive listing). The editor never stores them, and ACF's
  block validation checks every key present, hidden or not, so a stored empty
  required field blocks saving with "An ACF Block on this page requires
  attention". The old flexible content rows contain all sub-fields, so the
  migration has to drop the hidden ones per layout.
- The old editor and media_text HTML strings become inner blocks; core/html
  is a valid interim container for HTML that has no clean block equivalent.
- Groups are flattened the same way as repeaters: `group_sub` for each
  sub-field (`player_options_options`, `creative_showcase_image`,
  `citdot_card_0_staff_name`), each with its `_group_sub` field key twin, and
  `group` itself stored as an empty string. Conditional groups that are hidden
  (`value` on a staff card) are omitted like any hidden field.
- Inside a block render, `get_field( 'name' )` with no post id resolves to the
  block's own data (ACF local meta), not to the global `$post`. Template parts
  rendered from a block for another post (template-parts/service-card.php)
  must pass the post id explicitly.
- Texturize: the old layouts were rendered outside `the_content`, so text and
  textarea values were printed untouched and only WYSIWYG fields were
  texturized (`acf_the_content`). Blocks now render inside `the_content`, so
  inc/blocks.php removes `wptexturize` from it and applies it to `core/*`
  blocks only (the InnerBlocks that replaced the WYSIWYG fields); classic
  content without blocks keeps the default. Compared against staging, this
  keeps straight quotes and `...` in ACF fields as they are.
- Staging's rendered sections differ from the reference theme code in two
  places that are not the theme's doing: the Elfsight `<script>` of the
  google_reviews layout is not inside the section on staging (moved by a
  plugin), and `&` in social URLs prints as `&amp;` rather than `esc_url()`'s
  `&#038;`. Both are equivalent in the browser.

## Still to capture

- Lighthouse / Query Monitor baseline on 5 representative pages (not done).
- List of shortcodes used by the `shortcode` layout (17 rows) and which
  plugins provide them (not done; the theme's own `google_reviews`,
  `youtube_gallery` and `chatcom` shortcodes are already ported in
  `inc/shortcodes.php`). Read them from the `acfAllObjects_{id}` option rows on
  staging when the Phase 3 migration script first parses that data.
- Decision on the `template` post type (6 items): it must stay for now, because
  archive.php and single.php render the template posts chosen in Site Settings
  > Templates (case study archive, services archive, tag archives, blog post
  footer). Revisit once those templates are blocks.
- Font Awesome kit allowed domains (see the baseline note above).
