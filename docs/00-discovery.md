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
- Migration source (revised in Phase 3): the raw values in the `acf` meta
  row, which are what the blocks store, with individual postmeta rows as the
  fallback. The `acfAllObjects_{id}` JSON holds formatted values (images as
  arrays) and is not used; the cleanup step deletes it. See
  docs/03-phase3-brief.md.

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
  `inc/acf.php` so existing option and post-meta values stay readable. Phase 3
  keeps it: the migration reads the single row, and nothing needs the values
  as individual postmeta.
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

## Findings added in Phase 3

- The deploy branch had never contained `build/`. The workflow copied the
  repo's `.gitignore` into the deploy tree, and that file ignores `build/`,
  so `git add -A` left the compiled assets out while the job still passed.
  Staging had five build files from 2026-09-28, put there by hand. Found on
  the first deploy of 2026-10-05, before the theme was activated. The
  workflow now keeps `.gitignore` and `CLAUDE.md` out of the tree, anchors
  every exclude to the theme root (an unanchored `vendor`, added with
  Composer, had also dropped `assets/vendor/`), and fails if a compiled file
  is missing from the commit it is about to push.
- Pressable's git deploy adds and updates files but does not delete the ones
  removed from the repo. After removing a file, delete it on the server too
  (done for `templates/wc26.php`, `src/js/forminator-bootstrap.js`,
  `.gitignore` and `CLAUDE.md` on staging).
- `wp edge-cache purge --domain=<host> --yes` purges Pressable's edge cache
  from WP-CLI.
- Core block styles inside the sections. The old pages carried no core block
  CSS for their editor content: it came from the cache, so the blocks never
  rendered during a page view and WordPress never loaded their stylesheets,
  their block-supports rules (`wp-container-*`) or their per-block global
  styles. Rendered live, the inner blocks pulled all three in: core/columns
  went from the 0.5em flex gap to 2em (21px more between stacked columns on
  mobile) and images shifted on their baseline. inc/blocks.php now removes
  what the inner blocks of citcom/editor and citcom/media-text enqueue while
  they render. Blog posts and the sidebar block widgets always rendered live
  and keep their core styles; the sets of inline styles per page type match
  the old site's again. Found by the screenshot comparison on staging, not by
  the markup comparison (the markup was identical).
- Plugins that filter `the_content` now reach the sections, because blocks
  render inside it. On staging that is the Admin and Site Enhancements
  setting that opens external links in a new tab (and adds nofollow).

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
- Section settings per block: most blocks take the full group
  (`group_65f8774c86a9d`). `citcom/cta` takes only `anchor_name` from
  "Section settings (simple)" (`_anchor_name` = `field_6790f3345a787`, not the
  full group's `field_6616e84d4614e`), and `citcom/quote` takes none, as the
  old layouts did.
- Sidebar widgets: the old blocks are stored in the `widget_block` option as
  `wp:acf/latest-posts`, `acf/newsletter-signup`, `acf/posts-search`,
  `acf/related-posts` and `acf/top-blog-posts`. The migration renames them to
  `citcom/*` (block comment name and the `name` attribute); field names and
  keys are unchanged.
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
- Stylesheet order: in the old single stylesheet the flexContent partials came
  last, after Bootstrap and the theme partials. WordPress prints block styles
  where its placeholder is enqueued (wp_enqueue_scripts, priority 10), so the
  theme stylesheet is enqueued at priority 1 to keep the same cascade (theme
  first, blocks after). Block styles are inlined in the head up to
  WordPress's 40 KB total; on a heavy page (the diner home page) the largest
  are linked instead, and inc/assets.php keeps those render-blocking and
  versions them by file time.
- Swiper CSS: the old theme's `plugins/swiper.scss` is the Swiper 11.2.10
  bundle with one rule changed, `.swiper-slide` without `width: 100%` and with
  `height: auto`. The logo swiper depends on it (the col-* classes size the
  slides), so `src/scss/plugins/_swiper.scss` is that file, not the vendor
  bundle from node_modules.
- A `static` variable in a block's render.php does not persist between blocks:
  statics in an included file start again on every include. Use
  `citcom_counter()` / `citcom_once()` (inc/helpers.php) for ids and for
  inline scripts printed once per page.
- CSS masks and ShortPixel: block CSS is often inlined into the page, and
  ShortPixel rewrites url()s it finds in the HTML to its CDN; a mask image
  served from another origin is blocked by the browser. SVG masks are inlined
  as data URIs by the build; the one raster mask (diner intro,
  texture-worn.webp) is inlined with `url(...?inline)` (webpack.config.js).
  Check the intro ribbon on staging once deployed.
- `&` in social URLs prints as `&amp;` on staging rather than `esc_url()`'s
  `&#038;`; equivalent in the browser.
- Reviews (decided 2026-09-30): Elfsight is no longer used. The old theme
  still printed its embed from the google_reviews layout (6 rows: /packages/
  and its four sub-pages, /info/free-seo-audit/) and from the `google_reviews`
  and `youtube_gallery` shortcodes (the second was a copy of the first, never a
  gallery). The rebuild uses Trustindex everywhere: the migration turns each
  google_reviews row into `citcom/trustindex` with `trustindex_code` set to
  `[trustindex no-registration=google]` (the widget the diner reviews layout
  shows on the home page, from the Trustindex plugin); `[google_reviews]`
  prints the same widget; `[youtube_gallery]` stays registered and prints
  nothing. These six sections therefore differ from the baseline on purpose.
  The `trustindex_code` fields accept only a `[trustindex...]` shortcode or a
  cdn.trustindex.io loader snippet (validated on save, rebuilt on output).

## Still to capture

- Lighthouse / Query Monitor baseline on 5 representative pages (not done).
- List of shortcodes used by the `shortcode` layout (17 rows) and which
  plugins provide them (not done; the theme's own `google_reviews`,
  `youtube_gallery` and `chatcom` shortcodes are in `inc/shortcodes.php`, the
  first two no longer Elfsight, see the reviews note above).
  Done 2026-10-02 from a copy of staging's content (docs/03-phase3-brief.md,
  "Real-content test"): `[forminator_form]` (theme forms), `[trustindex]`
  (Trustindex plugin), `[pricing_table]` (the package-pricing-table plugin,
  which stays), and four rows holding a Google Calendar iframe as HTML.
- Decision on the `template` post type (6 items): it must stay for now, because
  archive.php and single.php render the template posts chosen in Site Settings
  > Templates (case study archive, services archive, tag archives, blog post
  footer). Revisit once those templates are blocks.
- Font Awesome kit allowed domains (see the baseline note above).

## Forms: Forminator inventory (read from staging 2026-09-30)

Forminator is being dropped (decided 2026-09-30): forms are built into the
theme, Mailchimp is the only integration, the old submissions are not
exported, and new submissions are kept for 30 days. Gareth marked which forms
and pages are still needed in docs/pages-and-forms-review.xlsx (see "Review
decisions" below). Read with `wp --skip-themes` on staging 1771004.

| Id | Form | Fields (* required) | Used on | Entries |
|---|---|---|---|---|
| 623 | contact-form | Name*, Email*, Message, mailing list checkbox, Company name* | contact_map (/contact-us/, /about-us/, /home/), cta "form", some service pages | 112 |
| 581 | newsletter-signup | name*, email*, two text*, consent checkbox* | footer newsletter modal (posts page only), /info/mailing-list/, newsletter page | 15 |
| 220023 | diner-guest-check-form | Name*, Last name*, Company name*, Email*, Message, mailing list checkbox | diner guest check (home, diner landing page) | 8 |
| 218706 | send-a-brief | name, email*, phone*, two text*, textarea*, captcha | /send-us-a-brief/, events service pages | 4 |
| 219819 | citcom-client-survey-2 | name*, email*, checkbox*, textarea | /packages/ and its four sub-pages | 0 |
| 219727 | citcom-client-survey | ten fields, mostly checkbox and radio groups | /info/client-survey/ | 14 |
| 219725 | free-technical-audit | ten fields incl. url*, two selects, slider* | /info/zero-click-era/ | 1 |
| 218841 | seo-audit | name*, email*, phone*, select | /info/free-seo-audit/ | 2 |
| 218732 | citcom-event-rsvp | six fields incl. phone*, email*, checkbox | /info/citcom-autumn-event/ | 25 |

- Quizzes (Forminator's scored quiz type): 219262 technical-skills-assessment,
  219265 jd-basic, 219271 basic-quiz and one unnamed (219273); used on
  /info/junior-developer-quiz/; 25 entries between them.
- Mailchimp: the Forminator Mailchimp add-on is active and connected on forms
  581, 623, 218732 and 220023 (the mailing list checkbox subscribes).
- Spam: honeypot on; Akismet off; a captcha field on send-a-brief.
- Behaviour: ajax submit, submissions stored, email notifications with
  conditional routing on 623 and 220023 (staging recipients are rewritten to
  the staging domain).
- Where the old theme touched Forminator: `form` post_object fields (post type
  `forminator_forms`) in the cta, contact_map and diner_guestcheck layouts;
  `[forminator_form]` in footer.php (newsletter modal) and in those three
  layouts; a jQuery module that added Bootstrap classes to Forminator's
  markup; `.forminator-*` rules in the form fields SCSS; the "SEND TO THE
  KITCHEN" relabel script in diner-guestcheck; and `[forminator_form]` /
  `[forminator_quiz]` shortcodes inside shortcode layouts on the /info/ pages.
- Trustindex on staging is the free plugin `wp-reviews-plugin-for-google`.

### Review decisions (docs/pages-and-forms-review.xlsx, filled in 2026-09-30)

- Forms kept, all rebuilt as theme forms: 623, 220023, 581, 218706, 219819,
  219727, 219725, 218841.
- Forms retired: CitCom Event RSVP (218732) and all four quizzes (219271,
  219265, 219262, 219273). Their shortcodes print nothing.
- Pages retired (every other page, landing page and service is kept). The
  Phase 3 migration deletes them (decided 2026-10-01, no redirects):
  /comp/ (218753), /marketing-agreement/ (219133), /suite/ (218516),
  /test-about-us-updates/ (219465), /info/citcom-autumn-event/ (218718),
  /info/ihd-ojdiudsa/ (219758), /info/junior-developer-quiz/ (219260),
  /info/thank-you-rsvp/ (218743), /info/wc2026/ (219935).
- `templates/wc26.php`, the page template of the retired /info/wc2026/ page
  (it printed the wc2026-sweepstake plugin's shortcode), is removed; that
  plugin is not needed by the theme.
- Once the migration is done nothing in the theme needs the Forminator plugin;
  deactivating it on staging is Gareth's call.

### Theme forms (built 2026-09-30)

- `inc/forms.php` is the engine (registry, REST route, storage, retention,
  recipients, shortcodes), `inc/forms-fields.php` renders, validates and
  summarises the fields, `forms/<slug>.php` are the definitions: `contact`
  (623), `guest-check` (220023), `newsletter` (581), `brief` (218706),
  `packages` (219819), `client-survey` (219727), `technical-audit` (219725)
  and `seo-audit` (218841). Each definition lists the Forminator ids it
  replaces (`legacy_ids`), so block data and `[forminator_form id="..."]`
  shortcodes that still carry an old id render the theme form with no data
  change. `[forminator_form]` with any other id, `[forminator_quiz]` and
  `[forminator_poll]` print nothing, whether or not the plugin is active.
- Field types: text, email, tel, url, textarea, select, range, checkbox,
  checkboxes (optionally with an "Other" text box), radio, html. A field can
  be shown only when another has a given value (`show_if`); hidden fields are
  neither required nor stored.
- Markup: own class names (`citcom-form-*`), three variants.
  - "bootstrap" (default): the structure Forminator had after the old jQuery
    module restyled it. Contact, newsletter, brief, technical audit, SEO audit.
  - "plain": no Bootstrap classes, for the diner guest check, which was
    rendered inline and never restyled.
  - "classic": Forminator's own "default" design, which the packages and
    client survey forms used; colours are CSS custom properties per form
    (`src/scss/elements/_forms.scss`).
- Compared with staging, same size and computed styles: contact 452x545,
  guest check (0.27% of pixels differ, anti-aliasing), packages 646x356
  (0.000%), client survey 1316x2222 (0.436%), technical audit 1316x889
  (1.673%, a 1px capture offset), SEO audit 646x452.
- The technical audit form's bolder labels and red stars were that form's own
  custom CSS in Forminator; they are scoped to `.citcom-form--technical-audit`.
- Forminator's two-column grid applied from 783px unless the form itself was
  480px or narrower; the theme does the same with a container query.
- Submissions go to `POST /wp-json/citcom/v1/forms/<slug>`, are stored as
  private `citcom_submission` posts (wp-admin > Form submissions) and deleted
  by a daily cron event after `CITCOM_FORMS_RETENTION_DAYS` (30).
- Recipients and the Mailchimp key and audience are in Site Settings > Forms
  (`acf-json/group_citcom_forms.json`); the key can also be the
  `CITCOM_MAILCHIMP_API_KEY` constant. They are left empty in the repo on
  purpose: Gareth enters them on each server (decided 2026-09-30), and the key
  is never read out of staging. With nothing entered, email goes to the site
  admin address and Mailchimp sign-ups are skipped.
- Recipient fields: "Enquiries go to" (general), "Mailing list sign-ups also go
  to" (marketing), and a "Different recipients for a form" list that overrides
  the general address for one form. How the old forms were routed (the named
  addresses are not recorded here, this repo is public; Gareth has the list):
  - contact, guest check, brief: the general enquiries address, plus the
    marketing contact when the mailing list box is ticked (contact and guest
    check)
  - newsletter: the marketing contact
  - packages and client survey: two named people
  - technical audit and SEO audit: one named person each
- Only contact, guest check and newsletter send to Mailchimp, as before.
- Mailchimp as the old add-on had it: audience `a4ffec5bd1`, no double opt-in,
  marketing permission `8efa2d4074` (Email); tag "Website Signup" for contact
  and newsletter, "Diner Mailing List" for the guest check; the newsletter
  also sets interest `97867c40f1` in the Monthly Marketing Round-Up group.
- Behaviour changes, on purpose:
  - The old add-on on the contact and guest check forms had no condition, so
    it appears to have subscribed every sender, ticked or not. The theme forms
    subscribe only when the mailing list box is ticked.
  - The newsletter popup only had its form on the blog listing and the brief
    popup had its form commented out, so both opened empty elsewhere. Both
    popups now always contain their form.
  - GTM still gets `formsuccess` / `formfailed`, with the form slug instead of
    the submitted data.
  - The captcha field on send-a-brief is not carried over; spam protection is
    a honeypot, a minimum time on the page and a rate limit.
  - The SEO audit's website address stays a free text field, as it was (a
    repurposed phone field); the technical audit's is a URL field and accepts
    an address typed without https://.
- The jQuery module that restyled Forminator's markup and the `.forminator-*`
  rules are removed; `src/js/forms.js` has no jQuery.

### Customizer Additional CSS

The old theme has Customizer > Additional CSS (924 bytes on staging). WordPress
stores it per theme, so it does not follow the site to the new theme. It is
ported in `src/scss/theme/_additional-css.scss`: the `#chatcom` section
background (needs `assets/img/patterns/ai-gradient.jpg`, now copied), a top
margin on the diner landing page's menu heading (`body.postid-219887`) and 60px
under every form submit button. Its small-screen rule for horizontal radio
groups is not ported: it went with the technical audit form's radio styling,
and that form no longer has a radio field.