# Phase 4 brief: performance

Target (Gareth, 2026-10-07): PageSpeed Insights loading under 1s on desktop
and under 3s on mobile, as a target rather than a hard goal. Lighthouse 13
(the engine behind PageSpeed Insights) run locally against staging with the
standard mobile and desktop presets; the keyless PageSpeed API ran out of
quota on the day. `tools/lighthouse.sh` is the runner.

## Baseline, 2026-10-07, staging

| Page | Desktop LCP | Mobile LCP | Mobile score |
|---|---|---|---|
| Home | 2.2s | 11.1s | 58 |
| /services/creative/ | 2.5s | 11.0s | 67 |
| /case-studies/ | 1.6s | 7.8s | 72 |
| Blog post | 1.8s | 3.6s | 89 |

Where the time went:

1. Images downloaded all at once. On /services/creative/ 61 images (2.7MB)
   started before the LCP; on the home page 47 (1.5MB). `loading="lazy"` was
   not holding the carousel slides back (Chrome treats images clipped by an
   overflow:hidden parent as visible), and the images were served at 1400px
   and 2560px for slots a fraction of that size (`sizes` said 100vw).
2. The home page hero video: a 2MB MP4 on every visit, mobile included.
3. Third-party scripts on every page: the ChatCom widget (15 requests, 288KB
   plus two fonts), jQuery (30KB), the Font Awesome kit (36KB plus icon
   files), Trustindex (99KB on the home page, its CSS render-blocking),
   Jetpack stats.
4. Render-blocking CSS: `theme.css` 54KB gzipped, about 47KB unused on any
   given page, holding first paint up by 1 to 1.6s on mobile.
5. Fonts: 150 to 184KB from Adobe Typekit and the chat widget.
6. Server response: 450 to 730ms on cache misses, 134ms on an edge-cache hit.
7. `theme.js` 82KB gzipped with about 70KB unused per page (Swiper and all of
   Bootstrap bundled for every page).

Decided 2026-10-08: the theme-only items (1, 4, 5 and 6 of the plan below)
first; the hero video on mobile and the third-party scripts (ChatCom,
Trustindex, Font Awesome) wait for Gareth's decision.

## Plan

1. Images: `sizes` that match the slot, real deferral in carousels,
   `fetchpriority` on the above-the-fold image. Theme only.
2. Hero video: poster first, `preload="none"`, desktop only or after first
   paint. Decision pending.
3. Third parties: ChatCom after idle or first interaction, Trustindex when its
   section nears, Font Awesome as a self-hosted subset. Decision pending.
4. CSS: critical CSS inline and `theme.css` deferred; Bootstrap cut down.
5. JavaScript: `theme.js` split per block, jQuery removed
   (docs/jquery-usage.md).
6. Fonts: preconnect, `swap`, preload the heading font.

## Done, first pass (2026-10-08)

- `sizes` on every themed image slot, worked out from the Bootstrap column
  classes by `citcom_image_sizes()` (inc/helpers.php) or set by hand where
  the slot is fixed (diner blocks, footer logos). Measured against the
  rendered sizes at 412px and 1350px on seven page types before choosing;
  nothing fetches a smaller file than before.
- Service gallery carousels: the slide in view and the next are fetched at
  once, the rest go through `citcom_defer_image()` and are fetched by
  `src/js/swiper.js` as the carousel nears them. /services/creative/ on
  mobile: 60 image requests at load before, 30 after, of which 15 are slide
  images instead of 33.
- jQuery removed (docs/jquery-usage.md). `theme.js` 82KB to 30KB gzipped;
  Swiper (28KB) and vlitejs (7KB) are hashed chunks under `build/chunks/`,
  fetched only where needed; Bootstrap cut to Modal, Offcanvas and Tooltip in
  JavaScript and to the parts the markup uses in CSS (alert and badge gone).
- Preconnect to the image CDN (spcdn.shortpixel.ai) instead of code.jquery.com.
- Theme photos re-encoded at the sizes they are shown: the diner story photo
  499KB to 263KB, the guest check 288KB to 125KB, the two sign fallbacks
  139KB and 162KB to about 70KB. The two grain textures compress no further
  as JPEG and WebP, so they get an AVIF copy through `image-set()` (87KB and
  107KB in place of 206KB and 362KB) where the browser understands it.
- Critical CSS (`tools/critical-css.mjs`, `assets/critical/<template>.css`,
  `docs/critical-urls.txt`): the rules each page type's first screen needs,
  8 to 13KB gzipped, inlined by `citcom_critical_css()`; every theme
  stylesheet then loads as preload + swap with a noscript fallback. The
  matcher keeps, besides what is in the first screen: hidden elements (their
  hiding rule), `:root` whatever its size, and everything inside a flex or
  grid item that starts in the first screen (its contents set the item's
  minimum width, so a missing rule far down the sidebar moved the first
  screen). Each of those was a layout shift found by Lighthouse before it was
  a rule. Regenerate after changing styles: `node tools/critical-css.mjs
  --base http://127.0.0.1:8899 --out assets/critical`.
- Scripts that measure layout at DOMContentLoaded (header height, nav pill,
  equal heights, scroll spy) wait for the deferred stylesheet
  (`stylesReady()` in `src/js/fx.js`).
- The kit's five woff2 files are preloaded, the list read from the kit CSS
  and cached for a day (`citcom_typekit_font_urls()`), so text is set in the
  right font at first paint.
- The first three listing cards load eagerly, the first with
  `fetchpriority="high"`: it is the archive pages' LCP element.
- The diner hero video carries its dimensions; without them it laid out at
  300x150 until its metadata arrived and pushed the page down 82px on a
  phone.
- Held-back carousel slides use `data-citcom-src`: ShortPixel's script reads
  `data-src` as a lazy-load convention and fetched every slide at full size.
- Checked: every page type on the local copy with no console errors; the
  carousel, header shrink, nav pill, offcanvas and its sub-menu slide, load
  more, services scroll spy, staff card hover and the video players all
  behave as before; the screenshot comparison of staging with `.baseline/old`
  after the first deploy: 246 pairs, every difference either the known ones
  (footer link, retired pages, Trustindex and forms on the packages pages)
  or content that changed since the baseline (the new M&S post in the blog
  listings and on the home page).

## Results

Lighthouse 13, three runs each, medians (`tools/lighthouse-median.sh` and
`tools/lighthouse-median.mjs`), staging, 2026-10-08:

| Page | Desktop LCP (was) | Mobile LCP (was) | Mobile score (was) | Desktop CLS | Mobile CLS |
|---|---|---|---|---|---|
| Home | 2.30s (2.2s) | 12.03s (11.1s) | 55 (58) | 0.000 | 0.000 |
| /services/creative/ | 1.66s (2.5s) | 8.55s (11.0s) | 69 (67) | 0.000 | 0.000 |
| /case-studies/ | 1.43s (1.6s) | 7.51s (7.8s) | 67 (72) | 0.001 | 0.000 |
| Blog post | 1.36s (1.8s) | 7.10s (3.6s) | 65 (89) | 0.052 | 0.000 |

The baseline was one run each; these are medians of three, and single runs
on staging swing by a second or more on mobile.

What is left on mobile is bytes on a simulated 1.6Mbps connection, and the
theme no longer owns most of them: the ChatCom widget (288KB of script and
two fonts on every page), the home page video (1.5MB), the Adobe fonts
(150KB; `font-display` is set in the Adobe Fonts project), Trustindex and
the Font Awesome kit. Those are items 2 and 3 of the plan, waiting for
Gareth's decision. Theme-side follow-ups, smaller: a metric-matched fallback
font (`size-adjust`) would remove the last of the home page's layout shift
(0.08, from the body copy reflowing when the web font arrives); the texture
images could go through the image CDN like the photos.
