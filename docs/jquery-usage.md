# jQuery usage in the theme JavaScript

jQuery is gone from the front end (Phase 4, 2026-10-08). The table below is
the dependency list that guided the rewrite; the "Replacement" column is what
`src/js/` does now. `src/js/fx.js` holds the few effects the original scripts
leaned on (fadeIn, fadeOut, slideDown, slideUp, jQuery's width/height/position
measurements and the two-way `.hover()`), with jQuery's durations and easing.
`passive-event-listeners.js` and the jQuery dependency of
`outdatedbrowser.min.js` went with it. A plugin that needs jQuery still gets
WordPress's own copy by declaring the dependency.

| Module | jQuery use | Replacement in Phase 4 |
|---|---|---|
| `theme.js` | `$(document).ready`, `.service-row` scroll spy (`offset`, `outerHeight`, `scrollTop`, `on('scroll')`), `.stretched-link` hover to toggle `stretch-hover`, `.wp-block-quote` `wrap`/`after`, `.showcase .service` hover with timeout, `.citdot-card.staff` `slideDown`/`slideUp`, `$(window).resize` | `DOMContentLoaded`, `IntersectionObserver` for the scroll spy, `mouseenter`/`mouseleave` listeners, `Element.insertAdjacentHTML`, CSS transitions for the staff card |
| `passive-event-listeners.js` | Registers `jQuery.event.special.touchstart/touchend/touchmove` so jQuery touch handlers are passive | Not needed once no jQuery touch handlers remain; delete |
| `equal-heights.js` | `equalHeights`, `equalHeightsWithReset`, `widthEqualHeight` use `outerHeight`, `width`, `css` | `getBoundingClientRect()` loops; `widthEqualHeight` on `.citcom-btn-bg` and `.icon-btn-bg` can become `aspect-ratio: 1` in CSS (already set) |
| `nav-and-header.js` | Header shrink on scroll, nav pill animation (`width`, `position`, `css`), dropdown hover/focus/blur, mobile slide (`fadeIn`/`fadeOut`), search input focus/blur blur effect, `--header-height` from `outerHeight` | Native listeners; `element.animate()` or CSS classes for fades; `ResizeObserver` for header height |
| `cards.js` | `.card-body` hover toggles `card-hover` | `mouseenter`/`mouseleave` or CSS `:hover` |
| `vlite.js` | Loops `.vlite` and `.wp-block-video`, `data()`, `fadeIn`, `delay().queue()` | `querySelectorAll`, `dataset`, CSS transition classes |
| `swiper.js` | Loops `.swiper-gallery` and `.swiper-swiper`, reads `data-*` options | `querySelectorAll` and `dataset`; Swiper itself has no jQuery dependency |
| `sidebar.js` | Category list pill (`height`, `position`, `css`), hover/mouseleave | Native listeners and `offsetTop` |
| `display-posts.js` | `$.ajax` load-more and search, `$(document).on('click')`, `.html()`, `.before()`, `.trigger()` | `fetch()` with `FormData`, delegated listeners, `insertAdjacentHTML` |
| `outdatedbrowser.min.js` (vendor, `assets/vendor/`) | Enqueued with a jQuery dependency in the old theme; the library itself is vanilla | Drop the dependency, or remove the library (it targets IE-era browsers) |

Removed already (present in the old bundle but never called): `bootstrap-select.js`
(`selectpicker` is not used anywhere), `jquery.mousewheel.js` (no `mousewheel`
handlers), `anchorScroll.js` (defined but never invoked), `findOverflows.js`
(debug helper), `clusterMap.js` (Google Maps demo with hard-coded Australian
coordinates, not referenced by any template). `ajax.js` was empty.

Bootstrap 5.3 itself has no jQuery dependency; it is imported as an ES module and
exposed on `window.bootstrap` for the inline `new bootstrap.Tooltip` calls.
