<template>
    <Teleport to="body">
        <Transition enter-active-class="transition-opacity duration-300 ease-out"
            enter-from-class="opacity-0" enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-500 ease-in"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="visible"
                class="fixed inset-0 z-9999 flex flex-col items-center justify-center bg-white/95 backdrop-blur-sm"
                role="status" aria-busy="true" :aria-label="label">

                <!-- Animated Logo SVG -->
                <div class="app-loader-logo" :style="{ width: logoSize + 'px', height: logoSize + 'px' }">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 97.803 96.015"
                        class="w-full h-full">
                        <g transform="translate(-981.705 -922.993)">
                            <!-- Outer circle -->
                            <g transform="translate(983.205 1017.507) rotate(-90)">
                                <g transform="translate(0)" fill="#0088EB" stroke-linecap="square">
                                    <path
                                        d="M 46.507 95.553 C 40.126 95.553 33.935 94.279 28.107 91.767 C 22.479 89.341 17.425 85.868 13.086 81.445 C 8.747 77.023 5.341 71.873 2.962 66.14 C 0.499 60.204 -0.75 53.9 -0.75 47.401 C -0.75 40.903 0.499 34.599 2.962 28.663 C 5.341 22.93 8.748 17.78 13.086 13.358 C 17.426 8.935 22.48 5.462 28.108 3.036 C 33.936 0.524 40.126 -0.75 46.507 -0.75 C 52.888 -0.75 59.078 0.524 64.906 3.036 C 70.534 5.462 75.588 8.935 79.928 13.358 C 84.266 17.78 87.673 22.93 90.052 28.663 C 92.516 34.599 93.765 40.903 93.765 47.401 C 93.765 53.9 92.516 60.204 90.053 66.14 C 87.674 71.873 84.268 77.023 79.929 81.445 C 75.59 85.868 70.536 89.341 64.908 91.767 C 59.08 94.279 52.889 95.553 46.507 95.553 Z"
                                        stroke="none" class="loader-path-0" />
                                    <path
                                        d="M 46.507 0 C 20.822 0 0 21.222 0 47.401 C 0 73.58 20.82 94.803 46.507 94.803 C 72.195 94.803 93.015 73.58 93.015 47.401 C 93.015 21.222 72.19 0 46.507 0 M 46.507 -1.5 C 52.991 -1.5 59.281 -0.205 65.203 2.348 C 70.921 4.812 76.055 8.34 80.463 12.833 C 84.869 17.324 88.329 22.553 90.745 28.376 C 93.247 34.403 94.515 40.804 94.515 47.401 C 94.515 53.999 93.247 60.4 90.746 66.427 C 88.33 72.25 84.871 77.479 80.465 81.97 C 76.057 86.463 70.923 89.991 65.205 92.455 C 59.283 95.008 52.992 96.303 46.507 96.303 C 40.023 96.303 33.732 95.008 27.81 92.455 C 22.092 89.991 16.958 86.463 12.55 81.97 C 8.144 77.479 4.685 72.25 2.269 66.427 C -0.232 60.4 -1.5 53.999 -1.5 47.401 C -1.5 40.804 -0.232 34.403 2.269 28.376 C 4.686 22.553 8.145 17.324 12.551 12.833 C 16.959 8.34 22.093 4.812 27.811 2.348 C 33.733 -0.205 40.024 -1.5 46.507 -1.5 Z"
                                        stroke="none" fill="#fff" class="loader-path-1" />
                                </g>
                            </g>
                            <!-- Inner circle -->
                            <g transform="translate(988.342 1012.467) rotate(-90)">
                                <path
                                    d="M41.467,0c22.9,0,41.467,18.922,41.467,42.264S64.371,84.529,41.467,84.529,0,65.606,0,42.264,18.565,0,41.467,0Z"
                                    fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2"
                                    class="loader-path-2" />
                            </g>
                            <!-- Logo icon -->
                            <g transform="translate(1007.859 940.665)">
                                <g transform="translate(0.01 26.873)">
                                    <path
                                        d="M391.607,416.848l-19.2,14.208a3.223,3.223,0,0,1-3.858,0l-19.193-14.208a1.239,1.239,0,0,0-1.97,1.006v15.569a5.86,5.86,0,0,0,2.452,4.766l18.38,13.131a3.913,3.913,0,0,0,4.533,0l18.376-13.134a5.9,5.9,0,0,0,2.453-4.766V417.84a1.234,1.234,0,0,0-1.97-.991Zm-3.073,14.944a3.784,3.784,0,0,1-1.516,3.2L372.953,445.75a4.113,4.113,0,0,1-4.946,0l-14.439-10.94a1.892,1.892,0,0,1-.413-.345,3.5,3.5,0,0,1-.95-1.953,2.735,2.735,0,0,1-.069-.565l.041-4.478a.993.993,0,0,1,1.571-.813l14.866,10.664a3.186,3.186,0,0,0,3.719,0L387.1,426.711a.9.9,0,0,1,1.433.73Z"
                                        transform="translate(-347.383 -416.604)" fill="none" stroke="#fff"
                                        stroke-linecap="round" stroke-width="2" class="loader-path-3" />
                                </g>
                                <path
                                    d="M348.71,416.968h2.2a1.285,1.285,0,0,0,1.285-1.288l-.02-7.006a2.871,2.871,0,0,1,2.852-2.877l6.929-.041a8.951,8.951,0,0,0,4.557-1.278l3.191-1.94a1.362,1.362,0,0,1,1.533-.014l3.53,2.218a7.245,7.245,0,0,0,3.97,1l6.872-.051c1.6,0,2.977.87,2.977,2.551l.027,5.779a2.337,2.337,0,0,1-.983,1.97L371.305,428.01a2.621,2.621,0,0,0-1.081,2.12v3.516a.919.919,0,0,0,1.447.752l20.886-15.256a2.28,2.28,0,0,0,1.03-2.138s-.01-9.349-.01-9.6v-2.841a3.354,3.354,0,0,0-3.355-3.355H379.129a5.091,5.091,0,0,1-2.715-.781l-4.7-2.965a2.325,2.325,0,0,0-2.477,0l-4.435,2.8a6.159,6.159,0,0,1-3.289.95H351.027a3.653,3.653,0,0,0-3.652,3.653v10.775a1.334,1.334,0,0,0,1.335,1.327Z"
                                    transform="translate(-347.375 -397.099)" fill="none" stroke="#fff"
                                    stroke-linecap="round" stroke-width="2" class="loader-path-4" />
                            </g>
                        </g>
                    </svg>
                </div>

                <!-- Optional loading text -->
                <p v-if="text" class="mt-6 text-base font-medium text-muted animate-pulse">
                    {{ text }}
                </p>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
defineProps( {
    visible: { type: Boolean, default: true },
    text: { type: String, default: '' },
    label: { type: String, default: 'جاري التحميل' },
    logoSize: { type: Number, default: 120 },
} );
</script>

<style scoped>
/* ── Stroke‑draw animation (vivus‑instant style) ── */

/* Promote all animated paths to their own compositor layer
   to avoid forced reflows during animation. */
.loader-path-0,
.loader-path-1,
.loader-path-2,
.loader-path-3,
.loader-path-4 {
    will-change: stroke-dashoffset, opacity;
}

.loader-path-0 {
    stroke-dasharray: 300 302;
    stroke-dashoffset: 301;
    animation: draw-0 2400ms ease-in-out infinite, fade-opacity 2400ms linear infinite;
}

.loader-path-1 {
    stroke-dasharray: 600 602;
    stroke-dashoffset: 601;
    animation: draw-1 2400ms ease-in-out infinite, fade-opacity 2400ms linear infinite;
}

.loader-path-2 {
    stroke-dasharray: 264 266;
    stroke-dashoffset: 265;
    animation: draw-2 2400ms ease-in-out infinite, fade-opacity 2400ms linear infinite;
}

.loader-path-3 {
    stroke-dasharray: 252 254;
    stroke-dashoffset: 253;
    animation: draw-3 2400ms ease-in-out infinite, fade-opacity 2400ms linear infinite;
}

.loader-path-4 {
    stroke-dasharray: 195 197;
    stroke-dashoffset: 196;
    animation: draw-4 2400ms ease-in-out infinite, fade-opacity 2400ms linear infinite;
}

/* Outer fill path */
@keyframes draw-0 {
    0%,
    10% {
        stroke-dashoffset: 301;
    }

    40% {
        stroke-dashoffset: 0;
    }

    100% {
        stroke-dashoffset: 0;
    }
}

/* Outer circle stroke */
@keyframes draw-1 {
    0%,
    14% {
        stroke-dashoffset: 601;
    }

    44% {
        stroke-dashoffset: 0;
    }

    100% {
        stroke-dashoffset: 0;
    }
}

/* Inner circle */
@keyframes draw-2 {
    0%,
    18% {
        stroke-dashoffset: 265;
    }

    48% {
        stroke-dashoffset: 0;
    }

    100% {
        stroke-dashoffset: 0;
    }
}

/* Shield / house bottom */
@keyframes draw-3 {
    0%,
    22% {
        stroke-dashoffset: 253;
    }

    52% {
        stroke-dashoffset: 0;
    }

    100% {
        stroke-dashoffset: 0;
    }
}

/* Shield / house top */
@keyframes draw-4 {
    0%,
    26% {
        stroke-dashoffset: 196;
    }

    56% {
        stroke-dashoffset: 0;
    }

    100% {
        stroke-dashoffset: 0;
    }
}

/* Fade out at end of cycle before repeating
   Uses 'opacity' instead of 'stroke-opacity' for GPU compositing. */
@keyframes fade-opacity {
    0% {
        opacity: 1;
    }

    85% {
        opacity: 1;
    }

    95% {
        opacity: 0;
    }

    100% {
        opacity: 0;
    }
}
</style>
