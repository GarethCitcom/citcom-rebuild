#!/usr/bin/env node
/**
 * Records what every page tells search engines and social networks, so a
 * change of SEO plugin (or anything else that writes to <head>) can be
 * compared before and after:
 *
 *   node tools/seo-snapshot.mjs capture --base https://example.com --out .baseline/seo-before.json
 *   node tools/seo-snapshot.mjs diff --a .baseline/seo-before.json --b .baseline/seo-after.json [--out report.txt] [--ignore field,field] [--alias https://other-host] [--only substring]
 *
 * capture takes its URLs from docs/urls.csv, from the site's sitemap
 * (--sitemap /sitemap.xml, the default; "none" to skip) and from --extra
 * (comma-separated paths), and fetches them one at a time with a pause
 * (--delay, 1500 ms by default: staging resets connections after a burst).
 * --paths <file.json> replaces all three with the paths of an earlier
 * snapshot, which is what the "after" run wants. Redirects are recorded, not
 * followed.
 *
 * Per page: status, redirect target, X-Robots-Tag, <title>, description,
 * robots, canonical, prev/next, every og:, article: and twitter: tag, the
 * verification tags and each JSON-LD block. diff ignores the host, so staging
 * can be compared with a local copy.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const [command, ...rest] = process.argv.slice(2);
const args = Object.fromEntries(rest.map((a, i, arr) => a.startsWith('--') ? [a.slice(2), arr[i + 1] && !arr[i + 1].startsWith('--') ? arr[i + 1] : true] : []).filter((e) => e.length));

const ENTITIES = { amp: '&', lt: '<', gt: '>', quot: '"', apos: "'", nbsp: ' ', ndash: '–', mdash: '—', hellip: '…', pound: '£', lsquo: '‘', rsquo: '’', ldquo: '“', rdquo: '”', copy: '©', reg: '®', trade: '™' };
function decode(s) {
  return String(s)
    .replace(/&#x([0-9a-f]+);/gi, (m, h) => String.fromCodePoint(parseInt(h, 16)))
    .replace(/&#(\d+);/g, (m, d) => String.fromCodePoint(parseInt(d, 10)))
    .replace(/&([a-z]+);/gi, (m, n) => ENTITIES[n.toLowerCase()] ?? m)
    .replace(/\s+/g, ' ').trim();
}
function attrs(tag) {
  const out = {};
  for (const m of tag.matchAll(/([a-zA-Z_:.-]+)\s*=\s*(?:"([^"]*)"|'([^']*)'|([^\s>]+))/g)) out[m[1].toLowerCase()] = decode(m[2] ?? m[3] ?? m[4] ?? '');
  return out;
}
function push(obj, key, value) { (obj[key] ||= []).push(value); }

function parse(html) {
  const page = { title: null, meta: {}, link: {}, jsonld: [] };
  const headEnd = html.search(/<\/head>/i);
  const head = headEnd > -1 ? html.slice(0, headEnd) : html;
  const title = head.match(/<title[^>]*>([\s\S]*?)<\/title>/i);
  if (title) page.title = decode(title[1]);
  for (const m of head.matchAll(/<meta\b[^>]*>/gi)) {
    const a = attrs(m[0]);
    const name = (a.property || a.name || '').toLowerCase();
    if (/^(description|robots|googlebot|bingbot|keywords|author|news_keywords|google-site-verification|msvalidate\.01|p:domain_verify|yandex-verification|facebook-domain-verification|og:.+|article:.+|twitter:.+|fb:.+|profile:.+)$/.test(name)) push(page.meta, name, a.content ?? '');
  }
  for (const m of head.matchAll(/<link\b[^>]*>/gi)) {
    const a = attrs(m[0]);
    const rel = (a.rel || '').toLowerCase();
    if (/^(canonical|prev|next|shortlink)$/.test(rel) || (rel === 'alternate' && a.hreflang)) push(page.link, rel === 'alternate' ? 'hreflang:' + a.hreflang : rel, a.href ?? '');
  }
  for (const m of html.matchAll(/<script\b[^>]*type\s*=\s*["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi)) {
    try { page.jsonld.push(JSON.parse(m[1])); } catch { page.jsonld.push({ '@type': 'INVALID JSON', raw: m[1].slice(0, 200) }); }
  }
  return page;
}

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
async function get(url) {
  for (let attempt = 1; ; attempt++) {
    try {
      const res = await fetch(url, { redirect: 'manual', headers: { 'user-agent': 'Mozilla/5.0 (compatible; citcom-seo-snapshot)', accept: 'text/html,application/xml;q=0.9,*/*;q=0.8' } });
      if ((res.status === 429 || res.status >= 500) && attempt < 4) { await sleep(15000 * attempt); continue; }
      return { status: res.status, location: res.headers.get('location'), xrobots: res.headers.get('x-robots-tag'), type: res.headers.get('content-type'), body: await res.text() };
    } catch (e) {
      if (attempt >= 4) return { status: 0, error: String(e.cause?.code || e.message), body: '' };
      await sleep(15000 * attempt);
    }
  }
}

async function capture() {
  if (!args.base || !args.out) { console.error('Usage: node tools/seo-snapshot.mjs capture --base <url> --out <file.json> [--sitemap /sitemap.xml|none] [--extra /a/,/b/] [--paths <snapshot.json>] [--delay 1500]'); process.exit(1); }
  const base = String(args.base).replace(/\/$/, '');
  const delay = Number(args.delay ?? 1500);
  const rel = (u) => { try { const x = new URL(u, base + '/'); return x.origin === new URL(base).origin ? x.pathname + x.search : x.href; } catch { return u; } };
  const snapshot = { base, taken: new Date().toISOString(), sitemaps: {}, pages: {} };
  const paths = new Set();

  if (args.paths) {
    const earlier = JSON.parse(fs.readFileSync(args.paths, 'utf8'));
    Object.keys(earlier.pages).forEach((p) => paths.add(p));
  } else {
    fs.readFileSync(path.join(root, 'docs/urls.csv'), 'utf8').split(/\r?\n/).slice(1).forEach((line) => { const p = line.split(',')[1]; if (p) paths.add(p.trim()); });
    String(args.extra || '').split(',').map((p) => p.trim()).filter(Boolean).forEach((p) => paths.add(p));
  }

  // Sitemaps: the index and every child, recorded as lists of paths.
  const queue = args.sitemap === 'none' ? [] : String(args.sitemap || '/sitemap.xml').split(',');
  const seen = new Set();
  while (queue.length) {
    const p = rel(queue.shift());
    if (seen.has(p)) continue;
    seen.add(p);
    // Never leave the site being captured: a staging copy can carry a sitemap file that lists the live host.
    if (!p.startsWith('/')) { snapshot.sitemaps[p] = { skipped: 'another host, not fetched' }; continue; }
    const res = await get(base + p);
    await sleep(delay);
    const locs = [...res.body.matchAll(/<loc>\s*(?:<!\[CDATA\[)?([^<\]]+)/g)].map((m) => decode(m[1]));
    if (res.status >= 300 && res.status < 400 && res.location) { snapshot.sitemaps[p] = { status: res.status, location: rel(res.location) }; queue.push(res.location); continue; }
    if (/<sitemapindex/i.test(res.body)) { snapshot.sitemaps[p] = { status: res.status, index: locs.map(rel) }; queue.push(...locs); continue; }
    snapshot.sitemaps[p] = { status: res.status, urls: locs.map(rel) };
    if (!args.paths) locs.map(rel).filter((u) => u.startsWith('/')).forEach((u) => paths.add(u));
  }

  let n = 0;
  for (const p of paths) {
    const res = await get(base + p);
    const page = { status: res.status };
    if (res.error) page.error = res.error;
    if (res.location) page.location = rel(res.location);
    if (res.xrobots) page.xrobots = res.xrobots;
    if (res.status === 200 || res.status === 404) Object.assign(page, parse(res.body));
    snapshot.pages[p] = page;
    if (++n % 20 === 0) console.log(`${n}/${paths.size}`);
    await sleep(delay);
  }
  fs.mkdirSync(path.dirname(path.resolve(args.out)), { recursive: true });
  fs.writeFileSync(args.out, JSON.stringify(snapshot, null, 1));
  const bad = Object.entries(snapshot.pages).filter(([, v]) => v.status === 0 || v.status >= 500 || v.status === 429);
  console.log(`${paths.size} pages, ${Object.keys(snapshot.sitemaps).length} sitemap files -> ${args.out}` + (bad.length ? `\nNOT FETCHED (${bad.length}): ${bad.map(([k, v]) => `${k} ${v.status} ${v.error || ''}`).join(', ')}` : ''));
}

const ROBOTS_DEFAULTS = new Set(['index', 'follow', 'max-snippet:-1', 'max-video-preview:-1']);
// A description built from the page's text is cut at a different length by each plugin: same start, same description.
const sameStart = (a, b) => { const t = (s) => s.replace(/\s*(\.\.\.|…)\s*$/, '').trim(); const x = t(a), y = t(b); return x.length > 60 && y.length > 60 && (x.startsWith(y) || y.startsWith(x)); };

// Everything a page says, flattened to "field" => comparable string.
function fields(page, origins) {
  const strip = (v) => origins.reduce((s, o) => s.split(o).join('{site}'), String(v)).replace(/\{site\}\/?(?=$|[\s"])/g, '{site}/');
  const out = { status: String(page.status) };
  if (page.location) out.location = strip(page.location);
  if (page.xrobots) out['x-robots-tag'] = page.xrobots;
  if (page.title !== undefined) out.title = page.title ?? '(none)';
  for (const [k, v] of Object.entries(page.meta || {})) {
    // Robots: the directives that only restate the default (index, follow, no limit) are left out.
    out[k] = /robots|googlebot|bingbot/.test(k) ? [...new Set(v.join(',').split(',').map((s) => s.trim().toLowerCase()).filter((s) => s && !ROBOTS_DEFAULTS.has(s)))].sort().join(', ') || '(defaults)' : v.map(strip).join(' || ');
  }
  for (const [k, v] of Object.entries(page.link || {})) out['link:' + k] = v.map(strip).join(' || ');
  const types = [];
  const walk = (node) => {
    if (Array.isArray(node)) return node.forEach(walk);
    if (node && typeof node === 'object') {
      if (node['@type']) types.push([].concat(node['@type']).join('+'));
      Object.values(node).forEach(walk);
    }
  };
  walk(page.jsonld || []);
  if (types.length) out['json-ld types'] = [...new Set(types)].sort().join(', ');
  return out;
}

function diff() {
  if (!args.a || !args.b) { console.error('Usage: node tools/seo-snapshot.mjs diff --a <before.json> --b <after.json> [--out report.txt] [--ignore field,field] [--only substring]'); process.exit(1); }
  const A = JSON.parse(fs.readFileSync(args.a, 'utf8')), B = JSON.parse(fs.readFileSync(args.b, 'utf8'));
  const ignore = new Set(String(args.ignore || '').split(',').map((s) => s.trim()).filter(Boolean));
  // --alias: other hosts to read as "this site" (a local copy still prints some staging URLs).
  const origins = [A.base, B.base, ...String(args.alias || '').split(',').map((s) => s.trim().replace(/\/$/, '')).filter(Boolean)].sort((x, y) => y.length - x.length);
  const sitePath = (u) => origins.reduce((s, o) => (s.startsWith(o) ? s.slice(o.length) || '/' : s), u);
  const byField = {}, lines = [];
  let same = 0, compared = 0;
  for (const p of Object.keys(A.pages)) {
    if (args.only && !p.includes(args.only)) continue;
    if (!B.pages[p]) { lines.push(`${p}\n  not in B`); continue; }
    compared++;
    const fa = fields(A.pages[p], origins), fb = fields(B.pages[p], origins);
    const changed = [...new Set([...Object.keys(fa), ...Object.keys(fb)])].filter((k) => !ignore.has(k) && fa[k] !== fb[k] && !(/description$/.test(k) && fa[k] && fb[k] && sameStart(fa[k], fb[k]))).sort();
    if (!changed.length) { same++; continue; }
    lines.push(p + '\n' + changed.map((k) => { (byField[k] ||= []).push(p); return `  ${k}\n    a: ${fa[k] ?? '(absent)'}\n    b: ${fb[k] ?? '(absent)'}`; }).join('\n'));
  }
  const summary = [`${compared} pages compared, ${same} identical in every field` + (ignore.size ? ` (ignoring ${[...ignore].join(', ')})` : '')];
  for (const [k, list] of Object.entries(byField).sort((x, y) => y[1].length - x[1].length)) summary.push(`  ${String(list.length).padStart(4)}  ${k}`);

  // Sitemaps: the set of page URLs listed, whatever the files are called.
  const urls = (S) => new Set(Object.values(S.sitemaps || {}).flatMap((s) => s.urls || []).map(sitePath));
  const ua = urls(A), ub = urls(B);
  const gone = [...ua].filter((u) => !ub.has(u)).sort(), added = [...ub].filter((u) => !ua.has(u)).sort();
  summary.push(`sitemap: ${ua.size} URLs in a, ${ub.size} in b, ${gone.length} only in a, ${added.length} only in b`);
  summary.push(`  files a: ${Object.keys(A.sitemaps || {}).join(' ')}`, `  files b: ${Object.keys(B.sitemaps || {}).join(' ')}`);
  if (gone.length) summary.push('  only in a:', ...gone.map((u) => '    ' + u));
  if (added.length) summary.push('  only in b:', ...added.map((u) => '    ' + u));

  console.log(summary.join('\n'));
  if (args.out) { fs.writeFileSync(args.out, summary.join('\n') + '\n\n' + lines.join('\n\n') + '\n'); console.log(`details -> ${args.out}`); } else console.log('\n' + lines.join('\n\n'));
}

if (command === 'capture') await capture();
else if (command === 'diff') diff();
else { console.error('Usage: node tools/seo-snapshot.mjs capture|diff ...'); process.exit(1); }
