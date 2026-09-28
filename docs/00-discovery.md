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
and mobile), no failures, only the deliberate 404 page returned 404. Stored in
`.baseline/old/` (562 MB, git-ignored) with `manifest.json`. Two things to know
when diffing against it:

- Font Awesome icons did not render in the staging shots (footer contact and
  social icons appear as list bullets and empty boxes). The kit at
  kit.fontawesome.com/4a7ba1b0a5.js is either domain-restricted to citcom.co.uk
  or too slow for the capture. Icon areas will always differ until the kit
  allows the staging host, or the diff ignores them. Check the kit's allowed
  domains before Phase 3 comparisons.
- The tool now takes `--insecure` (Laragon's self-signed certificate) and
  `--only slug,slug` (subset of urls.csv) for local captures.

## Findings added in Phase 1

- The templates nested `<main>` twice (page.php and flexible-acf.php both open
  one; get_header/get_footer are require_once so the header is not duplicated).
  The new theme outputs one `<main>`; the only selector that depended on the
  nesting (`body > main > main` for the modal blur) is now `body > main`.
- `focuspoint` and `swatch` are NOT ACF Extended field types. They are
  mu-plugins on the Pressable sites: `acf-focuspoint-master`
  (ooksanen/acf-focuspoint 1.2.0) and `acf-swatch-master`
  (nickforddev/acf-swatch 1.0.7). They stay in `wp-content/mu-plugins` on
  staging and live, so the new field JSON keeps those types; local installs
  need the same two plugins (installed locally under `wp-content/mu-plugins/`
  with a small loader, outside this repo).
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
