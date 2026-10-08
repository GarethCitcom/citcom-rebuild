// Medians over the run1..runN folders tools/lighthouse-median.sh writes: node tools/lighthouse-median.mjs <dir>
import fs from 'node:fs';
const dir = process.argv[2];
const runs = fs.readdirSync(dir).filter((d) => /^run\d+$/.test(d)).sort();
const pages = [...new Set(runs.flatMap((r) => fs.readdirSync(`${dir}/${r}`)))].sort();
const median = (xs) => { const s = [...xs].sort((a, b) => a - b); return s.length ? s[Math.floor((s.length - 1) / 2)] : NaN; };
const get = (lh, key) => lh.audits[key]?.numericValue;
console.log('page                                   runs  score  FCP    LCP    SI     CLS    TTFB');
for (const p of pages) {
  const results = runs.map((r) => { try { return JSON.parse(fs.readFileSync(`${dir}/${r}/${p}`, 'utf8')); } catch { return null; } }).filter(Boolean);
  const m = (f) => median(results.map(f));
  console.log(`${p.replace('.json', '').padEnd(38)} ${String(results.length).padStart(2)}    ${String(Math.round(m((lh) => lh.categories.performance.score * 100))).padStart(3)}   ${(m((lh) => get(lh, 'first-contentful-paint')) / 1000).toFixed(2)}s  ${(m((lh) => get(lh, 'largest-contentful-paint')) / 1000).toFixed(2)}s  ${(m((lh) => get(lh, 'speed-index')) / 1000).toFixed(2)}s  ${m((lh) => get(lh, 'cumulative-layout-shift')).toFixed(3)}  ${Math.round(m((lh) => get(lh, 'server-response-time')))}ms`);
}
