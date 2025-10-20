<?php

namespace App\Events;

use App\Models\Result;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResultReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The result instance
     *
     * @var Result
     */
    public $result;

    /**
     * Create a new event instance
     */
    public function __construct(Result $result)
    {
        $this->result = $result;
    }
}