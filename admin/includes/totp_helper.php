<?php
/**
 * Madde 4 — Saf PHP TOTP (RFC 6238) helper'i.
 * Hicbir 3rd-party kutuphane yoktur. Google Authenticator / Authy / 1Password ile uyumlu.
 *
 * Kullanim:
 *   $secret = mynak_totp_generate_secret();              // Base32, 32 char
 *   $uri    = mynak_totp_otpauth_uri('Mynakliyat Admin', 'admin@mynakliyat.com.tr', $secret);
 *   $valid  = mynak_totp_verify($secret, $userInputCode); // ±1 window tolerance
 *
 *   $codes  = mynak_totp_generate_backup_codes(8);        // ['XXXX-XXXX', ...]
 *   $hashed = mynak_totp_hash_backup_codes($codes);       // DB'ye yazilir
 *   $rest   = mynak_totp_consume_backup_code($hashed, $userInputCode); // tek-kullanim
 */

declare(strict_types=1);

if (defined('MYNAK_TOTP_HELPER_LOADED')) {
    return;
}
define('MYNAK_TOTP_HELPER_LOADED', true);

const MYNAK_TOTP_PERIOD     = 30;
const MYNAK_TOTP_DIGITS     = 6;
const MYNAK_TOTP_ALGO       = 'sha1';
const MYNAK_TOTP_SECRET_LEN = 20;
const MYNAK_TOTP_WINDOW     = 1;

/* --------------------------------------------------------------------- *
 * Base32 (RFC 4648, no padding for otpauth)
 * --------------------------------------------------------------------- */

function mynak_totp_base32_encode(string $bin): string
{
    if ($bin === '') { return ''; }
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    $len = strlen($bin);
    for ($i = 0; $i < $len; $i++) {
        $bits .= str_pad(decbin(ord($bin[$i])), 8, '0', STR_PAD_LEFT);
    }
    $out = '';
    $chunks = str_split($bits, 5);
    foreach ($chunks as $chunk) {
        if (strlen($chunk) < 5) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
        }
        $out .= $alphabet[bindec($chunk)];
    }
    return $out;
}

function mynak_totp_base32_decode(string $b32): string
{
    $b32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $b32) ?? '');
    if ($b32 === '') { return ''; }
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    $len = strlen($b32);
    for ($i = 0; $i < $len; $i++) {
        $pos = strpos($alphabet, $b32[$i]);
        if ($pos === false) { continue; }
        $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    $out = '';
    $bytes = str_split($bits, 8);
    foreach ($bytes as $byte) {
        if (strlen($byte) === 8) {
            $out .= chr((int) bindec($byte));
        }
    }
    return $out;
}

/* --------------------------------------------------------------------- *
 * Secret + URI
 * --------------------------------------------------------------------- */

function mynak_totp_generate_secret(int $byteLength = MYNAK_TOTP_SECRET_LEN): string
{
    $raw = random_bytes($byteLength);
    return mynak_totp_base32_encode($raw);
}

function mynak_totp_otpauth_uri(string $issuer, string $accountName, string $secret): string
{
    $issuer = trim(str_replace([':', '%3A'], '', $issuer));
    $accountName = trim(str_replace([':', '%3A'], '', $accountName));
    $label = rawurlencode($issuer . ':' . $accountName);
    $params = http_build_query([
        'secret' => $secret,
        'issuer' => $issuer,
        'algorithm' => strtoupper(MYNAK_TOTP_ALGO),
        'digits' => MYNAK_TOTP_DIGITS,
        'period' => MYNAK_TOTP_PERIOD,
    ], '', '&', PHP_QUERY_RFC3986);
    return 'otpauth://totp/' . $label . '?' . $params;
}

/**
 * Setup ekraninda QR yi 3rd-party servis araciligiyla gosterir.
 * Servis cevap vermezse text/manuel giris fallback'i UI tarafindan gosterilmelidir.
 */
function mynak_totp_qr_image_url(string $otpauthUri, int $size = 220): string
{
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size
        . '&data=' . rawurlencode($otpauthUri);
}

/* --------------------------------------------------------------------- *
 * HOTP / TOTP (RFC 4226 + 6238)
 * --------------------------------------------------------------------- */

function mynak_totp_hotp(string $secret, int $counter, int $digits = MYNAK_TOTP_DIGITS): string
{
    $key = mynak_totp_base32_decode($secret);
    if ($key === '') { return str_pad('0', $digits, '0', STR_PAD_LEFT); }
    $bin = '';
    for ($i = 7; $i >= 0; $i--) {
        $bin .= chr(($counter >> ($i * 8)) & 0xFF);
    }
    $hash = hash_hmac(MYNAK_TOTP_ALGO, $bin, $key, true);
    $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
    $code = ((ord($hash[$offset]) & 0x7F) << 24)
        | ((ord($hash[$offset + 1]) & 0xFF) << 16)
        | ((ord($hash[$offset + 2]) & 0xFF) << 8)
        | (ord($hash[$offset + 3]) & 0xFF);
    $modulo = 10 ** $digits;
    return str_pad((string) ($code % $modulo), $digits, '0', STR_PAD_LEFT);
}

function mynak_totp_now_code(string $secret, ?int $ts = null): string
{
    $ts = $ts ?? time();
    return mynak_totp_hotp($secret, (int) floor($ts / MYNAK_TOTP_PERIOD));
}

function mynak_totp_verify(string $secret, string $code, int $window = MYNAK_TOTP_WINDOW, ?int $ts = null): bool
{
    $code = preg_replace('/\s+/', '', $code) ?? '';
    if (!preg_match('/^\d{' . MYNAK_TOTP_DIGITS . '}$/', $code)) {
        return false;
    }
    $ts = $ts ?? time();
    $counter = (int) floor($ts / MYNAK_TOTP_PERIOD);
    for ($i = -$window; $i <= $window; $i++) {
        $candidate = mynak_totp_hotp($secret, $counter + $i);
        if (hash_equals($candidate, $code)) {
            return true;
        }
    }
    return false;
}

/* --------------------------------------------------------------------- *
 * Backup codes (tek-kullanim)
 * --------------------------------------------------------------------- */

/**
 * @return list<string>
 */
function mynak_totp_generate_backup_codes(int $count = 8): array
{
    $codes = [];
    for ($i = 0; $i < $count; $i++) {
        $left  = strtoupper(bin2hex(random_bytes(2)));
        $right = strtoupper(bin2hex(random_bytes(2)));
        $codes[] = $left . '-' . $right;
    }
    return $codes;
}

/**
 * @param list<string> $codes
 * @return list<array{hash:string, used_at:?string}>
 */
function mynak_totp_hash_backup_codes(array $codes): array
{
    $out = [];
    foreach ($codes as $code) {
        $norm = strtoupper(preg_replace('/[^A-F0-9]/', '', $code) ?? '');
        if ($norm === '') { continue; }
        $out[] = [
            'hash' => password_hash($norm, PASSWORD_BCRYPT),
            'used_at' => null,
        ];
    }
    return $out;
}

/**
 * Backup code'u dogrular ve kullanilmis olarak isaretler.
 * Donen yeni JSON DB'ye yazilmalidir.
 *
 * @param list<array{hash:string, used_at:?string}> $stored
 * @return array{ok:bool, updated: list<array{hash:string, used_at:?string}>}
 */
function mynak_totp_consume_backup_code(array $stored, string $input): array
{
    $norm = strtoupper(preg_replace('/[^A-F0-9]/', '', $input) ?? '');
    if ($norm === '' || strlen($norm) !== 8) {
        return ['ok' => false, 'updated' => $stored];
    }
    foreach ($stored as $i => $entry) {
        if (!empty($entry['used_at'])) { continue; }
        if (!isset($entry['hash']) || !is_string($entry['hash'])) { continue; }
        if (password_verify($norm, $entry['hash'])) {
            $stored[$i]['used_at'] = date('Y-m-d H:i:s');
            return ['ok' => true, 'updated' => $stored];
        }
    }
    return ['ok' => false, 'updated' => $stored];
}

/**
 * @return list<array{hash:string, used_at:?string}>
 */
function mynak_totp_decode_backup_codes_json(?string $json): array
{
    if (!is_string($json) || $json === '') { return []; }
    $data = json_decode($json, true);
    if (!is_array($data)) { return []; }
    $out = [];
    foreach ($data as $entry) {
        if (!is_array($entry) || !isset($entry['hash']) || !is_string($entry['hash'])) { continue; }
        $out[] = [
            'hash' => $entry['hash'],
            'used_at' => isset($entry['used_at']) && is_string($entry['used_at']) ? $entry['used_at'] : null,
        ];
    }
    return $out;
}

function mynak_totp_count_unused_backup_codes(array $stored): int
{
    $n = 0;
    foreach ($stored as $entry) {
        if (empty($entry['used_at'])) { $n++; }
    }
    return $n;
}
