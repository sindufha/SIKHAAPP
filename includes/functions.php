<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function appBasePath(): string
{
    static $basePath = null;
    if ($basePath !== null) return $basePath;

    $configured = getenv('APP_BASE_PATH');
    if ($configured !== false) {
        $configured = '/' . trim(str_replace('\\', '/', $configured), '/');
        return $basePath = ($configured === '/' ? '' : $configured);
    }

    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $basePath = preg_replace('#/(?:admin|guru|api|siswa)/[^/]+$#', '', $script) ?? '';
    if ($basePath === $script) {
        $basePath = rtrim(str_replace('\\', '/', dirname($script)), '/.');
    }
    return $basePath === '/' ? '' : rtrim($basePath, '/');
}

function appUrl(string $path = ''): string
{
    $path = ltrim($path, '/');
    return appBasePath() . ($path === '' ? '/' : '/' . $path);
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function redirect(string $url): never
{
    header('Location: ' . $url, true, 303);
    exit;
}

function destroySession(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function requireLogin(): void
{
    if (!isLoggedIn()) redirect(appUrl('login.php'));

    global $pdo;
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare('SELECT username, nama, role, is_active FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if (!$user || !(int)$user['is_active'] || !in_array($user['role'], ['ADMIN', 'GURU'], true)) {
            destroySession();
            redirect(appUrl('login.php'));
        }
        $_SESSION['username'] = $user['username'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['role'] = $user['role'];
    }
}

function hasRole(string $role): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function dashboardUrl(): string
{
    if (hasRole('ADMIN')) return appUrl('admin/dashboard.php');
    if (hasRole('GURU')) return appUrl('guru/dashboard.php');
    return appUrl('login.php');
}

function requireRole(string $role): void
{
    requireLogin();
    if (!hasRole($role)) redirect(dashboardUrl());
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . escape(csrfToken()) . '">';
}

function verifyCsrf(?string $token = null): void
{
    $token ??= $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!isCsrfValid($token)) {
        http_response_code(419);
        exit('Sesi formulir telah kedaluwarsa. Muat ulang halaman lalu coba lagi.');
    }
}

function isCsrfValid(mixed $token): bool
{
    return is_string($token) && hash_equals(csrfToken(), $token);
}

function logAudit(PDO $pdo, string $aksi, ?string $deskripsi = null, ?array $detail = null): void
{
    $detailJson = $detail === null ? null : json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $stmt = $pdo->prepare('INSERT INTO audit_log (id, user_id, aksi, deskripsi, detail, ip, user_agent) VALUES (UUID(), ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $_SESSION['user_id'] ?? null,
        $aksi,
        $deskripsi,
        $detailJson,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);
}

function jsonResponse(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function escape(mixed $value): string
{
    if (!is_scalar($value) && $value !== null) return '';
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function jsonForHtml(mixed $value): string
{
    return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
}

function postString(string $key, int $maxLength = 255): string
{
    $raw = $_POST[$key] ?? '';
    if (!is_string($raw) && !is_numeric($raw)) return '';
    $value = trim((string)$raw);
    return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
}

function getString(string $key, int $maxLength = 255): string
{
    $raw = $_GET[$key] ?? '';
    if (!is_string($raw) && !is_numeric($raw)) return '';
    $value = trim((string)$raw);
    return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
}

function abortRequest(string $message, int $status = 422): never
{
    http_response_code($status);
    exit(escape($message));
}

function isValidDate(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

function reportPeriod(?string $month, ?string $year): array
{
    $month = preg_match('/^(0[1-9]|1[0-2])$/', (string)$month) ? (string)$month : date('m');
    $year = preg_match('/^20\d{2}$/', (string)$year) ? (string)$year : date('Y');
    $start = DateTimeImmutable::createFromFormat('!Y-m-d', "$year-$month-01");
    return [$month, $year, $start->format('Y-m-d'), $start->format('Y-m-t')];
}

function monthNameIndonesian(int $month): string
{
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $months[$month] ?? '';
}

function dateIndonesian(?DateTimeInterface $date = null): string
{
    $date ??= new DateTimeImmutable();
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    return $days[(int)$date->format('w')] . ', ' . $date->format('d') . ' ' . monthNameIndonesian((int)$date->format('n')) . ' ' . $date->format('Y');
}
