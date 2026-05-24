#!/usr/bin/env bash
TARGET='69.57.161.222'
DOMAIN='lwxustotamin.online'
LOG='/root/tamicom-dns-watch.log'
ok=0
echo "=== DNS watch started at $(date -u) UTC ===" >> "$LOG"
for i in $(seq 1 240); do
  A1=$(dig +time=3 +tries=1 +short $DOMAIN @dns1.registrar-servers.com | tail -1)
  W1=$(dig +time=3 +tries=1 +short www.$DOMAIN @dns1.registrar-servers.com | tail -1)
  A2=$(dig +time=3 +tries=1 +short $DOMAIN @dns2.registrar-servers.com | tail -1)
  W2=$(dig +time=3 +tries=1 +short www.$DOMAIN @dns2.registrar-servers.com | tail -1)
  CF1=$(dig +time=3 +tries=1 +short $DOMAIN @1.1.1.1 | tail -1)
  CF2=$(dig +time=3 +tries=1 +short www.$DOMAIN @1.1.1.1 | tail -1)
  echo "$i $(date -u '+%H:%M:%S') dns1 @=$A1 www=$W1 | dns2 @=$A2 www=$W2 | cf @=$CF1 www=$CF2 | ok=$ok/10" >> "$LOG"
  if [ "$A1" = "$TARGET" ] && [ "$W1" = "$TARGET" ] && [ "$A2" = "$TARGET" ] && [ "$W2" = "$TARGET" ] && [ "$CF1" = "$TARGET" ] && [ "$CF2" = "$TARGET" ]; then
    ok=$((ok+1))
    if [ $ok -ge 10 ]; then
      echo "DNS-STABLE-OK $(date -u) UTC" >> "$LOG"
      exit 0
    fi
  else
    ok=0
  fi
  sleep 60
done
echo "DNS-NOT-STABLE-YET $(date -u) UTC" >> "$LOG"
