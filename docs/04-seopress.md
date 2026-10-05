# SEO plugin: SmartCrawl Pro to SEOPress

Decided 2026-10-05: SmartCrawl Pro goes (it came with the WPMU DEV
membership), SEOPress replaces it. The aim is that search engines see the same
site afterwards. Nothing here has been run on staging or live yet; the local
copy of staging's content (the replica) is where it was proved.

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
  home page a LocalBusiness with address and coordinates built in SmartCrawl's
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

## The copy: tools/seopress-from-smartcrawl.php

    wp eval-file tools/seopress-from-smartcrawl.php https://example.com dry-run
    wp eval-file tools/seopress-from-smartcrawl.php https://example.com

It only reads SmartCrawl's data, so reactivating SmartCrawl undoes the move.
It copies the templates, the separator, the organisation and its address, the
default share images, the sitemap's post types and taxonomies, and each
post's and term's own title, description, canonical, robots, social values,
focus keywords and primary category.

SEOPress has its own importer. It is not used because, as of SEOPress 10.3:

- it skips a term's noindex, which would open the six noindexed case study
  tag archives to indexing;
- it leaves SEOPress's activation defaults in place, and three of them are
  wrong for this site:
  - **"Redirect attachment pages" sends every blog post to the home page.**
    The theme's blog addresses (`/blog/{category}/{slug}`, inc/setup.php)
    reach WordPress as an attachment query, a quirk carried over from the old
    theme.
  - **"noindex attachment pages" marks every blog post noindex**, for the
    same reason.
  - blog tag archives are noindexed, and they are indexable today.
- it does not carry the primary category, the share images or the address.

The script switches those three off and reports what it could not place.
Anyone changing SEOPress's settings later should leave the two attachment
settings off (SEO > Advanced, and SEO > Titles & Metas > Archives).

## How it was checked: tools/seo-snapshot.mjs

    node tools/seo-snapshot.mjs capture --base <url> --out .baseline/seo-before.json
    node tools/seo-snapshot.mjs capture --base <url> --out .baseline/seo-after.json --paths .baseline/seo-before.json --sitemap /sitemaps.xml
    node tools/seo-snapshot.mjs diff --a .baseline/seo-before.json --b .baseline/seo-after.json

It records, per URL, the status, redirect target, title, description, robots,
canonical, social tags and JSON-LD, and compares two records whatever the
host. "Before" is 268 staging URLs with SmartCrawl active: docs/urls.csv,
every blog post at its `/blog/...` address, every case study tag archive and
filter, the redirect sources, and a tag, date, author, search and 404 page.
"After" was the replica with SEOPress and the script run.

Both attachment defaults above were found by this comparison, not by reading
settings. Run it again after the switch on staging and again on live.

Result on the replica, 268 URLs:

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
- Author archives answered 404 and now redirect to the home page.

## Not covered by the free plugin

To be decided by Gareth: SEOPress PRO, or the theme.

- Redirects (10). SEOPress keeps redirects in its PRO plugin; the script
  copies them when PRO is active and lists them when it is not. Eight are
  temporary (302) and point at the site's hosting address, not its own
  domain, which is worth correcting whichever way they go.
- Structured data. Free SEOPress prints Organization (with the address) and
  WebSite on the home page only. Gone without PRO or theme code: Article on
  about 85 blog posts, VideoObject on four service pages, LocalBusiness with
  coordinates on the home page, and Organization and WebSite on inner pages.

## Order of work, staging then live

1. `node tools/seo-snapshot.mjs capture` for the "before" record (staging's
   is in `.baseline/seo-before.json`, taken 2026-10-05).
2. Database export. Deploy the theme with inc/seo.php.
3. Install and activate SEOPress, deactivate SmartCrawl Pro (keep it
   installed until sign-off), run the script with `dry-run`, then for real.
4. `wp rewrite flush`, purge the caches, capture "after", diff.
5. Search Console: submit `/sitemaps.xml`. Switch IndexNow on in SEOPress on
   live only (the script leaves it off so a copy of the site cannot submit
   its URLs).
6. After sign-off: delete SmartCrawl Pro and the WPMU DEV Dashboard, and
   remove SmartCrawl's data (`wds*` options, `_wds_*` post meta, the
   `smartcrawl_redirects` table).
