<!DOCTYPE html>
<html lang="ar" dir="rtl">

				<head>
								<meta charset="UTF-8">
								<meta name="viewport" content="width=device-width, initial-scale=1.0">
								<meta name="theme-color" content="#0088eb">
								<title>صيانة مجدولة - تأمينكم</title>
								<style>
												* {
																margin: 0;
																padding: 0;
																box-sizing: border-box;
												}

												body {
																font-family: 'Segoe UI', Tahoma, sans-serif;
																background: linear-gradient(160deg, #f0f4f8 0%, #e2e8f0 50%, #f7fbfe 100%);
																color: #0f172a;
																min-height: 100vh;
																display: flex;
																align-items: center;
																justify-content: center;
																padding: 1rem;
												}

												.card {
																text-align: center;
																max-width: 440px;
																width: 100%;
																background: #fff;
																border-radius: 1.25rem;
																box-shadow: 0 4px 6px -1px rgb(0 0 0 / .07), 0 10px 15px -3px rgb(0 0 0 / .05);
																overflow: hidden;
												}

												.card-header {
																background: linear-gradient(135deg, #189e6b 0%, #1db97d 50%, #4dd5a1 100%);
																padding: 2rem 1.5rem 2.25rem;
																position: relative;
																overflow: hidden;
												}

												.card-header::before {
																content: '';
																position: absolute;
																top: -50%;
																right: -30%;
																width: 200%;
																height: 200%;
																background: radial-gradient(circle, rgba(255, 255, 255, .08) 0%, transparent 60%);
																pointer-events: none;
												}

												.icon-wrap {
																display: inline-flex;
																align-items: center;
																justify-content: center;
																width: 3.5rem;
																height: 3.5rem;
																background: rgba(255, 255, 255, .2);
																border-radius: .875rem;
																margin-bottom: .75rem;
																backdrop-filter: blur(8px);
												}

												.icon-wrap svg {
																width: 1.75rem;
																height: 1.75rem;
																color: #fff;
												}

												.card-header h1 {
																font-size: 1.125rem;
																font-weight: 700;
																color: #fff;
																margin: 0 0 .25rem;
												}

												.card-header .sub {
																color: rgba(255, 255, 255, .85);
																font-size: .8125rem;
												}

												.card-body {
																padding: 1.75rem 1.5rem 2rem;
												}

												.card-body p {
																font-size: .9375rem;
																color: #475569;
																line-height: 1.7;
																margin-bottom: 1.5rem;
												}

												.btn {
																display: inline-flex;
																align-items: center;
																gap: .5rem;
																padding: .75rem 2rem;
																background: linear-gradient(135deg, #189e6b, #1db97d);
																color: #fff;
																text-decoration: none;
																border: none;
																border-radius: .75rem;
																font-size: .9375rem;
																font-weight: 600;
																cursor: pointer;
																transition: all .25s ease;
																box-shadow: 0 4px 12px rgba(29, 185, 125, .25);
												}

												.btn:hover {
																transform: translateY(-1px);
																box-shadow: 0 6px 16px rgba(29, 185, 125, .35);
												}

												.btn svg {
																width: 1.125rem;
																height: 1.125rem;
												}

												.eta {
																margin-top: 1.25rem;
																display: flex;
																align-items: center;
																justify-content: center;
																gap: .375rem;
																font-size: .8125rem;
																color: #94a3b8;
												}

												.eta svg {
																width: 1rem;
																height: 1rem;
																color: #0088eb;
												}

												.progress-bar {
																margin-top: 1rem;
																width: 100%;
																height: 4px;
																background: #e2e8f0;
																border-radius: 2px;
																overflow: hidden;
												}

												.progress-bar-fill {
																height: 100%;
																width: 60%;
																background: linear-gradient(90deg, #0088eb, #33a0ef);
																border-radius: 2px;
																animation: progress-pulse 2s ease-in-out infinite;
												}

												@keyframes progress-pulse {

																0%,
																100% {
																				opacity: .7;
																}

																50% {
																				opacity: 1;
																}
												}

												.footer {
																padding: .75rem 1.5rem 1.25rem;
																border-top: 1px solid #f1f5f9;
												}

												.footer-badges {
																display: flex;
																align-items: center;
																justify-content: center;
																gap: 1rem;
																flex-wrap: wrap;
												}

												.badge {
																display: inline-flex;
																align-items: center;
																gap: .375rem;
																font-size: .6875rem;
																font-weight: 500;
																color: #94a3b8;
												}

												.badge svg {
																width: .875rem;
																height: .875rem;
																color: #1db97d;
												}

												.sub-footer {
																margin-top: 1rem;
																text-align: center;
																font-size: .75rem;
																color: #94a3b8;
												}

												.sub-footer a {
																color: #0088eb;
																font-weight: 700;
																text-decoration: none;
												}

												.sub-footer a:hover {
																text-decoration: underline;
												}
								</style>
				</head>

				<body>
								<div class="card">
												<div class="card-header">
																<div class="icon-wrap">
																				<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
																								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
																												d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
																								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
																												d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
																				</svg>
																</div>
																<h1>الموقع تحت الصيانة المجدولة</h1>
																<p class="sub">نقوم بتحديث النظام لتقديم خدمة أفضل</p>
												</div>

												<div class="card-body">
																<p>سنعود خلال دقائق قليلة. شكرًا لصبركم.</p>

																<div class="progress-bar">
																				<div class="progress-bar-fill"></div>
																</div>

																<div class="eta">
																				<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
																								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
																												d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
																				</svg>
																				نتوقع العودة خلال 5-10 دقائق
																</div>

																<br>

																<button class="btn" onclick="location.reload()">
																				<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
																								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
																												d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
																				</svg>
																				تحديث الصفحة
																</button>
												</div>

												<div class="footer">
																<div class="footer-badges">
																				<span class="badge">
																								<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
																												<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
																																d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
																								</svg>
																								محمي بـ SSL
																				</span>
																				<span class="badge">
																				</span>
																</div>
												</div>
								</div>

								<div class="sub-footer">
												هل تواجه مشكلة؟ اتصل بنا <a href="tel:920000000" dir="ltr">920000000</a>
								</div>
				</body>

</html>
