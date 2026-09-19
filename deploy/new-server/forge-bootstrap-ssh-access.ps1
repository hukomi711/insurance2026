param(
    [string]$ForgeServerId = '1254814',
    [string]$ForgeServerIp = '168.144.13.251',
    [string]$ForgeOrgSlug = $env:FORGE_ORG_SLUG,
    [string]$ForgeKeyName = 'insurance2026-deploy',
    [string]$TargetUser = 'forge',
    [string]$PublicKeyPath = "$env:USERPROFILE\.ssh\insurance2026_deploy.pub",
    [string]$PrivateKeyPath = "$env:USERPROFILE\.ssh\insurance2026_deploy"
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

function Invoke-ForgeApi {
    param(
        [ValidateSet('GET', 'POST')]
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

    if ($Method -eq 'GET') {
        return Invoke-RestMethod -Method Get -Uri $uri -Headers $headers
    }

    $json = $Payload | ConvertTo-Json -Compress -Depth 10
    return Invoke-RestMethod -Method Post -Uri $uri -Headers $headers -Body $json -ContentType 'application/json'
}

if ($ForgeServerId -notmatch '^\d+$') {
    throw 'ForgeServerId must be numeric.'
}

if (-not (Test-Path -LiteralPath $PublicKeyPath)) {
    throw "Public key not found: $PublicKeyPath"
}
if (-not (Test-Path -LiteralPath $PrivateKeyPath)) {
    throw "Private key not found: $PrivateKeyPath"
}

$pub = (Get-Content -LiteralPath $PublicKeyPath -Raw).Trim()
if ([string]::IsNullOrWhiteSpace($pub)) {
    throw 'Public key content is empty.'
}

Write-Host '[1/6] Reading Forge token...'
$token = ConvertTo-NormalizedToken -Token $env:FORGE_API_TOKEN
if ([string]::IsNullOrWhiteSpace($token)) {
    $secure = Read-Host 'Enter FORGE_API_TOKEN' -AsSecureString
    $bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
    try {
        $token = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($bstr)
    }
    finally {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
    }
    $token = ConvertTo-NormalizedToken -Token $token
}

if ($token -notmatch '^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$') {
    throw 'Token format is invalid. Expected a Forge JWT-like token (three dot-separated segments).'
}

Write-Host '[2/6] Verifying token via /me...'
$me = Invoke-ForgeApi -Method GET -Path '/me' -Token $token
$email = $me.data.attributes.email
if ([string]::IsNullOrWhiteSpace($email)) {
    $email = 'unknown'
}
Write-Host "  OK token owner: $email"

if ([string]::IsNullOrWhiteSpace($ForgeOrgSlug)) {
    Write-Host "[3/6] Discovering org slug for server $ForgeServerId..."
    $orgs = Invoke-ForgeApi -Method GET -Path '/orgs' -Token $token
    $found = $null
    $availableServers = @()

    foreach ($org in $orgs.data) {
        $slug = $org.attributes.slug
        if ([string]::IsNullOrWhiteSpace($slug)) {
            continue
        }

        $servers = Invoke-ForgeApi -Method GET -Path "/orgs/$slug/servers?page%5Bsize%5D=100" -Token $token
        foreach ($server in $servers.data) {
            $idFromAttr = $null
            if ($server.attributes -and $server.attributes.id) {
                $idFromAttr = [string]$server.attributes.id
            }
            elseif ($server.id) {
                $idFromAttr = [string]$server.id
            }

            $serverName = ''
            if ($server.attributes -and $server.attributes.name) {
                $serverName = [string]$server.attributes.name
            }

            $serverIp = ''
            if ($server.attributes -and $server.attributes.ip_address) {
                $serverIp = [string]$server.attributes.ip_address
            }

            $availableServers += [PSCustomObject]@{
                Org  = $slug
                Id   = $idFromAttr
                Name = $serverName
                Ip   = $serverIp
            }

            if ($idFromAttr -eq [string]$ForgeServerId) {
                $found = $slug
                break
            }
        }

        if ($found) {
            break
        }
    }

    if (-not $found) {
        $matchedByIp = $null
        if (-not [string]::IsNullOrWhiteSpace($ForgeServerIp)) {
            $matchedByIp = $availableServers | Where-Object { $_.Ip -eq $ForgeServerIp } | Select-Object -First 1
        }

        if ($matchedByIp) {
            Write-Warning "Server ID $ForgeServerId not found. Using server '$($matchedByIp.Name)' ($($matchedByIp.Id)) matched by IP $ForgeServerIp in org '$($matchedByIp.Org)'."
            $ForgeServerId = [string]$matchedByIp.Id
            $found = [string]$matchedByIp.Org
        }
        else {
            $serverSummary = ($availableServers | ForEach-Object { "$($_.Org):$($_.Id):$($_.Name):$($_.Ip)" }) -join '; '
            throw "Could not find server $ForgeServerId in your Forge organizations. Visible servers: $serverSummary"
        }
    }

    $ForgeOrgSlug = $found
}
else {
    Write-Host "[3/6] Using provided org slug: $ForgeOrgSlug"
}

Write-Host "  OK org slug: $ForgeOrgSlug"

Write-Host "[4/6] Checking existing SSH keys on server $ForgeServerId..."
$keys = Invoke-ForgeApi -Method GET -Path "/orgs/$ForgeOrgSlug/servers/$ForgeServerId/ssh-keys" -Token $token
$existing = $keys.data | Where-Object {
    $_.attributes.name -eq $ForgeKeyName -and $_.attributes.user -eq $TargetUser
} | Select-Object -First 1

if ($existing) {
    Write-Host "  OK key already exists for user '$TargetUser' (id: $($existing.id))"
}
else {
    Write-Host "[5/6] Adding SSH key for user '$TargetUser'..."
    $payload = @{
        name = $ForgeKeyName
        key  = $pub
        user = $TargetUser
    }
    [void](Invoke-ForgeApi -Method POST -Path "/orgs/$ForgeOrgSlug/servers/$ForgeServerId/ssh-keys" -Payload $payload -Token $token)
    Write-Host "  OK key added for user '$TargetUser'"
}

Write-Host '[6/6] Probing SSH access...'
& ssh -o BatchMode=yes -o ConnectTimeout=12 -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new -i $PrivateKeyPath "${TargetUser}@${ForgeServerIp}" 'echo SSH_OK && whoami'
if ($LASTEXITCODE -ne 0) {
    throw 'SSH probe failed. Wait 30-60 seconds and retry; if still failing, verify key was added to the correct server and user.'
}

Write-Host ''
Write-Host 'Bootstrap complete. SSH access is ready.'
