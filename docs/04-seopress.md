# SEO plugin: SmartCrawl Pro to SEOPress

Decided 2026-10-05: SmartCrawl Pro goes (it came with the WPMU DEV
membership) and the free SEOPress plugin replaces it. The aim is that search
engines see the same site afterwards. What free SEOPress does not do, the
theme does for now: redirects, and structured data for blog posts and the
home page. SEOPress PRO would replace both if it is ever bought.

## What SmartCrawl was doing

Read from staging, 2026-10-05:

- Titles and descriptions: a template per post type and taxonomy
  (`Page name | CitCom`), about 100 hand-written titles and 80 descriptions,
  and the original header.php asking SmartCrawl for the `<title>` directly.
- The sitemap at `/sitemap.xml` (WordPress's own switched off): posts, pages,
  case studies, services, landing pages and blog categories, plus 34 URLs
  added by hand.
- Open Graph and Twitter tags, with one default share image for each.
- Structured data: Organization and WebSite on every page, Article on blog
  posts, WebPage types, VideoObject where a page embeds a video, and on the
  home page a LocalBusiness, an address and coordinates built in SmartCrawl's
  schema builder.
- 10 redirects.
- noindex on six case study tag archives, a canonical on one more, and the
  primary category of each blog post.
- Not in use: automatic linking, Moz, the robots.txt editor, location-based
  redirects, the news sitemap. IndexNow was set up.

## What changed in the theme

- `inc/seo.php` declares `title-tag` support, so WordPress prints `<title>`
  and SEOPress answers through `pre_get_document_title`. header.php no longer
  prints one. With no SEO plugin active the title is what header.php used to
  fall back to: `CitCom.` on the front page, `Page name | CitCom.` elsewhere.
- The archives laid out by a template post (case studies, services, the case
  study tag filter, blog categories and tags) still take their title from that
  post's SEO title, now through the `seopress_titles_title` filter, and its
  description too.
- `/case-studies/?cs-tag=print` keeps the tag archive as its canonical
  (`seopress_titles_canonical`); SEOPress alone would print `/case-studies/`.
- Redirects: Site Settings > Redirects (`acf-json/group_citcom_redirects.json`,
  `inc/redirects.php`). Old address, new address, permanent or temporary. A
  row matches with or without the trailing slash and whatever the capitals.
- Structured data (`inc/seo.php`): an Article on every blog post, and on the
  home page one LocalBusiness with its address and coordinates, read from the
  `citcom_local_business` option. In SmartCrawl those were three unconnected
  items; the address now sits inside the business. Both step aside when
  SEOPress PRO is active. Not rebuilt: VideoObject on four service pages
  (SmartCrawl's had no thumbnail, so they earned nothing in search), the
  WebPage and navigation items, and Organization on inner pages. SEOPress
  prints the organisation, with the address, on the home page.
- Blog routing (`inc/setup.php`). The original theme sent
  `/blog/{category}/{slug}` to WordPress as an attachment query. It found the
  post, but every blog post counted as an attachment page, and anything that
  treats attachment pages specially treated the whole blog that way (see the
  SEOPress defaults below). The rules now ask for the post by name. Blog
  posts lose three body classes nothing used (`attachment`, `attachmentid-N`,
  `attachment-`); a `redirect_canonical` filter keeps the address without a
  trailing slash, which WordPress had only left alone because the request
  looked like an attachment. Checked on 32 blog addresses before and after
  (post, wrong category, with slash, feed, embed, paging, 404): same status,
  target and page.

## The copy: tools/seopress-from-smartcrawl.php

    wp eval-file tools/seopress-from-smartcrawl.php https://example.com dry-run
    wp eval-file tools/seopress-from-smartcrawl.php https://example.com

It only reads SmartCrawl's data, so reactivating SmartCrawl undoes the move.
It copies the templates, the separator, the organisation and its address, the
default share images, the sitemap's post types and taxonomies, each post's
and term's own title, description, canonical, robots, social values, focus
keywords and primary category, the redirects (into Site Settings > Redirects,
addresses and types as they stand) and the home page business (into
`citcom_local_business`).

SEOPress has its own importer. It is not used because, as of SEOPress 10.3:

- it skips a term's noindex, which would open the six noindexed case study
  tag archives to indexing;
- it leaves SEOPress's activation defaults in place, and three of them were
  wrong for this site:
  - **"Redirect attachment pages" sent every blog post to the home page**,
    because of the blog routing described above;
  - **"noindex attachment pages" marked every blog post noindex**, for the
    same reason;
  - blog tag archives are noindexed, and they are indexable today.
- it does not carry the primary category, the share images or the address,
  and can only place redirects in SEOPress PRO.

The script switches those three off. The routing fix means the two attachment
settings can no longer hurt the blog; they stay off because SmartCrawl did
neither.

## How it was checked: tools/seo-snapshot.mjs

    node tools/seo-snapshot.mjs capture --base <url> --out .baseline/seo-before.json
    node tools/seo-snapshot.mjs capture --base <url> --out .baseline/seo-after.json --paths .baseline/seo-before.json --sitemap /sitemaps.xml
    node tools/seo-snapshot.mjs diff --a .baseline/seo-before.json --b .baseline/seo-after.json

It records, per URL, the status, redirect target, title, description, robots,
canonical, social tags and JSON-LD, and compares two records whatever the
host. It never leaves the host it is given: staging's stored sitemap file
lists the live domain. "Before" is 268 staging URLs with SmartCrawl active:
docs/urls.csv, every blog post at its `/blog/...` address, every case study
tag archive and filter, the redirect sources, and a tag, date, author, search
and 404 page.

Both attachment defaults above were found by this comparison, not by reading
settings. Run it again after any change of SEO plugin or settings.

Result, 268 URLs (first on the replica, the local copy of staging's content):

- Status and redirects: identical, the ten redirects included. (On the
  replica author archives redirected to the home page, which is SEOPress
  with author archives switched off; on staging they still answer 404, as
  before.)
- Title: the same on every page that was right before. Different on purpose:
  ten case study tag archives and two paginated archives that used to show the
  first post's title and now show their own (`Print | CitCom`); search results
  gain quotes round the phrase.
- Description, canonical target, noindex: carried over everywhere they were
  set. New: every page now prints its own address as canonical (SmartCrawl
  left it out when it was the page's own); 404 and search pages are noindex;
  `/case-studies/` and `/services/` gain the description written on their
  template post.
- Share tags: same title, description and image, except the home page's
  share title (`CitCom.`, was `Home – Diner | CitCom`), four pages that had
  no image and now get the default one, one landing page whose image was
  picked out of its content and is now the default, and three posts whose
  Twitter card had been switched off individually, which SEOPress cannot do.
- Sitemap: moves to `/sitemaps.xml` (`/sitemap.xml` redirects to it). The
  same URLs, less the nine retired pages and the 34 hand-added extras: 13
  case study filter URLs whose canonical points elsewhere, six old addresses
  of posts that have since moved category, one service page under a second
  spelling, and 14 that the sitemap lists anyway.

## Staging run (2026-10-05)

- Database export first: `~/citcom-backups/pre-seopress-2026-10-05.sql`.
- Theme deployed (build of `5c7072a`), the changed files checked against the
  deploy branch by checksum, rewrite rules flushed.
- SEOPress 10.3 installed and activated, the script dry-run and then run with
  the same counts as on the replica (98 titles, 76 descriptions, 80 primary
  categories, six term noindex flags, 10 redirects, the home page business),
  SmartCrawl Pro deactivated, caches purged.
- The 268 URLs recorded again and compared with the "before" record: every
  one answers with the same status and the same redirect target. The
  differences are the ones listed above and nothing else. The sitemap lists
  188 URLs; the 29 it no longer lists are the nine retired pages and 20 of
  the hand-added extras.
- Structured data: an Article on all 90 blog post addresses checked, the
  LocalBusiness on the home page next to SEOPress's organisation.
- Blog addresses probed after the routing change: posts answer 200 without a
  trailing slash, the body no longer carries the attachment classes, and a
  real attachment address redirects to its file.
- Left in place until sign-off: SmartCrawl Pro (inactive) with all its data,
  one scheduled event of its own, and the WPMU DEV Dashboard.

## Order of work, staging then live

1. `node tools/seo-snapshot.mjs capture` for the "before" record (staging's
   is in `.baseline/seo-before.json`, taken 2026-10-05).
2. Database export. Deploy the theme, then `wp rewrite flush` (the blog rules
   changed).
3. Install and activate SEOPress, deactivate SmartCrawl Pro (keep it
   installed until sign-off), run the script with `dry-run`, then for real.
4. `wp rewrite flush`, purge the caches, capture "after", diff.
5. Search Console: submit `/sitemaps.xml`. Switch IndexNow on in SEOPress on
   live only (the script leaves it off so a copy of the site cannot submit
   its URLs).
6. Site Settings > Redirects: eight of the ten are temporary (302) and point
   at the site's hosting address, not its own domain. Worth changing to
   permanent and to plain paths (`/about-us/`).
7. After sign-off: delete SmartCrawl Pro and the WPMU DEV Dashboard, and
   remove SmartCrawl's data (`wds*` options, `_wds_*` post meta, the
   `smartcrawl_redirects` table).
