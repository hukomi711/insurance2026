#!/usr/bin/env bash
set -euo pipefail

# Bootstrap SSH access for a Forge-managed server using Forge API.
# This script does NOT store API tokens in project files.
#
# Required env vars:
#   FORGE_API_TOKEN      Forge API token
#        OR
#   FORGE_API_TOKEN_FILE Path to a file containing the Forge API token
#   FORGE_SERVER_ID      Numeric Forge server ID (e.g. 1254814)
#   FORGE_SERVER_IP      Server public IP (e.g. 168.144.13.251)
#   SSH_PUBLIC_KEY_FILE  Path to local public key file (preferred)
#        OR
#   SSH_PUBLIC_KEY       Raw public key content
#
# Optional env vars:
#   FORGE_ORG_SLUG       If set, skip auto-discovery and use this organization slug
#   FORGE_KEY_NAME       SSH key label in Forge (default: insurance2026-deploy)
#   FORGE_KEY_USERS      Comma-separated users to install key for (default: root,forge)
#   SSH_PRIVATE_KEY_FILE Path to private key for probe (default: SSH_PUBLIC_KEY_FILE without .pub)
#
# Usage:
#   export FORGE_API_TOKEN='<token>'
#   export FORGE_SERVER_ID='1254814'
#   export FORGE_SERVER_IP='168.144.13.251'
#   export SSH_PUBLIC_KEY_FILE="$HOME/.ssh/insurance2026_deploy.pub"
#   bash deploy/new-server/forge-bootstrap-ssh-access.sh

API_BASE="https://forge.laravel.com/api"
FORGE_API_TOKEN_FILE="${FORGE_API_TOKEN_FILE:-}"
FORGE_API_TOKEN="${FORGE_API_TOKEN:-}"
FORGE_SERVER_ID="${FORGE_SERVER_ID:?set FORGE_SERVER_ID}"
FORGE_SERVER_IP="${FORGE_SERVER_IP:?set FORGE_SERVER_IP}"
SSH_PUBLIC_KEY_FILE="${SSH_PUBLIC_KEY_FILE:-}"
SSH_PUBLIC_KEY="${SSH_PUBLIC_KEY:-}"
FORGE_KEY_NAME="${FORGE_KEY_NAME:-insurance2026-deploy}"
FORGE_KEY_USERS="${FORGE_KEY_USERS:-root,forge}"
SSH_PRIVATE_KEY_FILE="${SSH_PRIVATE_KEY_FILE:-}"
FORGE_ORG_SLUG="${FORGE_ORG_SLUG:-}"

sanitize_token() {
  # Normalize token formatting from clipboard pastes.
  FORGE_API_TOKEN="$(printf '%s' "$FORGE_API_TOKEN" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"

  # Accept accidental "Bearer <token>" input.
  FORGE_API_TOKEN="$(printf '%s' "$FORGE_API_TOKEN" | sed -E 's/^[Bb]earer[[:space:]]+//')"

  # Strip symmetric wrapping quotes accidentally included in exported values.
  if [[ ( "$FORGE_API_TOKEN" == \"*\" && "$FORGE_API_TOKEN" == *\" ) || ( "$FORGE_API_TOKEN" == \'.*\' ) ]]; then
    FORGE_API_TOKEN="${FORGE_API_TOKEN:1:${#FORGE_API_TOKEN}-2}"
  fi
}

validate_token_or_empty() {
  if [[ -z "$FORGE_API_TOKEN" ]]; then
    return 1
  fi

  if [[ "$FORGE_API_TOKEN" == "<TOKEN>" || "$FORGE_API_TOKEN" == "ضع_التوكن_الصحيح_هنا" ]]; then
    return 2
  fi

  # Forge API tokens are JWT-like values with 3 dot-separated segments.
  if [[ "$FORGE_API_TOKEN" != *.*.* ]]; then
    return 3
  fi

  return 0
}

if [[ -n "$FORGE_API_TOKEN_FILE" ]]; then
  if [[ ! -f "$FORGE_API_TOKEN_FILE" ]]; then
    echo "✗ FORGE_API_TOKEN_FILE not found: $FORGE_API_TOKEN_FILE"
    exit 1
  fi

  # Be tolerant to noisy token files (extra lines/commands pasted by mistake):
  # extract the first JWT-like token if present.
  RAW_TOKEN_FILE_CONTENT="$(tr -d '\r' < "$FORGE_API_TOKEN_FILE")"
  EXTRACTED_TOKEN="$(printf '%s\n' "$RAW_TOKEN_FILE_CONTENT" | grep -Eo '[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+' | head -n1 || true)"

  if [[ -n "$EXTRACTED_TOKEN" ]]; then
    FORGE_API_TOKEN="$EXTRACTED_TOKEN"
  else
    # Fallback to old behavior in case file has only a single non-newline token format.
    FORGE_API_TOKEN="$(printf '%s' "$RAW_TOKEN_FILE_CONTENT" | tr -d '\n')"
  fi
fi

sanitize_token

if [[ -t 0 ]]; then
  attempt=1
  max_attempts=5
  while true; do
    if validate_token_or_empty; then
      code=0
    else
      code=$?
    fi

    if [[ $code -eq 0 ]]; then
      break
    fi

    if [[ $attempt -gt $max_attempts ]]; then
      echo "✗ Too many invalid token attempts."
      exit 1
    fi

    case $code in
      1)
        echo "✗ Missing Forge token. Set FORGE_API_TOKEN / FORGE_API_TOKEN_FILE or paste it now."
        ;;
      2)
        echo "✗ Placeholder token detected. Paste the real Forge API token."
        ;;
      3)
        echo "✗ FORGE_API_TOKEN format looks invalid (expected 3 JWT segments separated by dots)."
        echo "  Token length seen: ${#FORGE_API_TOKEN}"
        echo "  Tip: if using FORGE_API_TOKEN_FILE, ensure the file contains only the token."
        ;;
    esac

    read -r -s -p "Enter FORGE_API_TOKEN: " FORGE_API_TOKEN
    echo
    sanitize_token
    attempt=$((attempt + 1))
  done
else
  if validate_token_or_empty; then
    code=0
  else
    code=$?
  fi
  case $code in
    0) ;;
    1)
      echo "✗ Missing Forge token. Set FORGE_API_TOKEN or FORGE_API_TOKEN_FILE."
      exit 1
      ;;
    2)
      echo "✗ FORGE_API_TOKEN is still a placeholder value, not a real token."
      echo "  Generate a token from https://forge.laravel.com/profile/api and export it again."
      exit 1
      ;;
    3)
      echo "✗ FORGE_API_TOKEN format looks invalid (expected 3 JWT segments separated by dots)."
      echo "  Token length seen: ${#FORGE_API_TOKEN}"
      echo "  Tip: if using FORGE_API_TOKEN_FILE, ensure the file contains only the token."
      exit 1
      ;;
  esac
fi

if [[ -z "$SSH_PUBLIC_KEY_FILE" && -z "$SSH_PUBLIC_KEY" ]]; then
  echo "✗ Set SSH_PUBLIC_KEY_FILE or SSH_PUBLIC_KEY"
  exit 1
fi

if [[ ! "$FORGE_SERVER_ID" =~ ^[0-9]+$ ]]; then
  echo "✗ FORGE_SERVER_ID must be numeric"
  exit 1
fi

if [[ -n "$SSH_PUBLIC_KEY_FILE" ]]; then
  if [[ ! -f "$SSH_PUBLIC_KEY_FILE" ]]; then
    echo "✗ Public key file not found: $SSH_PUBLIC_KEY_FILE"
    exit 1
  fi
  PUB_KEY_CONTENT="$(tr -d '\r' < "$SSH_PUBLIC_KEY_FILE")"
  if [[ -z "$SSH_PRIVATE_KEY_FILE" ]]; then
    SSH_PRIVATE_KEY_FILE="${SSH_PUBLIC_KEY_FILE%.pub}"
  fi
else
  PUB_KEY_CONTENT="$SSH_PUBLIC_KEY"
fi

if [[ -z "$PUB_KEY_CONTENT" ]]; then
  echo "✗ Public key content is empty"
  exit 1
fi

# Validate that the SSH public key we will install matches the private key used
# for probe/deploy when both sides are available locally.
if [[ -n "$SSH_PRIVATE_KEY_FILE" && -f "$SSH_PRIVATE_KEY_FILE" && -f "${SSH_PRIVATE_KEY_FILE}.pub" ]]; then
  PRIVATE_PUB_CONTENT="$(tr -d '\r' < "${SSH_PRIVATE_KEY_FILE}.pub")"
  if [[ "$PRIVATE_PUB_CONTENT" != "$PUB_KEY_CONTENT" ]]; then
    tmp_provided="$(mktemp)"
    tmp_private_pub="$(mktemp)"
    printf '%s\n' "$PUB_KEY_CONTENT" > "$tmp_provided"
    printf '%s\n' "$PRIVATE_PUB_CONTENT" > "$tmp_private_pub"
    provided_fp="$(ssh-keygen -lf "$tmp_provided" | awk '{print $2}')"
    private_fp="$(ssh-keygen -lf "$tmp_private_pub" | awk '{print $2}')"
    rm -f "$tmp_provided" "$tmp_private_pub"

    echo "✗ SSH key mismatch detected."
    echo "  Installed public key fingerprint: $provided_fp"
    echo "  Probe private key fingerprint:    $private_fp"
    echo ""
    echo "Use one of these fixes:"
    echo "  1) Set SSH_PUBLIC_KEY_FILE to '${SSH_PRIVATE_KEY_FILE}.pub'"
    echo "  2) Set SSH_PRIVATE_KEY_FILE to the private key matching the provided public key"
    exit 1
  fi
fi

api_get() {
  local path="$1"
  local body status tmp
  tmp="$(mktemp)"
  status="$(curl -g -sS -o "$tmp" -w '%{http_code}' \
    -H "Authorization: Bearer $FORGE_API_TOKEN" \
    -H "Accept: application/json" \
    "$API_BASE$path")"
  body="$(cat "$tmp")"
  rm -f "$tmp"
  if [[ "$status" -ge 400 ]]; then
    echo "✗ Forge API GET failed: $path (HTTP $status)" >&2
    echo "$body" >&2
    if [[ "$status" -eq 401 ]]; then
      echo "Hint: token is invalid/expired/revoked, copied with extra characters, or for a different Forge account." >&2
      echo "Hint: verify token at https://forge.laravel.com/profile/api and re-export it in this shell." >&2
    fi
    return 1
  fi
  printf '%s' "$body"
}

api_post() {
  local path="$1"
  local payload="$2"
  local body status tmp
  tmp="$(mktemp)"
  status="$(curl -g -sS -o "$tmp" -w '%{http_code}' \
    -X POST \
    -H "Authorization: Bearer $FORGE_API_TOKEN" \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -d "$payload" \
    "$API_BASE$path")"
  body="$(cat "$tmp")"
  rm -f "$tmp"
  if [[ "$status" -ge 400 ]]; then
    echo "✗ Forge API POST failed: $path (HTTP $status)" >&2
    echo "$body" >&2
    if [[ "$status" -eq 401 ]]; then
      echo "Hint: token is invalid/expired/revoked, copied with extra characters, or for a different Forge account." >&2
      echo "Hint: verify token at https://forge.laravel.com/profile/api and re-export it in this shell." >&2
    fi
    return 1
  fi
  printf '%s' "$body"
}

echo "[1/5] Validating Forge token (/me)..."
ME_JSON="$(api_get '/me')"
ME_EMAIL="$(printf '%s' "$ME_JSON" | jq -r '.data.attributes.email // "unknown"')"
echo "  ✓ Authenticated as: $ME_EMAIL"

if [[ -z "$FORGE_ORG_SLUG" ]]; then
  echo "[2/5] Discovering organization slug containing server ${FORGE_SERVER_ID}..."
  ORGS_JSON="$(api_get '/orgs')"
  ORG_COUNT="$(printf '%s' "$ORGS_JSON" | jq '.data | length')"

  if [[ "$ORG_COUNT" -eq 0 ]]; then
    echo "✗ No organizations found for this token"
    exit 1
  fi

  FOUND_ORG=""
  while IFS= read -r slug; do
    SERVERS_JSON="$(api_get "/orgs/$slug/servers?page%5Bsize%5D=100")" || continue
    if printf '%s' "$SERVERS_JSON" | jq -e --argjson id "$FORGE_SERVER_ID" '.data[] | select((.attributes.id // -1) == $id or ((.id | tonumber? // -1) == $id))' >/dev/null; then
      FOUND_ORG="$slug"
      break
    fi
  done < <(printf '%s' "$ORGS_JSON" | jq -r '.data[].attributes.slug // empty')

  if [[ -z "$FOUND_ORG" ]]; then
    echo "✗ Server ID ${FORGE_SERVER_ID} not found in token organizations"
    echo "  Tip: set FORGE_ORG_SLUG manually if needed."
    exit 1
  fi

  FORGE_ORG_SLUG="$FOUND_ORG"
  echo "  ✓ Found organization: $FORGE_ORG_SLUG"
else
  echo "[2/5] Using provided organization slug: $FORGE_ORG_SLUG"
fi

echo "[3/5] Ensuring SSH key exists on server users: $FORGE_KEY_USERS"
IFS=',' read -r -a USERS <<< "$FORGE_KEY_USERS"
for target_user in "${USERS[@]}"; do
  target_user="$(printf '%s' "$target_user" | xargs)"
  [[ -n "$target_user" ]] || continue

  KEYS_JSON="$(api_get "/orgs/$FORGE_ORG_SLUG/servers/$FORGE_SERVER_ID/ssh-keys")"
  KEY_EXISTS="$(printf '%s' "$KEYS_JSON" | jq -r --arg name "$FORGE_KEY_NAME" --arg usr "$target_user" '.data[] | select((.attributes.name // "") == $name and (.attributes.user // "") == $usr) | .id' | head -n1)"

  if [[ -n "$KEY_EXISTS" && "$KEY_EXISTS" != "null" ]]; then
    echo "  ✓ Key label already exists (id: $KEY_EXISTS, user: $target_user)"
    continue
  fi

  echo "[4/5] Adding SSH key for user '$target_user'..."
  PAYLOAD="$(jq -nc --arg name "$FORGE_KEY_NAME" --arg key "$PUB_KEY_CONTENT" --arg user "$target_user" '{name:$name, key:$key, user:$user}')"
  api_post "/orgs/$FORGE_ORG_SLUG/servers/$FORGE_SERVER_ID/ssh-keys" "$PAYLOAD" >/dev/null
  echo "  ✓ Key creation request accepted for user '$target_user'"
done

echo "[5/5] SSH probe using installed key..."
if [[ -n "$SSH_PRIVATE_KEY_FILE" && -f "$SSH_PRIVATE_KEY_FILE" ]]; then
  probe_ok=0
  for probe_user in "${USERS[@]}"; do
    probe_user="$(printf '%s' "$probe_user" | xargs)"
    [[ -n "$probe_user" ]] || continue
    if ssh -o BatchMode=yes -o ConnectTimeout=10 -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new -i "$SSH_PRIVATE_KEY_FILE" "${probe_user}@${FORGE_SERVER_IP}" 'echo SSH_OK && whoami' >/tmp/forge_ssh_probe.out 2>&1; then
      cat /tmp/forge_ssh_probe.out
      probe_ok=1
      break
    fi
  done

  if [[ "$probe_ok" -eq 1 ]]; then
    echo ""
    echo "Bootstrap complete. You can now run deploy/new-server/deploy.sh."
  else
    cat /tmp/forge_ssh_probe.out
    echo ""
    echo "✗ SSH probe still failed for users: $FORGE_KEY_USERS"
    exit 1
  fi
else
  echo "  ℹ SSH private key file not found; skipped probe."
  echo "  Set SSH_PRIVATE_KEY_FILE to enable automatic SSH validation."
  echo ""
  echo "Bootstrap requests completed."
fi
