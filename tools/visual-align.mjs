#!/usr/bin/env node
/**
 * Row-aligned comparison of two capture sets from tools/visual-baseline.mjs.
 *
 * A plain pixel diff marks everything below the first change as different: one
 * link removed from the footer menu fails all 246 shots. This lines the two
 * screenshots up row by row instead, the way a text diff lines up lines, and
 * reports only the bands that really changed:
 *
 *   node tools/visual-align.mjs --a .baseline/old --b .baseline/new --out .baseline/align [--only substring]
 *
 * For every pair it prints the bands whose height changed (something grew,
 * shrank, appeared or went) and the share of pixels that differ inside the
 * bands that kept their height (same layout, different pixels: an image that
 * resampled, a widget's "5 days ago"). report.json in --out has every band.
 *
 * Reading the result: a page whose rows all line up and whose pixel share is
 * under about 0.5% is the same page. Bands near the bottom are the footer.
 * Fixed-position elements (the chat button) land wherever the capture put
 * them and are noise.
 */
import fs from 'node:fs';
import path from 'node:path';
import { PNG } from 'pngjs';
import pixelmatch from 'pixelmatch';

const args = Object.fromEntries(process.argv.slice(2).map((a, i, arr) => a.startsWith('--') ? [a.slice(2), arr[i + 1] ?? true] : []).filter(Boolean));
if (!args.a || !args.b) {
  console.error('Usage: node tools/visual-align.mjs --a <dir> --b <dir> [--out <dir>] [--only <substring>]');
  process.exit(1);
}
const out = args.out || '.baseline/align';
fs.mkdirSync(out, { recursive: true });

const BUCKETS = 96; // each row is compared as 96 averaged colour cells

function signatures(img) {
  const w = img.width, h = img.height, per = Math.floor(w / BUCKETS);
  const sig = new Uint8Array(h * BUCKETS * 3);
  const flat = new Uint8Array(h); // 1 = a row of one colour, which matches anything and cannot anchor a resync
  for (let y = 0; y < h; y++) {
    let min = 255, max = 0;
    for (let k = 0; k < BUCKETS; k++) {
      let r = 0, g = 0, b = 0;
      const start = (y * w + k * per) * 4;
      for (let x = 0; x < per; x++) { const i = start + x * 4; r += img.data[i]; g += img.data[i + 1]; b += img.data[i + 2]; }
      const o = (y * BUCKETS + k) * 3;
      sig[o] = r / per; sig[o + 1] = g / per; sig[o + 2] = b / per;
      const lum = (sig[o] + sig[o + 1] + sig[o + 2]) / 3;
      if (lum < min) min = lum;
      if (lum > max) max = lum;
    }
    flat[y] = max - min < 6 ? 1 : 0;
  }
  return { sig, flat, h };
}

function rowsEqual(A, i, B, j) {
  let bad = 0;
  const oa = i * BUCKETS * 3, ob = j * BUCKETS * 3;
  for (let k = 0; k < BUCKETS * 3; k++) {
    if (Math.abs(A.sig[oa + k] - B.sig[ob + k]) > 10 && ++bad > 6) return false;
  }
  return true;
}

function runMatches(A, i, B, j, n) {
  let textured = 0;
  for (let k = 0; k < n; k++) {
    if (i + k >= A.h || j + k >= B.h) return k > 8 && textured > 2;
    if (!rowsEqual(A, i + k, B, j + k)) return false;
    if (!A.flat[i + k]) textured++;
  }
  return textured >= 6;
}

// Walk both images; on a mismatch, find the smallest skip (old rows + new rows) after which 40 rows line up again.
function align(A, B) {
  const bands = [];
  let i = 0, j = 0;
  while (i < A.h && j < B.h) {
    if (rowsEqual(A, i, B, j)) { i++; j++; continue; }
    let found = null;
    for (let s = 1; s <= 2600 && !found; s++) {
      for (let da = 0; da <= s; da++) {
        const db = s - da;
        if (i + da >= A.h || j + db >= B.h) continue;
        if (rowsEqual(A, i + da, B, j + db) && runMatches(A, i + da, B, j + db, 40)) { found = [da, db]; break; }
      }
    }
    if (!found) { bands.push({ old: [i, A.h], new: [j, B.h], tail: true }); return bands; }
    bands.push({ old: [i, i + found[0]], new: [j, j + found[1]] });
    i += found[0]; j += found[1];
  }
  if (i < A.h || j < B.h) bands.push({ old: [i, A.h], new: [j, B.h], tail: true });
  return bands;
}

const report = [];
const files = fs.readdirSync(args.a).filter(f => f.endsWith('.png') && (!args.only || f.includes(String(args.only))));
for (const f of files) {
  const pb = path.join(args.b, f);
  if (!fs.existsSync(pb)) { report.push({ file: f, result: 'missing-in-b' }); console.log('MISSING', f); continue; }
  const imgA = PNG.sync.read(fs.readFileSync(path.join(args.a, f)));
  const imgB = PNG.sync.read(fs.readFileSync(pb));
  const w = Math.min(imgA.width, imgB.width);
  const bands = align(signatures(imgA), signatures(imgB));
  let bad = 0;
  for (const band of bands) {
    const o = band.old[1] - band.old[0], n = band.new[1] - band.new[0];
    if (o !== n || o === 0) continue;
    const x = new PNG({ width: w, height: o }), y = new PNG({ width: w, height: o });
    PNG.bitblt(imgA, x, 0, band.old[0], w, o, 0, 0);
    PNG.bitblt(imgB, y, 0, band.new[0], w, o, 0, 0);
    band.pixels = pixelmatch(x.data, y.data, null, w, o, { threshold: 0.1 });
    bad += band.pixels;
  }
  const moved = bands.filter(b => (b.old[1] - b.old[0]) !== (b.new[1] - b.new[0]));
  const net = moved.reduce((t, b) => t + (b.new[1] - b.new[0]) - (b.old[1] - b.old[0]), 0);
  const ratio = bad / (w * imgA.height);
  report.push({ file: f, heightA: imgA.height, heightB: imgB.height, net, pixelRatio: +ratio.toFixed(5), bands });
  console.log(
    f.padEnd(70), String(imgA.height).padStart(6), '->', String(imgB.height).padEnd(6), ('net ' + (net > 0 ? '+' : '') + net).padEnd(10), (ratio * 100).toFixed(2).padStart(6) + '%',
    moved.filter(b => Math.abs((b.new[1] - b.new[0]) - (b.old[1] - b.old[0])) > 3).map(b => `[y${b.old[0]} ${b.old[1] - b.old[0]}->${b.new[1] - b.new[0]}]`).join(' ').slice(0, 120)
  );
}
fs.writeFileSync(path.join(out, 'report.json'), JSON.stringify(report));
const done = report.filter(r => r.bands);
console.log(`\n${done.length} pairs; ${done.filter(r => r.net === 0).length} with no net height change; ${done.filter(r => r.pixelRatio <= 0.005).length} with at most 0.5% of pixels different inside aligned bands`);
