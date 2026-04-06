/* global PerformanceObserver, performance, requestIdleCallback */
import { ref, onMounted, onUnmounted } from 'vue';

const performanceMetrics = ref({
    lcp: null,
    fid: null,
    cls: null,
    ttfb: null,
    fcp: null
});

// مراجع للمراقبين لإيقافهم لاحقًا
let lcpObserver, fidObserver, clsObserver, fcpObserver;

/**
 * مراقبة Core Web Vitals
 */
export const observeWebVitals = () => {
    if ( 'PerformanceObserver' in window ) {
        try {
            // LCP
            lcpObserver = new PerformanceObserver( ( list ) => {
                const entries = list.getEntries();
                if ( entries.length ) {
                    performanceMetrics.value.lcp = entries[ entries.length - 1 ].startTime;
                }
            } );
            lcpObserver.observe( { type: 'largest-contentful-paint', buffered: true } );

            // FID
            fidObserver = new PerformanceObserver( ( list ) => {
                list.getEntries().forEach( ( entry ) => {
                    performanceMetrics.value.fid = entry.processingStart - entry.startTime;
                } );
            } );
            fidObserver.observe( { type: 'first-input', buffered: true } );

            // CLS
            let clsValue = 0;
            clsObserver = new PerformanceObserver( ( list ) => {
                list.getEntries().forEach( ( entry ) => {
                    if ( !entry.hadRecentInput ) {
                        clsValue += entry.value;
                        performanceMetrics.value.cls = clsValue;
                    }
                } );
            } );
            clsObserver.observe( { type: 'layout-shift', buffered: true } );

            // FCP
            fcpObserver = new PerformanceObserver( ( list ) => {
                list.getEntries().forEach( ( entry ) => {
                    if ( entry.name === 'first-contentful-paint' ) {
                        performanceMetrics.value.fcp = entry.startTime;
                    }
                } );
            } );
            fcpObserver.observe( { type: 'paint', buffered: true } );

        } catch ( e ) {
            console.warn( 'Web Vitals not supported', e );
        }
    }

    // TTFB باستخدام Navigation Timing Level 2 إذا كان متاح
    const [ nav ] = performance.getEntriesByType( 'navigation' );
    if ( nav ) {
        performanceMetrics.value.ttfb = nav.responseStart;
    } else if ( 'performance' in window && 'timing' in performance ) {
        performanceMetrics.value.ttfb = performance.timing.responseStart - performance.timing.navigationStart;
    }
};

/**
 * تقييم الأداء
 */
export const getPerformanceScore = () => {
    const { lcp, fid, cls, fcp, ttfb } = performanceMetrics.value;
    let score = 100;

    if ( lcp ) score -= lcp > 4000 ? 25 : lcp > 2500 ? 15 : 0;
    if ( fid ) score -= fid > 300 ? 25 : fid > 100 ? 15 : 0;
    if ( cls ) score -= cls > 0.25 ? 25 : cls > 0.1 ? 15 : 0;
    if ( fcp ) score -= fcp > 3000 ? 15 : fcp > 1800 ? 10 : 0;
    if ( ttfb ) score -= ttfb > 500 ? 10 : ttfb > 200 ? 5 : 0;

    return Math.max( 0, score );
};

/**
 * تحسين الصور بشكل كسول
 */
export const lazyLoadImages = () => {
    document.querySelectorAll( 'img[data-src]' ).forEach( img => img.setAttribute( 'loading', 'lazy' ) );

    if ( 'IntersectionObserver' in window ) {
        const observer = new IntersectionObserver( ( entries, obs ) => {
            entries.forEach( entry => {
                if ( entry.isIntersecting ) {
                    const img = entry.target;
                    if ( img.dataset.src ) {
                        img.src = img.dataset.src;
                        img.classList.add( 'loaded' );
                    }
                    obs.unobserve( img );
                }
            } );
        }, { rootMargin: '50px 0px', threshold: 0.01 } );

        document.querySelectorAll( 'img[data-src]' ).forEach( img => observer.observe( img ) );
    }
};

/**
 * تأجيل تحميل السكربتات غير الحرجة
 */
export const deferNonCriticalScripts = () => {
    const loadScripts = () => {
        document.querySelectorAll( 'script[data-defer-src]' ).forEach( script => {
            const newScript = document.createElement( 'script' );
            newScript.src = script.dataset.deferSrc;
            document.body.appendChild( newScript );
        } );
    };

    window.addEventListener( 'load', () => {
        if ( 'requestIdleCallback' in window ) {
            requestIdleCallback( loadScripts );
        } else {
            setTimeout( loadScripts, 2000 );
        }
    } );
};

/**
 * Composable للاستخدام في المكونات
 */
export const usePerformance = () => {
    onMounted( () => {
        observeWebVitals();
        lazyLoadImages();
        deferNonCriticalScripts();
    } );

    onUnmounted( () => {
        lcpObserver?.disconnect();
        fidObserver?.disconnect();
        clsObserver?.disconnect();
        fcpObserver?.disconnect();
    } );

    return {
        metrics: performanceMetrics,
        getScore: getPerformanceScore,
        lazyLoadImages,
        deferNonCriticalScripts
    };
};

export default usePerformance;
