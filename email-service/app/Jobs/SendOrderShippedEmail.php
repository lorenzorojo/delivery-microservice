<?php

namespace App\Jobs;

use DateTime;
use App\Mail\OrderShipped;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderShippedEmail implements ShouldQueue
{
    use Queueable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public $order;
    public $fromAddress;
    public $toAddress;
    public $subject;
    public $contentBody;
    /**
     * Create a new job instance.
     */
    public function __construct($order, $from, $to, $sub, $content)
    {
        $this->order = $order;
        $this->fromAddress = $from;
        $this->toAddress = $to;
        $this->subject = $sub;
        $this->contentBody = $content;
        // $this->queue = 'email-microservice-queue';
        // $this->connection = 'sqs';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::send(new OrderShipped(
            $this->order,
            $this->fromAddress,
            $this->toAddress,
            $this->subject,
            $this->contentBody
        ));
    }


    public function fail($exception = null)
    {
        Log::error("Error: " . __CLASS__, [$exception]);
    }

    public function retryUntil(): DateTime
    {
        return now()->addMinutes(1);
    }
}
