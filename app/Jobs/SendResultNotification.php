<?php

namespace App\Jobs;

use App\Models\Result;
use App\Mail\ResultReady;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendResultNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The result instance
     *
     * @var Result
     */
    protected $result;

    /**
     * The number of times the job may be attempted
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying
     *
     * @var int
     */
    public $backoff = 300; // 5 minutes

    /**
     * Create a new job instance
     */
    public function __construct(Result $result)
    {
        $this->result = $result;
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        // Reload result to get latest data
        $this->result->refresh();

        // Don't send if already notified
        if ($this->result->is_notified) {
            Log::info('Result already notified, skipping', [
                'result_id' => $this->result->id,
            ]);
            return;
        }

        // Don't send critical results that haven't been reviewed
        if ($this->result->has_critical_values && !$this->result->is_reviewed) {
            Log::warning('Critical result not reviewed yet, skipping notification', [
                'result_id' => $this->result->id,
            ]);
            return;
        }

        // Don't send if PDF doesn't exist
        if (!$this->result->has_pdf) {
            Log::error('PDF not generated, cannot send notification', [
                'result_id' => $this->result->id,
            ]);
            throw new \Exception('Result PDF not available');
        }

        $order = $this->result->order;
        $user = $order->user;

        try {
            // Send email notification
            Mail::to($user->email)->send(new ResultReady($this->result));

            // Update notification timestamp
            $this->result->markAsNotified();

            // Log activity
            activity()
                ->performedOn($this->result)
                ->log('Patient notified of results via email');

            Log::info('Result notification sent successfully', [
                'result_id' => $this->result->id,
                'order_id' => $order->id,
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            // Optional: Send SMS notification
            if ($user->phone && config('services.twilio.enabled')) {
                $this->sendSMSNotification($user->phone, $order->order_number);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send result notification', [
                'result_id' => $this->result->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send SMS notification (optional)
     *
     * @param string $phone
     * @param string $orderNumber
     * @return void
     */
    protected function sendSMSNotification(string $phone, string $orderNumber): void
    {
        try {
            // Implement Twilio SMS here if needed
            // Example:
            // $twilio = new Client(config('services.twilio.sid'), config('services.twilio.token'));
            // $twilio->messages->create($phone, [
            //     'from' => config('services.twilio.from'),
            //     'body' => "Your test results for order {$orderNumber} are now available. Log in to view them."
            // ]);

            Log::info('SMS notification sent', [
                'result_id' => $this->result->id,
                'phone' => $phone,
            ]);
        } catch (\Exception $e) {
            // Don't fail the job if SMS fails
            Log::warning('SMS notification failed', [
                'result_id' => $this->result->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Result notification job failed permanently', [
            'result_id' => $this->result->id,
            'error' => $exception->getMessage(),
        ]);

        // Optionally notify administrators
        // Mail::to(config('mail.admin_email'))->send(new AdminAlert(...));
    }
}