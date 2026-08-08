<!DOCTYPE html>
<html lang="ar" dir="rtl">

				<head>
								<meta charset="UTF-8">
								<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
								<meta name="csrf-token" content="{{ csrf_token() }}">
								<meta name="theme-color" content="#1a1a2e">
								<meta name="mobile-web-app-capable" content="yes">
								<meta name="apple-mobile-web-app-status-bar-style" content="default">
								<link rel="apple-touch-icon" sizes="180x180" href="/images/apple-touch-icon.png">
								<link rel="manifest" href="/manifest.json">
								<title>تأمينكم</title>
								<link rel="icon" href="/favicon.ico" type="image/x-icon">
								{{-- Note: previously preloaded noto-kufi + roboto woff2 here. Removed because
								     the preload href (from $viteFonts) did not exactly match the URL that
								     @fontsource @font-face rules request after Vite hashing, triggering
								     "preloaded but not used" warnings. CSS @import in app.css still loads
								     them on demand with font-display: swap. --}}

								{{-- Inline critical CSS — renders skeleton instantly before any bundle loads --}}
								<style>
												#app-skeleton {
																display: flex;
																flex-direction: column;
																align-items: center;
																justify-content: center;
																min-height: 70vh;
																padding: 2rem;
																text-align: center;
																font-family: 'Noto Kufi Arabic', 'Segoe UI', system-ui, sans-serif
												}

												#app-skeleton h1 {
																font-size: 2.25rem;
																font-weight: 700;
																margin-bottom: 1rem;
																color: #1a1a2e;
																line-height: 1.4
												}

												#app-skeleton p {
																font-size: 1.125rem;
																color: #64748b;
																margin-bottom: 2rem
												}

												#app-skeleton .skeleton-pulse {
																width: 200px;
																height: 44px;
																border-radius: 8px;
																margin: 0 auto;
																background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
																background-size: 200% 100%;
																animation: shimmer 1.5s ease-in-out infinite
												}

												@keyframes shimmer {
																0% {
																				background-position: 200% 0
																}

																100% {
																				background-position: -200% 0
																}
												}

												@media(min-width:768px) {
																#app-skeleton h1 {
																				font-size: 3rem
																}
												}

												#app.vue-ready #app-skeleton {
																display: none
												}
								</style>

								{{-- Vite handles CSS/JS injection + its own preload directives --}}
								@vite(["resources/css/app.css", "resources/js/app.js"])

								<!-- Snap Pixel Code -->
								<script type="text/javascript">
												(function(e, t, n) {
																if (e.snaptr) return;
																var a = e.snaptr = function() {
																				a.handleRequest ? a.handleRequest.apply(a, arguments) : a.queue.push(arguments)
																};
																a.queue = [];
																var s = 'script';
																r = t.createElement(s);
																r.async = !0;
																r.src = n;
																var u = t.getElementsByTagName(s)[0];
																u.parentNode.insertBefore(r, u);
												})(window, document,
																'https://sc-static.net/scevent.min.js');

												snaptr('init', '4cee2d25-df49-4bc3-958b-cdc1e5776f9f', {});
												snaptr('track', 'PAGE_VIEW');
								</script>
								<!-- End Snap Pixel Code -->
				</head>

				<body class="bg-background min-h-screen">
								<div id="app">
												{{-- Static skeleton — shown instantly, hidden via CSS when Vue mounts --}}
												<div id="app-skeleton" aria-hidden="true" role="presentation">
																<h1>قارن أسعار التأمين في السعودية</h1>
																<p>احصل على أفضل عرض في دقائق</p>
																<div class="skeleton-pulse" aria-label="جاري التحميل..."></div>
												</div>
								</div>
				</body>

</html>
