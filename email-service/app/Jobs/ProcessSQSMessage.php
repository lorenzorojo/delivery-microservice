<?php

namespace App\Jobs;

use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessSQSMessage implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels, Dispatchable;

    protected $message;

    protected float $numberOne;

    protected float $numberTwo;

    public int $tries = 3;


    public function __construct($message, $numberOne, $numberTwo)
    {
        $this->message = $message;
        $this->queue = 'email-microservice-queue';
        $this->connection = 'sqs';
        $this->numberOne = $numberOne;
        $this->numberTwo = $numberTwo;
    }

    public function handle()
    {
        Log::info("Mensaje recibido desde SQS: {$this->message}");
        $suma = $this->numberOne + $this->numberTwo;
        Log::info("La suma es: {$suma}");
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
