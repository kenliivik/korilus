<?php
// Sends a plain-text email (with an optional Reply-To) through the mailbox in config 'smtp' (STARTTLS + AUTH LOGIN), or PHP mail() without one. Not served.
require_once __DIR__ . '/db.php';

function send_mail($to, $subject, $text, $replyTo = null) {
    $c = config('smtp') ?? [];
    $from = $c['from'] ?? $c['user'] ?? 'korilus@' . ($_SERVER['SERVER_NAME'] ?? 'localhost');
    $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $body = quoted_printable_encode(str_replace(["\r\n", "\n"], ["\n", "\r\n"], $text));
    $headers = [
        'From' => "Korilus <$from>",
        'Date' => date('r'),
        'Message-ID' => '<' . bin2hex(random_bytes(12)) . strstr($from, '@') . '>',
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Content-Transfer-Encoding' => 'quoted-printable',
    ];
    if ($replyTo) $headers['Reply-To'] = "<$replyTo>";
    if (!empty($c['host'])) {
        try {
            smtp_send($c, $from, $to, $subject, $headers, $body);
            return true;
        } catch (Exception $e) {
            error_log('korilus smtp: ' . $e->getMessage());
        }
    }
    return mail($to, $subject, $body, $headers, '-f' . $from);
}

function smtp_send($c, $from, $to, $subject, $headers, $body) {
    $host = $c['host'];
    $port = (int) ($c['port'] ?? 587);
    $local = in_array($host, ['127.0.0.1', 'localhost'], true);
    $s = stream_socket_client(($port === 465 ? 'ssl://' : 'tcp://') . "$host:$port", $errno, $error, 15);
    if (!$s) throw new Exception("connect $host:$port: $error");
    stream_set_timeout($s, 20);

    // Sends one line (null = just read) and checks the reply's first digit. $label hides secrets from the log.
    $say = function ($line, $expect, $label = null) use ($s) {
        if ($line !== null) fwrite($s, "$line\r\n");
        $reply = '';
        while (($l = fgets($s, 1000)) !== false) {
            $reply .= $l;
            if (($l[3] ?? ' ') === ' ') break;
        }
        if (($reply[0] ?? '') !== (string) $expect[0]) throw new Exception(($label ?? $line ?? 'greeting') . ' -> ' . trim($reply));
        return $reply;
    };

    $me = gethostname() ?: 'localhost';
    $say(null, '220');
    $ehlo = $say("EHLO $me", '250');
    if ($port !== 465 && (stripos($ehlo, 'STARTTLS') !== false || !$local)) {
        $say('STARTTLS', '220');
        if (!stream_socket_enable_crypto($s, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
            throw new Exception('TLS handshake failed');
        }
        $say("EHLO $me", '250');
    }
    if (!empty($c['user'])) {
        $say('AUTH LOGIN', '334');
        $say(base64_encode($c['user']), '334', 'user');
        $say(base64_encode($c['pass'] ?? ''), '235', 'password');
    }
    $say("MAIL FROM:<$from>", '250');
    $say("RCPT TO:<$to>", '250');
    $say('DATA', '354');
    $head = "To: <$to>\r\nSubject: $subject\r\n";
    foreach ($headers as $k => $v) $head .= "$k: $v\r\n";
    $say($head . "\r\n" . preg_replace('/^\./m', '..', $body) . "\r\n.", '250', 'message');
    fwrite($s, "QUIT\r\n");
    fclose($s);
}
