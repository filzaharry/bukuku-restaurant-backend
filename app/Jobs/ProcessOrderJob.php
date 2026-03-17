<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [5, 15, 30];
    public $timeout = 60;

    protected $orderId;
    protected $action;

    /**
     * Create a new job instance.
     */
    public function __construct(int $orderId, string $action = 'process')
    {
        $this->orderId = $orderId;
        $this->action = $action;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Processing order job started', [
                'order_id' => $this->orderId,
                'action' => $this->action,
                'job_id' => $this->job->getJobId(),
            ]);

            // Simulate order processing
            sleep(3);

            // Update order status in database
            $this->updateOrderStatus();

            // Send notification (could be another job)
            // SendEmailJob::dispatch($customerEmail, 'Order Status Update', $message);

            Log::info('Order processed successfully', [
                'order_id' => $this->orderId,
                'action' => $this->action,
                'job_id' => $this->job->getJobId(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process order', [
                'order_id' => $this->orderId,
                'action' => $this->action,
                'error' => $e->getMessage(),
                'job_id' => $this->job->getJobId(),
            ]);

            $this->fail($e);
        }
    }

    /**
     * Update order status in database.
     */
    private function updateOrderStatus(): void
    {
        // Simulate database update
        Log::info('Updating order status', [
            'order_id' => $this->orderId,
            'new_status' => $this->getNewStatus(),
        ]);

        // In real implementation:
        // DB::table('fnb_orders')
        //     ->where('id', $this->orderId)
        //     ->update(['status' => $this->getNewStatus()]);
    }

    /**
     * Get new status based on action.
     */
    private function getNewStatus(): string
    {
        return match ($this->action) {
            'process' => 'preparing',
            'complete' => 'completed',
            'cancel' => 'cancelled',
            default => 'pending',
        };
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessOrderJob failed permanently', [
            'order_id' => $this->orderId,
            'action' => $this->action,
            'error' => $exception->getMessage(),
            'job_id' => $this->job->getJobId(),
            'attempts' => $this->attempts(),
        ]);
    }
}
