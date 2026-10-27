# Analytics and cookie consent

How Google Tag Manager (GTM) is gated behind cookie consent, what the theme
sends to it, what to set in the Pressidium Cookie Consent plugin and in GTM,
and how to test it. Decided 2026-10-27, replacing the WPConsent plugin, which
never activated the GTM snippet it was meant to (the snippet carried the
attribute of an older TermsFeed banner), so GA4 had received no data.

Container GTM-KQQPQS8, which loads GA4 property G-7R3VHMR29K.

## What the theme does

- `header.php` prints the standard GTM loader as
  `<script type="text/plain" data-cookiecategory="analytics">`. A browser
  ignores a `text/plain` script. Pressidium's "Page Scripts" option rewrites
  it to a live script once the visitor allows the analytics category, and
  straight away on later page loads while that consent is saved. Nothing else
  on the page loads GTM: the `<noscript>` iframe fallback was removed because
  it loaded GTM with no consent at all.
- The container ID lives in one place, `citcom_gtm_id()` in `inc/assets.php`,
  and can be changed or emptied with the `citcom_gtm_id` filter. An empty ID
  prints no loader and no DNS prefetch for googletagmanager.com.
- The theme's JavaScript pushes these to `window.dataLayer`. Each push creates
  the array if needed, so it works before GTM has loaded; GTM reads whatever
  is queued when it does load, so an event pushed before consent is still
  processed if the visitor accepts later on the same page.

| Event | When | Fields |
| --- | --- | --- |
| `formsuccess` | Once per submit, after the server has confirmed success and the success message is shown. Never on a validation error. | `form`: the form slug, one of `contact`, `brief`, `newsletter`, `client-survey`, `guest-check`, `packages`, `seo-audit`, `technical-audit` |
| `formfailed` | Once per submit the server rejected (validation error, spam check, network failure). | `form`: as above |
| `contact_click` | On any click of an `a[href^="tel:"]` or `a[href^="mailto:"]` link, wherever it is on the page. | `method`: `phone` or `email` |

Sources: `src/js/forms.js` (the form events) and `src/js/contact-clicks.js`.

## Pressidium settings

Settings > Cookie Consent in wp-admin, on staging first, then live.

- **General > Page Scripts: ON.** Without it the theme's loader stays
  `text/plain` for ever and GTM never loads.
- **Analytics category: enabled**, with a plain description, for example:
  "Google Analytics, through Google Tag Manager, so we can see which pages are
  read and which forms are sent. No data is collected until you allow it."
  The Necessary category is always on; Targeting and Preferences can stay off
  unless something starts using them.
- **Consent Mode: OFF** for now. GTM simply does not load until consent is
  given, so there is nothing for Consent Mode to gate. Note that Pressidium
  only pushes its `pressidium-cookie-consent-accepted` and
  `pressidium-cookie-consent-changed` events to the dataLayer when Consent
  Mode is on; with it off they are window events only. Do not build GTM
  triggers on them.
- **Blocked Scripts: nothing for GTM.** That list is for scripts the plugin
  has to intercept by URL. The theme already ships the loader inert, so
  adding gtm.js there would be a second, unneeded block.
- **Cookies tab, under Analytics:** list the GA cookies so Pressidium's
  "autoclear" erases them when consent is withdrawn or rejected after having
  been given. GA4 sets `_ga` and one `_ga_<container>` cookie per property, so
  add `_ga` as an exact name and `^_ga_` with the regex option ticked. Domain
  `.citcom.co.uk` on live (`.citcomstaging.mystagingwebsite.com` on staging),
  path `/`.
- **Floating settings button: ON**, so a visitor can change their choice
  later without clearing cookies; withdrawing consent is what the last test
  below checks.
- The plugin's own cookie is `pressidium_cookie_consent`. Deleting it in
  DevTools is how to get a "fresh visit" when testing.

Nothing to style: the theme no longer carries the old TermsFeed dialogue skin.
Colours and wording are set in the plugin.

## GTM container changes

To be made in the GTM UI (container GTM-KQQPQS8), then published.

1. Check the container for leftovers from the old setup: any trigger,
   variable or tag that looked for WPConsent or TermsFeed consent, or a
   Consent Initialization tag. GTM only loads after consent now, so the GA4
   configuration tag should fire on Initialization or All Pages with no
   consent condition of its own.
2. Data layer variables: `form` (for the form events) and `method` (for
   `contact_click`).
3. Trigger "Lead form success": Custom Event, event name `formsuccess`, fire
   on some events where `form` matches the regex `^(contact|brief)$`.
   Tag: GA4 event `generate_lead` with parameter `form_name` = `{{form}}`.
4. Trigger "Newsletter success": Custom Event `formsuccess`, `form` equals
   `newsletter`. Tag: GA4 event `sign_up` with `method` = `newsletter`.
5. Trigger "Contact click": Custom Event `contact_click`. Tag: GA4 event
   `contact_click` with parameter `method` = `{{method}}`.
6. Optional: a GA4 event for `formfailed` with `form_name`, to spot a form
   that is rejecting real visitors.
7. In GA4 (Admin > Events), mark `generate_lead` as a key event. `sign_up`
   can be one as well if newsletter signups count as a goal. Register
   `form_name` and `method` as custom dimensions if they are to appear in
   reports rather than only in DebugView.

Use GTM Preview (Tag Assistant) to watch the events arrive: it shows the
dataLayer pushes and which tags fired for each.

## Test checklist

Run on citcomstaging.mystagingwebsite.com first, then on citcom.co.uk after
deploying. Use a private window, or delete the `pressidium_cookie_consent`
cookie and the `_ga*` cookies between runs. Tools: DevTools Network tab
filtered on `gtm.js` and `collect`, DevTools Application > Cookies, Tag
Assistant, and GA4 Realtime (or DebugView with Tag Assistant connected).

Pages to repeat on: a blog post, a service page, and /contact-us/.

- **Fresh visit, desktop and mobile.** The banner shows. With no choice made:
  no request to gtm.js, no `_ga` or `_ga_*` cookie, nothing in GA4 Realtime.
- **Accept analytics.** gtm.js is requested on the same page without a
  reload. Tag Assistant shows GTM-KQQPQS8 and G-7R3VHMR29K. GA4 Realtime
  shows one page_view for that page, not two.
- **Returning visitor with saved consent.** Open another page: no banner,
  gtm.js requested as the page loads, one page_view in Realtime.
- **Reject, or withdraw through the floating button.** On the next page no
  gtm.js request, and the `_ga` and `_ga_*` cookies are gone from
  Application > Cookies. (If they linger, the Cookies tab entries above are
  wrong: check the name, the regex tick and the domain.)
- **Contact form, with consent given.** First submit it with a required
  field empty: an error shows, Tag Assistant shows `formfailed` and no
  `formsuccess`, and nothing fires `generate_lead`. Then submit it properly:
  the success message appears, `formsuccess` appears once with `form:
  contact`, and `generate_lead` fires once. Delete the test entry from the
  form submissions afterwards.
- **Contact click.** Click a phone or email link in the footer: one
  `contact_click` with the right `method`.
- **No consent, then a form.** Without accepting, submit the contact form.
  Nothing reaches GA4. Then accept on the same page: GTM loads and processes
  the queued `formsuccess`, so `generate_lead` fires once. This is expected,
  not a fault.
