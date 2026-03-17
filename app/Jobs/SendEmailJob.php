<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];
    public $timeout = 30;

    protected $email;
    protected $subject;
    protected $content;

    /**
     * Create a new job instance.
     */
    public function __construct(string $email, string $subject, string $content)
    {
        $this->email = $email;
        $this->subject = $subject;
        $this->content = $content;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Sending email job started', [
                'email' => $this->email,
                'subject' => $this->subject,
                'job_id' => $this->job->getJobId(),
            ]);

            // Simulate email sending
            sleep(2);

            // In real implementation, you would use Mail facade
            // Mail::to($this->email)->send(new CustomEmail($this->subject, $this->content));

            Log::info('Email sent successfully', [
                'email' => $this->email,
                'subject' => $this->subject,
                'job_id' => $this->job->getJobId(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send email', [
                'email' => $this->email,
                'error' => $e->getMessage(),
                'job_id' => $this->job->getJobId(),
            ]);

            $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendEmailJob failed permanently', [
            'email' => $this->email,
            'error' => $exception->getMessage(),
            'job_id' => $this->job->getJobId(),
            'attempts' => $this->attempts(),
        ]);
    }
}
