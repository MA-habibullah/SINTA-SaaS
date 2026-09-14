<?php

namespace Modules\Core\Services;

use Illuminate\Support\Str;

class SecurityPayloadService
{
    /**
     * Default sensitive keys to always strip recursively
     */
    protected static array $defaultBlacklist = [
        'password',
        'password_hash',
        'password_hash_legacy',
        'remember_token',
        'token',
        'api_token',
        'access_token',
        'refresh_token',
        'secret',
        'secret_key',
        'server_key',
        'snap_token',
        'private_key',
        'seed',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'auth_token',
        'session_key',
        'pin',
        'pin_code',
        'otp',
        'otp_code',
        'cvv',
    ];

    /**
     * Resolve encryption key from application key or custom key
     */
    protected static function resolveKey(?string $customKey = null): string
    {
        $appKey = $customKey ?: config('app.key');
        if (str_starts_with($appKey, 'base64:')) {
            $appKey = base64_decode(substr($appKey, 7));
        }
        return hash('sha256', $appKey, true); // 32 bytes binary key for AES-256
    }

    /**
     * Encrypt arbitrary data into a tamper-proof AES-256-CBC envelope with HMAC-SHA256
     */
    public static function encrypt(mixed $data, ?string $customKey = null): string
    {
        $key = self::resolveKey($customKey);
        $iv = random_bytes(16); // 16 bytes IV for AES-CBC
        $jsonData = json_encode(self::sanitize($data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $ciphertext = openssl_encrypt($jsonData, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($ciphertext === false) {
            throw new \RuntimeException('Gagal mengenkripsi payload data keamanan.');
        }

        // HMAC SHA-256 for Authenticated Encryption (Anti-Tampering)
        $mac = hash_hmac('sha256', $iv . $ciphertext, $key, true);

        $payload = [
            'iv'    => base64_encode($iv),
            'value' => base64_encode($ciphertext),
            'mac'   => base64_encode($mac),
            'ts'    => time(),
        ];

        return 'ENC:' . base64_encode(json_encode($payload));
    }

    /**
     * Decrypt an encrypted envelope string back to original data
     */
    public static function decrypt(string $encryptedString, ?string $customKey = null): mixed
    {
        if (!str_starts_with($encryptedString, 'ENC:')) {
            throw new \InvalidArgumentException('Format payload enkripsi tidak valid.');
        }

        $rawJson = base64_decode(substr($encryptedString, 4));
        $payload = json_decode($rawJson, true);

        if (!isset($payload['iv'], $payload['value'], $payload['mac'])) {
            throw new \InvalidArgumentException('Struktur payload enkripsi tidak lengkap.');
        }

        $key = self::resolveKey($customKey);
        $iv = base64_decode($payload['iv']);
        $ciphertext = base64_decode($payload['value']);
        $expectedMac = base64_decode($payload['mac']);

        // Timing-Safe MAC Verification
        $actualMac = hash_hmac('sha256', $iv . $ciphertext, $key, true);
        if (!hash_equals($expectedMac, $actualMac)) {
            throw new \SecurityException('Integritas data payload enkripsi rusak atau telah dimodifikasi (MAC Mismatch).');
        }

        $decryptedJson = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($decryptedJson === false) {
            throw new \RuntimeException('Gagal mendekripsi payload data keamanan.');
        }

        return json_decode($decryptedJson, true);
    }

    /**
     * Sanitize array or object to recursively strip sensitive credentials
     */
    public static function sanitize(mixed $data, array $customBlacklist = []): mixed
    {
        $blacklist = array_merge(self::$defaultBlacklist, $customBlacklist);

        if ($data instanceof \Closure) {
            return $data;
        }

        if (is_object($data)) {
            if (method_exists($data, 'toArray')) {
                $data = $data->toArray();
            } else {
                $data = (array)$data;
            }
        }

        if (!is_array($data)) {
            return $data;
        }

        $sanitized = [];
        foreach ($data as $key => $value) {
            // Check if key is blacklisted
            $lowerKey = strtolower((string)$key);
            if (in_array($lowerKey, $blacklist, true)) {
                continue; // Strip
            }

            if ($value instanceof \Closure) {
                $sanitized[$key] = $value;
            } elseif (is_array($value) || is_object($value)) {
                $sanitized[$key] = self::sanitize($value, $customBlacklist);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Wrap payload in standard encrypted JSON response structure
     */
    public static function wrapEncryptedResponse(mixed $data, string $message = 'Data aman berhasil diambil.'): array
    {
        $cleanData = self::sanitize($data);
        $encryptedPayload = self::encrypt($cleanData);

        return [
            'success'   => true,
            'encrypted' => true,
            'message'   => $message,
            'payload'   => $encryptedPayload,
            'checksum'  => hash('sha256', $encryptedPayload),
        ];
    }
}
