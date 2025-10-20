<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LabSubmission;
use App\Services\ResultService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class LabWebhookController extends Controller
{
    protected $resultService;

    public function __construct(ResultService $resultService)
    {
        $this->resultService = $resultService;
    }

    /**
     * Receive results from lab partner webhook
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function receiveResults(Request $request): JsonResponse
    {
        $data = $request->all();

        Log::info('Lab webhook received', [
            'payload_size' => strlen(json_encode($data)),
            'ip' => $request->ip(),
        ]);

        try {
            // Extract order identifier (varies by lab partner)
            $labOrderId = $data['orderId'] 
                          ?? $data['order_id'] 
                          ?? $data['external_order_id']
                          ?? null;

            if (!$labOrderId) {
                Log::error('Lab webhook missing order ID', ['data' => $data]);
                return response()->json(['error' => 'Missing order ID'], 400);
            }

            // Find lab submission
            $labSubmission = LabSubmission::where('lab_order_id', $labOrderId)
                ->firstOrFail();

            // Check if result already exists
            if ($labSubmission->result()->exists()) {
                Log::info('Result already exists for this submission', [
                    'lab_submission_id' => $labSubmission->id,
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Result already processed',
                ]);
            }

            // Process the result
            $result = $this->resultService->processIncomingResult($data, $labSubmission->id);

            Log::info('Lab result processed successfully', [
                'result_id' => $result->id,
                'order_id' => $result->order_id,
                'lab_submission_id' => $labSubmission->id,
            ]);

            return response()->json([
                'success' => true,
                'result_id' => $result->id,
                'message' => 'Result processed successfully',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Lab submission not found', [
                'lab_order_id' => $labOrderId ?? 'unknown',
                'data' => $data,
            ]);

            return response()->json([
                'error' => 'Order not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to process lab webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);

            return response()->json([
                'error' => 'Failed to process result',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Receive HL7 messages
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function receiveHL7(Request $request): JsonResponse
    {
        $hl7Message = $request->getContent();

        Log::info('HL7 message received', [
            'message_size' => strlen($hl7Message),
            'ip' => $request->ip(),
        ]);

        try {
            // Parse HL7 message to extract order ID
            $message = new \Aranyasen\HL7\Message($hl7Message);
            $msh = $message->getSegmentByIndex(0);
            
            if (!$msh || $msh->getName() !== 'MSH') {
                throw new \Exception('Invalid HL7 message format');
            }

            // Determine message type
            $messageType = $msh->getField(9);
            
            if (strpos($messageType, 'ORU') === 0) {
                // Result message
                return $this->processHL7Result($hl7Message);
            } elseif (strpos($messageType, 'ACK') === 0) {
                // Acknowledgment - log and return
                Log::info('HL7 ACK received');
                return response()->json(['success' => true]);
            }

            Log::warning('Unsupported HL7 message type', [
                'message_type' => $messageType,
            ]);

            return response()->json([
                'error' => 'Unsupported message type',
            ], 400);

        } catch (\Exception $e) {
            Log::error('Failed to process HL7 message', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to process HL7 message',
            ], 500);
        }
    }

    /**
     * Process HL7 result message
     *
     * @param string $hl7Message
     * @return JsonResponse
     */
    protected function processHL7Result(string $hl7Message): JsonResponse
    {
        try {
            $message = new \Aranyasen\HL7\Message($hl7Message);
            
            // Extract order number from ORC or OBR segment
            $orc = null;
            foreach ($message->getSegments() as $segment) {
                if ($segment->getName() === 'ORC') {
                    $orc = $segment;
                    break;
                }
            }

            if (!$orc) {
                throw new \Exception('HL7 result missing ORC segment');
            }

            $orderNumber = $orc->getField(2); // Placer order number

            // Find submission by our order number
            $labSubmission = LabSubmission::whereHas('order', function ($query) use ($orderNumber) {
                $query->where('order_number', $orderNumber);
            })->firstOrFail();

            // Process result
            $result = $this->resultService->processIncomingResult(
                ['hl7' => $hl7Message],
                $labSubmission->id
            );

            return response()->json([
                'success' => true,
                'result_id' => $result->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process HL7 result', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}