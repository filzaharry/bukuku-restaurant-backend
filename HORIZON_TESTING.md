# Laravel Horizon Testing Guide

## Quick Start Commands

### 1. Start Redis (if not running)
```bash
# Start Redis server
redis-server

# Or using Docker
docker run -d --name redis -p 6379:6379 redis:7-alpine

# Check Redis status
redis-cli ping
```

### 2. Start Horizon
```bash
# Start Horizon in foreground
php artisan horizon

# Start Horizon in background
php artisan horizon &

# Check Horizon status
php artisan horizon:status
```

### 3. Access Horizon Dashboard
```
URL: http://127.0.0.1:8001/horizon
```

## Testing Jobs

### 1. Test Email Job
```bash
curl -X POST http://127.0.0.1:8001/api/queue/email \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "subject": "Test Email from Bukuku",
    "content": "This is a test email sent via queue job."
  }'
```

### 2. Test Order Job
```bash
curl -X POST http://127.0.0.1:8001/api/queue/order \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": 1234,
    "action": "process"
  }'
```

### 3. Test Report Job
```bash
curl -X POST http://127.0.0.1:8001/api/queue/report \
  -H "Content-Type: application/json" \
  -d '{
    "report_type": "sales",
    "start_date": "2024-01-01",
    "end_date": "2024-01-31",
    "user_id": 1
  }'
```

### 4. Test Multiple Jobs
```bash
curl -X POST http://127.0.0.1:8001/api/queue/multiple \
  -H "Content-Type: application/json" \
  -d '{
    "count": 10,
    "type": "email"
  }'
```

### 5. Check Queue Status
```bash
curl -X GET http://127.0.0.1:8001/api/queue/status
```

## Manual Job Testing

### Using Tinker
```bash
php artisan tinker
```

```php
// Test email job
App\Jobs\SendEmailJob::dispatch('test@example.com', 'Test Subject', 'Test Content');

// Test order job
App\Jobs\ProcessOrderJob::dispatch(1234, 'process');

// Test report job
App\Jobs\GenerateReportJob::dispatch('sales', '2024-01-01', '2024-01-31', 1);

// Dispatch multiple jobs
for ($i = 1; $i <= 5; $i++) {
    App\Jobs\SendEmailJob::dispatch("test{$i}@example.com", "Test Email #{$i}", "Content #{$i}");
}
```

## Monitoring Commands

### Horizon Commands
```bash
# Check Horizon status
php artisan horizon:status

# Check supervisor status
php artisan horizon:supervisor-status

# Take snapshot
php artisan horizon:snapshot

# Clear failed jobs
php artisan horizon:clear

# List failed jobs
php artisan horizon:failed

# Pause Horizon
php artisan horizon:pause

# Resume Horizon
php artisan horizon:continue

# Terminate Horizon
php artisan horizon:terminate
```

### Queue Commands
```bash
# Check queue length
redis-cli llen queues:default

# Monitor Redis
redis-cli monitor

# Check Redis info
redis-cli info

# Clear all queues
php artisan queue:clear

# Retry failed jobs
php artisan queue:retry all

# List failed jobs
php artisan queue:failed
```

## Testing Scenarios

### 1. Basic Job Processing
```bash
# Step 1: Start Horizon
php artisan horizon &

# Step 2: Dispatch a job
curl -X POST http://127.0.0.1:8001/api/queue/email \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "subject": "Test", "content": "Content"}'

# Step 3: Check Horizon dashboard
# Visit: http://127.0.0.1:8001/horizon

# Step 4: Check logs
tail -f storage/logs/laravel.log
```

### 2. Multiple Job Types
```bash
# Dispatch different job types
curl -X POST http://127.0.0.1:8001/api/queue/email -d '{"email": "test@example.com"}' -H "Content-Type: application/json"
curl -X POST http://127.0.0.1:8001/api/queue/order -d '{"order_id": 1234}' -H "Content-Type: application/json"
curl -X POST http://127.0.0.1:8001/api/queue/report -d '{"report_type": "sales"}' -H "Content-Type: application/json"

# Monitor in Horizon dashboard
# Check processing times and success rates
```

### 3. Stress Testing
```bash
# Dispatch 50 jobs
curl -X POST http://127.0.0.1:8001/api/queue/multiple \
  -H "Content-Type: application/json" \
  -d '{"count": 50, "type": "email"}'

# Monitor queue depth
watch -n 2 'redis-cli llen queues:default'

# Check Horizon metrics
# Visit: http://127.0.0.1:8001/horizon/metrics
```

### 4. Failure Testing
```bash
# Create a job that will fail
php artisan tinker
```

```php
// Create failing job
class FailingJob implements ShouldQueue {
    public function handle() {
        throw new Exception('Test failure');
    }
}
```

```bash
# Dispatch failing job
App\Jobs\FailingJob::dispatch();

# Check failed jobs in Horizon
# Visit: http://127.0.0.1:8001/horizon/failed
```

## Performance Testing

### 1. Throughput Test
```bash
# Dispatch 100 jobs and measure time
time curl -X POST http://127.0.0.1:8001/api/queue/multiple \
  -H "Content-Type: application/json" \
  -d '{"count": 100, "type": "email"}'

# Monitor processing time in Horizon
```

### 2. Memory Test
```bash
# Monitor memory usage
watch -n 5 'ps aux | grep horizon'

# Dispatch memory-intensive jobs
php artisan tinker
```

```php
// Create memory-intensive job
App\Jobs\GenerateReportJob::dispatch('sales', '2024-01-01', '2024-12-31', 1);
```

## Troubleshooting Tests

### 1. Redis Connection Test
```bash
# Test Redis connection
redis-cli ping

# Test Laravel Redis connection
php artisan tinker
```

```php
// Test Redis in Laravel
$redis = app('redis');
$redis->set('test', 'value');
echo $redis->get('test');
```

### 2. Queue Configuration Test
```bash
# Test queue configuration
php artisan tinker
```

```php
// Test queue connection
config('queue.default');
config('queue.connections.redis');

// Test job dispatch
$job = new App\Jobs\SendEmailJob('test@example.com', 'Test', 'Content');
dispatch($job);
```

### 3. Horizon Configuration Test
```bash
# Check Horizon config
php artisan tinker
```

```php
// Check Horizon configuration
config('horizon.name');
config('horizon.defaults');
config('horizon.environments');
```

## Expected Results

### Successful Job Processing
1. **Job Dispatched**: 200 OK response from API
2. **Job in Queue**: Visible in Horizon dashboard under "Recent Jobs"
3. **Job Processing**: Status changes to "Processing"
4. **Job Completed**: Status changes to "Completed"
5. **Metrics Updated**: Throughput and wait time metrics updated

### Failed Job Processing
1. **Job Dispatched**: 200 OK response from API
2. **Job Processing**: Status changes to "Processing"
3. **Job Failed**: Status changes to "Failed"
4. **Error Logged**: Exception details logged
5. **Retry Logic**: Job retried based on backoff configuration

### Dashboard Features
- **Overview**: Total jobs, jobs per minute, failed jobs
- **Recent Jobs**: List of recent jobs with status
- **Failed Jobs**: Detailed failure information
- **Metrics**: Throughput, wait times, and trends
- **Monitoring**: Real-time queue status

## Production Readiness Checklist

### Configuration
- [ ] Redis server running and optimized
- [ ] Horizon configured for production
- [ ] Supervisor configured for auto-restart
- [ ] Environment variables set correctly
- [ ] Scheduler running for snapshots

### Monitoring
- [ ] Horizon dashboard accessible
- [ ] Log monitoring configured
- [ ] Alerting for failed jobs
- [ ] Performance metrics collection
- [ ] Memory and CPU monitoring

### Security
- [ ] Authentication enabled for Horizon
- [ ] IP restrictions configured
- [ ] HTTPS enabled in production
- [ ] Redis authentication configured
- [ ] Firewall rules configured

## Common Issues & Solutions

### Issue: Jobs not processing
**Solution**: Check if Horizon is running
```bash
php artisan horizon:status
# If inactive, start Horizon
php artisan horizon
```

### Issue: Redis connection failed
**Solution**: Check Redis configuration
```bash
redis-cli ping
# Check Redis settings in .env
```

### Issue: Horizon dashboard not loading
**Solution**: Check web server and routing
```bash
php artisan route:list | grep horizon
# Check if horizon route exists
```

### Issue: Jobs failing repeatedly
**Solution**: Check job logic and error handling
```bash
php artisan horizon:failed
# Review failure reasons
```

---

**Horizon is now fully configured and ready for testing!**
