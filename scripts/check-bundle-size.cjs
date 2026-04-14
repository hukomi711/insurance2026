/**
 * CI Bundle Size Budget Check
 * Run after `vite build` to enforce size limits on critical chunks.
 *
 * Usage:  node scripts/check-bundle-size.cjs
 * Exit 1 on any budget violation so CI fails the build.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { gzipSync } = require( 'zlib' );

const BUILD_DIR = path.resolve( __dirname, '..', 'public', 'build', 'assets' );

/** Budgets in KB (gzipped). Chunk name is matched as a substring of the filename. */
const BUDGETS = {
    'vendor-vue':   70,
    'vendor-ui':    100,
    'vendor-echo':  25,
    'vendor-utils': 35,
    'app-':         35,   // app entry JS (matches app-XXXX.js, not app-XXXX.css)
};

const files = fs.readdirSync( BUILD_DIR ).filter( f => f.endsWith( '.js' ) );
const violations = [];
let checked = 0;

for ( const [ pattern, budgetKB ] of Object.entries( BUDGETS ) )
{
    const match = files.find( f => f.includes( pattern ) );
    if ( !match )
    {
        console.warn( `⚠  No file matching "${ pattern }" — skipped` );
        continue;
    }

    const filePath = path.join( BUILD_DIR, match );
    const raw = fs.readFileSync( filePath );
    const gzipKB = ( gzipSync( raw ).length / 1024 ).toFixed( 2 );

    checked++;
    const status = gzipKB > budgetKB ? '❌ OVER' : '✅ OK';
    console.log( `${ status }  ${ match.padEnd( 45 ) }  ${ gzipKB } KB / ${ budgetKB } KB budget` );

    if ( gzipKB > budgetKB )
    {
        violations.push( `${ match }: ${ gzipKB } KB exceeds ${ budgetKB } KB budget` );
    }
}

console.log( `\nChecked ${ checked } chunks.` );

if ( violations.length )
{
    console.error( `\n🚫 ${ violations.length } budget violation(s):\n${ violations.join( '\n' ) }` );
    process.exit( 1 );
}
else
{
    console.log( '✅ All chunks within budget.' );
}
