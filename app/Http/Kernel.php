<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    // ... existing code ...

    protected $routeMiddleware = [
        // ... other middleware ...
        'auth.jwt' => \App\Http\Middleware\JwtResponse::class, // Ensure this line is present
    ];

    // ... existing code ...
}