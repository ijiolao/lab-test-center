<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\VerifyWebhookSignature;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class VerifyWebhookSignatureTest extends TestCase
{
    public function test_request_with_missing_secret_is_rejected(): void
    {
        config(['lab-partners.webhook_secret' => null]);

        $middleware = new VerifyWebhookSignature();
        $request = Request::create('/webhooks/lab-results', 'POST');

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Webhook signature secret not configured.');

        $middleware->handle($request, fn () => new Response('next'));
    }

    public function test_request_with_invalid_signature_is_rejected(): void
    {
        config(['lab-partners.webhook_secret' => 'test-secret']);
        Carbon::setTestNow('2025-01-01 00:00:00');

        $payload = json_encode(['foo' => 'bar']);
        $request = Request::create('/webhooks/lab-results', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-Lab-Signature', 'bad-signature');
        $request->headers->set('X-Lab-Timestamp', Carbon::now()->timestamp);

        $middleware = new VerifyWebhookSignature();

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Invalid webhook signature.');

        $middleware->handle($request, fn () => new Response('next'));
    }

    public function test_valid_signature_allows_request_to_continue(): void
    {
        config([
            'lab-partners.webhook_secret' => 'test-secret',
            'lab-partners.webhook_tolerance' => 300,
        ]);
        Carbon::setTestNow('2025-01-01 00:00:00');

        $payload = json_encode(['foo' => 'bar']);
        $timestamp = Carbon::now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp . '.' . $payload, 'test-secret');

        $request = Request::create('/webhooks/lab-results', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-Lab-Signature', $signature);
        $request->headers->set('X-Lab-Timestamp', $timestamp);

        $middleware = new VerifyWebhookSignature();

        $response = $middleware->handle($request, fn () => new Response('passed'));

        $this->assertSame('passed', $response->getContent());
    }
}
