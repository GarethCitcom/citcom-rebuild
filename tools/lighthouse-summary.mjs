// Summarise Lighthouse JSON files from tools/lighthouse.sh: node tools/lighthouse-summary.mjs <dir> [brief]
import fs from 'node:fs';
const [ dir, brief ] = process.argv.slice( 2 );
const ms = ( v ) => ( v === undefined ? '-' : ( v / 1000 ).toFixed( 2 ) + 's' );
const strip = ( u ) =>
	String( u )
		.replace( /^https?:\/\/citcomstaging\.mystagingwebsite\.com/, '' )
		.replace( /^https?:\/\//, '' );
for ( const f of fs
	.readdirSync( dir )
	.filter( ( f ) => f.endsWith( '.json' ) )
	.sort() ) {
	const lh = JSON.parse( fs.readFileSync( `${ dir }/${ f }`, 'utf8' ) );
	const a = lh.audits;
	console.log(
		`\n== ${ f.replace( '.json', '' ) }  score ${ Math.round( lh.categories.performance.score * 100 ) }`
	);
	console.log(
		`   FCP ${ ms( a[ 'first-contentful-paint' ].numericValue ) }  LCP ${ ms( a[ 'largest-contentful-paint' ].numericValue ) }  SI ${ ms( a[ 'speed-index' ].numericValue ) }  TBT ${ Math.round( a[ 'total-blocking-time' ].numericValue ) }ms  CLS ${ a[ 'cumulative-layout-shift' ].numericValue.toFixed( 3 ) }  TTFB ${ Math.round( a[ 'server-response-time' ]?.numericValue || 0 ) }ms`
	);
	const lcpItems =
		a[ 'largest-contentful-paint-element' ]?.details?.items || [];
	const lcpEl = lcpItems[ 0 ]?.items?.[ 0 ]?.node?.snippet || '';
	console.log(
		`   LCP element: ${ lcpEl.replace( /\s+/g, ' ' ).slice( 0, 150 ) }`
	);
	const phases = lcpItems[ 1 ]?.items || [];
	if ( phases.length ) {
		console.log(
			'   LCP phases: ' +
				phases
					.map(
						( p ) => `${ p.phase } ${ Math.round( p.timing ) }ms`
					)
					.join( ', ' )
		);
	}
	const bytes = a[ 'resource-summary' ]?.details?.items || [];
	console.log(
		'   requests: ' +
			bytes
				.filter( ( b ) => b.resourceType !== 'total' )
				.map(
					( b ) =>
						`${ b.label } ${ b.requestCount }/${ Math.round( b.transferSize / 1024 ) }KB`
				)
				.join( ', ' ) +
			` | total ${ bytes.find( ( b ) => b.resourceType === 'total' )?.requestCount }/${ Math.round( ( bytes.find( ( b ) => b.resourceType === 'total' )?.transferSize || 0 ) / 1024 ) }KB`
	);
	if ( brief ) {
		continue;
	}
	const opps = Object.values( a )
		.filter(
			( x ) => x.details?.type === 'opportunity' && x.numericValue > 50
		)
		.sort( ( x, y ) => y.numericValue - x.numericValue );
	for ( const o of opps.slice( 0, 8 ) ) {
		console.log(
			`   opportunity: ${ o.title } (${ Math.round( o.numericValue ) }ms${ o.details.overallSavingsBytes ? ', ' + Math.round( o.details.overallSavingsBytes / 1024 ) + 'KB' : '' })`
		);
	}
	for ( const key of Object.keys( a ) ) {
		const x = a[ key ];
		if (
			! x ||
			x.score === null ||
			x.score >= 0.9 ||
			! x.details?.items?.length ||
			x.details.type === 'opportunity'
		) {
			continue;
		}
		if (
			/screenshot|metrics|diagnostics|network-requests|network-rtt|network-server-latency|main-thread-tasks|final-screenshot/.test(
				key
			)
		) {
			continue;
		}
		const items = x.details.items
			.slice( 0, 7 )
			.map(
				( i ) =>
					strip(
						i.url ||
							i.entity?.text ||
							i.entity ||
							i.label ||
							i.node?.snippet ||
							i.source?.url ||
							JSON.stringify( i ).slice( 0, 60 )
					).slice( 0, 80 ) +
					( i.wastedMs ? ` ${ Math.round( i.wastedMs ) }ms` : '' ) +
					( i.wastedBytes
						? ` ${ Math.round( i.wastedBytes / 1024 ) }KB`
						: '' ) +
					( i.transferSize
						? ` ${ Math.round( i.transferSize / 1024 ) }KB`
						: '' ) +
					( i.blockingTime
						? ` block ${ Math.round( i.blockingTime ) }ms`
						: '' ) +
					( i.mainThreadTime
						? ` main ${ Math.round( i.mainThreadTime ) }ms`
						: '' ) +
					( i.duration ? ` ${ Math.round( i.duration ) }ms` : '' )
			);
		console.log(
			`   ${ x.title }${ x.displayValue ? ' (' + x.displayValue + ')' : '' }: ${ items.join( ' | ' ) }`
		);
	}
}
