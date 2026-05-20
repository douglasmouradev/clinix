<?php

declare(strict_types=1);

namespace App\Core;

final class StripeClient
{
    public static function isConfigured(): bool
    {
        return STRIPE_SECRET_KEY !== '';
    }

    /** @return array{url: string, session_id: string}|null */
    public static function createCheckoutSession(
        int $amountCents,
        string $planName,
        int $tenantId,
        int $planId,
        string $customerEmail = ''
    ): ?array {
        if (!self::isConfigured()) {
            return null;
        }

        $successUrl = APP_URL . '/?route=billing&checkout=success';
        $cancelUrl = APP_URL . '/?route=billing&checkout=cancel';

        $payload = http_build_query([
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer_email' => $customerEmail,
            'client_reference_id' => (string) $tenantId,
            'metadata[tenant_id]' => (string) $tenantId,
            'metadata[plan_id]' => (string) $planId,
            'line_items[0][price_data][currency]' => 'brl',
            'line_items[0][price_data][unit_amount]' => $amountCents,
            'line_items[0][price_data][product_data][name]' => 'Clinix - Plano ' . $planName,
            'line_items[0][quantity]' => 1,
        ]);

        $response = self::request('POST', 'checkout/sessions', $payload);
        if ($response === null || empty($response['url'])) {
            return null;
        }

        return [
            'url' => (string) $response['url'],
            'session_id' => (string) ($response['id'] ?? ''),
        ];
    }

    /** @return array<string, mixed>|null */
    private static function request(string $method, string $path, string $body = ''): ?array
    {
        $url = 'https://api.stripe.com/v1/' . $path;
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => STRIPE_SECRET_KEY . ':',
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POST => $method === 'POST',
            CURLOPT_POSTFIELDS => $body,
        ]);

        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $code >= 400) {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }
}
