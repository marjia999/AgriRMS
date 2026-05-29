<?php

function sanitizeEmailHeaderValue(string $value): string {
    return trim(str_replace(["\r", "\n"], '', $value));
}

function sendPlatformEmail(string $to, string $subject, string $body, ?string $replyTo = null): bool {
    $safeTo = filter_var(trim($to), FILTER_VALIDATE_EMAIL);
    if (!$safeTo) {
        return false;
    }

    $safeSubject = sanitizeEmailHeaderValue($subject);
    $headers = "From: noreply@agrirms.com\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    if ($replyTo !== null) {
        $safeReplyTo = filter_var(trim($replyTo), FILTER_VALIDATE_EMAIL);
        if ($safeReplyTo) {
            $headers .= "Reply-To: " . sanitizeEmailHeaderValue($safeReplyTo) . "\r\n";
        }
    }

    return mail($safeTo, $safeSubject, $body, $headers);
}
