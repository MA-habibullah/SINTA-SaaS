<?php

namespace App\Helpers;

use PDO;

/**
 * ErrorTracker — Global Error Handler & DB Logger
 *
 * Cara penggunaan (di index.php, sebelum routing):
 *   App\Helpers\ErrorTracker::register();
 *
 * Menangkap:
 *  1. Uncaught Exceptions   → set_exception_handler()
 *  2. PHP E_WARNING, E_NOTICE, dll → set_error_handler()
 *  3. Fatal Errors (E_ERROR) → register_shutdown_function()
 */
class ErrorTracker
{
    /** Status debug mode */
    public static bool $debug = false;

    /**
     * Daftarkan semua handler secara sekaligus.
     * Panggil satu kali di index.php paling atas.
     */
    public static function register(bool $debug = false): void
    {
        self::$debug = $debug;

        if ($debug) {
            // Aktifkan tampilan error ke layar user untuk debugging
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
        } else {
            // Matikan tampilan error ke layar user (HARUS di production)
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
        }
        // Tetap catat ke log file server (opsional untuk fallback)
        ini_set('log_errors', '1');
        error_reporting(E_ALL);

        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    /**
     * Handler 1: Uncaught Exceptions (termasuk PDOException, dll)
     */
    public static function handleException(\Throwable $e): void
    {
        $trace = self::formatTrace($e->getTrace());

        self::logToDatabase(
            level:   get_class($e),
            message: $e->getMessage(),
            file:    $e->getFile(),
            line:    $e->getLine(),
            trace:   $trace
        );

        if (self::$debug) {
            self::sendDebugResponse($e);
        } else {
            self::sendUserFriendlyResponse();
        }
    }

    /**
     * Handler 2: PHP non-fatal errors (E_WARNING, E_NOTICE, E_USER_ERROR, dll)
     */
    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        // Abaikan error yang di-suppress dengan @
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $levelMap = [
            E_ERROR             => 'E_ERROR',
            E_WARNING           => 'E_WARNING',
            E_NOTICE            => 'E_NOTICE',
            E_DEPRECATED        => 'E_DEPRECATED',
            E_USER_ERROR        => 'E_USER_ERROR',
            E_USER_WARNING      => 'E_USER_WARNING',
            E_USER_NOTICE       => 'E_USER_NOTICE',
            E_USER_DEPRECATED   => 'E_USER_DEPRECATED',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        ];

        $level = $levelMap[$errno] ?? "E_UNKNOWN({$errno})";

        self::logToDatabase(
            level:   $level,
            message: $errstr,
            file:    $errfile,
            line:    $errline,
            trace:   debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
        );

        if (self::$debug) {
            // Biarkan PHP menampilkan error di layar jika debug mode diaktifkan
            return false;
        }

        // Kembalikan true agar PHP tidak menjalankan internal error handler
        return true;
    }

    /**
     * Handler 3: Fatal errors (E_ERROR, E_PARSE, E_CORE_ERROR, dll) via shutdown
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error === null) {
            return;
        }

        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

        if (in_array($error['type'], $fatalTypes, true)) {
            self::logToDatabase(
                level:   'Fatal',
                message: $error['message'],
                file:    $error['file'],
                line:    $error['line'],
                trace:   [] // Fatal error tidak menyediakan backtrace
            );

            // Pastikan response dikirim
            if (!headers_sent()) {
                if (self::$debug) {
                    $e = new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
                    self::sendDebugResponse($e);
                } else {
                    self::sendUserFriendlyResponse();
                }
            }
        }
    }

    /**
     * Tampilkan detail error interaktif untuk mode pengembangan (seperti Whoops!)
     */
    private static function sendDebugResponse(\Throwable $e): void
    {
        http_response_code(500);
        
        $isApiRequest = (
            str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/SINTA-SaaS/api/') ||
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest' ||
            str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        );

        if ($isApiRequest) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error'   => $e->getMessage(),
                'type'    => get_class($e),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => self::sanitizeTrace($e->getTrace())
            ]);
            exit;
        }

        $errorClass = get_class($e);
        $message    = htmlspecialchars($e->getMessage());
        $file       = htmlspecialchars($e->getFile());
        $line       = $e->getLine();
        
        // Escape values for JavaScript execution
        $jsErrorClass = addslashes($errorClass);
        $jsMessage    = addslashes($e->getMessage());
        $jsFile       = addslashes($e->getFile());
        $jsLine       = $e->getLine();

        $traceHtml = '';
        foreach ($e->getTrace() as $i => $frame) {
            $frameFile = htmlspecialchars($frame['file'] ?? '[internal]');
            $frameLine = $frame['line'] ?? 0;
            $frameFunc = htmlspecialchars(($frame['class'] ?? '') . ($frame['type'] ?? '') . $frame['function']);
            $traceHtml .= "<div style='padding: 12px; border-bottom: 1px solid #334155; font-family: monospace; font-size: 13px;'>";
            $traceHtml .= "<span style='color: #64748b; margin-right: 8px;'>#{$i}</span> <strong>{$frameFunc}</strong><br>";
            $traceHtml .= "<span style='color: #94a3b8; font-size: 12px;'>{$frameFile}:{$frameLine}</span>";
            $traceHtml .= "</div>";
        }

        echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Whoops! Error Terdeteksi</title>
    <style>
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #0f172a; color: #f1f5f9; margin: 0; padding: 20px; }
        .wrapper { max-width: 1100px; margin: 40px auto; background: #1e293b; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); overflow: hidden; border: 1px solid #334155; }
        .header { background: #e11d48; padding: 30px; }
        .header h1 { margin: 0 0 10px 0; font-size: 24px; font-weight: 700; color: #ffffff; }
        .header h2 { margin: 0; font-size: 16px; font-weight: 500; color: #ffe4e6; font-family: monospace; line-height: 1.5; }
        .details { padding: 30px; }
        .file-info { background: #0f172a; padding: 15px; border-radius: 8px; border-left: 4px solid #f43f5e; margin-bottom: 25px; font-family: monospace; font-size: 14px; line-height: 1.6; }
        .trace-title { font-size: 18px; font-weight: 600; margin-bottom: 15px; border-bottom: 1px solid #334155; padding-bottom: 10px; color: #94a3b8; }
        .trace-container { background: #0f172a; border-radius: 8px; overflow: hidden; border: 1px solid #334155; max-height: 450px; overflow-y: auto; }
    </style>
</head>
<body>
    <script>
        console.error(
            '%c[PHP BACKEND ERROR] %c%s',
            'background: #e11d48; color: white; font-weight: bold; padding: 3px 6px; border-radius: 4px; font-size: 12px;',
            'color: #f43f5e; font-weight: bold; font-size: 13px;',
            '{$jsErrorClass}: {$jsMessage}'
        );
        console.error('File: {$jsFile}\\nLine: {$jsLine}');
    </script>
    <div class="wrapper">
        <div class="header">
            <h1>{$errorClass}</h1>
            <h2>{$message}</h2>
        </div>
        <div class="details">
            <div class="file-info">
                <strong>File:</strong> {$file}<br>
                <strong>Line:</strong> {$line}
            </div>
            <div class="trace-title">Stack Trace</div>
            <div class="trace-container">
                {$traceHtml}
            </div>
        </div>
    </div>
</body>
</html>
HTML;
        exit;
    }

    /**
     * Simpan error ke tabel `system_errors` via PDO.
     * Menggunakan koneksi raw PDO agar aman bahkan saat autoloader belum siap.
     */
    private static function logToDatabase(
        string  $level,
        string  $message,
        ?string $file,
        ?int    $line,
        mixed   $trace
    ): void {
        try {
            // Dapatkan koneksi PDO (bisa dari singleton Database atau raw PDO sebagai fallback)
            $db = \App\Config\Database::getConnection();

            // Ambil info request
            $requestUrl    = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                           . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
                           . ($_SERVER['REQUEST_URI'] ?? '/');
            $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
            $userAgent     = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            $ip = filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';

            // Ambil tenant_id dari session jika tersedia
            $tenantId = null;
            if (session_status() === PHP_SESSION_ACTIVE) {
                $tenantId = $_SESSION['tenant_id'] ?? null;
            }

            // Format trace ke JSON
            $traceJson = is_array($trace)
                ? json_encode(self::sanitizeTrace($trace), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                : json_encode([['raw' => (string)$trace]]);

            $stmt = $db->prepare("
                INSERT INTO `system_errors`
                    (`id`, `tenant_id`, `error_level`, `message`, `file`, `line`,
                     `trace`, `request_url`, `request_method`, `user_agent`, `ip_address`)
                VALUES
                    (UUID(), :tenant_id, :error_level, :message, :file, :line,
                     :trace, :request_url, :request_method, :user_agent, :ip_address)
            ");

            $stmt->execute([
                'tenant_id'      => $tenantId,
                'error_level'    => substr($level, 0, 50),
                'message'        => substr($message, 0, 65535),
                'file'           => $file ? substr($file, 0, 500) : null,
                'line'           => $line,
                'trace'          => $traceJson,
                'request_url'    => substr($requestUrl, 0, 1000),
                'request_method' => substr($requestMethod, 0, 10),
                'user_agent'     => $userAgent ?: null,
                'ip_address'     => $ip,
            ]);

        } catch (\Throwable $e) {
            // Fallback: Pastikan error logging tidak menyebabkan infinite loop
            // Tulis ke error log file server sebagai fallback terakhir
            error_log("[ErrorTracker] DB Log failed: " . $e->getMessage());
            error_log("[ErrorTracker] Original Error [{$level}]: {$message} in {$file}:{$line}");
        }
    }

    /**
     * Tampilkan pesan user-friendly ke browser/API client.
     */
    private static function sendUserFriendlyResponse(): void
    {
        http_response_code(500);

        $isApiRequest = (
            str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/SINTA-SaaS/api/') ||
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest' ||
            str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        );

        if ($isApiRequest) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error'   => 'Terjadi kesalahan sistem. Tim kami sedang menanganinya.',
                'code'    => 500,
                'success' => false,
            ]);
        } else {
            echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 — Kesalahan Sistem</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; display: flex;
               align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .container { text-align: center; padding: 2rem; max-width: 520px; }
        .icon { font-size: 4rem; margin-bottom: 1rem; }
        h1 { color: #1e293b; font-size: 1.75rem; margin-bottom: 0.5rem; }
        p  { color: #64748b; line-height: 1.6; margin-bottom: 1.5rem; }
        a  { display: inline-block; padding: 0.625rem 1.5rem; background: #2563eb;
             color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600; }
        a:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">⚙️</div>
        <h1>Terjadi Kesalahan Sistem</h1>
        <p>Tim kami sedang menangani masalah ini. Silakan coba beberapa saat lagi atau hubungi administrator platform.</p>
        <a href="/SINTA-SaaS/dashboard">Kembali ke Dashboard</a>
    </div>
</body>
</html>
HTML;
        }

        exit;
    }

    /**
     * Format exception trace menjadi array bersih.
     */
    private static function formatTrace(array $trace): array
    {
        return self::sanitizeTrace($trace);
    }

    /**
     * Hapus argumen besar dari trace untuk menghemat ruang di DB.
     */
    private static function sanitizeTrace(array $trace): array
    {
        $clean = [];
        foreach (array_slice($trace, 0, 25) as $frame) {
            $clean[] = [
                'file'     => $frame['file']     ?? '[internal]',
                'line'     => $frame['line']     ?? 0,
                'function' => $frame['function'] ?? '',
                'class'    => $frame['class']    ?? '',
                'type'     => $frame['type']     ?? '',
            ];
        }
        return $clean;
    }
}
