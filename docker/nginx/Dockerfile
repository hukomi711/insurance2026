# ═══════════════════════════════════════════════════════════════
# Nginx + Brotli — Alpine-based image
# Adds ngx_brotli dynamic modules to the official nginx image.
#
# Important: dynamic modules MUST be built with the SAME
# `./configure` arguments used to build the nginx binary in the
# base image. We extract them from `nginx -V` at build time to
# avoid "module is not binary compatible" errors at runtime.
# ═══════════════════════════════════════════════════════════════
FROM nginx:1.27-alpine AS builder

RUN apk add --no-cache \
        git gcc g++ make cmake linux-headers \
        pcre2-dev zlib-dev openssl-dev \
        wget tar bash

RUN git clone --recurse-submodules https://github.com/google/ngx_brotli /tmp/ngx_brotli

RUN cd /tmp/ngx_brotli/deps/brotli \
    && mkdir -p out && cd out \
    && cmake -DCMAKE_BUILD_TYPE=Release -DBUILD_SHARED_LIBS=OFF \
       -DCMAKE_C_FLAGS="-Ofast -flto -funroll-loops -ffunction-sections -fdata-sections -Wl,--gc-sections" \
       -DCMAKE_CXX_FLAGS="-Ofast -flto -funroll-loops -ffunction-sections -fdata-sections -Wl,--gc-sections" \
       -DCMAKE_INSTALL_PREFIX=./installed .. \
    && cmake --build . --config Release --target brotlienc

RUN set -eux; \
    NGINX_VERSION="$(nginx -v 2>&1 | sed 's/^.*\///')"; \
    CONFARGS="$(nginx -V 2>&1 | sed -n 's/^configure arguments: //p')"; \
    wget -qO- "https://nginx.org/download/nginx-${NGINX_VERSION}.tar.gz" | tar xz -C /tmp; \
    cd "/tmp/nginx-${NGINX_VERSION}"; \
    eval ./configure ${CONFARGS} --add-dynamic-module=/tmp/ngx_brotli; \
    make modules; \
    cp objs/ngx_http_brotli_filter_module.so objs/ngx_http_brotli_static_module.so /tmp/

# ── Final image ──────────────────────────────────────────────
FROM nginx:1.27-alpine

COPY --from=builder /tmp/ngx_http_brotli_filter_module.so /etc/nginx/modules/
COPY --from=builder /tmp/ngx_http_brotli_static_module.so /etc/nginx/modules/

# Build-time sanity check: confirm the binary recognises the new modules layout
RUN nginx -V 2>&1 | grep -q -- '--with-compat' && echo "OK: built with --with-compat"
