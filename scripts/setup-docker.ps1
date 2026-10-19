$ErrorActionPreference = 'Stop'
Set-Location (Split-Path $PSScriptRoot -Parent)
function Invoke-Checked { param([string[]]$Arguments) & docker @Arguments; if ($LASTEXITCODE -ne 0) { throw "Docker gagal: $Arguments" } }
if (!(Test-Path '.env')) { Copy-Item '.env.example' '.env' }
function New-Secret { $bytes = New-Object byte[] 32; $rng=[System.Security.Cryptography.RandomNumberGenerator]::Create(); $rng.GetBytes($bytes); $rng.Dispose(); return [Convert]::ToBase64String($bytes) }
$content = [IO.File]::ReadAllText((Join-Path (Get-Location) '.env'))
foreach ($key in @('DB_PASSWORD','MYSQL_ROOT_PASSWORD')) {
 if ($content -match "(?m)^$key=\s*$") { $secret=New-Secret; $content=[regex]::Replace($content,"(?m)^$key=\s*$","$key=$secret") }
}
if ($content -match '(?m)^APP_KEY=\s*$') { $secret=New-Secret; $content=[regex]::Replace($content,'(?m)^APP_KEY=\s*$',"APP_KEY=base64:$secret") }
[IO.File]::WriteAllText((Join-Path (Get-Location) '.env'),$content,(New-Object System.Text.UTF8Encoding $false))
New-Item -ItemType Directory -Force '.local-data' | Out-Null
Invoke-Checked -Arguments @('compose','pull','db')
Invoke-Checked -Arguments @('compose','build','--pull')
Invoke-Checked -Arguments @('compose','up','-d')
Invoke-Checked -Arguments @('compose','exec','app','php','artisan','migrate','--force')
Invoke-Checked -Arguments @('compose','exec','app','php','artisan','db:seed','--force')
Write-Host 'Siap. Lanjutkan: docker compose exec app php artisan ipl:create-admin'
Write-Host 'Dashboard http://localhost:8080 | Admin /admin | Warga /warga/login'
