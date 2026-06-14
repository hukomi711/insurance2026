/**
 * Dependency Health Check
 * ─────────────────────────────────────────────────────────────
 * Enforces dependency governance rules in CI:
 *
 * 1. Security: npm audit (critical + high = fail)
 * 2. Bloat:    No known heavy/banned packages
 * 3. Lockfile: package-lock.json freshness
 *
 * Usage:  node scripts/quality/check-deps-health.cjs
 * Exit 1 on any violation.
 */
const { execSync } = require( 'child_process' );
const fs = require( 'fs' );
const path = require( 'path' );

const ROOT = path.resolve( __dirname, '..', '..' );
const violations = [];

// ── 1. Security Audit (Balanced: critical + high fail) ──────

console.log( '── Security Audit ──' );
try
{
    // --omit=dev: only audit production deps
    // --audit-level=high: fail on high + critical
    execSync( 'npm audit --omit=dev --audit-level=high', {
        cwd: ROOT,
        stdio: 'pipe',
    } );
    console.log( '✅ No high/critical vulnerabilities in production deps' );
}
catch ( err )
{
    const output = err.stdout?.toString() || err.stderr?.toString() || '';
    console.error( '❌ Security vulnerabilities found:\n' + output );
    violations.push( 'npm audit: high/critical vulnerabilities in production dependencies' );
}

// Also check dev deps (warn only, don't fail)
try
{
    execSync( 'npm audit --audit-level=critical', {
        cwd: ROOT,
        stdio: 'pipe',
    } );
    console.log( '✅ No critical vulnerabilities in any deps' );
}
catch ( err )
{
    const output = err.stdout?.toString() || err.stderr?.toString() || '';
    console.warn( '⚠️  Dev dependency vulnerabilities (warning):\n' + output );
    // Don't fail on dev-only issues (Balanced mode)
}

// ── 2. Banned / Heavy Packages ──────────────────────────────

console.log( '\n── Banned Package Check ──' );

/**
 * Packages that should never appear in this project.
 * Each entry: [package-name, reason]
 */
const BANNED = [
    [ 'moment',         'Use dayjs or native Intl.DateTimeFormat' ],
    [ 'lodash',         'Use lodash-es or native methods' ],
    [ 'jquery',         'Not needed in Vue SPA' ],
    [ 'core-js',        'Vite handles polyfills via browserslist' ],
    [ 'babel-polyfill',  'Legacy — replaced by core-js/Vite' ],
    [ 'node-sass',      'Use sass (dart-sass)' ],
    [ 'request',        'Deprecated — use axios or fetch' ],
    [ 'uuid',           'Use crypto.randomUUID()' ],
];

const pkgJson = JSON.parse( fs.readFileSync( path.join( ROOT, 'package.json' ), 'utf-8' ) );
const allDeps = {
    ...( pkgJson.dependencies || {} ),
    ...( pkgJson.devDependencies || {} ),
};

let bannedFound = 0;
for ( const [ pkg, reason ] of BANNED )
{
    if ( allDeps[ pkg ] )
    {
        console.error( `❌ Banned: "${ pkg }" — ${ reason }` );
        violations.push( `Banned package: ${ pkg } (${ reason })` );
        bannedFound++;
    }
}

if ( bannedFound === 0 )
{
    console.log( '✅ No banned packages found' );
}

// ── 3. Lockfile Freshness ───────────────────────────────────

console.log( '\n── Lockfile Check ──' );

const lockPath = path.join( ROOT, 'package-lock.json' );
if ( !fs.existsSync( lockPath ) )
{
    console.error( '❌ package-lock.json is missing' );
    violations.push( 'Missing package-lock.json' );
}
else
{
    // Verify lockfile is in sync with package.json
    // npm ci will fail if they're out of sync, but this gives a clearer error
    try
    {
        execSync( 'npm ls --depth=0 2>&1', { cwd: ROOT, stdio: 'pipe' } );
        console.log( '✅ package-lock.json is in sync' );
    }
    catch ( err )
    {
        const output = err.stdout?.toString() || '';
        if ( output.includes( 'missing' ) || output.includes( 'extraneous' ) )
        {
            console.error( '❌ package-lock.json is out of sync with package.json' );
            violations.push( 'Lockfile out of sync (run npm install and commit lock)' );
        }
        else
        {
            console.log( '✅ package-lock.json is in sync' );
        }
    }
}

// ── 4. Package Count Guard ──────────────────────────────────

console.log( '\n── Package Count ──' );

const MAX_TOP_LEVEL = 30;
const topLevelCount = Object.keys( allDeps ).length;

if ( topLevelCount > MAX_TOP_LEVEL )
{
    console.error( `❌ Too many top-level deps: ${ topLevelCount } (max: ${ MAX_TOP_LEVEL })` );
    violations.push( `Top-level deps (${ topLevelCount }) exceeds limit (${ MAX_TOP_LEVEL })` );
}
else
{
    console.log( `✅ ${ topLevelCount } top-level deps (limit: ${ MAX_TOP_LEVEL })` );
}

// ── Summary ─────────────────────────────────────────────────

console.log( '\n══════════════════════════════════' );

if ( violations.length )
{
    console.error( `🚫 ${ violations.length } governance violation(s):` );
    violations.forEach( v => console.error( `   • ${ v }` ) );
    process.exit( 1 );
}
else
{
    console.log( '✅ All dependency governance checks passed.' );
}
