<!DOCTYPE html>
<html lang="ar" dir="rtl">

				<head>
								<meta charset="UTF-8">
								<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
								<meta name="csrf-token" content="{{ csrf_token() }}">
								<meta name="theme-color" content="#1a1a2e">
								<meta name="mobile-web-app-capable" content="yes">
								<meta name="apple-mobile-web-app-status-bar-style" content="default">
								<meta name="robots" content="index, follow">
								<link rel="apple-touch-icon" sizes="180x180" href="/images/apple-touch-icon.png">
								<link rel="manifest" href="/manifest.json">
								<title>تأمينكم - مقارنة أسعار التأمين في السعودية</title>
								<meta name="description"
												content="قارن أسعار تأمين المركبات من أفضل شركات التأمين في المملكة العربية السعودية. احصل على أفضل عرض في دقائق.">
								<link rel="icon" href="/favicon.ico" type="image/x-icon">

								{{-- Open Graph --}}
								<meta property="og:type" content="website">
								<meta property="og:locale" content="ar_SA">
								<meta property="og:site_name" content="تأمينكم">
								<meta property="og:title" content="تأمينكم - مقارنة أسعار التأمين في السعودية">
								<meta property="og:description"
												content="قارن أسعار تأمين المركبات من أفضل شركات التأمين في المملكة العربية السعودية. احصل على أفضل عرض في دقائق.">
								<meta property="og:url" content="{{ url("/") }}">
								<meta property="og:image" content="{{ url("/images/og-image.png") }}">
								<meta property="og:image:width" content="1200">
								<meta property="og:image:height" content="630">
								<meta property="og:image:alt" content="تأمينكم - مقارنة أسعار التأمين في السعودية">
								<meta name="twitter:card" content="summary_large_image">
								<meta name="twitter:title" content="تأمينكم - مقارنة أسعار التأمين في السعودية">
								<meta name="twitter:description"
												content="قارن أسعار تأمين المركبات من أفضل شركات التأمين في المملكة العربية السعودية.">
								<meta name="twitter:image" content="{{ url("/images/og-image.png") }}">
								<link rel="canonical" href="{{ url("/") }}">
								@production
												@if (!empty($viteFonts["noto-kufi"]))
																<link rel="preload" as="font" type="font/woff2" href="/build/{{ $viteFonts["noto-kufi"] }}"
																				crossorigin>
												@endif
												@if (!empty($viteFonts["roboto"]))
																<link rel="preload" as="font" type="font/woff2" href="/build/{{ $viteFonts["roboto"] }}" crossorigin>
												@endif
								@endproduction

								{{-- Preload LCP image — first blog card image, discovered early before Vue renders --}}
								<link rel="preload" as="image"
												href="/images/blog/2026/01/كيف-تضمن-مستقبلك-المالي-من-خلال-التأمين-الادخاري؟-1024x562.png"
												fetchpriority="high">

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

								{{-- Global structured data — Organization + WebSite --}}
								<script type="application/ld+json">
								{
									"@@context": "https://schema.org",
									"@@type": "Organization",
									"name": "تأمينكم",
									"alternateName": "Tamicomz",
									"url": "{{ url('/') }}",
									"logo": "{{ url('/images/logo.png') }}",
									"sameAs": []
								}
								</script>
								<script type="application/ld+json">
								{
									"@@context": "https://schema.org",
									"@@type": "WebSite",
									"name": "تأمينكم",
									"url": "{{ url('/') }}",
									"inLanguage": "ar",
									"potentialAction": {
										"@@type": "SearchAction",
										"target": "{{ url('/blog') }}?q={search_term_string}",
										"query-input": "required name=search_term_string"
									}
								}
								</script>

								{{-- Vite handles CSS/JS injection + its own preload directives --}}
								@vite(["resources/css/app.css", "resources/js/app.js"])
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
