# ═══════════════════════════════════════════════════════════════
# Nginx + Brotli — Alpine-based image
# Adds ngx_brotli dynamic module to the official nginx image.
# ═══════════════════════════════════════════════════════════════
FROM nginx:1.27-alpine AS builder

RUN apk add --no-cache git gcc g++ make pcre-dev zlib-dev linux-headers cmake \
    && git clone --recurse-submodules https://github.com/google/ngx_brotli /tmp/ngx_brotli \
    && cd /tmp/ngx_brotli/deps/brotli && mkdir out && cd out \
    && cmake -DCMAKE_BUILD_TYPE=Release -DBUILD_SHARED_LIBS=OFF \
       -DCMAKE_C_FLAGS="-Ofast -flto -funroll-loops -ffunction-sections -fdata-sections -Wl,--gc-sections" \
       -DCMAKE_CXX_FLAGS="-Ofast -flto -funroll-loops -ffunction-sections -fdata-sections -Wl,--gc-sections" \
       -DCMAKE_INSTALL_PREFIX=./installed .. \
    && cmake --build . --config Release --target brotlienc \
    && NGINX_VERSION=$(nginx -v 2>&1 | sed 's/^.*\///' ) \
    && wget -qO- "https://nginx.org/download/nginx-${NGINX_VERSION}.tar.gz" | tar xz -C /tmp \
    && cd /tmp/nginx-${NGINX_VERSION} \
    && ./configure --with-compat --add-dynamic-module=/tmp/ngx_brotli \
    && make modules \
    && cp objs/ngx_http_brotli_filter_module.so objs/ngx_http_brotli_static_module.so /tmp/

# ── Final image ──────────────────────────────────────────────
FROM nginx:1.27-alpine

COPY --from=builder /tmp/ngx_http_brotli_filter_module.so /etc/nginx/modules/
COPY --from=builder /tmp/ngx_http_brotli_static_module.so /etc/nginx/modules/
