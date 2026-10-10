<?php
// Minimal Web Push sender (RFC 8291 aes128gcm payloads, RFC 8292 VAPID).
// Needs only PHP's openssl and curl extensions, so it runs on plain shared hosting.

function b64u($s) { return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); }
function unb64u($s) { return base64_decode(strtr($s, '-_', '+/')); }

// Raw 65-byte public key (0x04 || x || y) of an EC key.
function raw_public($key) {
    $ec = openssl_pkey_get_details($key)['ec'];
    return "\x04" . str_pad($ec['x'], 32, "\0", STR_PAD_LEFT) . str_pad($ec['y'], 32, "\0", STR_PAD_LEFT);
}

function new_p256_key() {
    return openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
}

// The server's VAPID key, created on first use and kept in the private data folder.
function vapid_key($file) {
    if (!is_file($file)) {
        openssl_pkey_export(new_p256_key(), $pem);
        file_put_contents($file, $pem, LOCK_EX);
        chmod($file, 0600);
    }
    return openssl_pkey_get_private(file_get_contents($file));
}

// Turns DER ECDSA signature into the raw r || s form JWTs use.
function der_to_raw($der) {
    $rlen = ord($der[3]);
    $r = substr($der, 4, $rlen);
    $s = substr($der, 6 + $rlen, ord($der[5 + $rlen]));
    $fix = fn($n) => str_pad(ltrim($n, "\0"), 32, "\0", STR_PAD_LEFT);
    return $fix($r) . $fix($s);
}

function vapid_header($endpoint, $key) {
    $u = parse_url($endpoint);
    $jwt = b64u('{"typ":"JWT","alg":"ES256"}') . '.' . b64u(json_encode([
        'aud' => $u['scheme'] . '://' . $u['host'],
        'exp' => time() + 12 * 3600,
        'sub' => 'https://korilus.dsgnr.ee',
    ], JSON_UNESCAPED_SLASHES));
    openssl_sign($jwt, $sig, $key, OPENSSL_ALGO_SHA256);
    return 'vapid t=' . $jwt . '.' . b64u(der_to_raw($sig)) . ', k=' . b64u(raw_public($key));
}

function encrypt_payload($payload, $p256dh, $auth) {
    $uaPublic = unb64u($p256dh);
    $peer = openssl_pkey_get_public("-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode(
        hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . $uaPublic), 64) . "-----END PUBLIC KEY-----\n");
    $local = new_p256_key();
    $asPublic = raw_public($local);
    $secret = openssl_pkey_derive($peer, $local, 32);

    $ikm = hash_hmac('sha256', "WebPush: info\0" . $uaPublic . $asPublic . "\1",
        hash_hmac('sha256', $secret, unb64u($auth), true), true);
    $salt = random_bytes(16);
    $prk = hash_hmac('sha256', $ikm, $salt, true);
    $cek = substr(hash_hmac('sha256', "Content-Encoding: aes128gcm\0\1", $prk, true), 0, 16);
    $nonce = substr(hash_hmac('sha256', "Content-Encoding: nonce\0\1", $prk, true), 0, 12);

    $cipher = openssl_encrypt($payload . "\2", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);
    return $salt . pack('N', 4096) . chr(65) . $asPublic . $cipher . $tag;
}

// Returns the push service's HTTP status (201 = sent, 404/410 = subscription is gone).
function send_push($subscription, $payload, $vapidKey) {
    $endpoint = $subscription['endpoint'];
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . vapid_header($endpoint, $vapidKey),
            'Content-Encoding: aes128gcm',
            'Content-Type: application/octet-stream',
            'TTL: 86400',
        ],
        CURLOPT_POSTFIELDS => encrypt_payload($payload, $subscription['keys']['p256dh'], $subscription['keys']['auth']),
    ]);
    curl_exec($ch);
    return curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
}
