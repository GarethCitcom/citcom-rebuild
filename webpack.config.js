/**
 * wp-scripts build with theme-specific entries.
 *
 * - theme:  src/theme.js (imports src/scss/theme.scss)  -> build/theme.js, build/theme.css
 * - editor: src/editor.scss                             -> build/editor.css
 * - blocks: blocks/<name>/{style,editor}.scss, view.js  -> build/blocks/<name>/...
 *
 * block.json files reference the built CSS with "file:../../build/blocks/<name>/style.css".
 */
const path = require( 'path' );
const fs = require( 'fs' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const RemoveEmptyScriptsPlugin = require( 'webpack-remove-empty-scripts' );
const MiniCSSExtractPlugin = require( 'mini-css-extract-plugin' );

const entry = {
	theme: './src/theme.js',
	editor: './src/editor.scss',
};

const blocksDir = path.resolve( __dirname, 'blocks' );
if ( fs.existsSync( blocksDir ) ) {
	for ( const dirent of fs.readdirSync( blocksDir, { withFileTypes: true } ) ) {
		if ( ! dirent.isDirectory() ) {
			continue;
		}
		for ( const [ file, name ] of [ [ 'style.scss', 'style' ], [ 'editor.scss', 'editor' ], [ 'view.js', 'view' ] ] ) {
			const source = path.join( blocksDir, dirent.name, file );
			if ( fs.existsSync( source ) ) {
				entry[ `blocks/${ dirent.name }/${ name }` ] = './' + path.relative( __dirname, source ).split( path.sep ).join( '/' );
			}
		}
	}
}

// Bootstrap 5.3 still uses @import and the old division syntax; keep the build log readable.
const rules = defaultConfig.module.rules.map( ( rule ) => {
	if ( ! rule.test || ! String( rule.test ).includes( 'sc|sa' ) || ! Array.isArray( rule.use ) ) {
		return rule;
	}
	return {
		...rule,
		use: rule.use.map( ( use ) => {
			if ( typeof use !== 'object' || ! String( use.loader ).includes( 'sass-loader' ) ) {
				return use;
			}
			return {
				...use,
				options: {
					...use.options,
					sassOptions: {
						...( use.options && use.options.sassOptions ),
						quietDeps: true,
						silenceDeprecations: [ 'import', 'global-builtin', 'color-functions', 'slash-div', 'if-function', 'legacy-js-api', 'abs-percent', 'function-units' ],
					},
				},
			};
		} ),
	};
} );

// Plain [name].css output (wp-scripts would prefix style.scss output with "style-") and no RTL variants.
const plugins = defaultConfig.plugins
	.filter( ( plugin ) => ! [ 'MiniCssExtractPlugin', 'RtlCssPlugin' ].includes( plugin.constructor.name ) )
	.concat( [ new MiniCSSExtractPlugin( { filename: '[name].css' } ), new RemoveEmptyScriptsPlugin() ] );

module.exports = {
	...defaultConfig,
	entry,
	// wp-scripts renames any style.scss chunk to style-[entry]; keep [name].css instead.
	optimization: {
		...defaultConfig.optimization,
		splitChunks: {
			...defaultConfig.optimization.splitChunks,
			cacheGroups: {
				...defaultConfig.optimization.splitChunks.cacheGroups,
				style: false,
			},
		},
	},
	module: {
		...defaultConfig.module,
		rules,
	},
	plugins,
};
