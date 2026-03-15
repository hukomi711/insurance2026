<template>
    <div class="stc-public flex min-h-[100vh] w-full flex-col" dir="rtl">
        <!-- Header (matches mystc) -->
        <header class="stc-main-header">
            <div class="flex items-center gap-2 lg:gap-4">
                <a href="/" class="h-10 lg:h-14" title="logo">
                    <img src="/images/logo/STC-01.svg" alt="stc logo" class="h-full w-auto" />
                </a>
            </div>
            <div class="flex flex-1 items-center justify-end gap-3">
                <button type="button" class="stc-lang-btn">
                    <span>English</span>
                </button>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="stc-main-content flex flex-1">
            <div class="flex min-w-full flex-1 flex-col">
                <div class="flex h-full grow items-center">
                    <div class="mx-auto w-full max-w-7xl px-4">
                        <div class="flex w-full justify-center">
                            <div class="w-full p-6 md:w-2/3 lg:w-1/2 lg:p-8">
                                <slot />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer (matches mystc) -->
                <footer class="stc-main-footer">
                    <div class="flex flex-wrap items-center justify-center gap-4 lg:justify-between">
                        <div class="flex items-center justify-center gap-4 lg:justify-between">
                            <a href="https://www.stc.com.sa/content/stc/sa/ar/personal/home.html"
                                target="_blank" rel="noreferrer">صفحة stc الرئيسية</a>
                            <a href="https://www.stc.com.sa/content/stc/sa/ar/content-page/privacy.html"
                                target="_blank" rel="noreferrer">إشعار الخصوصية</a>
                        </div>
                        <div class="stc-copyright text-sm">الحقوق محفوظة © 2026 stc</div>
                        <div class="stc-social flex flex-wrap items-center gap-2.5">
                            <!-- Facebook -->
                            <a href="https://www.facebook.com/stc.ksa" target="_blank" rel="noopener noreferrer"
                                aria-label="Facebook">
                                <svg class="stc-social-icon" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" />
                                </svg>
                            </a>
                            <!-- Snapchat -->
                            <a href="https://www.snapchat.com/add/stcnow" target="_blank" rel="noopener noreferrer"
                                aria-label="Snapchat">
                                <svg class="stc-social-icon" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M12.206.793c.99 0 4.347.276 5.93 3.821.529 1.193.403 3.219.299 4.847l-.003.06c-.012.18-.022.345-.03.51.075.045.203.09.401.09.3-.016.659-.12.922-.214.12-.042.24-.082.36-.104a.927.927 0 01.201-.022c.197 0 .36.045.495.135.18.12.27.315.27.51 0 .405-.45.63-.636.705a5.67 5.67 0 01-.304.126c-.36.143-.976.386-1.125.899-.03.105-.044.21-.03.315.015.24.18.48.327.69.57.81 1.395 1.573 1.5 1.664l.004.003c.15.135.39.39.39.735a.755.755 0 01-.12.405c-.195.3-.51.465-.72.545-.15.06-.42.15-.793.21-.09.016-.195.03-.315.06-.075.015-.165.045-.27.09-.195.075-.345.24-.525.45-.24.285-.54.645-1.125.81a2.418 2.418 0 01-.66.09c-.36 0-.725-.09-1.066-.18-.405-.105-.78-.203-1.185-.203-.09 0-.165 0-.24.015-.405.015-.765.113-1.11.21-.345.09-.705.18-1.065.18-.255 0-.48-.03-.66-.09-.585-.165-.885-.525-1.125-.81-.18-.21-.33-.375-.525-.45-.105-.045-.195-.075-.27-.09-.12-.03-.224-.044-.314-.06-.375-.06-.646-.15-.794-.21-.21-.08-.525-.245-.72-.545a.755.755 0 01-.12-.405c0-.345.24-.6.39-.735l.004-.003c.105-.09.93-.854 1.5-1.665.15-.21.315-.45.33-.69a.573.573 0 00-.03-.315c-.15-.514-.766-.756-1.126-.9a5.426 5.426 0 01-.303-.125c-.186-.09-.636-.345-.636-.705 0-.195.09-.39.27-.51.135-.09.3-.135.495-.135.06 0 .135.008.201.022.12.022.24.062.36.104.263.094.622.198.922.214.198 0 .326-.045.401-.09a43.333 43.333 0 01-.032-.57c-.104-1.628-.23-3.654.3-4.846C7.848 1.069 11.215.793 12.206.793z" />
                                </svg>
                            </a>
                            <!-- Instagram -->
                            <a href="https://www.instagram.com/stc_ksa" target="_blank" rel="noopener noreferrer"
                                aria-label="Instagram">
                                <svg class="stc-social-icon" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z" />
                                </svg>
                            </a>
                            <!-- X (Twitter) -->
                            <a href="https://twitter.com/stc_ksa" target="_blank" rel="noopener noreferrer"
                                aria-label="X">
                                <svg class="stc-social-icon" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                                </svg>
                            </a>
                            <!-- YouTube -->
                            <a href="https://www.youtube.com/user/stcsaudiarabia" target="_blank"
                                rel="noopener noreferrer" aria-label="YouTube">
                                <svg class="stc-social-icon" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />
                                </svg>
                            </a>
                            <!-- LinkedIn -->
                            <a href="https://www.linkedin.com/company/stc" target="_blank" rel="noopener noreferrer"
                                aria-label="LinkedIn">
                                <svg class="stc-social-icon" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                                </svg>
                            </a>
                            <!-- Telegram -->
                            <a href="https://t.me/stc_ksa" target="_blank" rel="noopener noreferrer"
                                aria-label="Telegram">
                                <svg class="stc-social-icon" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M11.944 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0012 0h-.056zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 01.171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.479.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </footer>
            </div>
        </main>
    </div>
</template>

<script setup>
// No props needed — uses slots for page content
</script>

<!-- Scoped styles for StcLayout's own elements (header, footer) -->
<style scoped>
.stc-main-header {
    position: fixed;
    top: 0;
    z-index: 999;
    display: flex;
    height: 70px;
    width: 100%;
    align-items: center;
    justify-content: space-between;
    background: white;
    padding: 0 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

@media (min-width: 1024px) {
    .stc-main-header {
        position: sticky;
    }
}

.stc-main-content {
    margin-top: 70px;
}

@media (min-width: 1024px) {
    .stc-main-content {
        margin-top: 0;
    }
}

.stc-lang-btn {
    background: transparent;
    border: none;
    cursor: pointer;
    font-weight: 500;
    font-size: 0.875rem;
    color: #4F008C;
    padding: 6px 8px;
    border-radius: 4px;
    transition: background-color 250ms ease;
}

.stc-lang-btn:hover {
    background-color: rgba(79, 0, 140, 0.04);
}

.stc-main-footer {
    z-index: 30;
    height: auto;
    border-top: 1px solid #dde0e2;
    background: white;
    padding: 20px;
}

@media (min-width: 1024px) {
    .stc-main-footer {
        height: 80px;
    }
}

.stc-main-footer a {
    color: inherit;
    text-decoration: none;
    transition: opacity 0.2s;
}

.stc-main-footer a:hover {
    opacity: 0.7;
}

.stc-copyright {
    color: #1D252D;
}

.stc-social-icon {
    width: 20px;
    height: 20px;
    color: #1D252D;
    transition: opacity 0.2s;
}

.stc-social-icon:hover {
    opacity: 0.7;
}
</style>

<!-- Global styles for slot content (namespaced under .stc-public) -->
<style>
.stc-public .stc-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: transparent;
    border: none;
    cursor: pointer;
    font-weight: 500;
    font-size: 0.875rem;
    line-height: 1.75;
    color: #FF375E;
    padding: 6px 8px;
    border-radius: 4px;
    transition: background-color 250ms ease;
}

.stc-public .stc-back-btn:hover {
    background-color: rgba(255, 55, 94, 0.04);
}

.stc-public .stc-btn-contained {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-width: 64px;
    padding: 6px 16px;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    font-weight: 500;
    font-size: 0.875rem;
    line-height: 1.75;
    letter-spacing: 0.02857em;
    text-transform: uppercase;
    color: #fff;
    background-color: #FF375E;
    box-shadow: 0 3px 1px -2px rgba(0,0,0,0.2), 0 2px 2px 0 rgba(0,0,0,0.14), 0 1px 5px 0 rgba(0,0,0,0.12);
    transition: background-color 250ms ease, box-shadow 250ms ease;
}

.stc-public .stc-btn-contained:hover {
    background-color: #E02E50;
    box-shadow: 0 2px 4px -1px rgba(0,0,0,0.2), 0 4px 5px 0 rgba(0,0,0,0.14), 0 1px 10px 0 rgba(0,0,0,0.12);
}

.stc-public .stc-btn-contained:active {
    box-shadow: 0 5px 5px -3px rgba(0,0,0,0.2), 0 8px 10px 1px rgba(0,0,0,0.14), 0 3px 14px 2px rgba(0,0,0,0.12);
}

.stc-public .stc-btn-contained.stc-btn-disabled,
.stc-public .stc-btn-contained:disabled {
    color: rgba(0, 0, 0, 0.26);
    background-color: rgba(0, 0, 0, 0.12);
    box-shadow: none;
    cursor: default;
    pointer-events: none;
}

.stc-public .stc-btn-outlined {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-width: 64px;
    padding: 5px 15px;
    border-radius: 4px;
    border: 1px solid #FF375E;
    cursor: pointer;
    font-weight: 500;
    font-size: 0.875rem;
    line-height: 1.75;
    letter-spacing: 0.02857em;
    text-transform: uppercase;
    color: #FF375E;
    background-color: transparent;
    transition: background-color 250ms ease, border-color 250ms ease;
}

.stc-public .stc-btn-outlined:hover {
    background-color: rgba(255, 55, 94, 0.04);
}

.stc-public .stc-text-primary {
    color: #4F008C;
}

.stc-public .stc-text-dark {
    color: #1D252D;
}

.stc-public .stc-loader {
    display: flex;
    gap: 6px;
    align-items: center;
    justify-content: center;
}

.stc-public .stc-loader__dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #4F008C;
    animation: stcDotPulse 1.4s ease-in-out infinite;
}

.stc-public .stc-loader__dot:nth-child(2) { animation-delay: 0.2s; }
.stc-public .stc-loader__dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes stcDotPulse {
    0%, 80%, 100% { opacity: 0.3; transform: scale(0.8); }
    40% { opacity: 1; transform: scale(1.1); }
}

/* Fade transitions for Vue <Transition name="stc-fade"> */
.stc-fade-enter-active,
.stc-fade-leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.stc-fade-enter-from,
.stc-fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
