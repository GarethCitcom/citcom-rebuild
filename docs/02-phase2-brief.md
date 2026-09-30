# Phase 2 brief: block library

Goal: every flexible layout on the current site has a `citcom/*` block that renders
the same markup, so that after migration (Phase 3) each page is pixel-identical to
its shot in `.baseline/old`. Build in usage order so the most content is covered
soonest. `citcom/page-header` (Phase 1) is the pattern to copy.

## How each block is built

For every layout, in this order, one commit per block:

1. Read `templates/flexFunctions/<layout>.php` in the reference theme and the layout's
   sub-fields in `acf-json/group_66f9a66681625.json` (the diner layouts are in
   `functions/acf/diner-layouts.php`).
2. `blocks/<name>/fields.json`: the layout's sub-fields with their original keys and
   names, location `block == citcom/<name>`, group key `group_citcom_<name>`. Keep
   third-party types (`swatch`, `acfe_image_selector`, `acfe_column`,
   `acfe_code_editor`, `acfe_post_types`, `audio_video_player`, `font-awesome`,
   `range`) as they are; the field type plugins exist on staging and live
   (swatch is a mu-plugin, see docs/00-discovery.md). The old `focuspoint` type
   becomes the theme's `citcom_focuspoint` (same value shape).
3. `blocks/<name>/block.json`: `apiVersion` 3, category `citcom`, `acf.blockVersion`
   3, `acf.postTypes` limited to the five content types, `style` pointing at
   `file:../../build/blocks/<name>/style.css`, `supports.anchor` true,
   `supports.jsx` true only where the block uses InnerBlocks.
4. `blocks/<name>/render.php`: port `flexXxx()` line for line. Open with
   `citcom_section_attrs( $block, get_fields() )`, output
   `<section {anchor} class="section-padding flex-<old_layout_name> {classes} ..." data-index="{citcom_block_index()}">`
   and keep every inner class. Run editor content through `citdotLists()` where the
   old function did. Where the old code had a bug that affected output (for example
   page_header testing `service` against a field that stores `services`), keep the
   behaviour and comment it, because the baseline was captured with it.
5. `blocks/<name>/style.scss`: `@import "../../src/scss/tokens"; @import "../../src/scss/theme/mixin";`
   then the matching `assets/_dev/scss/flexContent/*.scss` partial. Shared partials
   (`diner-shared.scss`) become `src/scss/blocks/_diner-shared.scss` imported by each
   diner block. Vendor CSS a block needs (Swiper) is imported from node_modules inside
   that block's `style.scss`, not globally.
6. `blocks/<name>/view.js` only when the layout has JavaScript that is not already in
   `src/theme.js` (display_posts inline `displayPostsQuery`, contact_map Snazzy Maps
   embed, video player options). Register it via `block.json` `viewScript`.
7. Build, add the block to the local fixture page next to the same content as a
   staging page that uses it, and compare desktop and mobile shots by eye. Record the
   staging URL used for the comparison in the commit message.

## Order and notes per block

Rows are from docs/00-discovery.md. "Ref" is the staging page used to compare.

| # | Block | Rows | Notes | Ref |
|---|---|---|---|---|
| 1 | `citcom/editor` | 210 | InnerBlocks (`supports.jsx`) wrapped in `<section class="section-padding flex-editor"><div class="container-xl"><div class="editor">`. Allowed inner blocks: the core allow-list in inc/blocks.php (the old `acfe_block_editor` field allowed paragraph, image, quote, heading, list, shortcode, table, buttons, columns, media-text, spacer, group, video, embed). Content passes through `citdotLists()`; check whether the old layout wrapped output in `text-block`. Migration turns the stored Gutenberg HTML into real inner blocks. | /contact-us/ |
| 2 | `citcom/media-text` | 134 | Two focal point images with `range` scale, or a video (`audio_video_player`); `text` is a clone of the block editor field, so this block also uses InnerBlocks for the text column. `align`, `citdot_style`, `extra_padding` flags map to the `media_left/media_right` and `section-extra-padding` classes seen on staging. | /about-us/ |
| 3 | `citcom/cta` | 83 | `type_of_cta`, wysiwyg content, two link fields, optional Forminator form (`post_object` to a form post). Forminator renders via `do_shortcode`. | /services/creative/ |
| 4 | `citcom/display-posts` | 68 | Ports `flexPosts()` plus `templates/card-post.php` (becomes `template-parts/card-post.php`) and the two admin-ajax handlers in `functions/ajax.php` (`loadmore`, `postsearch`) into `inc/ajax.php`. Prints the `displayPostsQuery` object the JS expects. Used by the archive template posts, so `/case-studies/` and `/services/` only look right once this and the template posts are migrated. | /case-studies/ |
| 5 | `citcom/icons` | 48 | `number_of_columns` radio and `icon_columns` repeater with Font Awesome icons. | /services/marketing/ |
| 6 | `citcom/gallery` | 39 | `style` image selector (seven layouts, thumbnails in `assets/img/gallery/`) and a repeater; port `flexContent/gallery.scss`. | /services/print/ |
| 7 | `citcom/quote` | 26 | wysiwyg quote and source; relies on `.flex-quote .wp-block-quote.is-style-plain` rules already in `src/scss/theme/_global.scss`. | /case-studies/bt-smb/ |
| 8 | `citcom/shortcode` | 17 | before/after wysiwyg around a shortcode text. Needs the list of shortcodes in use (see "Still to capture"); the theme's own `google_reviews`, `youtube_gallery` and `chatcom` shortcodes are in inc/shortcodes.php. | /packages/ |
| 9 | `citcom/sub-services` | 7 | `post_object` multi-select of services; ports `templates/service-card.php` (Swiper gallery per service). Needs Swiper CSS: `@import "swiper/css/bundle"` in the block style. | /services/creative/ |
| 10 | `citcom/trustindex` | 6 | Replaces the google_reviews layout (decided 2026-09-30: Elfsight is dropped, reviews are Trustindex everywhere). One textarea, `trustindex_code`, taking a Trustindex shortcode or embed snippet; default `[trustindex no-registration=google]`. Same section wrapper as the old layout. Rendered by `citcom_trustindex_embed()` in inc/shortcodes.php, which the diner reviews block reuses. | /packages/ |
| 11 | `citcom/stats` | 5 | title, opening text, `number_stats` repeater; uses `thousandsCurrencyFormat()` from inc/helpers.php. | /case-studies/delivering-400-roi-for-the-supercar-rooms/ |
| 12 | `citcom/video` | 3 | Self-hosted (`audio_video_player`) or YouTube, `player_options` group, `citdot_container`; vlite markup expected by `src/js/vlite.js` (`.vlite` element with `data-options`, `#...-volume` button). | /case-studies/citizen-celebrates-bt-scheme-with-animated-video/ |
| 13 | `citcom/swiper` | 3 | Image or content slides with the autoplay/loop/speed options read as `data-*` by `src/js/swiper.js`; section has the extra `fluid` class. Import `swiper/css/bundle`. | /about-us/ |
| 14 | `citcom/contact-map` | 2 | Snazzy Maps code (desktop and mobile), socials toggle, Forminator form, title and message. | /contact-us/ |
| 15 | `citcom/services-showcase` | 1 | Six service groups with `serviceShapeSVG()`; JS hover behaviour is already in `src/theme.js`. | /home/ |
| 16 | `citcom/citdot-cards` | 1 | Repeater of staff/value cards; `.citdot-card` styles are in `src/scss/elements/_card.scss`. | /about-us/ |
| 17 to 24 | `citcom/diner-hero`, `diner-intro`, `diner-menu`, `diner-story`, `diner-story-light`, `diner-wall`, `diner-reviews`, `diner-guestcheck` | 14 | Fields are defined in PHP (`functions/acf/diner-layouts.php`), so write fields.json by hand from that file. Shared helpers in `functions/lib/diner-components.php` (`diner_button`, `diner_stamp`, `diner_rating_stars`) become `inc/diner.php`. Assets in `assets/img/diner/` are copied into `assets/img/diner/`. | / (Home, the Diner page) and /info/citcom-creative-diner/ |

### Progress

- 2026-09-28/29: blocks 1 to 8 built and compared (see docs/00-discovery.md,
  "Findings added in Phase 1" and the Phase 2 notes there).
- 2026-09-29: blocks 9 to 16 built. Each was compared with its staging section
  after normalising ids, image URLs and srcset: sub-services, swiper (three
  sections), stats, services-showcase, citdot-cards and contact-map are
  byte-identical; video differs only in the JSON slash escaping the old
  `json_encode()` produced (kept unescaped as staging prints it). The google_reviews layout was first
  ported with its Elfsight embed, then replaced on 2026-09-30 by
  `citcom/trustindex`: those six sections intentionally differ from staging,
  which still prints the dead Elfsight embed.
  Local test content: `tools/local-fixture-phase2.php` (pages /home-classic/,
  /results/, /services/creative/, plus sections appended to /about-us/ and
  /contact-us/). Notes: `citcom/stats` does not use `thousandsCurrencyFormat()`
  (the old layout never did); the `audio_video_player` field type is not
  installed locally, so the video block also accepts a bare attachment id.

## Also in Phase 2

- `template-parts/card-post.php`, `template-parts/service-card.php` and `inc/ajax.php`
  (needed by display-posts and sub-services).
- The five old ACF blocks (`latest-posts`, `newsletter-signup`, `posts-search`,
  `related-posts`, `top-blog-posts`) used in the blog sidebars: port them into
  `blocks/` with their field groups (`group_6706ea51d5e74`, `group_67085971d331a`,
  `group_670861105bed5`) so widgets keep working. The `posts-search` block relies on
  the `postsearch` ajax handler.
- The "Section settings (simple)" group (`group_6790f33456b40`, anchor only): find
  which layouts used it instead of the full group before deciding whether it is
  needed as a separate group or just the anchor field on those blocks.
- Decided 2026-09-29: swatch and the ACF Extended field types stay; focuspoint
  is the theme's `citcom_focuspoint` type.

## Not in Phase 2

Migration command (Phase 3), jQuery removal (Phase 4), deploy wiring and the
cutover checklist (Phase 5), the WC2026 sweepstake plugin page template
(`templates/wc26.php` is already ported as is).
