# Laravel Horizon Setup Guide

## Overview

Laravel Horizon provides a beautiful dashboard and code-driven configuration for your Redis powered queues. Horizon allows you to easily monitor key metrics of your queue system such as job throughput, failures, retries, and wait times.

## Installation

### 1. Install Horizon

```bash
composer require laravel/horizon
```

### 2. Publish Assets

```bash
php artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider"
```

### 3. Install Horizon Scaffolding

```bash
php artisan horizon:install
```

## Configuration

### Environment Variables

Add these to your `.env` file:

```bash
# Horizon Configuration
HORIZON_NAME=Bukuku Restaurant
HORIZON_PATH=horizon
HORIZON_DOMAIN=null

# Redis Configuration (Required)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue Configuration
QUEUE_CONNECTION=redis
```

### Horizon Configuration

The `config/horizon.php` file contains the configuration options:

```php
return [
    'name' => env('HORIZON_NAME', 'Bukuku Restaurant'),
    'path' => env('HORIZON_PATH', 'horizon'),
    'domain' => env('HORIZON_DOMAIN'),
    
    // Redis connection
    'use' => 'default',
    
    // Queue workers
    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'maxProcesses' => 1,
            'memory' => 128,
            'tries' => 1,
            'timeout' => 60,
        ],
    ],
    
    // Environment-specific settings
    'environments' => [
        'production' => [
            'supervisor-1' => [
                'maxProcesses' => 10,
            ],
        ],
        'local' => [
            'supervisor-1' => [
                'maxProcesses' => 3,
            ],
        ],
    ],
];
```

## Queue Setup

### 1. Configure Redis

Make sure Redis is running and configured in `config/database.php`:

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
    ],
    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
    ],
],
```

### 2. Update Queue Configuration

In `config/queue.php`:

```php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
        'after_commit' => false,
    ],
],
```

## Jobs

### Available Jobs

#### 1. SendEmailJob
- **Purpose**: Send emails via queue
- **Tries**: 3
- **Backoff**: [10, 30, 60] seconds
- **Timeout**: 30 seconds

#### 2. ProcessOrderJob
- **Purpose**: Process restaurant orders
- **Tries**: 3
- **Backoff**: [5, 15, 30] seconds
- **Timeout**: 60 seconds

#### 3. GenerateReportJob
- **Purpose**: Generate reports (sales, orders, inventory)
- **Tries**: 2
- **Backoff**: [30, 60] seconds
- **Timeout**: 120 seconds

### Creating New Jobs

```bash
php artisan make:job YourJobName
```

Example job structure:

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class YourJobName implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function handle()
    {
        // Your job logic here
    }
}
```

## Running Horizon

### 1. Start Horizon

```bash
php artisan horizon
```

### 2. Start Horizon in Background

```bash
php artisan horizon &

# Or using supervisor (recommended for production)
```

### 3. Supervisor Configuration

Create `/etc/supervisor/conf.d/horizon.conf`:

```ini
[program:horizon]
process_name=%(program_name)s
command=php /path/to/your/project/artisan horizon
autostart=true
autorestart=true
user=your-user
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/horizon.log
stopwaitsecs=3600
```

Then:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start horizon
```

## Monitoring

### 1. Horizon Dashboard

Access Horizon at: `http://your-app.com/horizon`

Features:
- **Jobs Overview**: See all jobs, failed jobs, and recent jobs
- **Metrics**: Job throughput, wait times, and throughput trends
- **Failed Jobs**: Detailed failure information and retry options
- **Queue Monitoring**: Real-time queue status and worker information

### 2. API Endpoints

Use these endpoints for testing and monitoring:

```bash
# Get queue status
GET /queue/status

# Test email job
POST /queue/email
{
    "email": "test@example.com",
    "subject": "Test Email",
    "content": "Email content"
}

# Test order job
POST /queue/order
{
    "order_id": 1234,
    "action": "process"
}

# Test report job
POST /queue/report
{
    "report_type": "sales",
    "start_date": "2024-01-01",
    "end_date": "2024-01-31",
    "user_id": 1
}

# Test multiple jobs
POST /queue/multiple
{
    "count": 5,
    "type": "email"
}
```

### 3. CLI Commands

```bash
# Start Horizon
php artisan horizon

# Stop Horizon
php artisan horizon:terminate

# Pause Horizon
php artisan horizon:pause

# Continue Horizon
php artisan horizon:continue

# Clear failed jobs
php artisan horizon:clear

# List failed jobs
php artisan horizon:failed

# Take snapshot
php artisan horizon:snapshot

# Purge recent jobs
php artisan horizon:purge
```

## Scheduling

### Automatic Snapshots

Horizon automatically takes snapshots every 5 minutes via scheduler:

```php
// In routes/console.php
Schedule::command('horizon:snapshot')->everyFiveMinutes()->withoutOverlapping();
```

### Custom Snapshot Command

Custom command for additional logging:

```bash
php artisan app:horizon-snapshot-command
```

## Troubleshooting

### Common Issues

#### 1. Redis Connection Failed

```bash
# Check Redis status
redis-cli ping

# Check Redis configuration
php artisan config:cache
php artisan config:clear
```

#### 2. Jobs Not Processing

```bash
# Check queue status
php artisan queue:status

# Clear queue
php artisan queue:clear

# Restart workers
php artisan horizon:terminate
php artisan horizon
```

#### 3. Horizon Dashboard Not Loading

```bash
# Check if Horizon is running
ps aux | grep horizon

# Check logs
tail -f storage/logs/horizon.log

# Clear cache
php artisan optimize:clear
```

#### 4. Jobs Failing

```bash
# Check failed jobs
php artisan horizon:failed

# View job details
php artisan tinker
>>> $failedJobs = \Laravel\Horizon\Models\JobFailed::get();
>>> $failedJobs->take(5)->each(function($job) {
>>>     dump($job->exception);
>>> });
```

### Debug Mode

Enable debug logging in `.env`:

```bash
LOG_LEVEL=debug
```

Add logging to your jobs:

```php
use Illuminate\Support\Facades\Log;

public function handle()
{
    Log::info('Job started', ['job_id' => $this->job->getJobId()]);
    
    // Your job logic
    
    Log::info('Job completed', ['job_id' => $this->job->getJobId()]);
}
```

## Performance Tuning

### 1. Worker Configuration

Adjust worker settings in `config/horizon.php`:

```php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'maxProcesses' => 10,  // Increase for high throughput
            'memory' => 256,      // Increase for memory-intensive jobs
            'timeout' => 300,     // Increase for long-running jobs
        ],
    ],
],
```

### 2. Redis Optimization

```bash
# Redis configuration for production
maxmemory 2gb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

### 3. Queue Priorities

Use different queues for different priorities:

```php
// High priority
SendEmailJob::dispatch($email, $subject, $content)
    ->onQueue('high-priority');

// Low priority
GenerateReportJob::dispatch($type, $start, $end)
    ->onQueue('low-priority');
```

## Security

### 1. Authentication

Horizon uses Laravel's authentication. Add middleware in `config/horizon.php`:

```php
'middleware' => ['web', 'auth'],
```

### 2. IP Restrictions

Add IP restrictions via web server or middleware:

```php
'middleware' => ['web', 'auth', 'ip:192.168.1.0/24'],
```

## Best Practices

### 1. Job Design

- Keep jobs small and focused
- Use proper error handling
- Set appropriate timeouts
- Use retries with exponential backoff
- Log important events

### 2. Monitoring

- Monitor queue depth regularly
- Set up alerts for failed jobs
- Track job completion times
- Monitor Redis memory usage

### 3. Maintenance

- Regularly clear old failed jobs
- Monitor worker memory usage
- Restart workers periodically
- Keep Redis optimized

## Integration with Bukuku

### Restaurant Order Processing

```php
// When order is created
ProcessOrderJob::dispatch($orderId, 'process');

// When order is ready
ProcessOrderJob::dispatch($orderId, 'complete');

// Send email notifications
SendEmailJob::dispatch($customerEmail, 'Order Status Update', $message);
```

### Report Generation

```php
// Daily sales report
GenerateReportJob::dispatch('sales', $startDate, $endDate, $userId);

// Weekly inventory report
GenerateReportJob::dispatch('inventory', $startDate, $endDate, $userId);
```

## Support

### Documentation

- [Laravel Horizon Official Docs](https://laravel.com/docs/horizon)
- [Laravel Queue Documentation](https://laravel.com/docs/queues)
- [Redis Documentation](https://redis.io/documentation)

### Getting Help

- Check Laravel logs: `storage/logs/laravel.log`
- Check Horizon logs: `storage/logs/horizon.log`
- Use Redis CLI: `redis-cli monitor`
- Check job status in Horizon dashboard

---

**Horizon is now configured and ready for use in Bukuku Restaurant Management System!**
