#!/usr/bin/env bash
set -e

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

echo "Repository: $ROOT"

git status --short

echo
read -p "Stage current safe changes? [y/N] " confirm
if [[ "$confirm" != "y" && "$confirm" != "Y" ]]; then
  echo "Aborting. Stage files manually, then rerun this script."
  exit 1
fi

git add -A -- \
  ':!insurance2026-vps.tar.gz' \
  ':!.env' \
  ':!.env.production' \
  ':!docker/secrets/*'

echo "Staged changes, excluding deployment archives, env files, and docker secrets."

read -p "Enter commit message: " commit_msg
if [[ -z "$commit_msg" ]]; then
  echo "Commit message cannot be empty."
  exit 1
fi

git commit -m "$commit_msg"

git push origin HEAD

echo "Commit pushed. Ready to deploy."

if [[ -x ./deploy/legacy/deploy-prod.sh ]]; then
  read -p "Run ./deploy/legacy/deploy-prod.sh now? [y/N] " deploy_confirm
  if [[ "$deploy_confirm" == "y" || "$deploy_confirm" == "Y" ]]; then
    ./deploy/legacy/deploy-prod.sh
  else
    echo "Deployment skipped. Run ./deploy/legacy/deploy-prod.sh when ready."
  fi
else
  echo "deploy/legacy/deploy-prod.sh not found or not executable."
  echo "Use ./deploy/legacy/deploy-local-direct.sh or ./deploy/legacy/deploy-extract-and-build.sh if appropriate."
fi
