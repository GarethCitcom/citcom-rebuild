#!/usr/bin/env node
/**
 * Visual regression capture for the citcom rebuild.
 *
 * Usage:
 *   node tools/visual-baseline.mjs capture --base https://citcomstaging.mystagingwebsite.com --out .baseline/old
 *   node tools/visual-baseline.mjs capture --base http://citcom-rebuild.test --out .baseline/new
 *   node tools/visual-baseline.mjs diff --a .baseline/old --b .baseline/new --out .baseline/diff [--threshold 0.001]
 *
 * Options for capture: --insecure (accept the Laragon self-signed certificate),
  * --only slug1,slug2 (capture only URLs whose path contains one of these; prefix an entry with = for an exact path).
 *
 * Reads docs/urls.csv (type,path). Captures full-page PNGs at desktop (1440)
 * and mobile (390) with animations, AOS, lozad lazy-load and carousels
 * neutralised so screenshots are deterministic. Requires: npm i -D playwright pixelmatch pngjs
 * then: npx playwright install chromium
 */
import fs from 'node:fs';
import path from 'node:path';
import { chromium } from 'playwright';

const args = Object.fromEntries(process.argv.slice(3).map((a, i, arr) => a.startsWith('--') ? [a.slice(2), arr[i + 1] ?? true] : []).filter(Boolean));
const cmd = process.argv[2];
const VIEWPORTS = { desktop: { width: 1440, height: 900 }, mobile: { width: 390, height: 844, isMobile: true, deviceScaleFactor: 2 } };

const FREEZE_CSS = `
  *, *::before, *::after { transition: none !important; animation: none !important; caret-color: transparent !important; }
  [data-aos] { opacity: 1 !important; transform: none !important; visibility: visible !important; }
  .swiper-wrapper { transform: none !important; }
  html { scroll-behavior: auto !important; }
`;

function slug(p) { return p.replace(/^\/|\/$/g, '').replace(/[^a-z0-9]+/gi, '_') || 'home'; }

function readUrls() {
  const rows = fs.readFileSync(path.resolve('docs/urls.csv'), 'utf8').trim().split('\n').slice(1);
  return rows.map(l => { const [type, p] = l.split(','); return { type, path: p }; });
}

async function capture() {
  const base = args.base?.replace(/\/$/, '');
  const out = args.out || '.baseline/capture';
  if (!base) throw new Error('--base is required');
  fs.mkdirSync(out, { recursive: true });
  const only = args.only ? String(args.only).split(',').filter(Boolean) : [];
  const urls = readUrls().filter(u => !only.length || only.some(o => o.startsWith('=') ? u.path === o.slice(1) : u.path.includes(o)));
  const browser = await chromium.launch();
  const manifest = [];
  for (const [vpName, vp] of Object.entries(VIEWPORTS)) {
    const ctx = await browser.newContext({ viewport: { width: vp.width, height: vp.height }, isMobile: !!vp.isMobile, deviceScaleFactor: vp.deviceScaleFactor || 1, reducedMotion: 'reduce', ignoreHTTPSErrors: !!args.insecure });
    await ctx.addInitScript(() => { window.__frozen = true; });
    const page = await ctx.newPage();
    for (const u of urls) {
      const target = base + u.path;
      const file = path.join(out, `${slug(u.path)}.${vpName}.png`);
      try {
        const res = await page.goto(target, { waitUntil: 'networkidle', timeout: 60000 });
        await page.addStyleTag({ content: FREEZE_CSS });
        // force lazy images + AOS elements to render, then walk the page so IntersectionObservers fire
        await page.evaluate(async () => {
          document.querySelectorAll('img[data-src]').forEach(i => { i.src = i.dataset.src; i.removeAttribute('data-src'); });
          document.querySelectorAll('[data-background-image]').forEach(e => { e.style.backgroundImage = `url(${e.dataset.backgroundImage})`; });
          const h = document.body.scrollHeight; for (let y = 0; y < h; y += 600) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 60)); }
          window.scrollTo(0, 0);
          document.documentElement.classList.remove('no-js', 'no-animation');
        });
        await page.waitForTimeout(800);
        await page.screenshot({ path: file, fullPage: true });
        manifest.push({ path: u.path, type: u.type, viewport: vpName, status: res?.status(), file });
        console.log('ok  ', vpName, res?.status(), u.path);
      } catch (e) {
        manifest.push({ path: u.path, type: u.type, viewport: vpName, error: String(e.message).split('\n')[0] });
        console.log('FAIL', vpName, u.path, e.message.split('\n')[0]);
      }
    }
    await ctx.close();
  }
  await browser.close();
  fs.writeFileSync(path.join(out, 'manifest.json'), JSON.stringify({ base, capturedAt: new Date().toISOString(), shots: manifest }, null, 2));
}

async function diff() {
  const { PNG } = await import('pngjs');
  const pixelmatch = (await import('pixelmatch')).default;
  const a = args.a, b = args.b, out = args.out || '.baseline/diff';
  const threshold = Number(args.threshold ?? 0.001); // fraction of differing pixels allowed
  fs.mkdirSync(out, { recursive: true });
  const files = fs.readdirSync(a).filter(f => f.endsWith('.png'));
  const report = [];
  for (const f of files) {
    const pb = path.join(b, f);
    if (!fs.existsSync(pb)) { report.push({ file: f, result: 'missing-in-b' }); continue; }
    const A = PNG.sync.read(fs.readFileSync(path.join(a, f)));
    const B = PNG.sync.read(fs.readFileSync(pb));
    const w = Math.max(A.width, B.width), h = Math.max(A.height, B.height);
    const pad = (img) => { if (img.width === w && img.height === h) return img; const n = new PNG({ width: w, height: h }); PNG.bitblt(img, n, 0, 0, img.width, img.height, 0, 0); return n; };
    const pa = pad(A), pbb = pad(B), d = new PNG({ width: w, height: h });
    const bad = pixelmatch(pa.data, pbb.data, d.data, w, h, { threshold: 0.1 });
    const ratio = bad / (w * h);
    const pass = ratio <= threshold && A.height === B.height;
    if (!pass) fs.writeFileSync(path.join(out, f), PNG.sync.write(d));
    report.push({ file: f, differingPixels: bad, ratio: +ratio.toFixed(5), heightA: A.height, heightB: B.height, result: pass ? 'pass' : 'FAIL' });
    console.log(pass ? 'pass' : 'FAIL', f, (ratio * 100).toFixed(3) + '%', A.height !== B.height ? `height ${A.height}->${B.height}` : '');
  }
  fs.writeFileSync(path.join(out, 'report.json'), JSON.stringify(report, null, 2));
  const fails = report.filter(r => r.result !== 'pass').length;
  console.log(`\n${report.length - fails}/${report.length} passed`);
  process.exitCode = fails ? 1 : 0;
}

if (cmd === 'capture') await capture();
else if (cmd === 'diff') await diff();
else { console.error('usage: capture|diff'); process.exit(2); }
