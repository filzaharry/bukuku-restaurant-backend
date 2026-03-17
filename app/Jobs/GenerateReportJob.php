<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $backoff = [30, 60];
    public $timeout = 120;

    protected $reportType;
    protected $startDate;
    protected $endDate;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $reportType, string $startDate, string $endDate, int $userId = null)
    {
        $this->reportType = $reportType;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Generating report job started', [
                'report_type' => $this->reportType,
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'user_id' => $this->userId,
                'job_id' => $this->job->getJobId(),
            ]);

            // Simulate report generation (could be CPU intensive)
            sleep(5);

            // Generate report data
            $reportData = $this->generateReportData();

            // Save report to storage
            $filePath = $this->saveReport($reportData);

            // Send notification when ready
            // SendEmailJob::dispatch($userEmail, 'Report Ready', "Your {$this->reportType} report is ready.");

            Log::info('Report generated successfully', [
                'report_type' => $this->reportType,
                'file_path' => $filePath,
                'job_id' => $this->job->getJobId(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate report', [
                'report_type' => $this->reportType,
                'error' => $e->getMessage(),
                'job_id' => $this->job->getJobId(),
            ]);

            $this->fail($e);
        }
    }

    /**
     * Generate report data.
     */
    private function generateReportData(): array
    {
        // Simulate data generation based on report type
        return match ($this->reportType) {
            'sales' => $this->generateSalesReport(),
            'orders' => $this->generateOrdersReport(),
            'inventory' => $this->generateInventoryReport(),
            default => [],
        };
    }

    /**
     * Generate sales report data.
     */
    private function generateSalesReport(): array
    {
        Log::info('Generating sales report data');
        
        // Simulate database queries
        return [
            'total_sales' => rand(10000, 50000),
            'total_orders' => rand(100, 500),
            'average_order_value' => rand(50, 200),
            'top_selling_items' => [
                ['name' => 'Nasi Goreng', 'quantity' => 150, 'revenue' => 3000],
                ['name' => 'Ayam Bakar', 'quantity' => 120, 'revenue' => 3600],
            ],
            'period' => [
                'start' => $this->startDate,
                'end' => $this->endDate,
            ],
        ];
    }

    /**
     * Generate orders report data.
     */
    private function generateOrdersReport(): array
    {
        Log::info('Generating orders report data');
        
        return [
            'total_orders' => rand(200, 800),
            'completed_orders' => rand(180, 750),
            'cancelled_orders' => rand(5, 50),
            'average_preparation_time' => rand(10, 25) . ' minutes',
            'peak_hours' => ['12:00-13:00', '18:00-19:00'],
        ];
    }

    /**
     * Generate inventory report data.
     */
    private function generateInventoryReport(): array
    {
        Log::info('Generating inventory report data');
        
        return [
            'low_stock_items' => [
                ['name' => 'Ayam', 'current_stock' => 15, 'min_stock' => 20],
                ['name' => 'Nasi', 'current_stock' => 50, 'min_stock' => 100],
            ],
            'total_items' => rand(50, 200),
            'total_value' => rand(50000, 200000),
        ];
    }

    /**
     * Save report to storage.
     */
    private function saveReport(array $data): string
    {
        $filename = "reports/{$this->reportType}_{$this->startDate}_to_{$this->endDate}.json";
        $jsonContent = json_encode($data, JSON_PRETTY_PRINT);

        // Save to storage (could be MinIO in production)
        Storage::put($filename, $jsonContent);

        Log::info('Report saved to storage', ['filename' => $filename]);

        return $filename;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateReportJob failed permanently', [
            'report_type' => $this->reportType,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'error' => $exception->getMessage(),
            'job_id' => $this->job->getJobId(),
            'attempts' => $this->attempts(),
        ]);
    }
}
