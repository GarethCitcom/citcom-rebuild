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
  markup already, so it becomes the block's inner blocks as it is. Classic
  HTML (no block comments) is converted with `citcom_html_to_blocks()` and
  noted in the report.
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
- The old `post_content` (acf-to-content's search copy) is kept in
  `_citcom_legacy_content` for the rollback; the raw ACF values are not
  touched by a run, so the old theme keeps working on them until the cleanup
  step after sign-off.
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

## Staging run

Needs WP-CLI on staging (the Pressable connector, or SSH). Staging only; live
is Phase 5.

1. Pressable backup of staging. The visual baseline is already captured
   (`.baseline/old`, 2026-09-29).
2. `wp plugin deactivate acf-to-content` (it rewrites post_content from the
   old fields on save, which would wipe migrated content). Leave Forminator
   until the forms are checked, then deactivate it too.
3. Point the Pressable git deploy at the `deploy` branch and deploy the theme
   (build included). Push to GitHub first, when Gareth says so.
4. `wp theme activate citcom-rebuild`, then at once
   `wp citcom migrate acf-ui`: the theme registers the post types, taxonomy
   and options page in PHP, so the ACF UI copies must go inactive in the same
   session. Check `wp citcom migrate acf-ui --dry-run` first: every
   `acf-field-group` that the theme ships as JSON shows "keep".
5. `wp citcom migrate retire` (binned; the bin keeps them 30 days).
6. `wp citcom migrate run --dry-run --report=/tmp/migrate.json`; read every
   warning and note. Expected notes: the shortcode list, and any classic HTML
   conversions. A "no block for layout" warning means a layout the discovery
   did not see; stop and look.
7. `wp citcom migrate run`, then `wp citcom migrate widgets`.
8. `wp rewrite flush`, `wp cache flush`, purge the Pressable edge cache.
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
