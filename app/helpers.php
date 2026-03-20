<?php

declare(strict_types=1);

// Ensure sessions are available even for direct-entry scripts under /public/* (e.g. admin pages).
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = app_config();
    $db = is_array($config['db'] ?? null) ? (array) $config['db'] : [];

    $host = (string) (getenv('DB_HOST') ?: '127.0.0.1');
    $port = (string) (getenv('DB_PORT') ?: '3306');
    $name = (string) (getenv('DB_NAME') ?: ((string) ($db['name'] ?? 'pasarkita')));
    $user = (string) (getenv('DB_USER') ?: ((string) ($db['user'] ?? 'root')));
    $pass = (string) (getenv('DB_PASS') ?: ((string) ($db['pass'] ?? '')));

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo 'Database connection failed.';
        exit;
    }

    return $pdo;
}

function app_config(): array
{
    static $config;
    if (is_array($config)) {
        return $config;
    }

    $path = base_path('config/config.php');
    if (is_file($path)) {
        $loaded = require $path;
        if (is_array($loaded)) {
            $config = $loaded;
            return $config;
        }
    }

    $config = [];
    return $config;
}

function telegram_config(): array
{
    $config = app_config();
    $tg = is_array($config['telegram'] ?? null) ? (array) $config['telegram'] : [];

    $botToken = trim((string) ($tg['bot_token'] ?? ''));
    $chatId = trim((string) ($tg['chat_id'] ?? ''));

    // Treat placeholders as disabled.
    if ($botToken === 'ISI_BOT_TOKEN' || $botToken === 'YOUR_BOT_TOKEN') {
        $botToken = '';
    }
    if ($chatId === 'ISI_CHAT_ID' || $chatId === 'YOUR_CHAT_ID') {
        $chatId = '';
    }

    return [
        'bot_token' => $botToken,
        'chat_id' => $chatId,
    ];
}

function telegram_is_configured(): bool
{
    $tg = telegram_config();
    return ((string) ($tg['bot_token'] ?? '')) !== '' && ((string) ($tg['chat_id'] ?? '')) !== '';
}

function telegram_send_message(string $text): bool
{
    $text = trim($text);
    if ($text === '' || !telegram_is_configured()) {
        return false;
    }

    // Telegram message limit is 4096 chars. Keep some buffer.
    if (mb_strlen($text) > 3800) {
        $text = mb_substr($text, 0, 3790) . "…";
    }

    $tg = telegram_config();
    $botToken = (string) ($tg['bot_token'] ?? '');
    $chatId = (string) ($tg['chat_id'] ?? '');

    $endpoint = 'https://api.telegram.org/bot' . rawurlencode($botToken) . '/sendMessage';
    $payload = [
        'chat_id' => $chatId,
        'text' => $text,
        'disable_web_page_preview' => 'true',
    ];

    try {
        if (function_exists('curl_init')) {
            $ch = curl_init($endpoint);
            if ($ch === false) {
                return false;
            }
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 1,
                CURLOPT_TIMEOUT => 2,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $resp = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $resp !== false && $code >= 200 && $code < 300;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($payload),
                'timeout' => 2,
            ],
        ]);

        $resp = @file_get_contents($endpoint, false, $context);
        return $resp !== false;
    } catch (Throwable $e) {
        return false;
    }
}

function telegram_send_photo(string $filePath, string $caption = ''): bool
{
    $filePath = (string) $filePath;
    if ($filePath === '' || !is_file($filePath) || !telegram_is_configured()) {
        return false;
    }
    if (!function_exists('curl_init')) {
        return false;
    }

    $tg = telegram_config();
    $botToken = (string) ($tg['bot_token'] ?? '');
    $chatId = (string) ($tg['chat_id'] ?? '');

    $endpoint = 'https://api.telegram.org/bot' . rawurlencode($botToken) . '/sendPhoto';
    $payload = [
        'chat_id' => $chatId,
        'photo' => new CURLFile($filePath),
        'disable_web_page_preview' => 'true',
    ];
    $caption = trim($caption);
    if ($caption !== '') {
        if (mb_strlen($caption) > 900) {
            $caption = mb_substr($caption, 0, 890) . '…';
        }
        $payload['caption'] = $caption;
    }

    try {
        $ch = curl_init($endpoint);
        if ($ch === false) {
            return false;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $resp !== false && $code >= 200 && $code < 300;
    } catch (Throwable $e) {
        return false;
    }
}

function telegram_send_document(string $filePath, string $caption = ''): bool
{
    $filePath = (string) $filePath;
    if ($filePath === '' || !is_file($filePath) || !telegram_is_configured()) {
        return false;
    }
    if (!function_exists('curl_init')) {
        return false;
    }

    $tg = telegram_config();
    $botToken = (string) ($tg['bot_token'] ?? '');
    $chatId = (string) ($tg['chat_id'] ?? '');

    $endpoint = 'https://api.telegram.org/bot' . rawurlencode($botToken) . '/sendDocument';
    $payload = [
        'chat_id' => $chatId,
        'document' => new CURLFile($filePath),
        'disable_web_page_preview' => 'true',
    ];
    $caption = trim($caption);
    if ($caption !== '') {
        if (mb_strlen($caption) > 900) {
            $caption = mb_substr($caption, 0, 890) . '…';
        }
        $payload['caption'] = $caption;
    }

    try {
        $ch = curl_init($endpoint);
        if ($ch === false) {
            return false;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $resp !== false && $code >= 200 && $code < 300;
    } catch (Throwable $e) {
        return false;
    }
}

function db_has_column(string $table, string $column): bool
{
    $table = trim($table);
    $column = trim($column);
    if ($table === '' || $column === '') {
        return false;
    }

    static $cache = [];
    $key = strtolower($table . '.' . $column);
    if (array_key_exists($key, $cache)) {
        return (bool) $cache[$key];
    }

    try {
        $stmt = db()->prepare('SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c LIMIT 1');
        $stmt->execute(['t' => $table, 'c' => $column]);
        $cache[$key] = (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        $cache[$key] = false;
    }

    return (bool) $cache[$key];
}

function db_user_find_for_login(string $identifier): ?array
{
    $identifier = trim($identifier);
    if ($identifier === '') {
        return null;
    }

    $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;

    $sql = $isEmail
        ? 'SELECT id, full_name, username, email, phone, birth_date, password_hash FROM users WHERE email = :id LIMIT 1'
        : 'SELECT id, full_name, username, email, phone, birth_date, password_hash FROM users WHERE username = BINARY :id LIMIT 1';

    $stmt = db()->prepare($sql);
    $stmt->execute(['id' => $identifier]);
    $user = $stmt->fetch();

    return is_array($user) ? $user : null;
}

function db_user_exists_username(string $username): bool
{
    $stmt = db()->prepare('SELECT 1 FROM users WHERE username = BINARY :u LIMIT 1');
    $stmt->execute(['u' => $username]);
    return (bool) $stmt->fetchColumn();
}

function db_user_exists_email(string $email): bool
{
    $stmt = db()->prepare('SELECT 1 FROM users WHERE email = :e LIMIT 1');
    $stmt->execute(['e' => $email]);
    return (bool) $stmt->fetchColumn();
}

function db_user_create(array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO users (full_name, username, email, phone, birth_date, password_hash) VALUES (:full_name, :username, :email, :phone, :birth_date, :password_hash)'
    );

    $params = [
        'full_name' => (string) ($data['full_name'] ?? ''),
        'username' => (string) ($data['username'] ?? ''),
        'email' => (string) ($data['email'] ?? ''),
        'phone' => (string) ($data['phone'] ?? ''),
        'birth_date' => (string) ($data['birth_date'] ?? ''),
        'password_hash' => (string) ($data['password_hash'] ?? ''),
    ];

    $stmt->execute($params);

    return (int) db()->lastInsertId();
}

function db_user_mark_login(int $userId): void
{
    $stmt = db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
    $stmt->execute(['id' => $userId]);
}

function db_user_update_full_name(int $userId, string $fullName): void
{
    $userId = (int) $userId;
    $fullName = trim($fullName);
    if ($userId <= 0) {
        throw new RuntimeException('User tidak valid.');
    }
    if ($fullName === '' || mb_strlen($fullName) < 3) {
        throw new RuntimeException('Nama lengkap minimal 3 karakter.');
    }

    $stmt = db()->prepare('UPDATE users SET full_name = :full_name WHERE id = :id');
    $stmt->execute([
        'full_name' => $fullName,
        'id' => $userId,
    ]);
}

function db_users_list(): array
{
    $stmt = db()->prepare('SELECT id, username, full_name, email, created_at FROM users ORDER BY full_name ASC');
    $stmt->execute();
    $rows = $stmt->fetchAll();
    return is_array($rows) ? $rows : [];
}

function db_admins_list(): array
{
    $stmt = db()->prepare('SELECT id, username, full_name, email, created_at FROM admins ORDER BY full_name ASC, username ASC');
    $stmt->execute();
    $rows = $stmt->fetchAll();
    return is_array($rows) ? $rows : [];
}

function base_url(): string
{
    $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    $scriptName = str_replace('\\', '/', $scriptName);

    $base = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

    // If the app is accessed via /something/public/... (including direct file access like
    // /something/public/admin/index.php), treat /something as base.
    if ($base !== '') {
        $pos = strrpos($base, '/public');
        if ($pos !== false) {
            $after = substr($base, $pos + 7, 1);
            if ($after === '' || $after === '/') {
                $base = substr($base, 0, $pos);
            }
        }
    }

    return $base;
}

function url(string $path = ''): string
{
    $base = base_url();

    if ($path === '' || $path === '/') {
        return $base === '' ? '/' : $base . '/';
    }

    // Allow absolute URLs.
    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    if ($path[0] !== '/') {
        $path = '/' . $path;
    }

    // Avoid double-prefixing if caller already supplied the base.
    if ($base !== '' && strncmp($path, $base . '/', strlen($base) + 1) === 0) {
        return $path;
    }

    return $base . $path;
}

function asset(string $path): string
{
    $path = ltrim($path, '/');
    return url('/' . $path);
}

function product_first_image_url(int $productId): ?string
{
    $productId = (int) $productId;
    if ($productId <= 0) {
        return null;
    }

    $dir = base_path('public/products/' . $productId);
    if (!is_dir($dir)) {
        return null;
    }

    $files = glob($dir . '/*.{jpg,jpeg,png,webp,gif,JPG,JPEG,PNG,WEBP,GIF}', GLOB_BRACE);
    if (!is_array($files) || empty($files)) {
        return null;
    }

    sort($files, SORT_NATURAL | SORT_FLAG_CASE);
    $first = (string) ($files[0] ?? '');
    if ($first === '') {
        return null;
    }

    $base = basename($first);
    $rel = 'products/' . rawurlencode((string) $productId) . '/' . rawurlencode($base);
    return asset($rel);
}

function base_path(string $path = ''): string
{
    $base = dirname(__DIR__);
    return $path === '' ? $base : $base . DIRECTORY_SEPARATOR . $path;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function request_path(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        return '/';
    }

    $base = base_url();
    if ($base !== '' && strncmp($path, $base, strlen($base)) === 0) {
        $path = substr($path, strlen($base));
        if ($path === '') {
            $path = '/';
        }
    }

    // Treat index.php as the homepage.
    if ($path === '/index.php' || $path === '/public/index.php') {
        return '/';
    }

    return $path;
}

function is_path(string $needle): bool
{
    $path = trim(request_path(), '/');
    $needle = trim($needle, '/');

    if ($needle === '') {
        return $path === '';
    }

    return $path === $needle;
}

function redirect(string $to): never
{
    header('Location: ' . url($to));
    exit;
}

function session_flash_set(string $key, mixed $value): void
{
    $_SESSION['_flash'][$key] = $value;
}

function session_flash_get(string $key, mixed $default = null): mixed
{
    if (!isset($_SESSION['_flash']) || !is_array($_SESSION['_flash'])) {
        return $default;
    }

    if (!array_key_exists($key, $_SESSION['_flash'])) {
        return $default;
    }

    $value = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);

    return $value;
}

function old(string $key, string $default = ''): string
{
    static $cached = null;
    if (!is_array($cached)) {
        $cached = session_flash_get('old_input', []);
        if (!is_array($cached)) {
            $cached = [];
        }
    }

    $value = $cached[$key] ?? null;
    return is_string($value) ? $value : $default;
}

function set_old_input(array $input): void
{
    session_flash_set('old_input', $input);
}

function clear_old_input(): void
{
    if (isset($_SESSION['_flash']) && is_array($_SESSION['_flash'])) {
        unset($_SESSION['_flash']['old_input']);
    }
}

function csrf_token(): string
{
    if (!isset($_SESSION['_csrf']) || !is_string($_SESSION['_csrf']) || $_SESSION['_csrf'] === '') {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $token = $_POST['_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        echo 'CSRF token mismatch.';
        exit;
    }
}

function auth_user(): ?array
{
    $user = $_SESSION['auth_user'] ?? null;
    if (!is_array($user)) {
        return null;
    }

    $id = (int) ($user['id'] ?? 0);
    if ($id <= 0) {
        unset($_SESSION['auth_user']);
        return null;
    }

    // Verify user still exists in database; if not, force logout
    try {
        $dbUser = db_user_find_by_id($id);
    } catch (Throwable $e) {
        $dbUser = null;
    }

    if (!is_array($dbUser)) {
        unset($_SESSION['auth_user']);
        if (function_exists('session_regenerate_id')) {
            session_regenerate_id(true);
        }
        return null;
    }

    return $user;
}

function auth_admin(): ?array
{
    $admin = $_SESSION['auth_admin'] ?? null;
    if (!is_array($admin)) {
        return null;
    }

    $id = (int) ($admin['id'] ?? 0);
    if ($id <= 0) {
        unset($_SESSION['auth_admin']);
        return null;
    }

    // verify admin still exists
    try {
        $stmt = db()->prepare('SELECT id, username, email, full_name FROM admins WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $dbAdmin = $stmt->fetch();
    } catch (Throwable $e) {
        $dbAdmin = null;
    }

    if (!is_array($dbAdmin)) {
        unset($_SESSION['auth_admin']);
        if (function_exists('session_regenerate_id')) {
            session_regenerate_id(true);
        }
        return null;
    }

    // keep stored fields up to date
    $_SESSION['auth_admin']['username'] = (string) ($dbAdmin['username'] ?? '');
    $_SESSION['auth_admin']['email'] = (string) ($dbAdmin['email'] ?? '');
    $_SESSION['auth_admin']['name'] = (string) ($dbAdmin['full_name'] ?? '');

    return $admin;
}

function db_user_find_by_id(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    $stmt = db()->prepare('SELECT id, full_name, username, email, phone, birth_date FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();

    return is_array($user) ? $user : null;
}

function db_user_trial_get_by_id(int $userId): array
{
    $userId = (int) $userId;
    if ($userId <= 0) {
        return [
            'trial_started_at' => null,
            'is_trial_active' => false,
            'expires_at' => null,
            'is_expired' => true,
        ];
    }

    $hasTrialStarted = db_has_column('users', 'trial_started_at');
    $hasTrialActive = db_has_column('users', 'is_trial_active');

    if (!$hasTrialStarted || !$hasTrialActive) {
        return [
            'trial_started_at' => null,
            'is_trial_active' => false,
            'expires_at' => null,
            'is_expired' => true,
        ];
    }

    $stmt = db()->prepare('SELECT trial_started_at, is_trial_active FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch();
    $trialStartedAt = is_array($row) ? ($row['trial_started_at'] ?? null) : null;
    $isActive = is_array($row) ? ((int) ($row['is_trial_active'] ?? 0) === 1) : false;

    $trialStartedAtStr = is_string($trialStartedAt) && $trialStartedAt !== '' ? $trialStartedAt : null;
    if ($trialStartedAtStr === null) {
        return [
            'trial_started_at' => null,
            'is_trial_active' => false,
            'expires_at' => null,
            'is_expired' => false,
        ];
    }

    $tz = new DateTimeZone(date_default_timezone_get() ?: 'UTC');
    try {
        $started = new DateTimeImmutable($trialStartedAtStr, $tz);
    } catch (Throwable $e) {
        return [
            'trial_started_at' => null,
            'is_trial_active' => false,
            'expires_at' => null,
            'is_expired' => true,
        ];
    }
    $expires = $started->modify('+3 days');
    $now = new DateTimeImmutable('now', $tz);
    $expired = $now > $expires;

    // If expired but still marked active, flip it off best-effort.
    if ($expired && $isActive) {
        try {
            $u = db()->prepare('UPDATE users SET is_trial_active = 0 WHERE id = :id LIMIT 1');
            $u->execute(['id' => $userId]);
        } catch (Throwable $e) {
            // ignore
        }
        $isActive = false;
    }

    return [
        'trial_started_at' => $started->format('Y-m-d H:i:s'),
        'is_trial_active' => $isActive,
        'expires_at' => $expires->format('Y-m-d H:i:s'),
        'is_expired' => $expired,
    ];
}

function db_user_trial_start(int $userId): array
{
    $userId = (int) $userId;
    if ($userId <= 0) {
        throw new RuntimeException('User tidak valid.');
    }

    if (!db_has_column('users', 'trial_started_at') || !db_has_column('users', 'is_trial_active')) {
        throw new RuntimeException('Kolom trial belum tersedia di database.');
    }

    // Only set if not started yet.
    $stmt = db()->prepare('UPDATE users SET trial_started_at = NOW(), is_trial_active = 1 WHERE id = :id AND trial_started_at IS NULL LIMIT 1');
    $stmt->execute(['id' => $userId]);

    return db_user_trial_get_by_id($userId);
}

function db_conversation_find_open_by_user_id(int $userId): ?array
{
    $userId = (int) $userId;
    if ($userId <= 0) {
        return null;
    }

    $stmt = db()->prepare("SELECT id, user_id, status, created_at, updated_at FROM conversations WHERE user_id = :user_id AND status = 'open' ORDER BY updated_at DESC, id DESC LIMIT 1");
    $stmt->execute(['user_id' => $userId]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function db_conversation_create(int $userId): int
{
    $userId = (int) $userId;
    if ($userId <= 0) {
        throw new RuntimeException('User tidak valid.');
    }

    $stmt = db()->prepare("INSERT INTO conversations (user_id, status) VALUES (:user_id, 'open')");
    $stmt->execute(['user_id' => $userId]);
    return (int) db()->lastInsertId();
}

function db_message_create(int $conversationId, string $senderRole, ?int $senderId, string $message): int
{
    $conversationId = (int) $conversationId;
    $senderRole = strtolower(trim($senderRole));
    $message = trim($message);

    if ($conversationId <= 0) {
        throw new RuntimeException('Conversation tidak valid.');
    }
    if ($senderRole !== 'user' && $senderRole !== 'admin') {
        throw new RuntimeException('Sender role tidak valid.');
    }
    if ($message === '') {
        throw new RuntimeException('Pesan kosong.');
    }

    $stmt = db()->prepare('INSERT INTO messages (conversation_id, sender_role, sender_id, message) VALUES (:conversation_id, :sender_role, :sender_id, :message)');
    $stmt->execute([
        'conversation_id' => $conversationId,
        'sender_role' => $senderRole,
        'sender_id' => $senderId,
        'message' => $message,
    ]);

    // IMPORTANT: read lastInsertId() immediately after the INSERT.
    // Some drivers return 0 if another statement runs afterwards.
    $messageId = (int) db()->lastInsertId();
    if ($messageId <= 0) {
        throw new RuntimeException('Failed to create message: invalid ID returned.');
    }

    // Touch conversation updated_at
    try {
        $u = db()->prepare('UPDATE conversations SET updated_at = NOW() WHERE id = :id LIMIT 1');
        $u->execute(['id' => $conversationId]);
    } catch (Throwable $e) {
        // ignore
    }

    return $messageId;
}

function db_messages_fetch(int $conversationId, int $afterId = 0, int $limit = 200): array
{
    $conversationId = (int) $conversationId;
    $afterId = (int) $afterId;
    $limit = (int) $limit;
    if ($conversationId <= 0) {
        return [];
    }
    if ($afterId < 0) {
        $afterId = 0;
    }
    if ($limit < 1) {
        $limit = 1;
    }
    if ($limit > 500) {
        $limit = 500;
    }

    // If afterId is zero, caller is requesting initial history. Fetch the
    // newest $limit messages and return them in ascending order so the UI
    // displays oldest -> newest. This avoids sending the entire history.
    if ($afterId === 0) {
        $sql = 'SELECT id, conversation_id, sender_role, sender_id, message, created_at'
            . ' FROM messages'
            . ' WHERE conversation_id = :conversation_id'
            . ' ORDER BY id DESC'
            . ' LIMIT ' . $limit;
        $stmt = db()->prepare($sql);
        $stmt->execute([
            'conversation_id' => $conversationId,
        ]);
        $rows = $stmt->fetchAll();
        if (!is_array($rows) || empty($rows)) return [];
        // rows are newest-first; reverse to oldest-first for the client
        return array_reverse($rows);
    }

    $sql = 'SELECT id, conversation_id, sender_role, sender_id, message, created_at'
        . ' FROM messages'
        . ' WHERE conversation_id = :conversation_id AND id > :after_id'
        . ' ORDER BY id ASC'
        . ' LIMIT ' . $limit;
    $stmt = db()->prepare($sql);
    $stmt->execute([
        'conversation_id' => $conversationId,
        'after_id' => $afterId,
    ]);
    $rows = $stmt->fetchAll();
    return is_array($rows) ? $rows : [];
}

function db_message_find(int $messageId): ?array
{
    $messageId = (int) $messageId;
    if ($messageId <= 0) {
        return null;
    }

    $stmt = db()->prepare('SELECT id, conversation_id, sender_role, sender_id, message, created_at FROM messages WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $messageId]);

    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function db_admin_conversations_list(int $limit = 50): array
{
    $limit = (int) $limit;
    if ($limit < 1) {
        $limit = 1;
    }
    if ($limit > 200) {
        $limit = 200;
    }

    // last message via subquery
    $sql = "SELECT c.id AS conversation_id, c.user_id, c.status, c.updated_at,
        u.full_name,
        m.message AS last_message,
        m.created_at AS last_message_at
      FROM conversations c
      JOIN users u ON u.id = c.user_id
      LEFT JOIN messages m ON m.id = (
        SELECT mm.id FROM messages mm WHERE mm.conversation_id = c.id ORDER BY mm.id DESC LIMIT 1
      )
      ORDER BY c.status ASC, c.updated_at DESC, c.id DESC
      LIMIT " . $limit;

    $stmt = db()->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    return is_array($rows) ? $rows : [];
}

function db_user_log_create(?int $userId, ?int $adminId, string $action, string $description = '', array $metadata = []): int
{
    $action = trim($action);
    $description = trim($description);

    if ($action === '') {
        throw new RuntimeException('Action tidak boleh kosong.');
    }

    $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $stmt = db()->prepare('INSERT INTO user_logs (user_id, admin_id, action, description, ip_address, user_agent, metadata) VALUES (:user_id, :admin_id, :action, :description, :ip_address, :user_agent, :metadata)');
    $stmt->execute([
        'user_id' => $userId,
        'admin_id' => $adminId,
        'action' => $action,
        'description' => $description,
        'ip_address' => $ipAddress,
        'user_agent' => $userAgent,
        'metadata' => json_encode($metadata),
    ]);

    return (int) db()->lastInsertId();
}

function db_user_logs_fetch(
    ?int $userId = null,
    ?int $adminId = null,
    int $limit = 50,
    int $offset = 0,
    ?string $userQuery = null,
    ?string $adminQuery = null,
    string $scope = 'all',
    ?int $logId = null
): array
{
    $where = [];
    $params = [];

    if ($userId !== null) {
        $where[] = 'user_id = :user_id';
        $params['user_id'] = $userId;
    }

    if ($adminId !== null) {
        $where[] = 'admin_id = :admin_id';
        $params['admin_id'] = $adminId;
    }

    if ($logId !== null) {
        $where[] = 'l.id = :log_id';
        $params['log_id'] = $logId;
    }

    if ($userQuery !== null && $userQuery !== '') {
        $where[] = '(u.full_name LIKE :user_fullname OR u.username LIKE :user_username OR u.email LIKE :user_email)';
        $params['user_fullname'] = '%' . $userQuery . '%';
        $params['user_username'] = '%' . $userQuery . '%';
        $params['user_email'] = '%' . $userQuery . '%';
    }

    if ($adminQuery !== null && $adminQuery !== '') {
        $where[] = '(a.full_name LIKE :admin_fullname OR a.username LIKE :admin_username OR a.email LIKE :admin_email)';
        $params['admin_fullname'] = '%' . $adminQuery . '%';
        $params['admin_username'] = '%' . $adminQuery . '%';
        $params['admin_email'] = '%' . $adminQuery . '%';
    }

    if ($scope === 'user') {
        $where[] = 'l.user_id IS NOT NULL';
        $where[] = 'l.admin_id IS NULL';
    } elseif ($scope === 'admin') {
        $where[] = 'l.admin_id IS NOT NULL';
        $where[] = 'l.user_id IS NULL';
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "SELECT l.id, l.user_id, l.admin_id, l.action, l.description, l.ip_address, l.metadata, l.created_at,
                   u.full_name AS user_name, u.username AS user_username,
                   a.full_name AS admin_name, a.username AS admin_username
            FROM user_logs l
            LEFT JOIN users u ON u.id = l.user_id
            LEFT JOIN admins a ON a.id = l.admin_id
            {$whereClause}
            ORDER BY l.created_at DESC
            LIMIT :limit OFFSET :offset";

    $stmt = db()->prepare($sql);
    $params['limit'] = $limit;
    $params['offset'] = $offset;
    $stmt->execute($params);

    $rows = $stmt->fetchAll();
    return is_array($rows) ? $rows : [];
}

function db_user_logs_count(?int $userId = null, ?int $adminId = null, ?string $userQuery = null, ?string $adminQuery = null, string $scope = 'all', ?int $logId = null): int
{
    $where = [];
    $params = [];

    if ($userId !== null) {
        $where[] = 'user_id = :user_id';
        $params['user_id'] = $userId;
    }

    if ($adminId !== null) {
        $where[] = 'admin_id = :admin_id';
        $params['admin_id'] = $adminId;
    }

    if ($logId !== null) {
        $where[] = 'l.id = :log_id';
        $params['log_id'] = $logId;
    }

    if ($userQuery !== null && $userQuery !== '') {
        $where[] = '(u.full_name LIKE :user_fullname OR u.username LIKE :user_username OR u.email LIKE :user_email)';
        $params['user_fullname'] = '%' . $userQuery . '%';
        $params['user_username'] = '%' . $userQuery . '%';
        $params['user_email'] = '%' . $userQuery . '%';
    }

    if ($adminQuery !== null && $adminQuery !== '') {
        $where[] = '(a.full_name LIKE :admin_fullname OR a.username LIKE :admin_username OR a.email LIKE :admin_email)';
        $params['admin_fullname'] = '%' . $adminQuery . '%';
        $params['admin_username'] = '%' . $adminQuery . '%';
        $params['admin_email'] = '%' . $adminQuery . '%';
    }

    if ($scope === 'user') {
        $where[] = 'l.user_id IS NOT NULL';
        $where[] = 'l.admin_id IS NULL';
    } elseif ($scope === 'admin') {
        $where[] = 'l.admin_id IS NOT NULL';
        $where[] = 'l.user_id IS NULL';
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = db()->prepare("SELECT COUNT(*) as cnt
            FROM user_logs l
            LEFT JOIN users u ON u.id = l.user_id
            LEFT JOIN admins a ON a.id = l.admin_id
            {$whereClause}");
    $stmt->execute($params);

    return (int) ($stmt->fetch()['cnt'] ?? 0);
}

function db_user_log_find(int $id): ?array
{
    $id = (int) $id;
    if ($id <= 0) {
        return null;
    }

    $stmt = db()->prepare("SELECT l.id, l.user_id, l.admin_id, l.action, l.description, l.ip_address, l.user_agent, l.metadata, l.created_at,
                   u.full_name AS user_name, u.username AS user_username,
                   a.full_name AS admin_name, a.username AS admin_username
            FROM user_logs l
            LEFT JOIN users u ON u.id = l.user_id
            LEFT JOIN admins a ON a.id = l.admin_id
            WHERE l.id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);

    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function db_conversation_find_by_id(int $conversationId): ?array
{
    $conversationId = (int) $conversationId;
    if ($conversationId <= 0) {
        return null;
    }
    $stmt = db()->prepare('SELECT id, user_id, status, created_at, updated_at FROM conversations WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $conversationId]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function db_admin_delete_conversation(int $conversationId): void
{
    $conversationId = (int) $conversationId;
    if ($conversationId <= 0) {
        throw new RuntimeException('Conversation tidak valid.');
    }
    $stmt = db()->prepare('DELETE FROM conversations WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $conversationId]);
    if ($stmt->rowCount() < 1) {
        throw new RuntimeException('Conversation tidak ditemukan.');
    }
}

function is_admin_user(?array $user = null): bool
{
    if (!is_array($user)) {
        // if no explicit user given, check authenticated admin session
        $user = auth_admin();
    }
    if (!is_array($user)) {
        return false;
    }

    $userId = (int) ($user['id'] ?? 0);
    if ($userId <= 0) {
        return false;
    }

    try {
        static $cache = [];
        if (array_key_exists($userId, $cache)) {
            return $cache[$userId];
        }

        $stmt = db()->prepare('SELECT 1 FROM admins WHERE id = :uid LIMIT 1');
        $stmt->execute(['uid' => $userId]);
        $res = (bool) $stmt->fetchColumn();
        $cache[$userId] = $res;
        return $res;
    } catch (Throwable $e) {
        return false;
    }
}

// -------------------------------------------------------
// Admin user table helpers (new system)
// -------------------------------------------------------
function db_admin_list(int $limit = 100): array
{
    $stmt = db()->prepare('SELECT id, username, email, full_name, created_at FROM admins ORDER BY created_at DESC LIMIT :lim');
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function db_admin_create(string $username, string $email, string $fullName, string $passwordHash): int
{
    $stmt = db()->prepare('INSERT INTO admins (username, email, full_name, password_hash) VALUES (:u, :e, :n, :p)');
    $stmt->execute(['u' => $username, 'e' => $email, 'n' => $fullName, 'p' => $passwordHash]);
    return (int) db()->lastInsertId();
}

function db_admin_delete(int $adminId): void
{
    $stmt = db()->prepare('DELETE FROM admins WHERE id = :id');
    $stmt->execute(['id' => $adminId]);
}

function db_admin_find_for_login(string $identifier): ?array
{
    $identifier = trim($identifier);
    if ($identifier === '') {
        return null;
    }
    $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;
    if ($isEmail) {
        $stmt = db()->prepare('SELECT id, username, email, full_name, password_hash FROM admins WHERE email = :id LIMIT 1');
    } else {
        $stmt = db()->prepare('SELECT id, username, email, full_name, password_hash FROM admins WHERE username = BINARY :id LIMIT 1');
    }
    $stmt->execute(['id' => $identifier]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function is_admin_session(): bool
{
    return isset($_SESSION['admin_auth']) && $_SESSION['admin_auth'] === true;
}

function admin_next_url_from_request(): string
{
    $next = $_SERVER['REQUEST_URI'] ?? '';
    if (!is_string($next) || $next === '') {
        return '/public/admin/index.php';
    }

    $pos = strpos($next, '/public/admin/');
    if ($pos === false) {
        return '/public/admin/index.php';
    }

    $tail = substr($next, $pos);
    return ($tail === '') ? '/public/admin/index.php' : $tail;
}

function require_admin_auth(): array
{
    if (!is_admin_session() || !is_admin_user(null)) {
        redirect('/admin/login');
    }
    // return stored admin info
    return $_SESSION['auth_admin'] ?? [];
}

function get_admin_id(): int
{
    return (int) ($_SESSION['auth_admin']['id'] ?? 0);
}

function db_orders_by_user_id(int $userId, int $limit = 20): array
{
    $userId = (int) $userId;
    if ($userId <= 0) {
        return [];
    }

    $limit = (int) $limit;
    if ($limit < 1) {
        $limit = 1;
    }
    if ($limit > 100) {
        $limit = 100;
    }

    $selectHandlingFee = db_has_column('orders', 'handling_fee') ? ', handling_fee' : '';

    $sql = 'SELECT id, user_id, total_amount, status, payment_method, shipping_method, shipping_fee, shipping_address, created_at'
        . $selectHandlingFee
        . ' FROM orders WHERE user_id = :user_id ORDER BY id DESC LIMIT ' . $limit;
    $stmt = db()->prepare($sql);
    $stmt->execute(['user_id' => $userId]);
    $rows = $stmt->fetchAll();

    $orders = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $orders[] = [
            'id' => (int) ($row['id'] ?? 0),
            'user_id' => (int) ($row['user_id'] ?? 0),
            'total_amount' => (int) ($row['total_amount'] ?? 0),
            'status' => (string) ($row['status'] ?? ''),
            'payment_method' => $row['payment_method'] === null ? null : (string) $row['payment_method'],
            'shipping_method' => $row['shipping_method'] === null ? null : (string) $row['shipping_method'],
            'shipping_fee' => (int) ($row['shipping_fee'] ?? 0),
            'shipping_address' => $row['shipping_address'] === null ? null : (string) $row['shipping_address'],
            'handling_fee' => isset($row['handling_fee']) ? (int) ($row['handling_fee'] ?? 0) : 0,
            'created_at' => (string) ($row['created_at'] ?? ''),
        ];
    }

    return $orders;
}

function db_order_items_by_order_ids(array $orderIds): array
{
    $ids = [];
    foreach ($orderIds as $id) {
        $id = (int) $id;
        if ($id > 0) {
            $ids[] = $id;
        }
    }
    $ids = array_values(array_unique($ids));
    if (!$ids) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = 'SELECT order_id, product_id, product_name, unit_price, qty, line_total'
        . ' FROM order_items WHERE order_id IN (' . $placeholders . ') ORDER BY id ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();

    $byOrder = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $orderId = (int) ($row['order_id'] ?? 0);
        if ($orderId <= 0) {
            continue;
        }
        if (!isset($byOrder[$orderId])) {
            $byOrder[$orderId] = [];
        }
        $byOrder[$orderId][] = [
            'product_id' => isset($row['product_id']) ? (int) ($row['product_id'] ?? 0) : 0,
            'product_name' => (string) ($row['product_name'] ?? ''),
            'unit_price' => (int) ($row['unit_price'] ?? 0),
            'qty' => (int) ($row['qty'] ?? 0),
            'line_total' => (int) ($row['line_total'] ?? 0),
        ];
    }

    return $byOrder;
}

function db_order_shipments_by_order_ids(array $orderIds): array
{
    $ids = [];
    foreach ($orderIds as $id) {
        $id = (int) $id;
        if ($id > 0) {
            $ids[] = $id;
        }
    }
    $ids = array_values(array_unique($ids));
    if (!$ids) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = 'SELECT order_id, carrier, service, tracking_number FROM order_shipments WHERE order_id IN (' . $placeholders . ')';
    $stmt = db()->prepare($sql);
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();

    $byOrder = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $orderId = (int) ($row['order_id'] ?? 0);
        if ($orderId <= 0) {
            continue;
        }
        $byOrder[$orderId] = [
            'order_id' => $orderId,
            'carrier' => $row['carrier'] === null ? '' : (string) $row['carrier'],
            'service' => $row['service'] === null ? '' : (string) $row['service'],
            'tracking_number' => $row['tracking_number'] === null ? '' : (string) $row['tracking_number'],
        ];
    }

    return $byOrder;
}

function db_order_tracking_events_by_order_ids(array $orderIds, int $limitPerOrder = 50): array
{
    $ids = [];
    foreach ($orderIds as $id) {
        $id = (int) $id;
        if ($id > 0) {
            $ids[] = $id;
        }
    }
    $ids = array_values(array_unique($ids));
    if (!$ids) {
        return [];
    }

    $limitPerOrder = (int) $limitPerOrder;
    if ($limitPerOrder < 1) {
        $limitPerOrder = 1;
    }
    if ($limitPerOrder > 200) {
        $limitPerOrder = 200;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = 'SELECT order_id, occurred_at, title, description'
        . ' FROM order_tracking_events WHERE order_id IN (' . $placeholders . ')'
        . ' ORDER BY order_id ASC, occurred_at DESC, id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();

    $byOrder = [];
    $counts = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $orderId = (int) ($row['order_id'] ?? 0);
        if ($orderId <= 0) {
            continue;
        }
        $counts[$orderId] = (int) ($counts[$orderId] ?? 0);
        if ($counts[$orderId] >= $limitPerOrder) {
            continue;
        }
        $counts[$orderId]++;

        if (!isset($byOrder[$orderId])) {
            $byOrder[$orderId] = [];
        }
        $byOrder[$orderId][] = [
            'occurred_at' => (string) ($row['occurred_at'] ?? ''),
            'title' => (string) ($row['title'] ?? ''),
            'description' => $row['description'] === null ? '' : (string) $row['description'],
        ];
    }

    return $byOrder;
}

function db_user_addresses_by_user_id(int $userId): array
{
    $userId = (int) $userId;
    if ($userId <= 0) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT id, user_id, label, recipient_name, phone, region, street, detail, is_primary, created_at'
        . ' FROM user_addresses WHERE user_id = :user_id'
        . ' ORDER BY is_primary DESC, id DESC'
    );
    $stmt->execute(['user_id' => $userId]);
    $rows = $stmt->fetchAll();

    $items = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $items[] = [
            'id' => (int) ($row['id'] ?? 0),
            'user_id' => (int) ($row['user_id'] ?? 0),
            'label' => (string) ($row['label'] ?? 'home'),
            'recipient_name' => (string) ($row['recipient_name'] ?? ''),
            'phone' => (string) ($row['phone'] ?? ''),
            'region' => (string) ($row['region'] ?? ''),
            'street' => (string) ($row['street'] ?? ''),
            'detail' => ($row['detail'] === null) ? '' : (string) $row['detail'],
            'is_primary' => ((int) ($row['is_primary'] ?? 0)) === 1,
            'created_at' => (string) ($row['created_at'] ?? ''),
        ];
    }

    return $items;
}

function db_user_address_save(int $userId, array $data): int
{
    $userId = (int) $userId;
    if ($userId <= 0) {
        throw new RuntimeException('User tidak valid.');
    }

    $addressId = (int) ($data['id'] ?? 0);

    $label = strtolower(trim((string) ($data['label'] ?? 'home')));
    if ($label !== 'home' && $label !== 'office') {
        $label = 'home';
    }

    $recipientName = trim((string) ($data['recipient_name'] ?? ''));
    $phone = trim((string) ($data['phone'] ?? ''));
    $region = trim((string) ($data['region'] ?? ''));
    $street = trim((string) ($data['street'] ?? ''));
    $detail = trim((string) ($data['detail'] ?? ''));
    $isPrimary = (bool) ($data['is_primary'] ?? false);

    if ($recipientName === '' || mb_strlen($recipientName) < 3) {
        throw new RuntimeException('Nama lengkap minimal 3 karakter.');
    }
    if ($phone === '' || mb_strlen($phone) < 8) {
        throw new RuntimeException('Nomor telepon tidak valid.');
    }
    if ($region === '' || mb_strlen($region) < 3) {
        throw new RuntimeException('Provinsi/Kota/Kecamatan wajib diisi.');
    }
    if ($street === '' || mb_strlen($street) < 3) {
        throw new RuntimeException('Alamat jalan wajib diisi.');
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        // If no primary exists yet, force primary for the first address.
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM user_addresses WHERE user_id = :user_id AND is_primary = 1');
        $stmt->execute(['user_id' => $userId]);
        $hasPrimary = ((int) $stmt->fetchColumn()) > 0;
        if (!$hasPrimary) {
            $isPrimary = true;
        }

        if ($isPrimary) {
            $stmt = $pdo->prepare('UPDATE user_addresses SET is_primary = 0 WHERE user_id = :user_id');
            $stmt->execute(['user_id' => $userId]);
        }

        if ($addressId > 0) {
            $stmt = $pdo->prepare(
                'UPDATE user_addresses'
                . ' SET label = :label, recipient_name = :recipient_name, phone = :phone, region = :region, street = :street, detail = :detail, is_primary = :is_primary'
                . ' WHERE id = :id AND user_id = :user_id'
            );
            $stmt->execute([
                'label' => $label,
                'recipient_name' => $recipientName,
                'phone' => $phone,
                'region' => $region,
                'street' => $street,
                'detail' => ($detail === '' ? null : $detail),
                'is_primary' => $isPrimary ? 1 : 0,
                'id' => $addressId,
                'user_id' => $userId,
            ]);

            if ($stmt->rowCount() < 1) {
                throw new RuntimeException('Alamat tidak ditemukan.');
            }

            $pdo->commit();
            return $addressId;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO user_addresses (user_id, label, recipient_name, phone, region, street, detail, is_primary)'
            . ' VALUES (:user_id, :label, :recipient_name, :phone, :region, :street, :detail, :is_primary)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'label' => $label,
            'recipient_name' => $recipientName,
            'phone' => $phone,
            'region' => $region,
            'street' => $street,
            'detail' => ($detail === '' ? null : $detail),
            'is_primary' => $isPrimary ? 1 : 0,
        ]);

        $newId = (int) $pdo->lastInsertId();
        $pdo->commit();
        return $newId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function db_user_address_delete(int $userId, int $addressId): void
{
    $userId = (int) $userId;
    $addressId = (int) $addressId;
    if ($userId <= 0 || $addressId <= 0) {
        throw new RuntimeException('Alamat tidak valid.');
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT is_primary FROM user_addresses WHERE id = :id AND user_id = :user_id LIMIT 1');
        $stmt->execute(['id' => $addressId, 'user_id' => $userId]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            throw new RuntimeException('Alamat tidak ditemukan.');
        }
        $wasPrimary = ((int) ($row['is_primary'] ?? 0)) === 1;

        $stmt = $pdo->prepare('DELETE FROM user_addresses WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $addressId, 'user_id' => $userId]);

        if ($wasPrimary) {
            $stmt = $pdo->prepare('SELECT id FROM user_addresses WHERE user_id = :user_id ORDER BY id DESC LIMIT 1');
            $stmt->execute(['user_id' => $userId]);
            $nextId = (int) ($stmt->fetchColumn() ?: 0);
            if ($nextId > 0) {
                $stmt = $pdo->prepare('UPDATE user_addresses SET is_primary = 1 WHERE id = :id AND user_id = :user_id');
                $stmt->execute(['id' => $nextId, 'user_id' => $userId]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function db_user_address_set_primary(int $userId, int $addressId): void
{
    $userId = (int) $userId;
    $addressId = (int) $addressId;
    if ($userId <= 0 || $addressId <= 0) {
        throw new RuntimeException('Alamat tidak valid.');
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('UPDATE user_addresses SET is_primary = 0 WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);

        $stmt = $pdo->prepare('UPDATE user_addresses SET is_primary = 1 WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $addressId, 'user_id' => $userId]);
        if ($stmt->rowCount() < 1) {
            throw new RuntimeException('Alamat tidak ditemukan.');
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function db_user_primary_address_by_user_id(int $userId): ?array
{
    $userId = (int) $userId;
    if ($userId <= 0) {
        return null;
    }

    $stmt = db()->prepare(
        'SELECT id, user_id, label, recipient_name, phone, region, street, detail, is_primary'
        . ' FROM user_addresses WHERE user_id = :user_id AND is_primary = 1'
        . ' ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute(['user_id' => $userId]);
    $row = $stmt->fetch();
    if (!is_array($row)) {
        return null;
    }

    return [
        'id' => (int) ($row['id'] ?? 0),
        'user_id' => (int) ($row['user_id'] ?? 0),
        'label' => (string) ($row['label'] ?? 'home'),
        'recipient_name' => (string) ($row['recipient_name'] ?? ''),
        'phone' => (string) ($row['phone'] ?? ''),
        'region' => (string) ($row['region'] ?? ''),
        'street' => (string) ($row['street'] ?? ''),
        'detail' => ($row['detail'] === null) ? '' : (string) $row['detail'],
        'is_primary' => ((int) ($row['is_primary'] ?? 0)) === 1,
    ];
}

function format_shipping_address(array $address): string
{
    $recipient = trim((string) ($address['recipient_name'] ?? ''));
    $phone = trim((string) ($address['phone'] ?? ''));
    $street = trim((string) ($address['street'] ?? ''));
    $region = trim((string) ($address['region'] ?? ''));
    $detail = trim((string) ($address['detail'] ?? ''));

    $lines = [];
    $firstLine = trim($recipient . ($phone !== '' ? ' (' . $phone . ')' : ''));
    if ($firstLine !== '') {
        $lines[] = $firstLine;
    }
    if ($street !== '') {
        $lines[] = $street;
    }
    if ($region !== '') {
        $lines[] = $region;
    }
    if ($detail !== '') {
        $lines[] = $detail;
    }

    return trim(implode("\n", $lines));
}

function view(string $name, array $data = []): void
{
    $viewFile = base_path('views/' . $name . '.php');
    if (!is_file($viewFile)) {
        http_response_code(500);
        echo 'View not found: ' . e($name);
        return;
    }

    extract($data, EXTR_SKIP);
    require $viewFile;
}

function db_products_all(bool $onlyActive = true): array
{
    $supportsWeight = db_has_column('products', 'weight_grams');
    $weightSelect = $supportsWeight ? 'p.weight_grams' : '0';

    $sql = 'SELECT p.id, p.name, c.name as category, p.price, ' . $weightSelect . ' as weight_grams, p.stock, p.badge, p.description 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id';
    $params = [];

    if ($onlyActive) {
        $sql .= ' WHERE p.is_active = :active';
        $params['active'] = 1;
    }

    // sort_order column removed; just order by newest first
    $sql .= ' ORDER BY p.id DESC';

    try {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        http_response_code(500);
        echo 'Failed to load products from database. Please import database/schema.sql.';
        exit;
    }

    $items = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $items[] = [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'category' => (string) ($row['category'] ?? ''),
            'price' => (int) ($row['price'] ?? 0),
            'stock' => (int) ($row['stock'] ?? 0),
            'weight_grams' => (int) ($row['weight_grams'] ?? 0),
            'badge' => ($row['badge'] === null) ? null : (string) $row['badge'],
            'desc' => (string) ($row['description'] ?? ''),
        ];
    }

    return $items;
}

function db_products_by_ids(array $productIds, bool $onlyActive = true): array
{
    $supportsWeight = db_has_column('products', 'weight_grams');
    $weightSelect = $supportsWeight ? 'p.weight_grams' : '0';

    $ids = [];
    foreach ($productIds as $id) {
        $id = (int) $id;
        if ($id > 0) {
            $ids[] = $id;
        }
    }
    $ids = array_values(array_unique($ids));
    if (!$ids) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = 'SELECT p.id, p.name, c.name as category, p.price, ' . $weightSelect . ' as weight_grams, p.stock, p.badge, p.description, p.is_active 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id IN (' . $placeholders . ')';
    if ($onlyActive) {
        $sql .= ' AND p.is_active = 1';
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();

    $byId = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $pid = (int) ($row['id'] ?? 0);
        if ($pid <= 0) {
            continue;
        }
        $byId[$pid] = [
            'id' => $pid,
            'name' => (string) ($row['name'] ?? ''),
            'category' => (string) ($row['category'] ?? ''),
            'price' => (int) ($row['price'] ?? 0),
            'weight_grams' => (int) ($row['weight_grams'] ?? 0),
            'stock' => (int) ($row['stock'] ?? 0),
            'badge' => ($row['badge'] === null) ? null : (string) $row['badge'],
            'desc' => (string) ($row['description'] ?? ''),
        ];
    }

    return $byId;
}

function cart_add(int $productId, int $qty = 1): void
{
    if ($productId <= 0) {
        return;
    }

    if ($qty < 1) {
        $qty = 1;
    }

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $current = (int) ($_SESSION['cart'][$productId] ?? 0);
    $_SESSION['cart'][$productId] = $current + $qty;

    // If user already has an explicit selection set, auto-include newly added items.
    if (isset($_SESSION['cart_selected']) && is_array($_SESSION['cart_selected'])) {
        $sel = cart_selected_ids_raw();
        $sel[] = $productId;
        cart_selected_set_ids($sel);
    }
}

function buy_now_set(int $productId, int $qty = 1): void
{
    $productId = (int) $productId;
    $qty = (int) $qty;
    if ($productId <= 0) {
        return;
    }
    if ($qty < 1) {
        $qty = 1;
    }

    $_SESSION['buy_now'] = [
        'product_id' => $productId,
        'qty' => $qty,
        'created_at' => time(),
    ];
}

function buy_now_get_raw(): ?array
{
    $raw = $_SESSION['buy_now'] ?? null;
    return is_array($raw) ? $raw : null;
}

function buy_now_clear(): void
{
    unset($_SESSION['buy_now']);
}

function buy_now_get_items(): array
{
    $raw = buy_now_get_raw();
    if (!$raw) {
        return [];
    }

    $pid = (int) ($raw['product_id'] ?? 0);
    $qty = (int) ($raw['qty'] ?? 0);
    if ($pid <= 0 || $qty <= 0) {
        return [];
    }

    $productsById = db_products_by_ids([$pid], true);
    $p = $productsById[$pid] ?? null;
    if (!$p) {
        return [];
    }

    $unitPrice = (int) ($p['price'] ?? 0);
    return [[
        'product' => $p,
        'qty' => $qty,
        'unit_price' => $unitPrice,
        'line_total' => $unitPrice * $qty,
    ]];
}

function cart_clear(): void
{
    unset($_SESSION['cart']);
    unset($_SESSION['cart_selected']);
}

function cart_set_qty(int $productId, int $qty): void
{
    if ($productId <= 0) {
        return;
    }

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $qty = max(0, (int)$qty);
    if ($qty === 0) {
        unset($_SESSION['cart'][$productId]);
        cart_selected_remove_id($productId);
        return;
    }

    $_SESSION['cart'][$productId] = $qty;
}

function cart_remove(int $productId): void
{
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        unset($_SESSION['cart'][$productId]);
    }

    cart_selected_remove_id($productId);
}

function cart_selected_ids_raw(): array
{
    $sel = $_SESSION['cart_selected'] ?? null;
    return is_array($sel) ? $sel : [];
}

function cart_selected_set_ids(array $productIds): void
{
    $ids = [];
    foreach ($productIds as $id) {
        $id = (int) $id;
        if ($id > 0) {
            $ids[] = $id;
        }
    }
    $ids = array_values(array_unique($ids));

    // Keep only items that are still in cart.
    $cartIds = array_map('intval', array_keys(cart_get_raw()));
    $cartIdMap = array_fill_keys($cartIds, true);

    $filtered = [];
    foreach ($ids as $id) {
        if (isset($cartIdMap[$id])) {
            $filtered[] = $id;
        }
    }

    $_SESSION['cart_selected'] = $filtered;
}

function cart_selected_remove_id(int $productId): void
{
    $productId = (int) $productId;
    if ($productId <= 0) {
        return;
    }
    if (!isset($_SESSION['cart_selected']) || !is_array($_SESSION['cart_selected'])) {
        return;
    }

    $sel = [];
    foreach ($_SESSION['cart_selected'] as $id) {
        $id = (int) $id;
        if ($id > 0 && $id !== $productId) {
            $sel[] = $id;
        }
    }
    $_SESSION['cart_selected'] = array_values(array_unique($sel));
}

function cart_selected_sync_with_cart(): void
{
    if (!isset($_SESSION['cart_selected']) || !is_array($_SESSION['cart_selected'])) {
        return;
    }
    cart_selected_set_ids($_SESSION['cart_selected']);
}

function cart_selected_get_ids(): array
{
    $rawCart = cart_get_raw();
    $cartIds = array_map('intval', array_keys($rawCart));

    // If no explicit selection exists, default to "all items in cart".
    if (!isset($_SESSION['cart_selected'])) {
        return $cartIds;
    }

    $sel = cart_selected_ids_raw();
    cart_selected_sync_with_cart();
    return array_values(array_unique(array_map('intval', $sel)));
}

function cart_get_selected_items(): array
{
    $items = cart_get_items();
    $selectedIds = cart_selected_get_ids();
    $selectedMap = array_fill_keys($selectedIds, true);

    $filtered = [];
    foreach ($items as $it) {
        $p = $it['product'] ?? null;
        $pid = is_array($p) ? (int) ($p['id'] ?? 0) : 0;
        if ($pid > 0 && isset($selectedMap[$pid])) {
            $filtered[] = $it;
        }
    }
    return $filtered;
}

function cart_count(): int
{
    $raw = cart_get_raw();
    $count = 0;
    foreach ($raw as $qty) {
        $count += (int)$qty;
    }
    return $count;
}

function cart_get_raw(): array
{
    $cart = $_SESSION['cart'] ?? [];
    return is_array($cart) ? $cart : [];
}

function cart_get_items(): array
{
    $raw = cart_get_raw();
    $productIds = array_keys($raw);
    $productsById = db_products_by_ids($productIds, true);

    $items = [];
    foreach ($raw as $pid => $qty) {
        $pid = (int) $pid;
        $qty = (int) $qty;
        if ($pid <= 0 || $qty <= 0) {
            continue;
        }
        if (!isset($productsById[$pid])) {
            continue;
        }
        $p = $productsById[$pid];
        $unitPrice = (int) ($p['price'] ?? 0);
        $items[] = [
            'product' => $p,
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'line_total' => $unitPrice * $qty,
        ];
    }

    return $items;
}

function cart_total_amount(array $cartItems): int
{
    $total = 0;
    foreach ($cartItems as $it) {
        if (!is_array($it)) {
            continue;
        }
        $total += (int) ($it['line_total'] ?? 0);
    }
    return max(0, $total);
}

function payment_methods(): array
{
    return [
        'qr' => 'Pembayaran via QR',
    ];
}

function shipping_options(): array
{
    return [
        'standard' => ['label' => 'Standar', 'fee' => 10000],
    ];
}

function handling_fee(): int
{
    $stmt = db()->prepare('SELECT value FROM settings WHERE key_name = :key LIMIT 1');
    $stmt->execute(['key' => 'handling_fee']);
    $value = $stmt->fetchColumn();
    return is_numeric($value) ? (int) $value : 2000;
}

function db_setting_get(string $key): ?string
{
    $stmt = db()->prepare('SELECT value FROM settings WHERE key_name = :k LIMIT 1');
    $stmt->execute(['k' => $key]);
    return $stmt->fetchColumn() ?: null;
}

function db_setting_set(string $key, string $value): void
{
    $stmt = db()->prepare('INSERT INTO settings (key_name, value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE value = VALUES(value)');
    $stmt->execute(['k' => $key, 'v' => $value]);
}

function order_status_normalize(?string $status): string
{
    $s = strtolower(trim((string) ($status ?? '')));
    if ($s === '') {
        return 'awaiting_payment';
    }

    $aliases = [
        // awaiting payment
        'placed' => 'awaiting_payment',
        'pending' => 'awaiting_payment',
        'unpaid' => 'awaiting_payment',
        'waiting_payment' => 'awaiting_payment',
        'menunggu_pembayaran' => 'awaiting_payment',
        'menunggu pembayaran' => 'awaiting_payment',

        // waiting admin confirmation
        'payment_review' => 'payment_review',
        'review' => 'payment_review',
        'verifying' => 'payment_review',
        'pending_verification' => 'payment_review',
        'menunggu_konfirmasi' => 'payment_review',
        'menunggu konfirmasi' => 'payment_review',
        'menunggu_verifikasi' => 'payment_review',
        'menunggu verifikasi' => 'payment_review',

        // paid
        'dibayar' => 'paid',
        'payment_received' => 'paid',
        'payment_confirmed' => 'paid',
        'payment_submitted' => 'paid',

        // processing
        'diproses' => 'processing',
        'packing' => 'processing',
        'packed' => 'processing',

        // shipped
        'dikirim' => 'shipped',
        'shipping' => 'shipped',
        'in_transit' => 'shipped',
        'in-transit' => 'shipped',

        // delivered
        'completed' => 'delivered',
        'selesai' => 'delivered',
        'done' => 'delivered',

        // cancelled
        'canceled' => 'cancelled',
        'dibatalkan' => 'cancelled',
        'batal' => 'cancelled',
        'void' => 'cancelled',
    ];

    return $aliases[$s] ?? $s;
}

function order_status_label(?string $status): string
{
    $key = order_status_normalize($status);

    return match ($key) {
        'cancelled' => 'Dibatalkan',
        'payment_review' => 'Menunggu Konfirmasi',
        'paid' => 'Dibayar',
        'processing' => 'Diproses',
        'shipped' => 'Dikirim',
        'delivered' => 'Selesai',
        default => 'Menunggu Pembayaran',
    };
}

function order_status_badge_class(?string $status): string
{
    $key = order_status_normalize($status);

    return match ($key) {
        'cancelled' => 'border-rose-200 bg-rose-50 text-rose-800',
        'paid' => 'border-green-200 bg-green-50 text-green-800',
        'shipped' => 'border-blue-200 bg-blue-50 text-blue-800',
        'delivered' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        // keep processing/payment review visually neutral
        'processing' => 'border-gray-200 bg-gray-50 text-gray-700',
        'payment_review' => 'border-gray-200 bg-gray-50 text-gray-700',
        default => 'border-gray-200 bg-gray-50 text-gray-700',
    };
}

function order_status_is_unpaid(?string $status): bool
{
    return order_status_normalize($status) === 'awaiting_payment';
}

function db_order_add_tracking_event(int $orderId, string $title, ?string $description = null, ?string $occurredAt = null): void
{
    $orderId = (int) $orderId;
    $title = trim($title);
    $description = $description !== null ? trim($description) : null;
    $occurredAt = $occurredAt !== null ? trim($occurredAt) : '';

    if ($orderId <= 0 || $title === '') {
        return;
    }
    try {
        if ($occurredAt === '') {
            // Use DB time to stay consistent with orders.created_at timezone.
            $stmt = db()->prepare('INSERT INTO order_tracking_events (order_id, occurred_at, title, description) VALUES (:order_id, NOW(), :title, :description)');
            $stmt->execute([
                'order_id' => $orderId,
                'title' => $title,
                'description' => ($description === '' ? null : $description),
            ]);
        } else {
            $stmt = db()->prepare('INSERT INTO order_tracking_events (order_id, occurred_at, title, description) VALUES (:order_id, :occurred_at, :title, :description)');
            $stmt->execute([
                'order_id' => $orderId,
                'occurred_at' => $occurredAt,
                'title' => $title,
                'description' => ($description === '' ? null : $description),
            ]);
        }
    } catch (Throwable $e) {
        // ignore if table doesn't exist yet
    }
}

function db_admin_set_order_status(int $orderId, string $status, ?string $paymentMethod = null): void
{
    $orderId = (int) $orderId;
    if ($orderId <= 0) {
        throw new RuntimeException('Order tidak valid.');
    }

    $status = order_status_normalize($status);

    $params = [
        'id' => $orderId,
        'status' => $status,
    ];
    $setParts = ['status = :status'];

    if ($paymentMethod !== null) {
        $setParts[] = 'payment_method = :payment_method';
        $params['payment_method'] = $paymentMethod;
    }

    $sql = 'UPDATE orders SET ' . implode(', ', $setParts) . ' WHERE id = :id LIMIT 1';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    if ($stmt->rowCount() < 1) {
        throw new RuntimeException('Order tidak ditemukan.');
    }
}

function db_order_find_by_id(int $orderId): ?array
{
    $orderId = (int) $orderId;
    if ($orderId <= 0) {
        return null;
    }

    $selectHandlingFee = db_has_column('orders', 'handling_fee') ? ', handling_fee' : '';
    $selectPaymentProof = db_has_column('orders', 'payment_proof') ? ', payment_proof' : '';

    $sql = 'SELECT id, user_id, total_amount, status, payment_method, shipping_method, shipping_fee, shipping_address, created_at'
        . $selectHandlingFee
        . $selectPaymentProof
        . ' FROM orders WHERE id = :id LIMIT 1';
    $stmt = db()->prepare($sql);
    $stmt->execute(['id' => $orderId]);
    $row = $stmt->fetch();
    if (!is_array($row)) {
        return null;
    }

    return [
        'id' => (int) ($row['id'] ?? 0),
        'user_id' => (int) ($row['user_id'] ?? 0),
        'total_amount' => (int) ($row['total_amount'] ?? 0),
        'status' => (string) ($row['status'] ?? ''),
        'payment_method' => $row['payment_method'] === null ? null : (string) $row['payment_method'],
        'shipping_method' => $row['shipping_method'] === null ? null : (string) $row['shipping_method'],
        'shipping_fee' => (int) ($row['shipping_fee'] ?? 0),
        'shipping_address' => $row['shipping_address'] === null ? null : (string) $row['shipping_address'],
        'created_at' => (string) ($row['created_at'] ?? ''),
        'handling_fee' => isset($row['handling_fee']) ? (int) $row['handling_fee'] : 0,
        'payment_proof' => array_key_exists('payment_proof', $row) && $row['payment_proof'] !== null ? (string) $row['payment_proof'] : null,
    ];
}

function db_order_attach_payment_proof(int $orderId, int $userId, string $proofPath): void
{
    $orderId = (int) $orderId;
    $userId = (int) $userId;
    $proofPath = trim($proofPath);

    if ($orderId <= 0 || $userId <= 0) {
        throw new RuntimeException('Order tidak valid.');
    }
    if ($proofPath === '') {
        throw new RuntimeException('Bukti pembayaran kosong.');
    }

    $setParts = [];
    $params = [
        'id' => $orderId,
        'user_id' => $userId,
        'status' => 'payment_review',
        'payment_method' => 'qr',
    ];

    if (db_has_column('orders', 'payment_proof')) {
        $setParts[] = 'payment_proof = :payment_proof';
        $params['payment_proof'] = $proofPath;
    }

    // Mark payment as waiting admin confirmation after proof upload.
    $setParts[] = 'status = :status';
    $setParts[] = 'payment_method = :payment_method';

    $sql = 'UPDATE orders SET ' . implode(', ', $setParts) . ' WHERE id = :id AND user_id = :user_id LIMIT 1';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() < 1) {
        throw new RuntimeException('Order tidak ditemukan atau bukan milik kamu.');
    }

    // Keep only one latest proof-submitted event.
    try {
        $del = db()->prepare('DELETE FROM order_tracking_events WHERE order_id = :order_id AND title = :title');
        $del->execute([
            'order_id' => $orderId,
            'title' => 'Bukti pembayaran dikirim',
        ]);
    } catch (Throwable $e) {
        // ignore
    }

    db_order_add_tracking_event($orderId, 'Bukti pembayaran dikirim', 'Menunggu konfirmasi admin.');
}

function db_checkout_create_order(int $userId, array $cartItems, array $options = []): int
{
    if ($userId <= 0) {
        throw new RuntimeException('User not found.');
    }
    if (!$cartItems) {
        throw new RuntimeException('Cart is empty.');
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $total = 0;
        $preparedItems = [];

        foreach ($cartItems as $it) {
            $product = $it['product'] ?? null;
            $qty = (int) ($it['qty'] ?? 0);
            if (!is_array($product) || $qty <= 0) {
                continue;
            }

            $productId = (int) ($product['id'] ?? 0);
            if ($productId <= 0) {
                continue;
            }

            $stmt = $pdo->prepare('SELECT id, name, price, stock, is_active FROM products WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $productId]);
            $row = $stmt->fetch();
            if (!is_array($row) || (int) ($row['id'] ?? 0) <= 0) {
                throw new RuntimeException('Produk tidak ditemukan.');
            }
            if ((int) ($row['is_active'] ?? 0) !== 1) {
                throw new RuntimeException('Produk tidak aktif.');
            }

            $stock = (int) ($row['stock'] ?? 0);
            if ($stock < $qty) {
                throw new RuntimeException('Stok tidak cukup untuk: ' . (string) ($row['name'] ?? 'produk') . '.');
            }

            $unitPrice = (int) ($row['price'] ?? 0);
            $lineTotal = $unitPrice * $qty;
            $total += $lineTotal;

            $preparedItems[] = [
                'product_id' => (int) $row['id'],
                'product_name' => (string) ($row['name'] ?? ''),
                'unit_price' => $unitPrice,
                'qty' => $qty,
                'line_total' => $lineTotal,
            ];
        }

        if (!$preparedItems) {
            throw new RuntimeException('Cart kosong.');
        }

        // handle shipping & payment options
        $paymentMethod = is_string($options['payment_method'] ?? null) ? $options['payment_method'] : null;
        $shippingMethod = is_string($options['shipping_method'] ?? null) ? $options['shipping_method'] : null;
        $shippingFee = (int) ($options['shipping_fee'] ?? 0);
        $handlingFee = (int) ($options['handling_fee'] ?? 0);
        $shippingAddress = is_string($options['shipping_address'] ?? null) ? $options['shipping_address'] : null;

        $finalTotal = $total + max(0, $shippingFee) + max(0, $handlingFee);

        $cols = ['user_id', 'total_amount', 'status', 'payment_method', 'shipping_method', 'shipping_fee', 'shipping_address'];
        $vals = [':user_id', ':total_amount', ':status', ':payment_method', ':shipping_method', ':shipping_fee', ':shipping_address'];
        $params = [
            'user_id' => $userId,
            'total_amount' => $finalTotal,
            'status' => 'awaiting_payment',
            'payment_method' => $paymentMethod,
            'shipping_method' => $shippingMethod,
            'shipping_fee' => $shippingFee,
            'shipping_address' => $shippingAddress,
        ];

        if (db_has_column('orders', 'handling_fee')) {
            $cols[] = 'handling_fee';
            $vals[] = ':handling_fee';
            $params['handling_fee'] = max(0, $handlingFee);
        }

        $stmt = $pdo->prepare('INSERT INTO orders (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')');
        $stmt->execute($params);

        $orderId = (int) $pdo->lastInsertId();
        if ($orderId <= 0) {
            throw new RuntimeException('Gagal membuat order.');
        }

        $itemStmt = $pdo->prepare(
            'INSERT INTO order_items (order_id, product_id, product_name, unit_price, qty, line_total) VALUES (:order_id, :product_id, :product_name, :unit_price, :qty, :line_total)'
        );
        $stockStmt = $pdo->prepare('UPDATE products SET stock = stock - :qty WHERE id = :id');

        foreach ($preparedItems as $pi) {
            $itemStmt->execute([
                'order_id' => $orderId,
                'product_id' => $pi['product_id'],
                'product_name' => $pi['product_name'],
                'unit_price' => $pi['unit_price'],
                'qty' => $pi['qty'],
                'line_total' => $pi['line_total'],
            ]);
            $stockStmt->execute(['qty' => $pi['qty'], 'id' => $pi['product_id']]);
        }

        // Tracking: order created (use DB time)
        try {
            $tStmt = $pdo->prepare('INSERT INTO order_tracking_events (order_id, occurred_at, title, description) VALUES (:order_id, NOW(), :title, :description)');
            $tStmt->execute([
                'order_id' => $orderId,
                'title' => 'Pesanan dibuat',
                'description' => null,
            ]);
        } catch (Throwable $e) {
            // ignore if table doesn't exist yet
        }

        // If shipping fee > 0 and we want to record it as a separate line, we could add an order_item of shipping here.
        // For now shipping_fee is stored in orders.total_amount and shipping_fee column.

        $pdo->commit();
        return $orderId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Create an order for virtual items (non-product items like plans).
 * Each item must be an array with keys: product_name, unit_price, qty, line_total
 */
function db_checkout_create_virtual_order(int $userId, array $virtualItems, array $options = []): int
{
    if ($userId <= 0) {
        throw new RuntimeException('User not found.');
    }
    if (!$virtualItems) {
        throw new RuntimeException('Items kosong.');
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $total = 0;
        foreach ($virtualItems as $it) {
            $qty = (int) ($it['qty'] ?? 0);
            $unitPrice = (int) ($it['unit_price'] ?? 0);
            if ($qty <= 0 || $unitPrice < 0) continue;
            $line = $unitPrice * $qty;
            $total += $line;
        }
        if ($total < 0) $total = 0;

        $paymentMethod = is_string($options['payment_method'] ?? null) ? $options['payment_method'] : null;
        $shippingMethod = is_string($options['shipping_method'] ?? null) ? $options['shipping_method'] : null;
        $shippingFee = (int) ($options['shipping_fee'] ?? 0);
        $handlingFee = (int) ($options['handling_fee'] ?? 0);
        $shippingAddress = is_string($options['shipping_address'] ?? null) ? $options['shipping_address'] : null;

        $finalTotal = $total + max(0, $shippingFee) + max(0, $handlingFee);

        $cols = ['user_id', 'total_amount', 'status', 'payment_method', 'shipping_method', 'shipping_fee', 'shipping_address'];
        $vals = [':user_id', ':total_amount', ':status', ':payment_method', ':shipping_method', ':shipping_fee', ':shipping_address'];
        $params = [
            'user_id' => $userId,
            'total_amount' => $finalTotal,
            'status' => 'awaiting_payment',
            'payment_method' => $paymentMethod,
            'shipping_method' => $shippingMethod,
            'shipping_fee' => $shippingFee,
            'shipping_address' => $shippingAddress,
        ];

        if (db_has_column('orders', 'handling_fee')) {
            $cols[] = 'handling_fee';
            $vals[] = ':handling_fee';
            $params['handling_fee'] = max(0, $handlingFee);
        }

        $stmt = $pdo->prepare('INSERT INTO orders (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')');
        $stmt->execute($params);
        $orderId = (int) $pdo->lastInsertId();
        if ($orderId <= 0) {
            throw new RuntimeException('Gagal membuat order.');
        }

        $itemStmt = $pdo->prepare(
            'INSERT INTO order_items (order_id, product_id, product_name, unit_price, qty, line_total) VALUES (:order_id, :product_id, :product_name, :unit_price, :qty, :line_total)'
        );

        foreach ($virtualItems as $it) {
            $qty = (int) ($it['qty'] ?? 0);
            $unitPrice = (int) ($it['unit_price'] ?? 0);
            $lineTotal = (int) ($unitPrice * $qty);
            $name = (string) ($it['product_name'] ?? ($it['name'] ?? 'Item'));

            $itemStmt->execute([
                'order_id' => $orderId,
                'product_id' => null,
                'product_name' => $name,
                'unit_price' => $unitPrice,
                'qty' => $qty,
                'line_total' => $lineTotal,
            ]);
        }

        try {
            $tStmt = $pdo->prepare('INSERT INTO order_tracking_events (order_id, occurred_at, title, description) VALUES (:order_id, NOW(), :title, :description)');
            $tStmt->execute([
                'order_id' => $orderId,
                'title' => 'Pesanan dibuat',
                'description' => null,
            ]);
        } catch (Throwable $e) {
            // ignore
        }

        $pdo->commit();
        return $orderId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
