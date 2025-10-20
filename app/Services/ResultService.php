<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Result;
use App\Models\LabSubmission;
use App\Services\LabPartner\LabPartnerManager;
use App\Jobs\SendResultNotification;
use App\Events\ResultReceived;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ResultService
{
    protected $labManager;

    public function __construct(LabPartnerManager $labManager)
    {
        $this->labManager = $labManager;
    }

    /**
     * Process incoming result from lab partner
     *
     * @param mixed $rawData
     * @param int $labSubmissionId
     * @return Result
     */
    public function processIncomingResult($rawData, int $labSubmissionId): Result
    {
        return DB::transaction(function () use ($rawData, $labSubmissionId) {
            $labSubmission = LabSubmission::findOrFail($labSubmissionId);
            $order = $labSubmission->order;

            // Use lab adapter to parse results
            $adapter = $this->labManager->getAdapter($labSubmission->labPartner);
            $parsedData = $adapter->parseResults($rawData);

            // Check for critical values
            $hasCriticalValues = $this->hasCriticalValues($parsedData);

            // Create result record
            $result = Result::create([
                'order_id' => $order->id,
                'lab_submission_id' => $labSubmission->id,
                'raw_data' => is_array($rawData) ? $rawData : ['data' => $rawData],
                'parsed_data' => $parsedData,
                'result_date' => $parsedData['result_date'] ?? now(),
                'has_critical_values' => $hasCriticalValues,
            ]);

            // Generate PDF
            $this->generatePDF($result);

            // Update order status
            $order->update(['status' => 'completed']);

            // Update lab submission status
            $labSubmission->markAsCompleted();

            // Fire event
            event(new ResultReceived($result));

            // Queue notification job (delay if critical and needs review)
            if ($hasCriticalValues) {
                Log::warning('Critical values detected, requiring review', [
                    'result_id' => $result->id,
                    'order_id' => $order->id,
                ]);
            } else {
                SendResultNotification::dispatch($result)->delay(now()->addMinutes(5));
            }

            Log::info('Result processed successfully', [
                'result_id' => $result->id,
                'order_id' => $order->id,
                'has_critical' => $hasCriticalValues,
            ]);

            return $result;
        });
    }

    /**
     * Generate PDF for result
     *
     * @param Result $result
     * @return string Path to PDF
     */
    public function generatePDF(Result $result): string
    {
        $order = $result->order;
        $user = $order->user;

        $pdf = Pdf::loadView('pdfs.result', [
            'result' => $result,
            'order' => $order,
            'user' => $user,
            'parsedData' => $result->parsed_data,
        ]);

        $filename = "results/{$order->order_number}_" . now()->timestamp . ".pdf";

        Storage::put($filename, $pdf->output());

        $result->update(['pdf_path' => $filename]);

        Log::info('PDF generated', [
            'result_id' => $result->id,
            'pdf_path' => $filename,
        ]);

        return $filename;
    }

    /**
     * Check if parsed data contains critical values
     *
     * @param array $parsedData
     * @return bool
     */
    protected function hasCriticalValues(array $parsedData): bool
    {
        if (!isset($parsedData['tests']) || !is_array($parsedData['tests'])) {
            return false;
        }

        foreach ($parsedData['tests'] as $test) {
            if (isset($test['flag']) && in_array($test['flag'], ['HH', 'LL', 'CRIT', 'CRITICAL'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Review a result
     *
     * @param Result $result
     * @param int|null $reviewerId
     * @param string|null $notes
     * @return bool
     */
    public function reviewResult(Result $result, ?int $reviewerId = null, ?string $notes = null): bool
    {
        $result->markAsReviewed($reviewerId, $notes);

        // If result was awaiting review, now send notification
        if ($result->has_critical_values && !$result->is_notified) {
            SendResultNotification::dispatch($result);
        }

        Log::info('Result reviewed', [
            'result_id' => $result->id,
            'reviewed_by' => $reviewerId ?? auth()->id(),
        ]);

        return true;
    }

    /**
     * Mark result as viewed by patient
     *
     * @param Result $result
     * @return bool
     */
    public function markAsViewed(Result $result): bool
    {
        return $result->markAsViewed();
    }

    /**
     * Get results awaiting review
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getResultsAwaitingReview()
    {
        return Result::with(['order.user', 'order.items'])
            ->unreviewed()
            ->critical()
            ->orderBy('result_date', 'desc')
            ->get();
    }

    /**
     * Get results ready for patient notification
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getResultsReadyForNotification()
    {
        return Result::with(['order.user'])
            ->where('is_reviewed', true)
            ->whereNull('patient_notified_at')
            ->orderBy('result_date', 'desc')
            ->get();
    }

    /**
     * Get patient's unviewed results
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUnviewedResultsForPatient(int $userId)
    {
        return Result::forPatient($userId)
            ->whereNotNull('patient_notified_at')
            ->whereNull('patient_viewed_at')
            ->with('order.items')
            ->orderBy('result_date', 'desc')
            ->get();
    }

    /**
     * Get result statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'awaiting_review' => Result::unreviewed()->critical()->count(),
            'ready_to_notify' => Result::reviewed()
                ->whereNull('patient_notified_at')
                ->count(),
            'total_this_week' => Result::where('result_date', '>=', now()->startOfWeek())->count(),
            'critical_this_week' => Result::critical()
                ->where('result_date', '>=', now()->startOfWeek())
                ->count(),
        ];
    }

    /**
     * Regenerate PDF for a result
     *
     * @param Result $result
     * @return string
     */
    public function regeneratePDF(Result $result): string
    {
        // Delete old PDF
        if ($result->pdf_path && Storage::exists($result->pdf_path)) {
            Storage::delete($result->pdf_path);
        }

        // Generate new PDF
        return $this->generatePDF($result);
    }
}