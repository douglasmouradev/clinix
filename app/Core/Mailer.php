<?php

declare(strict_types=1);

namespace App\Core;

final class Mailer
{
    public static function send(string $to, string $subject, string $body): bool
    {
        $from = MAIL_FROM;
        if ($from === '' || $to === '') {
            return false;
        }

        if (SMTP_HOST !== '') {
            return self::sendSmtp($to, $subject, $body, $from);
        }

        $headers = [
            'From: ' . $from,
            'Reply-To: ' . $from,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
        ];

        return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
    }

    private static function sendSmtp(string $to, string $subject, string $body, string $from): bool
    {
        try {
            $socket = @fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 10);
            if (!$socket) {
                return false;
            }

            $read = static fn (): string => (string) fgets($socket, 515);
            $write = static function (string $cmd) use ($socket): void {
                fwrite($socket, $cmd . "\r\n");
            };

            $read();
            $write('EHLO clinix.local');
            $read();
            if (SMTP_USER !== '') {
                $write('AUTH LOGIN');
                $read();
                $write(base64_encode(SMTP_USER));
                $read();
                $write(base64_encode(SMTP_PASS));
                $read();
            }
            $write('MAIL FROM:<' . $from . '>');
            $read();
            $write('RCPT TO:<' . $to . '>');
            $read();
            $write('DATA');
            $read();
            $message = "Subject: {$subject}\r\nFrom: {$from}\r\nTo: {$to}\r\n\r\n{$body}\r\n.";
            $write($message);
            $read();
            $write('QUIT');
            fclose($socket);

            return true;
        } catch (\Throwable) {
            return @mail($to, $subject, $body);
        }
    }
}
