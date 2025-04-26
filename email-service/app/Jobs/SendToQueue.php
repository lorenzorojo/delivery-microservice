<?php

namespace App\Jobs;

use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SendToQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $message;

    public int $tries = 3;

    public function __construct($message)
    {
        $this->message = $message;
        $this->queue = 'email-microservice-queue';
        $this->connection = 'sqs';
    }

    public function handle()
    {
        Log::info("Enviando mensaje a SQS: {$this->message}");
    }

    public function fail($exception = null)
    {
        Log::error("Error: ".__CLASS__,[$exception]);
    }

    public function retryUntil(): DateTime
    {
        return now()->addMinutes(1);
    }
}
