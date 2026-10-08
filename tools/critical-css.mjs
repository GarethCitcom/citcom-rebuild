#!/usr/bin/env node
/**
 * Critical CSS: the rules the first screen of every page type needs, so they
 * can be inlined and the stylesheets fetched without blocking the first paint.
 *
 *   node tools/critical-css.mjs --base http://127.0.0.1:8899 --out assets/critical.css [--urls docs/critical-urls.txt]
 *
 * For each URL, at a phone and a desktop viewport, every rule of the theme's
 * own stylesheets (build/theme.css, build/diner.css, build/blocks/*) is kept
 * when one of its selectors matches an element in the first screen. Pseudo-classes and pseudo-elements are ignored for the
 * match, so :hover and ::before rules of visible elements come along. The
 * union over all pages, in stylesheet order, is written out. Run it again
 * after changing styles or adding a block that can sit at the top of a page
 * (inc/assets.php inlines the file; docs/05-phase4-brief.md).
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const args = Object.fromEntries(process.argv.slice(2).map((a, i, arr) => a.startsWith('--') ? [a.slice(2), arr[i + 1] ?? true] : []).filter(Boolean));
if (!args.base || !args.out) {
  console.error('Usage: node tools/critical-css.mjs --base <url> --out <dir> [--urls <file>] [--max-rule <bytes>]');
  process.exit(1);
}
const base = String(args.base).replace(/\/$/, '');
const pairs = fs.readFileSync(args.urls || path.join(root, 'docs/critical-urls.txt'), 'utf8').split(/\r?\n/).map((l) => l.trim()).filter((l) => l && !l.startsWith('#')).map((l) => l.split(/\s+/));
const urls = pairs.map(([, url]) => url);
const templateOf = Object.fromEntries(pairs.map(([template, url]) => [url, template]));

// Runs in the page: returns [{ sheet, index, css }] for the rules in use above the fold.
function collect() {
  const fold = window.innerHeight;
  const inFold = (el) => {
    const r = el.getBoundingClientRect();
    return r.bottom > -fold && r.top < fold && (r.width || r.height || el === document.documentElement || el === document.body);
  };
  const strip = (sel) => sel
    .replace(/::?(before|after|first-line|first-letter|placeholder|selection|marker|backdrop)/g, '')
    .replace(/:(hover|focus|focus-visible|focus-within|active|visited|link|checked|disabled|enabled|target|empty|invalid|valid|required|optional|read-only|placeholder-shown|autofill|indeterminate)/g, '')
    .replace(/:not\((.*?)\)/g, '')
    .trim();
  const matches = (selectorText) => {
    const list = selectorText.split(/,(?![^(]*\))/);
    for (const raw of list) {
      const sel = strip(raw);
      if (!sel) return true;
      let nodes;
      try { nodes = document.querySelectorAll(sel); } catch (e) { return true; }
      for (const el of nodes) if (inFold(el)) return true;
    }
    return false;
  };
  const out = [];
  const walk = (rules, sheet, prefix) => {
    for (let i = 0; i < rules.length; i++) {
      const rule = rules[i];
      const key = prefix + i;
      if (rule.type === CSSRule.STYLE_RULE) {
        if (matches(rule.selectorText)) out.push({ sheet, key, css: rule.cssText });
      } else if (rule.type === CSSRule.MEDIA_RULE || rule.type === CSSRule.SUPPORTS_RULE) {
        const inner = [];
        const sub = { push: (r) => inner.push(r) };
        walkInto(rule.cssRules, sheet, key + '.', sub);
        if (inner.length) out.push({ sheet, key, css: `@${rule.type === CSSRule.MEDIA_RULE ? 'media ' + rule.conditionText : 'supports ' + rule.conditionText}{${inner.map((r) => r.css).join('')}}` });
      } else if (rule.type === CSSRule.FONT_FACE_RULE || rule.type === CSSRule.KEYFRAMES_RULE) {
        // Keyframes and font faces are small and referenced by name; kept when the sheet is in use at all.
        out.push({ sheet, key, css: rule.cssText, aux: true });
      }
    }
  };
  const walkInto = (rules, sheet, prefix, target) => {
    for (let i = 0; i < rules.length; i++) {
      const rule = rules[i];
      const key = prefix + i;
      if (rule.type === CSSRule.STYLE_RULE) {
        if (matches(rule.selectorText)) target.push({ sheet, key, css: rule.cssText });
      } else if (rule.type === CSSRule.MEDIA_RULE || rule.type === CSSRule.SUPPORTS_RULE) {
        const inner = [];
        walkInto(rule.cssRules, sheet, key + '.', { push: (r) => inner.push(r) });
        if (inner.length) target.push({ sheet, key, css: `@${rule.type === CSSRule.MEDIA_RULE ? 'media ' + rule.conditionText : 'supports ' + rule.conditionText}{${inner.map((r) => r.css).join('')}}` });
      }
    }
  };
  for (const sheet of document.styleSheets) {
    const href = sheet.href || '';
    if (!/\/build\/(theme|diner)\.css|\/build\/blocks\//.test(href) && !(sheet.ownerNode && sheet.ownerNode.id && /^citcom-.*-inline-css$/.test(sheet.ownerNode.id))) continue;
    const name = href ? href.replace(/^.*\/build\//, '').replace(/\?.*$/, '') : sheet.ownerNode.id;
    let rules;
    try { rules = sheet.cssRules; } catch (e) { continue; }
    walk(rules, name, '');
  }
  return out;
}

const browser = await chromium.launch();
const kept = {}; // template -> sheet -> Map(key -> css)
const order = {}; // template -> sheets in first-seen order
for (const [label, vp] of [['mobile', { width: 412, height: 915 }], ['desktop', { width: 1350, height: 940 }]]) {
  const page = await browser.newPage({ viewport: vp, deviceScaleFactor: 1 });
  for (const url of urls) {
    const template = templateOf[url];
    kept[template] ||= new Map();
    order[template] ||= [];
    await page.goto(base + url, { waitUntil: 'load', timeout: 120000 });
    await page.waitForTimeout(800);
    const rules = await page.evaluate(collect);
    for (const r of rules) {
      if (!kept[template].has(r.sheet)) { kept[template].set(r.sheet, new Map()); order[template].push(r.sheet); }
      kept[template].get(r.sheet).set(r.key, r.css);
    }
    console.log(`${label} ${template} ${url}: ${rules.length} rules`);
  }
  await page.close();
}
await browser.close();

// A rule over --max-rule bytes is an inlined image, left for the stylesheet.
const maxRule = Number(args['max-rule'] || 2048);
fs.mkdirSync(args.out, { recursive: true });
for (const template of Object.keys(kept)) {
  // Stylesheet order as the page loads them: theme.css first, then diner and the blocks.
  const sheets = order[template].sort((a, b) => (a === 'theme.css' ? -1 : b === 'theme.css' ? 1 : a.localeCompare(b)));
  let css = '';
  let dropped = 0;
  for (const sheet of sheets) {
    const rules = [...kept[template].get(sheet).entries()].sort((a, b) => a[0].localeCompare(b[0], undefined, { numeric: true }));
    const small = rules.filter(([, c]) => c.length <= maxRule);
    dropped += rules.length - small.length;
    css += `/* ${sheet} */\n` + small.map(([, c]) => c).join('\n') + '\n';
  }
  // Image paths in the built CSS are relative to build/; inlined in the page they must point at the theme.
  css = css.replace(/url\("?\.\.\/build\//g, 'url("../build/').replace(/url\("?images\//g, 'url("../build/images/');
  const file = path.join(args.out, template + '.css');
  fs.writeFileSync(file, css);
  console.log(`${template}: ${(css.length / 1024).toFixed(1)}KB, ${dropped} rules over ${maxRule} bytes left out -> ${file}`);
}
