/**
 * CI bundle-transfer budget check.
 *
 * Reads Vite's manifest and measures the gzip transfer size of each static
 * import closure. This stays valid when hashed chunk names or Vite's automatic
 * chunk boundaries change.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { gzipSync } = require( 'zlib' );

const ROOT = path.resolve( __dirname, '..', '..' );
const BUILD_DIR = path.join( ROOT, 'public', 'build' );
const MANIFEST_PATH = path.join( BUILD_DIR, 'manifest.json' );

/** Gzip transfer budgets in KB. Route budgets include the startup closure. */
const BUDGETS = {
    startup: {
        maxGzipKB: 190,
        entries: [ 'resources/js/app.js', 'resources/css/app.css' ],
    },
    home: {
        maxGzipKB: 205,
        entries: [
            'resources/js/app.js',
            'resources/css/app.css',
            'resources/js/components/layout/PublicLayout.vue',
            'resources/js/car.insurance/HomePage.vue',
        ],
    },
    compare: {
        maxGzipKB: 280,
        entries: [
            'resources/js/app.js',
            'resources/css/app.css',
            'resources/js/components/layout/PublicLayout.vue',
            'resources/js/car.insurance/flow/ComparePage.vue',
        ],
    },
    checkout: {
        maxGzipKB: 235,
        entries: [
            'resources/js/app.js',
            'resources/css/app.css',
            'resources/js/components/layout/PublicLayout.vue',
            'resources/js/car.insurance/flow/CheckoutPage.vue',
        ],
    },
    dashboard: {
        maxGzipKB: 235,
        entries: [
            'resources/js/app.js',
            'resources/css/app.css',
            'resources/js/dashboard/layouts/DashboardLayout.vue',
            'resources/js/dashboard/pages/DashboardHome.vue',
        ],
    },
};

if ( !fs.existsSync( MANIFEST_PATH ) )
{
    console.error( `🚫 Vite manifest not found: ${ MANIFEST_PATH }` );
    process.exit( 1 );
}

const manifest = JSON.parse( fs.readFileSync( MANIFEST_PATH, 'utf8' ) );
const gzipCache = new Map();

function gzipBytes( relativeFile )
{
    if ( gzipCache.has( relativeFile ) ) return gzipCache.get( relativeFile );

    const absolutePath = path.join( BUILD_DIR, relativeFile );
    if ( !fs.existsSync( absolutePath ) )
    {
        throw new Error( `Manifest asset is missing: ${ relativeFile }` );
    }

    const bytes = gzipSync( fs.readFileSync( absolutePath ) ).length;
    gzipCache.set( relativeFile, bytes );
    return bytes;
}

function collectStaticClosure( entryKeys )
{
    const visitedEntries = new Set();
    const files = new Set();

    function visit( key )
    {
        if ( visitedEntries.has( key ) ) return;

        const entry = manifest[ key ];
        if ( !entry ) throw new Error( `Manifest entry is missing: ${ key }` );

        visitedEntries.add( key );
        if ( entry.file ) files.add( entry.file );
        for ( const css of entry.css || [] ) files.add( css );
        for ( const imported of entry.imports || [] ) visit( imported );
    }

    for ( const key of entryKeys ) visit( key );

    return {
        requests: files.size,
        gzipBytes: [ ...files ].reduce( ( sum, file ) => sum + gzipBytes( file ), 0 ),
    };
}

const violations = [];

for ( const [ name, budget ] of Object.entries( BUDGETS ) )
{
    try
    {
        const result = collectStaticClosure( budget.entries );
        const gzipKB = result.gzipBytes / 1024;
        const status = gzipKB > budget.maxGzipKB ? '❌ OVER' : '✅ OK';

        console.log(
            `${ status }  ${ name.padEnd( 12 ) }  ${ gzipKB.toFixed( 2 ) } KB / ${ budget.maxGzipKB } KB  (${ result.requests } requests)`,
        );

        if ( gzipKB > budget.maxGzipKB )
        {
            violations.push( `${ name }: ${ gzipKB.toFixed( 2 ) } KB exceeds ${ budget.maxGzipKB } KB` );
        }
    }
    catch ( error )
    {
        violations.push( `${ name }: ${ error.message }` );
    }
}

if ( violations.length )
{
    console.error( `\n🚫 ${ violations.length } bundle budget violation(s):\n${ violations.join( '\n' ) }` );
    process.exit( 1 );
}

console.log( `\n✅ All ${ Object.keys( BUDGETS ).length } bundle transfer budgets passed.` );
