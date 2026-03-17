<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailJob;
use App\Jobs\ProcessOrderJob;
use App\Jobs\GenerateReportJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;

class JobTestController extends Controller
{
    /**
     * Test dispatching email jobs.
     */
    public function testEmailJob(Request $request)
    {
        try {
            $email = $request->input('email', 'test@example.com');
            $subject = $request->input('subject', 'Test Email from Bukuku');
            $content = $request->input('content', 'This is a test email sent via queue job.');

            // Dispatch job to queue
            SendEmailJob::dispatch($email, $subject, $content);

            Log::info('Email job dispatched', [
                'email' => $email,
                'subject' => $subject,
            ]);

            return Response::json([
                'statusCode' => 200,
                'message' => 'Email job dispatched successfully',
                'data' => [
                    'email' => $email,
                    'subject' => $subject,
                    'job_class' => 'SendEmailJob',
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to dispatch email job', ['error' => $e->getMessage()]);

            return Response::json([
                'statusCode' => 500,
                'message' => 'Failed to dispatch email job',
                'data' => ['error' => $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Test dispatching order processing jobs.
     */
    public function testOrderJob(Request $request)
    {
        try {
            $orderId = $request->input('order_id', rand(1000, 9999));
            $action = $request->input('action', 'process');

            // Validate action
            $validActions = ['process', 'complete', 'cancel'];
            if (!in_array($action, $validActions)) {
                return Response::json([
                    'statusCode' => 422,
                    'message' => 'Invalid action',
                    'data' => ['valid_actions' => $validActions],
                ], 422);
            }

            // Dispatch job to queue
            ProcessOrderJob::dispatch($orderId, $action);

            Log::info('Order job dispatched', [
                'order_id' => $orderId,
                'action' => $action,
            ]);

            return Response::json([
                'statusCode' => 200,
                'message' => 'Order processing job dispatched successfully',
                'data' => [
                    'order_id' => $orderId,
                    'action' => $action,
                    'job_class' => 'ProcessOrderJob',
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to dispatch order job', ['error' => $e->getMessage()]);

            return Response::json([
                'statusCode' => 500,
                'message' => 'Failed to dispatch order job',
                'data' => ['error' => $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Test dispatching report generation jobs.
     */
    public function testReportJob(Request $request)
    {
        try {
            $reportType = $request->input('report_type', 'sales');
            $startDate = $request->input('start_date', now()->subDays(7)->format('Y-m-d'));
            $endDate = $request->input('end_date', now()->format('Y-m-d'));
            $userId = $request->input('user_id', 1);

            // Validate report type
            $validTypes = ['sales', 'orders', 'inventory'];
            if (!in_array($reportType, $validTypes)) {
                return Response::json([
                    'statusCode' => 422,
                    'message' => 'Invalid report type',
                    'data' => ['valid_types' => $validTypes],
                ], 422);
            }

            // Dispatch job to queue
            GenerateReportJob::dispatch($reportType, $startDate, $endDate, $userId);

            Log::info('Report job dispatched', [
                'report_type' => $reportType,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'user_id' => $userId,
            ]);

            return Response::json([
                'statusCode' => 200,
                'message' => 'Report generation job dispatched successfully',
                'data' => [
                    'report_type' => $reportType,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'user_id' => $userId,
                    'job_class' => 'GenerateReportJob',
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to dispatch report job', ['error' => $e->getMessage()]);

            return Response::json([
                'statusCode' => 500,
                'message' => 'Failed to dispatch report job',
                'data' => ['error' => $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Dispatch multiple jobs for testing.
     */
    public function testMultipleJobs(Request $request)
    {
        try {
            $jobCount = $request->input('count', 5);
            $jobType = $request->input('type', 'email');

            $dispatchedJobs = [];

            for ($i = 1; $i <= $jobCount; $i++) {
                switch ($jobType) {
                    case 'email':
                        SendEmailJob::dispatch(
                            "test{$i}@example.com",
                            "Test Email #{$i}",
                            "This is test email number {$i}"
                        );
                        $dispatchedJobs[] = "SendEmailJob #{$i}";
                        break;

                    case 'order':
                        ProcessOrderJob::dispatch(1000 + $i, 'process');
                        $dispatchedJobs[] = "ProcessOrderJob #{$i}";
                        break;

                    case 'report':
                        GenerateReportJob::dispatch('sales', '2024-01-01', '2024-01-31', $i);
                        $dispatchedJobs[] = "GenerateReportJob #{$i}";
                        break;
                }
            }

            Log::info('Multiple jobs dispatched', [
                'count' => $jobCount,
                'type' => $jobType,
            ]);

            return Response::json([
                'statusCode' => 200,
                'message' => "Successfully dispatched {$jobCount} {$jobType} jobs",
                'data' => [
                    'count' => $jobCount,
                    'type' => $jobType,
                    'dispatched_jobs' => $dispatchedJobs,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to dispatch multiple jobs', ['error' => $e->getMessage()]);

            return Response::json([
                'statusCode' => 500,
                'message' => 'Failed to dispatch multiple jobs',
                'data' => ['error' => $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Get queue status information.
     */
    public function getQueueStatus()
    {
        try {
            // Get Redis connection info
            $redis = app('redis');
            $redisInfo = $redis->info();

            // Get queue size
            $queues = [
                'default' => $redis->llen('queues:default'),
                'emails' => $redis->llen('queues:emails'),
                'reports' => $redis->llen('queues:reports'),
            ];

            return Response::json([
                'statusCode' => 200,
                'message' => 'Queue status retrieved successfully',
                'data' => [
                    'redis_connected' => $redis->ping() === '+PONG',
                    'redis_version' => $redisInfo['redis_version'] ?? 'Unknown',
                    'queues' => $queues,
                    'total_pending' => array_sum($queues),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get queue status', ['error' => $e->getMessage()]);

            return Response::json([
                'statusCode' => 500,
                'message' => 'Failed to get queue status',
                'data' => ['error' => $e->getMessage()],
            ], 500);
        }
    }
}
