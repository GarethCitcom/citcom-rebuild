/**
 * Compare a migrated legacy copy with the block fixture page it was made from
 * (tools/local-fixture-legacy.php): the two must render the same HTML apart
 * from the post id, the slug and per-request nonces.
 *
 *   node tools/legacy-compare.mjs https://citcom-rebuild.test about-us services/creative
 *
 * A path keeps its parents: services/creative is compared with services/legacy-creative.
 *
 * Prints "same" per slug, or the first difference with context.
 */
process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0';

const [base, ...slugs] = process.argv.slice(2);
if (!base || slugs.length === 0) {
	console.error('Usage: node tools/legacy-compare.mjs <base url> <slug> [slug...]');
	process.exit(1);
}

async function fetchHtml(url) {
	const res = await fetch(url, { headers: { 'User-Agent': 'legacy-compare' } });
	if (!res.ok) {
		throw new Error(`${url}: HTTP ${res.status}`);
	}
	return res.text();
}

function normalise(html) {
	// The sections live in <main>; the menu's "current" classes differ by design.
	const main = html.match(/<main\b[\s\S]*<\/main>/);
	return (main ? main[0] : html)
		.replace(/legacy-/g, '')
		.replace(/\b(page-id|postid|post)-\d+\b/g, '$1-N')
		.replace(/"id":\s*\d+/g, '"id":N')
		.replace(/\?p=\d+/g, '?p=N')
		.replace(/wp\/v2\/(pages|posts|[a-z-]+)\/\d+/g, 'wp/v2/$1/N')
		.replace(/_wpnonce=[a-f0-9]+/g, '_wpnonce=X')
		.replace(/(swiper|player|video)-[a-f0-9]{13}/g, '$1-X')
		.replace(/name="_wpnonce" value="[a-f0-9]+"/g, 'name="_wpnonce" value="X"')
		.replace(/name="citcom_form_nonce" value="[a-f0-9]+"/g, 'name="citcom_form_nonce" value="X"')
		.replace(/<link rel="(canonical|shortlink)"[^>]*>/g, '')
		.replace(/<meta property="og:url"[^>]*>/g, '')
		.replace(/<meta name="generator"[^>]*>/g, '')
		.replace(/\s+/g, ' ')
		// Whitespace between tags: the legacy copy's inner blocks lose the newlines between them.
		.replace(/> </g, '><');
}

let failed = false;
for (const slug of slugs) {
	let a;
	let b;
	try {
		const legacy = slug.replace(/([^/]+)$/, 'legacy-$1');
		a = normalise(await fetchHtml(`${base}/${slug}/`));
		b = normalise(await fetchHtml(`${base}/${legacy}/`));
	} catch (e) {
		console.log(`${slug}: ${e.message}`);
		failed = true;
		continue;
	}
	const sections = (h) => (h.match(/<section\b/g) || []).length;
	if (a === b) {
		console.log(`${slug}: same (${sections(a)} sections, ${a.length} chars)`);
		continue;
	}
	failed = true;
	let i = 0;
	while (i < a.length && i < b.length && a[i] === b[i]) {
		i++;
	}
	console.log(`${slug}: DIFFERENT at ${i} of ${a.length}/${b.length} chars (${sections(a)} vs ${sections(b)} sections)`);
	console.log('  original: ...' + a.slice(Math.max(0, i - 120), i + 200));
	console.log('  legacy:   ...' + b.slice(Math.max(0, i - 120), i + 200));
}
process.exit(failed ? 1 : 0);
