#!/bin/bash
# Lighthouse (the engine behind PageSpeed Insights), mobile and desktop presets,
# one JSON per run, then a summary. Uses Playwright's Chromium.
#
#   bash tools/lighthouse.sh .baseline/lh-<label> https://example.com/ https://example.com/services/creative/ ...
#
# The keyless PageSpeed Insights API shares one small daily quota with everyone,
# so this runs the same audits locally instead. docs/05-phase4-brief.md.
OUT="$1"; shift
[ -z "$OUT" ] || [ $# -eq 0 ] && { echo "usage: bash tools/lighthouse.sh <out-dir> <url> ..."; exit 1; }
mkdir -p "$OUT"
export CHROME_PATH="${CHROME_PATH:-$(node -e "console.log(require('playwright').chromium.executablePath())")}"
for url in "$@"; do
  name=$(echo "$url" | sed -E 's#^https?://[^/]+##; s#[^A-Za-z0-9]+#_#g; s#^_+|_+$##g'); [ -z "$name" ] && name=home
  for strategy in mobile desktop; do
    preset=""; [ "$strategy" = desktop ] && preset="--preset=desktop"
    npx -y lighthouse "$url" $preset --only-categories=performance --output=json --output-path="$OUT/$name-$strategy.json" --chrome-flags="--headless=new --no-sandbox" --quiet 2>/dev/null
    echo "done $strategy $name"
  done
done
node tools/lighthouse-summary.mjs "$OUT" brief
