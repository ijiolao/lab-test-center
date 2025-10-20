<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\LabPartner;
use App\Services\LabPartner\LabPartnerManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SubmitOrderToLab implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The order instance
     *
     * @var Order
     */
    protected $order;

    /**
     * The lab partner ID (optional - will auto-select if not provided)
     *
     * @var int|null
     */
    protected $labPartnerId;

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
    public $backoff = 600; // 10 minutes

    /**
     * Create a new job instance
     */
    public function __construct(Order $order, ?int $labPartnerId = null)
    {
        $this->order = $order;
        $this->labPartnerId = $labPartnerId;
    }

    /**
     * Execute the job
     */
    public function handle(LabPartnerManager $labManager): void
    {
        // Reload order to get latest status
        $this->order->refresh();

        // Validate order can be submitted
        if (!$this->order->canBeSubmittedToLab()) {
            Log::warning('Order cannot be submitted to lab', [
                'order_id' => $this->order->id,
                'status' => $this->order->status,
            ]);
            return;
        }

        // Check if already submitted
        if ($this->order->labSubmissions()->successful()->exists()) {
            Log::info('Order already submitted to lab', [
                'order_id' => $this->order->id,
            ]);
            return;
        }

        try {
            // Get lab partner
            $labPartner = $this->getLabPartner($labManager);

            if (!$labPartner) {
                throw new \Exception('No suitable lab partner found for this order');
            }

            Log::info('Submitting order to lab partner', [
                'order_id' => $this->order->id,
                'lab_partner_id' => $labPartner->id,
                'lab_partner_name' => $labPartner->name,
            ]);

            // Submit order
            $submission = $labManager->submitOrder($this->order, $labPartner);

            Log::info('Order submitted successfully', [
                'order_id' => $this->order->id,
                'submission_id' => $submission->id,
                'lab_order_id' => $submission->lab_order_id,
            ]);

            // Log activity
            activity()
                ->performedOn($this->order)
                ->withProperties([
                    'lab_partner' => $labPartner->name,
                    'submission_id' => $submission->id,
                ])
                ->log('Order submitted to lab');

        } catch (\Exception $e) {
            Log::error('Failed to submit order to lab', [
                'order_id' => $this->order->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            // Don't throw on last attempt - mark as failed instead
            if ($this->attempts() >= $this->tries) {
                $this->order->update(['status' => 'collected']); // Revert to collected
            }

            throw $e;
        }
    }

    /**
     * Get lab partner for the order
     *
     * @param LabPartnerManager $labManager
     * @return LabPartner|null
     */
    protected function getLabPartner(LabPartnerManager $labManager): ?LabPartner
    {
        // Use specified lab partner if provided
        if ($this->labPartnerId) {
            $labPartner = LabPartner::find($this->labPartnerId);
            
            if ($labPartner && $labPartner->is_active) {
                return $labPartner;
            }
        }

        // Auto-select best lab partner
        return $labManager->findBestLabPartner($this->order);
    }

    /**
     * Handle a job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Order submission job failed permanently', [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage(),
        ]);

        // Notify administrators
        // Mail::to(config('mail.admin_email'))->send(new OrderSubmissionFailed($this->order, $exception));
    }
}