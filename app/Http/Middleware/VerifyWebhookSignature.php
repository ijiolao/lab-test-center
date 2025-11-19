<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('lab-partners.webhook_secret');

        if (!$secret) {
            Log::error('Webhook request rejected because LAB_WEBHOOK_SECRET is unset.');
            abort(503, 'Webhook signature secret not configured.');
        }

        $signature = $request->header('X-Lab-Signature');
        $timestamp = (int) $request->header('X-Lab-Timestamp');

        if (!$signature || !$timestamp) {
            Log::warning('Webhook missing signature or timestamp headers.');
            abort(401, 'Invalid webhook signature.');
        }

        $tolerance = (int) config('lab-partners.webhook_tolerance', 300);
        if (abs(now()->timestamp - $timestamp) > $tolerance) {
            Log::warning('Webhook timestamp outside tolerance window.', [
                'provided' => $timestamp,
                'tolerance' => $tolerance,
            ]);
            abort(401, 'Webhook timestamp out of range.');
        }

        $payload = $timestamp . '.' . $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('Webhook signature mismatch.', [
                'expected' => $expectedSignature,
                'provided' => $signature,
            ]);
            abort(401, 'Invalid webhook signature.');
        }

        return $next($request);
    }
}
