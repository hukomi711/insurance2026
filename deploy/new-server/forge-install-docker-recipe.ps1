param(
    [string]$ForgeServerId = '1254814',
    [string]$ForgeOrgSlug = 'mohammad-bon',
    [string]$ForgeApiToken = $env:FORGE_API_TOKEN,
    [ValidateSet('InstallDocker', 'FreePorts')]
    [string]$Action = 'InstallDocker',
    [switch]$KeepRecipe
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function ConvertTo-NormalizedToken {
    param([string]$Token)

    $value = ''
    if ($null -ne $Token) {
        $value = [string]$Token
    }
    $value = $value.Trim()
    $value = $value -replace '^[Bb]earer\s+', ''

    if (($value.StartsWith('"') -and $value.EndsWith('"')) -or ($value.StartsWith("'") -and $value.EndsWith("'"))) {
        if ($value.Length -ge 2) {
            $value = $value.Substring(1, $value.Length - 2)
        }
    }

    return $value.Trim()
}

function Read-Token {
    $secure = Read-Host 'Enter FORGE_API_TOKEN' -AsSecureString
    $bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
    try {
        return [Runtime.InteropServices.Marshal]::PtrToStringBSTR($bstr)
    }
    finally {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
    }
}

function Invoke-ForgeApi {
    param(
        [ValidateSet('GET', 'POST', 'DELETE')]
        [string]$Method,
        [string]$Path,
        [object]$Payload,
        [string]$Token
    )

    $headers = @{
        Authorization = "Bearer $Token"
        Accept        = 'application/json'
    }

    $uri = "https://forge.laravel.com/api$Path"

    try {
        if ($Method -eq 'GET') {
            return Invoke-RestMethod -Method Get -Uri $uri -Headers $headers
        }

        if ($Method -eq 'DELETE') {
            return Invoke-RestMethod -Method Delete -Uri $uri -Headers $headers
        }

        $json = $null
        if ($null -ne $Payload) {
            $json = $Payload | ConvertTo-Json -Depth 12 -Compress
        }
        return Invoke-RestMethod -Method Post -Uri $uri -Headers $headers -Body $json -ContentType 'application/json'
    }
    catch {
        $status = $null
        if ($_.Exception.Response -and $_.Exception.Response.StatusCode) {
            $status = [int]$_.Exception.Response.StatusCode
        }

        $details = $_.ErrorDetails.Message
        if ([string]::IsNullOrWhiteSpace($details)) {
            $details = $_.Exception.Message
        }

        if ($status) {
            throw "Forge API $Method failed for $Path (HTTP $status). $details"
        }

        throw "Forge API $Method failed for $Path. $details"
    }
}

if ($ForgeServerId -notmatch '^\d+$') {
    throw 'ForgeServerId must be numeric.'
}

$token = ConvertTo-NormalizedToken -Token $ForgeApiToken
if ([string]::IsNullOrWhiteSpace($token)) {
    $token = ConvertTo-NormalizedToken -Token (Read-Token)
}

if ($token -notmatch '^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$') {
    throw 'Token format is invalid. Expected Forge token in JWT-like format.'
}

Write-Host '[1/5] Validating token and org access...'
$me = Invoke-ForgeApi -Method GET -Path '/me' -Token $token
$email = $me.data.attributes.email
if ([string]::IsNullOrWhiteSpace($email)) { $email = 'unknown' }
Write-Host "  OK token owner: $email"

$servers = Invoke-ForgeApi -Method GET -Path "/orgs/$ForgeOrgSlug/servers?page%5Bsize%5D=100" -Token $token
$target = $servers.data | Where-Object {
    $sid = $null
    if ($_.attributes -and $_.attributes.id) {
        $sid = [string]$_.attributes.id
    }
    elseif ($_.id) {
        $sid = [string]$_.id
    }
    $sid -eq [string]$ForgeServerId
} | Select-Object -First 1

if (-not $target) {
    $visible = ($servers.data | ForEach-Object {
            $sid = if ($_.attributes -and $_.attributes.id) { [string]$_.attributes.id } else { [string]$_.id }
            $name = if ($_.attributes -and $_.attributes.name) { [string]$_.attributes.name } else { '' }
            $ip = if ($_.attributes -and $_.attributes.ip_address) { [string]$_.attributes.ip_address } else { '' }
            "${sid}:${name}:${ip}"
        }) -join '; '
    throw "Server $ForgeServerId not found in org $ForgeOrgSlug. Visible servers: $visible"
}

$script = ''
$recipeNamePrefix = ''
$successMessage = ''

if ($Action -eq 'FreePorts') {
    $script = @'
set -euo pipefail

echo "--- stopping host web services ---"
for svc in nginx apache2 httpd caddy; do
    if systemctl list-unit-files 2>/dev/null | grep -q "^${svc}\.service"; then
        systemctl stop "$svc" || true
        systemctl disable "$svc" || true
        systemctl mask "$svc" || true
    fi
    if systemctl list-unit-files 2>/dev/null | grep -q "^${svc}\.socket"; then
        systemctl stop "${svc}.socket" || true
        systemctl disable "${svc}.socket" || true
        systemctl mask "${svc}.socket" || true
    fi
done

listener_pids() {
    ss -ltnp 2>/dev/null \
    | grep -E ':(80|443)([[:space:]]|$)' \
        | grep -oE 'pid=[0-9]+' \
        | cut -d= -f2 \
        | sort -u
}

echo "--- listeners after service stop ---"
ss -ltnp | grep -E ':(80|443)([[:space:]]|$)' || true

# Force terminate remaining listeners on :80/:443.
PIDS="$(listener_pids || true)"
if [ -n "$PIDS" ]; then
    echo "--- terminating remaining listener pids ---"
    echo "$PIDS" | xargs -r kill -TERM || true
    sleep 2
fi

PIDS="$(listener_pids || true)"
if [ -n "$PIDS" ]; then
    echo "--- force killing stubborn listener pids ---"
    echo "$PIDS" | xargs -r kill -KILL || true
    systemctl kill --signal=KILL nginx 2>/dev/null || true
    pkill -9 nginx 2>/dev/null || true
    killall -9 nginx 2>/dev/null || true
    sleep 1
fi

echo "--- listeners after forced cleanup ---"
ss -ltnp | grep -E ':(80|443)([[:space:]]|$)' || true

if ss -ltnp | grep -E ':(80|443)([[:space:]]|$)' >/dev/null; then
    echo "Ports 80/443 are still in use after stopping known web services." >&2
    exit 1
fi

echo "Ports 80/443 are free."
'@

    $recipeNamePrefix = 'ins2026-free-web-ports'
    $successMessage = 'Host web ports 80/443 are free for Docker nginx.'
}
else {
    $script = @'
set -euo pipefail

if command -v dnf >/dev/null 2>&1; then
  dnf -y install git curl tar gzip bind-utils openssl dnf-plugins-core
  if ! command -v docker >/dev/null 2>&1; then
    dnf config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
    dnf -y install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
  fi
elif command -v apt-get >/dev/null 2>&1; then
  export DEBIAN_FRONTEND=noninteractive
  apt-get update -y
  apt-get install -y ca-certificates curl gnupg lsb-release git tar gzip dnsutils openssl
  if ! command -v docker >/dev/null 2>&1; then
    install -m 0755 -d /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
    chmod a+r /etc/apt/keyrings/docker.gpg
    . /etc/os-release
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu ${VERSION_CODENAME} stable" > /etc/apt/sources.list.d/docker.list
    apt-get update -y
    apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
  fi
else
  echo "Unsupported package manager" >&2
  exit 1
fi

systemctl enable --now docker
usermod -aG docker forge || true
mkdir -p /home/forge/insurance2026
chown -R forge:forge /home/forge/insurance2026

# Quick verification
command -v docker
docker --version
docker compose version
id forge
'@

    $recipeNamePrefix = 'ins2026-docker-bootstrap'
    $successMessage = 'Docker bootstrap via Forge recipe completed successfully.'
}

$recipeName = "$recipeNamePrefix-$(Get-Date -Format 'yyyyMMddHHmmss')"

Write-Host '[2/5] Creating temporary root recipe...'
$createBody = @{
    name   = $recipeName
    user   = 'root'
    script = $script
}
$recipe = Invoke-ForgeApi -Method POST -Path "/orgs/$ForgeOrgSlug/recipes" -Payload $createBody -Token $token
$recipeId = [string]$recipe.data.id
if ([string]::IsNullOrWhiteSpace($recipeId)) {
    throw 'Could not read recipe id from Forge API response.'
}
Write-Host "  OK recipe id: $recipeId"

Write-Host '[3/5] Triggering recipe run on target server...'
$runBody = @{ servers = @([int]$ForgeServerId); email = $false }
[void](Invoke-ForgeApi -Method POST -Path "/orgs/$ForgeOrgSlug/recipes/$recipeId/runs" -Payload $runBody -Token $token)
Write-Host '  OK run accepted'

Write-Host '[4/5] Waiting for recipe completion...'
$logId = $null
$status = 'waiting'
for ($i = 1; $i -le 120; $i++) {
    Start-Sleep -Seconds 5
    $runs = Invoke-ForgeApi -Method GET -Path "/orgs/$ForgeOrgSlug/recipes/$recipeId/runs?page%5Bsize%5D=30" -Token $token
    $match = $runs.data | Where-Object { $_.attributes.server_id -eq [int]$ForgeServerId } | Select-Object -First 1
    if (-not $match) {
        continue
    }

    $logId = [string]$match.id
    $status = [string]$match.attributes.status
    Write-Host "  status: $status"

    if ($status -in @('finished', 'failed')) {
        break
    }
}

if ([string]::IsNullOrWhiteSpace($logId)) {
    throw 'Could not locate recipe run log id.'
}

$runLog = Invoke-ForgeApi -Method GET -Path "/orgs/$ForgeOrgSlug/recipes/$recipeId/runs/$logId" -Token $token
$finalStatus = [string]$runLog.data.attributes.status
$output = [string]$runLog.data.attributes.output

Write-Host '--- recipe output (tail) ---'
if ([string]::IsNullOrWhiteSpace($output)) {
    Write-Host '(no output)'
}
else {
    $lines = $output -split "`r?`n"
    $tail = $lines | Select-Object -Last 120
    $tail | ForEach-Object { Write-Host $_ }
}

if (-not $KeepRecipe) {
    Write-Host '[5/5] Cleaning up temporary recipe...'
    [void](Invoke-ForgeApi -Method DELETE -Path "/orgs/$ForgeOrgSlug/recipes/$recipeId" -Token $token)
    Write-Host '  OK recipe deleted'
}
else {
    Write-Host "[5/5] Keeping recipe id $recipeId as requested."
}

if ($finalStatus -ne 'finished') {
    throw "Recipe did not finish successfully (status=$finalStatus)."
}

Write-Host ''
Write-Host $successMessage
