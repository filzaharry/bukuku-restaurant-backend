# API Rate Limiting Documentation

## Overview
This application implements API rate limiting to protect against abuse and ensure system stability. Rate limiting is applied to critical authentication endpoints using a custom middleware.

## Rate Limiting Configuration

### Critical Endpoints

| Endpoint | Max Attempts | Time Window | Purpose |
|----------|--------------|-------------|---------|
| `POST /api/v1/auth/login` | 5 attempts | 1 minute | Prevent brute force login attacks |
| `POST /api/v1/auth/register` | 3 attempts | 1 minute | Prevent spam registration |
| `POST /api/v1/auth/google` | 10 attempts | 1 minute | Moderate protection for OAuth |
| `POST /api/v1/auth/forgot-password` | 3 attempts | 5 minutes | Prevent email bombing |
| `POST /api/v1/auth/verify-otp` | 10 attempts | 1 minute | Allow reasonable OTP attempts |
| `POST /api/v1/auth/reset-password` | 3 attempts | 5 minutes | Prevent password reset abuse |

## Rate Limiting Headers

All rate-limited responses include the following headers:

- `X-RateLimit-Limit`: Maximum number of requests allowed
- `X-RateLimit-Remaining`: Number of requests remaining in the current window
- `X-RateLimit-Reset`: Unix timestamp when the rate limit window resets
- `Retry-After`: Seconds until the client can retry (only on 429 responses)

## Rate Limit Response

When rate limit is exceeded, the API returns:

```json
{
    "statusCode": 429,
    "message": "Too many attempts. Please try again later.",
    "data": {
        "retry_after": 45,
        "max_attempts": 5
    }
}
```

## Implementation Details

### Middleware Configuration

The rate limiting middleware is registered in `bootstrap/app.php`:

```php
$middleware->alias([
    'rate.limit' => \App\Http\Middleware\RateLimitMiddleware::class,
]);
```

### Rate Limiting Logic

The middleware uses Laravel's built-in `RateLimiter` facade with the following features:

1. **Request Signature**: Based on IP, method, and path
2. **Sliding Window**: Attempts are tracked with expiration
3. **Custom Response**: JSON response with retry information
4. **Headers**: Standard rate limiting headers for client-side handling

### Key Features

- **IP-based tracking**: Each IP address has separate rate limits
- **Path-specific**: Different endpoints have different limits
- **Method-aware**: GET, POST, PUT, DELETE are tracked separately
- **Graceful degradation**: Returns informative error messages
- **Standard headers**: Compatible with HTTP rate limiting standards

## Security Benefits

1. **Brute Force Protection**: Prevents automated password guessing
2. **DDoS Mitigation**: Limits impact of high-volume attacks
3. **Resource Protection**: Prevents server overload
4. **Abuse Prevention**: Stops spam registration and email bombing

## Client-Side Integration

### Handling Rate Limits

```javascript
try {
    const response = await fetch('/api/v1/auth/login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(loginData)
    });
    
    if (response.status === 429) {
        const retryAfter = response.headers.get('Retry-After');
        const remaining = response.headers.get('X-RateLimit-Remaining');
        
        console.log(`Rate limited. Retry after ${retryAfter} seconds`);
        console.log(`Attempts remaining: ${remaining}`);
        
        // Show user-friendly message
        alert('Too many attempts. Please try again later.');
        return;
    }
    
    const data = await response.json();
    // Handle successful response
    
} catch (error) {
    console.error('Login error:', error);
}
```

### Rate Limit Display

```javascript
function updateRateLimitDisplay(response) {
    const limit = response.headers.get('X-RateLimit-Limit');
    const remaining = response.headers.get('X-RateLimit-Remaining');
    
    if (limit && remaining) {
        document.getElementById('rate-limit').textContent = 
            `${remaining}/${limit} requests remaining`;
    }
}
```

## Testing

Rate limiting is thoroughly tested with the following test cases:

1. **Login Endpoint**: 5 attempts allowed, 6th triggers rate limit
2. **Register Endpoint**: 3 attempts allowed, 4th triggers rate limit
3. **Forgot Password**: 3 attempts per 5 minutes
4. **OTP Verification**: 10 attempts allowed
5. **Headers Verification**: All responses include proper headers

Run tests with:
```bash
php artisan test --filter RateLimitTest
```

## Monitoring and Logging

Rate limiting attempts are logged and can be monitored:

```bash
# Monitor rate limit hits
tail -f storage/logs/laravel.log | grep "rate limit"

# Check Redis cache (if using Redis for rate limiting)
redis-cli keys "rate-limit:*"
```

## Customization

### Adding Rate Limits to New Endpoints

```php
// In routes file
Route::post('/new-endpoint', [Controller::class, 'method'])
     ->middleware('rate.limit:10,2') // 10 attempts per 2 minutes
     ->name('new.endpoint');
```

### Adjusting Existing Limits

Modify the parameters in the route definition:

```php
// More restrictive
->middleware('rate.limit:3,1') // 3 attempts per 1 minute

// More lenient
->middleware('rate.limit:20,5') // 20 attempts per 5 minutes
```

## Best Practices

1. **Monitor Rate Limits**: Regularly check for abuse patterns
2. **Adjust Limits**: Tune limits based on usage patterns
3. **User Feedback**: Provide clear messages when limits are hit
4. **Documentation**: Keep rate limit documentation updated
5. **Testing**: Continuously test rate limiting effectiveness

## Troubleshooting

### Common Issues

1. **Rate Limit Too Strict**: Users getting blocked frequently
   - Solution: Increase max attempts or time window

2. **Rate Limit Not Working**: No limits being applied
   - Check middleware registration in `bootstrap/app.php`
   - Verify route middleware syntax

3. **Headers Missing**: Rate limit headers not present
   - Ensure middleware is applied correctly
   - Check for syntax errors in route definition

### Debug Mode

To debug rate limiting issues, you can temporarily add logging:

```php
// In RateLimitMiddleware.php
\Log::info('Rate limit check', [
    'key' => $key,
    'attempts' => RateLimiter::attempts($key),
    'max_attempts' => $maxAttempts
]);
```

## Future Enhancements

1. **User-based Rate Limiting**: Different limits for authenticated vs anonymous users
2. **Dynamic Rate Limiting**: Adjust limits based on system load
3. **Whitelist IPs**: Exclude trusted IPs from rate limiting
4. **Advanced Analytics**: Detailed rate limiting metrics and reporting
