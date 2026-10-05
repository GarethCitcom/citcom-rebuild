# Phase 3 brief: content migration

Goal: every page, service, case study, landing page and template post on
staging renders through citcom/* blocks instead of the old flexible content,
pixel-identical to its shot in `.baseline/old`, with no content re-entered
by hand. The vehicle is one WP-CLI command in the theme, `wp citcom migrate`,
that can be dry-run, run, undone and run again.

## What moves, and from where

- Source: the raw ACF values of each post. ACF Extended's "ultra" performance
  mode keeps them in one `acf` postmeta row (a post saved before that mode
  was on has individual rows; both are read). A flexible content row is
  stored flat: `blocks` holds the layout names in order, `blocks_{i}_{field}`
  the row's values, `blocks_{i}_layout_settings_{field}` the Section settings
  the ACF Extended layout modal cloned into the row. The old theme's
  `acfAllObjects_{id}` option cache is not used: it holds formatted values
  (images expanded to arrays), while the blocks need the raw ones.
- Target: `post_content` holding one serialised block per row, built from the
  new theme's own field definitions. For a block, the migration takes the
  field group(s) ACF locates on it (its own group, and the Section settings
  group attached to it) and copies each field's value from the row under the
  new field name and key, flattened the way ACF blocks store repeaters and
  groups, with the `_name` key twins and focal points as arrays. The block
  comment therefore holds exactly what the editor would have stored.
- Editor and media_text rows: the block editor clone (`content`) is block
  markup already, so it becomes the block's inner blocks. Classic HTML (no
  block comments) is converted with `citcom_html_to_blocks()` and noted in
  the report.
- Inner block attributes: the old field stored its blocks with bare comments
  (`<!-- wp:heading -->` in front of an h4, `<!-- wp:spacer -->` in front of
  a 40px spacer). That renders correctly, but the block editor rebuilds the
  markup from the attributes and marks the block "unexpected or invalid
  content". `citcom_migrate_restore_attrs()` reads the attributes back from
  the saved HTML for the four block types that need them (heading, image,
  spacer, embed); the HTML is not changed.
- google_reviews rows become citcom/trustindex with the default widget
  (`[trustindex no-registration=google]`); form fields holding a Forminator
  post id become the theme form's slug (an id with no theme form is a
  warning); a swiper row of the "content" type (never built) is a warning.
- Disabled rows (ACF 6.5 layout meta, or the older ACF Extended toggle) are
  not rendered on the old site, so they are skipped and counted; a renamed
  row keeps its custom title as the block's name in the List View.
- Hidden fields: the old editor kept a hidden sub-field's last value in the
  row and the old templates read some of them regardless (a section pattern
  switched on, then the background set back to default, still printed the
  pattern; /contact-us/ has one). Hidden fields that hold a value are
  therefore copied; empty ones are left out, as the block editor would, so
  no empty required field can stop a page saving.
- Section settings the old site never applied: the old front end rendered
  from the `acfAllObjects_{id}` cache, not from the rows. Where a cached row
  has no section settings, the page rendered without any, whatever the row
  holds, so the migration leaves that block's settings at their defaults and
  notes what was saved. One row on staging: the reviews section of
  /info/citcom-creative-diner/ (bottom padding off and a light background
  were saved in the editor and never shown). The cache is used for nothing
  else, and is ignored when it does not line up with the rows.
- The old `post_content` (acf-to-content's search copy) is kept in
  `_citcom_legacy_content` for the rollback; the raw ACF values are not
  touched by a run, so the old theme keeps working on them until the cleanup
  step after sign-off. A run and a rollback keep each post's modified date.
- Also handled: the `acf/*` sidebar blocks in the block widgets are renamed
  to `citcom/*`; the ACF UI post types, taxonomy, options page and the old
  field groups the theme does not ship as JSON are deactivated (never
  deleted); a page template from the old theme is reset to default; the nine
  retired pages are binned.

## The command

    wp citcom migrate status
    wp citcom migrate acf-ui [--dry-run] [--undo]
    wp citcom migrate retire [--dry-run]
    wp citcom migrate run [--post=<ids>] [--type=<types>] [--dry-run] [--force] [--show] [--report=<file>]
    wp citcom migrate widgets [--dry-run]
    wp citcom migrate rollback [--post=<ids>]
    wp citcom migrate cleanup [--dry-run] [--yes]

Code: `inc/migrate.php` (plain functions, one per step) and
`inc/cli-migrate.php` (the command), loaded only under WP-CLI. A run writes
through `wp_update_post()` with kses off (it would strip the embed code in
block attributes) and without `wp_targeted_link_rel`, so the markup stays as
the editors wrote it. A post already migrated is skipped unless `--force`.
The report lists rows, converted, disabled, warnings and notes per post;
notes include every shortcode a `shortcode` row relies on, which answers the
"Still to capture" item in docs/00-discovery.md.

## Local test (done 2026-10-01)

There is no copy of the staging content locally, so the storage format was
reproduced with `tools/local-fixture-legacy.php`: it takes the block fixture
pages, turns their blocks back into flexible content rows and lets ACF write
them, with the old field groups registered from the reference theme's
acf-json at run time (never copied) and the diner layouts rebuilt from the
diner blocks. Then `wp citcom migrate run` on the copies and
`tools/legacy-compare.mjs` against the originals.

Result: ten pages (About, Contact, the diner home page, diner extras, Results,
Home Classic, the Creative service, Packages, Website Packages, the forms test
page), 49 rows across 23 of the 25 layouts, all identical after migration
(the `<main>` markup, apart from ids and nonces). display_posts (the blog page
and the case study archive template, not public pages) was checked on the
block data instead: identical apart from an empty hidden field the fixture had
stored. Four migrated pages were opened in the block editor: no block errors,
no "requires attention" notice. Rollback, re-run and the already-migrated
guard were exercised. The copies (`/legacy-<slug>/`) are still on the local
site for re-testing.

What this does not test: the real content's oddities (classic HTML in editor
fields, stale values, rows of layouts with few uses). That is what the staging
dry run is for.

## Real-content test (done 2026-10-02)

A copy of staging's content was taken over SSH (read-only) and loaded into a
separate local site: `C:\laragon\www\citcom-replica`, database
`citcom-replica`, served with `php -S 127.0.0.1:8899 router.php` from that
folder. Only what the test needs was copied: posts (without Forminator's and
a few plugin post types), postmeta, terms, a whitelist of settings (the ACF
options, widgets, reading and permalink settings), the `acfAllObjects_*`
cache, and the users' ids and display names with placeholder emails. No
password hashes, form entries, plugin settings or API keys left staging.
Uploads stay on staging (attachment URLs point there); the 40 SVGs the theme
reads from disk were copied.

The steps of "Staging run" below were then run on the copy:

- `acf-ui`: 8 entries deactivated (4 post types, the taxonomy, the options
  page, the Flexible Content and Flex Block Editor groups); 15 field groups
  kept because the theme ships them.
- `retire`: 9 pages binned.
- `run`: 107 posts, 753 rows, 752 blocks, 1 disabled row skipped (the unused
  light story section on the home page), no warnings.
- `widgets`: 8 of 16 block widgets renamed.
- Shortcodes the `shortcode` rows rely on: `[forminator_form]` for forms 581,
  218706, 219725 and 219727 (all theme forms now), `[trustindex
  no-registration=google]` (Trustindex plugin), `[pricing_table]` four times
  (the package-pricing-table plugin on staging, which stays), and four rows
  that hold a Google Calendar appointment iframe as plain HTML.

Checks:

- Every page of docs/urls.csv was compared, section by section, with the HTML
  captured from staging under the old theme: 713 of 761 sections identical.
  The other 48: 16 belong to the retired pages; 8 are forms (theme form in
  place of Forminator); 9 are Trustindex or Elfsight sections and 4 the
  pricing tables, whose plugins are not installed on the copy; 9 are a
  trailing space in a sidebar widget's class attribute on the blog pages;
  1 is a video poster that needs the audio_video_player field type (a
  staging mu-plugin); 1 is a link in a case study whose cached HTML on
  staging is older than its saved content (the old site still shows
  `target="_blank"`, the editor's content does not have it).
- Differences that come from rendering live instead of from the cache, and do
  not show: `wp-block-paragraph` on paragraphs the cache rendered before
  WordPress added that class, the hash in `wp-container-core-columns-*`, and
  `fa-classic` on icons (the Font Awesome field's kit settings were not
  copied).
- All 3,094 blocks of the 107 posts were parsed with the block editor's own
  validator: 207 invalid in 56 posts before the attribute restore, 0 after.

What the test changed in the code: the attribute restore, the unapplied
settings rule, keeping the modified dates, and a fix to the post card
(`template-parts/card-post.php` read its image without a post id, so a case
study with no featured image lost its card image inside a display-posts
block).

For the live cutover (Phase 5) the same copy-and-compare should be repeated
with live's content first: live may hold cases staging does not.

## Staging run (done 2026-10-05)

Run over SSH on staging, in the order below; live is Phase 5. Outcome:

- Database backup on the server before anything else:
  `~/citcom-backups/pre-migration-2026-10-05.sql` (outside the web root).
- The push showed that CI had never published `build/` to the deploy branch
  (see docs/00-discovery.md, "Findings added in Phase 3"); fixed in the
  workflow before the theme was activated. Four files Pressable's deploy had
  left behind in the theme folder were removed by hand.
- `acf-to-content-master` deactivated, theme activated, `acf-ui` (8 entries
  deactivated), `retire` (9 pages binned), dry run, `run` (107 posts, 752
  blocks, 1 disabled row skipped, no warnings), `widgets` (8 renamed),
  rewrite and object cache flushed, edge cache purged. Each step reported
  exactly what it had reported on the copy.
- The migrated `post_content` of all 107 posts is byte-identical to the
  copy's, which passed the block editor's validator; modified dates are
  unchanged.
- Every page was compared again, section by section, with the HTML captured
  under the old theme: the only differences are the retired pages, the forms,
  the six Trustindex sections, and one that the copy could not show: the
  Admin and Site Enhancements setting "open external links in a new tab" now
  reaches page content. It works on `the_content`, which the old theme's
  sections never went through, so external links in sections now get
  `target="_blank" rel="noopener noreferrer nofollow"` as they already did in
  blog posts. Nothing changes visually. It is the plugin's setting, left as
  it is; Gareth to decide whether nofollow on every external link (social
  profiles and client sites included) is wanted.
- The footer menu no longer shows "Marketing Agreement": the page is retired,
  and WordPress hides the menu item of a binned page. The footer is 45px
  shorter on desktop and about 40px on mobile on every page because of it.
- Screenshots. All of docs/urls.csv was captured again on staging and
  compared with `.baseline/old` by `tools/visual-align.mjs` (a plain pixel
  diff fails every page on the footer change alone). The first comparison
  found what the markup comparison could not, all fixed and redeployed the
  same day (docs/00-discovery.md, "Findings added in Phase 3"): core block
  styles loading inside the sections, the collapsed page header on blog posts
  and search results, two form spacings on small screens, and a capture tool
  that caught lazy images half loaded. Final run, 227 shots of pages that
  were kept (18 belong to the retired pages; /info/zero-click-era/ on mobile
  timed out waiting for the network to go idle and was checked by measuring
  its form instead):
  - Layout: in 211 shots every content row lines up with the baseline. The
    other 16 are the five packages pages and /info/free-seo-audit/ (Trustindex
    in place of Elfsight, theme form in place of Forminator), the search
    results (more matches now) and two shots that differ by one CSS pixel
    over the whole page.
  - Pixels inside the aligned rows: 213 shots within 0.5%, 12 between 0.5%
    and 2%, 2 above (video pages, where the new capture shows the video's
    first frame and the baseline a black player). What is left is images:
    the browser picks from srcset differently now that they are lazy, so a
    photo can be resampled or cropped a pixel or two differently.
- Still to do on staging: enter the recipients and the Mailchimp key in Site
  Settings > Forms and send one test per form; deactivate Forminator once
  the forms are accepted; `wp citcom migrate cleanup` after sign-off.

The steps, for the record and for the live run:

1. Backup: a Pressable backup, or `wp db export` to a folder outside
   `htdocs`. The visual baseline is already captured (`.baseline/old`,
   2026-09-29).
2. `wp plugin deactivate acf-to-content` (it rewrites post_content from the
   old fields on save, which would wipe migrated content). Leave Forminator
   until the forms are checked, then deactivate it too.
3. Push to GitHub, when Gareth says so. The staging site's git deploy
   already follows the `deploy` branch, so the build arrives a minute or so
   after CI finishes. Check `build/theme.css` on the server against the
   branch before activating, and remove any file the repo no longer has:
   Pressable's deploy adds and updates files but does not delete them.
4. `wp theme activate citcom-rebuild`, then at once
   `wp citcom migrate acf-ui`: the theme registers the post types, taxonomy
   and options page in PHP, so the ACF UI copies must go inactive in the same
   session. Check `wp citcom migrate acf-ui --dry-run` first: every
   `acf-field-group` that the theme ships as JSON shows "keep".
5. `wp citcom migrate retire` (binned; the bin keeps them 30 days).
6. `wp citcom migrate run --dry-run --report=/tmp/migrate.json`; read every
   warning and note. Expected, from the real-content test: no warnings; notes
   for the shortcode list and for the diner landing page's unapplied section
   settings. A "no block for layout" warning means a layout the discovery did
   not see; stop and look.
7. `wp citcom migrate run`, then `wp citcom migrate widgets`.
8. `wp rewrite flush`, `wp cache flush`,
   `wp edge-cache purge --domain=<host> --yes`.
9. Checks:
   - `node tools/visual-baseline.mjs capture --base https://citcomstaging.mystagingwebsite.com --out .baseline/new`
     then `diff`. Known, intended differences: the six Trustindex sections
     (Elfsight is gone), form markup (theme forms), the retired pages (404).
   - Open ten posts of mixed types in the editor: no "requires attention",
     the List View shows the custom row titles where they were set.
   - Forms: one test submission per form, Site Settings > Forms filled in
     with the staging addresses and the Mailchimp key by Gareth.
   - Site search still finds page copy (WordPress searches post_content, which
     now holds the block markup with the text inside).
10. Sign-off, then `wp citcom migrate cleanup` (deletes the 368
    `acfAllObjects_*` options, the raw rows and the rollback copies) and
    `wp plugin deactivate forminator`. The account-level mu-plugins
    acf-focuspoint and acf-getallobjects can then be removed by Gareth; the
    theme no longer needs them.

Rollback at any point before the cleanup: `wp citcom migrate rollback`,
`wp citcom migrate acf-ui --undo`, `wp theme activate citcom`, restore the
binned pages from the bin, reactivate acf-to-content. The raw ACF values
were never changed.

## Not in Phase 3

Performance work beyond what the blocks already do, jQuery removal (Phase 4),
the live cutover and its checklist (Phase 5).
