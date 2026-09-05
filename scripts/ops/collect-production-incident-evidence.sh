#!/usr/bin/env bash
# Read-only evidence collector for transient Nginx/PHP-FPM outages.
# Run on the production host from /opt/insurance2026.
set -u

APP_DIR="${APP_DIR:-/opt/insurance2026}"
WINDOW="${WINDOW:-2h}"
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
OUTPUT_DIR="${OUTPUT_DIR:-/tmp/insurance2026-incident-${STAMP}}"

mkdir -p "$OUTPUT_DIR"

section() {
    printf '\n===== %s =====\n' "$1"
}

capture() {
    local name="$1"
    shift
    {
        section "$name"
        "$@"
    } >"$OUTPUT_DIR/$name.txt" 2>&1 || true
}

echo "Collecting read-only production evidence"
echo "Window: $WINDOW"
echo "Output: $OUTPUT_DIR"

capture host date -u
capture host_uptime uptime
capture containers docker compose -f "$APP_DIR/docker-compose.yml" ps -a

capture app_inspect docker inspect --format \
    'name={{.Name}} status={{.State.Status}} running={{.State.Running}} started={{.State.StartedAt}} finished={{.State.FinishedAt}} exit={{.State.ExitCode}} oom={{.State.OOMKilled}} restarts={{.RestartCount}} image={{.Image}}' \
    ins2026-app

capture service_inspect docker inspect --format \
    'name={{.Name}} status={{.State.Status}} running={{.State.Running}} started={{.State.StartedAt}} finished={{.State.FinishedAt}} exit={{.State.ExitCode}} oom={{.State.OOMKilled}} restarts={{.RestartCount}} image={{.Image}}' \
    ins2026-nginx ins2026-horizon ins2026-reverb ins2026-scheduler

capture docker_events docker events --since "$WINDOW" --until now \
    --filter type=container \
    --format '{{.Time}} {{.Type}} {{.Action}} {{.Actor.Attributes.name}} image={{.Actor.Attributes.image}} exit={{.Actor.Attributes.exitCode}}'

capture app_logs docker logs --since "$WINDOW" --timestamps ins2026-app
capture nginx_logs docker logs --since "$WINDOW" --timestamps ins2026-nginx
capture horizon_logs docker logs --since "$WINDOW" --timestamps ins2026-horizon
capture reverb_logs docker logs --since "$WINDOW" --timestamps ins2026-reverb

capture app_health docker inspect --format \
    '{{range .State.Health.Log}}{{.Start}} status={{.ExitCode}} output={{printf "%q" .Output}}{{"\n"}}{{end}}' \
    ins2026-app

capture nginx_health docker inspect --format \
    '{{range .State.Health.Log}}{{.Start}} status={{.ExitCode}} output={{printf "%q" .Output}}{{"\n"}}{{end}}' \
    ins2026-nginx

capture kernel_oom journalctl --since "$WINDOW" --no-pager -k \
    -g 'out of memory|oom-killer|killed process|memory cgroup'

capture docker_service journalctl --since "$WINDOW" --no-pager \
    -u docker.service -u containerd.service

capture nginx_service journalctl --since "$WINDOW" --no-pager \
    -u nginx.service

capture php_fpm_service journalctl --since "$WINDOW" --no-pager \
    -u php-fpm.service -u php-fpm

capture nginx_error_log docker exec ins2026-nginx sh -c \
    'tail -n 500 /var/log/nginx/error.log'

capture nginx_access_502 docker exec ins2026-nginx sh -c \
    'awk '\''$9 ~ /^502$/ {print}'\'' /var/log/nginx/access.log | tail -n 500'

capture release_history git -C "$APP_DIR" log --date=iso-str \
    --pretty=format:'%h %ad %an %s' -n 30

capture compose_config docker compose -f "$APP_DIR/docker-compose.yml" config --services

printf '\nEvidence collection complete: %s\n' "$OUTPUT_DIR"
printf 'This collector does not restart services, change configuration, or modify the database.\n'
