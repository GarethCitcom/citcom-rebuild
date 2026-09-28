# Phase 1 brief: foundation

Goal: a bootable `citcom-rebuild` theme with tokens, build pipeline, header,
footer and ONE block (`citcom/page-header`) rendering pixel-identically to the
old `page_header` layout, so the pattern is proven before the block library.

## Deliverables

1. Theme scaffold: `style.css` header (Theme Name: Citcom Rebuild), `functions.php`
   that requires `inc/*.php` (setup, assets, blocks, acf, cpt, helpers), and
   `theme.json` (v3) carrying colours, font families, font sizes and spacing
   extracted from the old `assets/_dev/scss/theme/_variables.scss` (or wherever
   the Bootstrap overrides live). Disable core colour/typography UI that the
   design doesn't use; lock the editor to `citcom/*` blocks plus a small core
   allow-list (paragraph, heading, list, image, buttons, table, separator,
   spacer, group, columns) via `allowed_block_types_all`.
2. Build: `package.json` with `@wordpress/scripts`, `sass`; `src/theme.scss`
   importing the ported SCSS (Bootstrap 5.3 subset only for what is used);
   `wp-scripts build` producing `build/theme.css`, `build/theme.js`, and per
   block assets. jQuery stays for now (main.js depends on it); log every
   jQuery dependency in `docs/jquery-usage.md` for removal in Phase 4.
3. Templates: `header.php`, `footer.php`, `index.php`, `page.php`,
   `single.php`, `single-service.php`, `single-case-study.php`,
   `single-landing-page.php`, `archive.php`, `search.php`, `404.php`,
   `template-parts/`. Port the old markup 1:1. `page.php` and the singles
   simply call `the_content()` inside `<main>`; the blocks provide the
   sections.
4. CPT/taxonomy registration moved from ACF UI JSON into PHP
   (`inc/cpt.php`) with identical slugs, rewrite rules and supports (add
   `editor` support and `show_in_rest` so the block editor is available).
   Keep the ACF post-type JSON files out of the new theme.
5. Options page (Site Settings) field groups copied into `acf-json/` unchanged
   (same keys) so existing option values keep working.
6. Shared "Section settings" field group + `citcom_section_attrs()` helper.
7. `citcom/page-header` block complete: `fields.json` (title, type, service,
   bg-color, pattern, header_image, breadcrumb), `render.php` producing the
   same markup as `flexPageHeader()`, `style.scss` ported from
   `flexContent/_page-header.scss`, editor preview working.
8. A local fixture page in Laragon using page-header + core paragraph, and a
   desktop screenshot compared by eye against the same page on staging.
9. Update `docs/00-discovery.md` "Still to capture" items as they get done.

## Not in Phase 1

Other blocks, the migration command, performance work, deploy wiring (Phase 5).

## Reference pointers

- Old rendering: `templates/acf-getFlexLayout.php`,
  `templates/flexFunctions/page_header.php`,
  `functions/acf/flexible-content.php` (layoutSettings, blocksStyles).
- Old asset loading: `functions/styles-scripts.php`.
- Old header/footer: `header.php`, `footer.php`, `functions/lib/get-logos.php`
  (fix the uploads path bug when porting), `functions/theme_content/menus.php`,
  `functions/lib/bs4Navwalker.php`.
- Field definitions: `acf-json/group_66f9a66681625.json` (layouts),
  `group_65f8774c86a9d.json` (Section settings), `group_66042a32b1be4.json`.
