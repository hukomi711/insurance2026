# Bundle Size Baseline

> Generated: 2026-03-28 · Vite 7 · Vue 3 · Tailwind v4

## Summary

| Category | Size    |
|----------|---------|
| JS       | 1368 KB |
| CSS      | 250 KB  |
| Fonts    | 213 KB  |
| Images   | 1437 KB |
| **Total**| **3268 KB** |

## Critical-path chunks (initial page load)

| Chunk           | Raw      | Gzip     |
|-----------------|----------|----------|
| vendor-vue      | 167.63 KB | 62.78 KB |
| vendor-ui       | 150.95 KB | 46.32 KB |
| app.js          | 87.45 KB  | 25.84 KB |
| vendor-utils    | 78.20 KB  | 29.53 KB |
| app.css (main)  | 154.55 KB | 25.65 KB |
| app.css (admin) | 15.84 KB  | 2.85 KB  |

## Largest route chunks

| Chunk            | Raw       | Gzip     |
|------------------|-----------|----------|
| DashboardHome    | 62.49 KB  | 17.90 KB |
| ComparePage      | 52.29 KB  | 15.12 KB |
| Show (blog)      | 48.34 KB  | 13.59 KB |
| PaymentModal     | 30.81 KB  | 8.40 KB  |
| CheckoutPage     | 24.35 KB  | 8.21 KB  |
| VehicleDetails   | 23.10 KB  | 7.02 KB  |

## Fonts

| Font file                          | Size     |
|------------------------------------|----------|
| fa-solid-900-subset.woff2          | 6.3 KB   |
| noto-kufi-arabic-400               | 42.9 KB  |
| noto-kufi-arabic-500               | 46.3 KB  |
| noto-kufi-arabic-700               | 42.8 KB  |
| noto-kufi-arabic-latin-400         | 10.2 KB  |
| roboto-latin-400                   | 21.4 KB  |
| roboto-latin-500                   | 21.7 KB  |
| roboto-latin-700                   | 21.7 KB  |

## CI Budget Thresholds

| Chunk        | Budget (gzip) |
|--------------|---------------|
| vendor-vue   | 70 KB         |
| vendor-ui    | 55 KB         |
| app.js       | 35 KB         |
| vendor-utils | 35 KB         |
| vendor-echo  | 25 KB         |
| app.css      | 30 KB         |

## Recent Optimizations Applied

- Saudi_Fransi SVG → WebP: **292 KB → 4.8 KB** (−98%)
- FA font subset (64/2000 icons): **154.5 KB → 6.3 KB** (−96%)
- FA CSS subset (64/2013 rules): **55.4 KB → 8.9 KB** (−84%)
- FA TTF fallback removed: **−426 KB**
- DashboardHome modal lazy-loading: **136 KB → 62 KB** (−54%)
- English locale lazy-loaded: app.js **119 KB → 87 KB** (−27%)
- Main CSS after FA subset: **199 KB → 155 KB** (−23%)
