<?php

namespace App\Services\LabPartner;

use App\Models\Order;
use App\Models\LabPartner;
use App\Models\LabSubmission;
use Aranyasen\HL7\Message;
use Aranyasen\HL7\Segment;
use Illuminate\Support\Facades\Log;

/**
 * HL7/MLLP Adapter
 * 
 * Integration for lab partners using HL7 v2.x over MLLP protocol
 */
class HL7Adapter implements LabPartnerInterface
{
    protected $labPartner;

    public function __construct(LabPartner $labPartner)
    {
        $this->labPartner = $labPartner;
    }

    /**
     * Submit order via HL7
     *
     * @param Order $order
     * @return LabSubmission
     * @throws \Exception
     */
    public function submitOrder(Order $order): LabSubmission
    {
        $submission = LabSubmission::create([
            'order_id' => $order->id,
            'lab_partner_id' => $this->labPartner->id,
            'status' => 'pending',
        ]);

        try {
            $hl7Message = $this->buildHL7OrderMessage($order);

            $submission->update(['request_payload' => ['hl7' => $hl7Message]]);

            // Send via MLLP
            $response = $this->sendHL7Message($hl7Message);

            // Parse acknowledgment
            $ack = $this->parseAcknowledgment($response);

            if ($ack['success']) {
                $submission->markAsSubmitted(
                    $ack['message_control_id'] ?? $order->order_number,
                    ['hl7_response' => $response]
                );

                $order->update(['status' => 'sent_to_lab']);

                Log::info('HL7 order submitted successfully', [
                    'order_id' => $order->id,
                ]);
            } else {
                throw new \Exception('HL7 NACK received: ' . ($ack['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error('HL7 order submission failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            $submission->markAsFailed($e->getMessage());

            throw $e;
        }

        return $submission;
    }

    /**
     * Build HL7 ORM^O01 message (Order)
     *
     * @param Order $order
     * @return string
     */
    protected function buildHL7OrderMessage(Order $order): string
    {
        $message = new Message();

        // MSH Segment (Message Header)
        $msh = new Segment('MSH');
        $msh->setField(1, '|');
        $msh->setField(2, '^~\\&');
        $msh->setField(3, config('app.name')); // Sending application
        $msh->setField(4, 'LAB'); // Sending facility
        $msh->setField(5, $this->labPartner->name); // Receiving application
        $msh->setField(6, $this->labPartner->credentials['facility_id'] ?? 'LAB'); // Receiving facility
        $msh->setField(7, date('YmdHis')); // Message timestamp
        $msh->setField(9, 'ORM^O01'); // Message type
        $msh->setField(10, $order->order_number); // Message control ID
        $msh->setField(11, 'P'); // Processing ID (P=Production)
        $msh->setField(12, '2.5.1'); // HL7 version
        $message->addSegment($msh);

        // PID Segment (Patient Identification)
        $user = $order->user;
        $pid = new Segment('PID');
        $pid->setField(1, '1'); // Set ID
        $pid->setField(3, $user->id); // Patient ID
        $pid->setField(5, $user->last_name . '^' . $user->first_name); // Patient name
        $pid->setField(7, $user->date_of_birth->format('Ymd')); // Date of birth
        $pid->setField(8, strtoupper($user->gender[0] ?? 'U')); // Gender
        $pid->setField(13, $user->phone ?? ''); // Phone number
        $message->addSegment($pid);

        // PV1 Segment (Patient Visit)
        $pv1 = new Segment('PV1');
        $pv1->setField(1, '1');
        $pv1->setField(2, 'O'); // Patient class (O=Outpatient)
        $message->addSegment($pv1);

        // ORC Segment (Common Order)
        $orc = new Segment('ORC');
        $orc->setField(1, 'NW'); // Order control (NW=New order)
        $orc->setField(2, $order->order_number); // Placer order number
        $orc->setField(5, 'SC'); // Order status (SC=Scheduled)
        $orc->setField(9, $order->created_at->format('YmdHis')); // Transaction date/time
        $message->addSegment($orc);

        // OBR Segments (Observation Request) - one per test
        foreach ($order->items as $index => $item) {
            $obr = new Segment('OBR');
            $obr->setField(1, $index + 1); // Set ID
            $obr->setField(2, $order->order_number . '-' . $item->id); // Placer order number
            $obr->setField(4, $item->test_code . '^' . $item->test_name . '^L'); // Universal service ID
            $obr->setField(7, $order->collection_date->format('YmdHis')); // Observation date/time
            $obr->setField(15, $item->test->specimen_type); // Specimen source
            $obr->setField(16, 'R'); // Order control code reason (R=Routine)
            $message->addSegment($obr);

            // SPM Segment (Specimen) - optional but recommended
            $spm = new Segment('SPM');
            $spm->setField(1, $index + 1);
            $spm->setField(2, $item->specimen_barcode);
            $spm->setField(4, $item->test->specimen_type);
            $message->addSegment($spm);
        }

        return $message->toString();
    }

    /**
     * Send HL7 message via MLLP
     *
     * @param string $message
     * @return string Response message
     * @throws \Exception
     */
    protected function sendHL7Message(string $message): string
    {
        $host = $this->labPartner->api_endpoint;
        $port = $this->labPartner->credentials['port'] ?? 2575;

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if (!$socket) {
            throw new \Exception('Failed to create socket: ' . socket_strerror(socket_last_error()));
        }

        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 30, 'usec' => 0]);
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 30, 'usec' => 0]);

        if (!@socket_connect($socket, $host, $port)) {
            $error = socket_strerror(socket_last_error($socket));
            socket_close($socket);
            throw new \Exception("Failed to connect to HL7 server at {$host}:{$port} - {$error}");
        }

        // MLLP framing: <VT>message<FS><CR>
        $framedMessage = "\x0B" . $message . "\x1C\x0D";

        $written = socket_write($socket, $framedMessage, strlen($framedMessage));

        if ($written === false) {
            $error = socket_strerror(socket_last_error($socket));
            socket_close($socket);
            throw new \Exception("Failed to write to socket: {$error}");
        }

        // Read response
        $response = '';
        while ($chunk = socket_read($socket, 2048)) {
            $response .= $chunk;
            if (strpos($chunk, "\x1C\x0D") !== false) {
                break;
            }
        }

        socket_close($socket);

        // Remove MLLP framing
        $response = trim($response, "\x0B\x1C\x0D");

        return $response;
    }

    /**
     * Parse HL7 acknowledgment
     *
     * @param string $hl7Response
     * @return array
     */
    protected function parseAcknowledgment(string $hl7Response): array
    {
        try {
            $message = new Message($hl7Response);
            $msa = $message->getSegmentByIndex(1); // MSA segment

            if (!$msa || $msa->getName() !== 'MSA') {
                return ['success' => false, 'error' => 'Invalid ACK format'];
            }

            $ackCode = $msa->getField(1);

            return [
                'success' => in_array($ackCode, ['AA', 'CA']), // AA=Application Accept, CA=Commit Accept
                'ack_code' => $ackCode,
                'message_control_id' => $msa->getField(2),
                'error' => $ackCode === 'AE' || $ackCode === 'AR' ? $msa->getField(3) : null,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Failed to parse ACK: ' . $e->getMessage()];
        }
    }

    /**
     * Parse HL7 result message (ORU^R01)
     *
     * @param mixed $rawData
     * @return array
     */
    public function parseResults($rawData): array
    {
        $hl7String = is_string($rawData) ? $rawData : ($rawData['hl7'] ?? '');

        if (empty($hl7String)) {
            throw new \Exception('Invalid HL7 result data');
        }

        try {
            $message = new Message($hl7String);

            $tests = [];
            $resultDate = null;
            $performingLab = null;

            // Parse MSH for metadata
            $msh = $message->getSegmentByIndex(0);
            if ($msh) {
                $resultDate = $msh->getField(7);
                $performingLab = $msh->getField(4);
            }

            // Find all OBX segments (results)
            $segments = $message->getSegments();
            foreach ($segments as $segment) {
                if ($segment->getName() === 'OBX') {
                    $tests[] = [
                        'test_code' => $segment->getField(3, 1) ?? '',
                        'test_name' => $segment->getField(3, 2) ?? '',
                        'value' => $segment->getField(5) ?? '',
                        'unit' => $segment->getField(6) ?? '',
                        'reference_range' => $segment->getField(7) ?? '',
                        'flag' => $segment->getField(8) ?? null, // H, L, HH, LL, etc.
                        'status' => $segment->getField(11) ?? 'F', // F = Final
                        'observation_date' => $segment->getField(14) ?? null,
                    ];
                }
            }

            return [
                'tests' => $tests,
                'result_date' => $resultDate ? $this->parseHL7DateTime($resultDate) : now()->toISOString(),
                'performing_lab' => $performingLab ?? $this->labPartner->name,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to parse HL7 result', [
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to parse HL7 result: ' . $e->getMessage());
        }
    }

    /**
     * Parse HL7 datetime format (YYYYMMDDHHMMSS)
     *
     * @param string $hl7DateTime
     * @return string ISO 8601 format
     */
    protected function parseHL7DateTime(string $hl7DateTime): string
    {
        $hl7DateTime = preg_replace('/[^0-9]/', '', $hl7DateTime);

        if (strlen($hl7DateTime) >= 8) {
            $year = substr($hl7DateTime, 0, 4);
            $month = substr($hl7DateTime, 4, 2);
            $day = substr($hl7DateTime, 6, 2);
            $hour = strlen($hl7DateTime) >= 10 ? substr($hl7DateTime, 8, 2) : '00';
            $minute = strlen($hl7DateTime) >= 12 ? substr($hl7DateTime, 10, 2) : '00';
            $second = strlen($hl7DateTime) >= 14 ? substr($hl7DateTime, 12, 2) : '00';

            return "{$year}-{$month}-{$day}T{$hour}:{$minute}:{$second}";
        }

        return now()->toISOString();
    }

    /**
     * Get order status (not typically supported in HL7)
     *
     * @param string $labOrderId
     * @return array
     */
    public function getOrderStatus(string $labOrderId): array
    {
        // HL7 is typically unidirectional, status queries not common
        // Would need to implement QRY^Q01 message type if supported
        return [
            'status' => 'processing',
            'message' => 'Status queries not supported via HL7',
        ];
    }

    /**
     * Validate HL7 connection
     *
     * @return bool
     */
    public function validateConnection(): bool
    {
        try {
            $host = $this->labPartner->api_endpoint;
            $port = $this->labPartner->credentials['port'] ?? 2575;

            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

            if (!$socket) {
                return false;
            }

            socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);
            socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 5, 'usec' => 0]);

            $result = @socket_connect($socket, $host, $port);

            socket_close($socket);

            return $result !== false;
        } catch (\Exception $e) {
            Log::error('HL7 connection validation failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get supported tests
     *
     * @return array
     */
    public function getSupportedTests(): array
    {
        // HL7 doesn't provide a test catalog query
        // Return configured supported tests or empty for "all"
        return $this->labPartner->supported_tests ?? [];
    }
}