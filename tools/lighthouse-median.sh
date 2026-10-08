#!/bin/bash
# Three Lighthouse runs per page and strategy; summarise with tools/lighthouse-median.mjs. bash tools/lighthouse-median.sh <out-dir> <url> ...
OUT="$1"; shift
export CHROME_PATH="${CHROME_PATH:-$(node -e "console.log(require('playwright').chromium.executablePath())")}"
for url in "$@"; do
  name=$(echo "$url" | sed -E 's#^https?://[^/]+##; s#[^A-Za-z0-9]+#_#g; s#^_+|_+$##g'); [ -z "$name" ] && name=home
  for strategy in mobile desktop; do
    preset=""; [ "$strategy" = desktop ] && preset="--preset=desktop"
    for run in 1 2 3; do
      mkdir -p "$OUT/run$run"
      npx -y lighthouse "$url" $preset --only-categories=performance --output=json --output-path="$OUT/run$run/$name-$strategy.json" --chrome-flags="--headless=new --no-sandbox" --quiet 2>/dev/null
    done
    echo "done $name $strategy"
  done
done
