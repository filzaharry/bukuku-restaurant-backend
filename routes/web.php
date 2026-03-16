<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// API Documentation Route
Route::get('/api-docs', function () {
    $routes = [
        'GET /api' => 'Welcome message - Returns Hello World',
        'GET /api/test' => 'Test endpoint - Returns API status with timestamp',
        'POST /api/test/post' => 'Test POST endpoint - Accepts name and optional email',
        'POST /api/v1/auth/login' => 'User login - Returns JWT token',
        'POST /api/v1/auth/register' => 'User registration - Creates new user account',
        'POST /api/v1/auth/forgot-password' => 'Forgot password - Sends OTP to email',
        'POST /api/v1/auth/verify-otp' => 'Verify OTP - Verifies OTP and returns reset token',
        'POST /api/v1/auth/reset-password' => 'Reset password - Resets password with token',
    ];

    $html = '<!DOCTYPE html>
<html>
<head>
    <title>Bukuku API Documentation</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .endpoint { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .method { display: inline-block; padding: 5px 10px; color: white; font-weight: bold; border-radius: 3px; }
        .get { background-color: #61affe; }
        .post { background-color: #49cc90; }
        .path { font-weight: bold; margin-left: 10px; }
        .description { margin-top: 10px; color: #666; }
        .test-section { margin-top: 20px; }
        .test-button { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        .test-result { margin-top: 10px; padding: 10px; background-color: #f8f9fa; border-radius: 5px; white-space: pre-wrap; font-family: monospace; }
    </style>
</head>
<body>
    <h1>Bukuku API Documentation</h1>
    <p>Base URL: <strong>http://127.0.0.1:8000</strong></p>
    
    <div class="test-section">
        <h3>Quick Test</h3>
        <button class="test-button" onclick="testEndpoint(\'GET\', \'/api\')">Test GET /api</button>
        <button class="test-button" onclick="testEndpoint(\'GET\', \'/api/test\')">Test GET /api/test</button>
        <button class="test-button" onclick="testPostEndpoint()">Test POST /api/test/post</button>
        <div id="test-result" class="test-result" style="display:none;"></div>
    </div>';

    foreach ($routes as $endpoint => $description) {
        $method = explode(' ', $endpoint)[0];
        $path = explode(' ', $endpoint)[1];
        $methodClass = strtolower($method);
        
        $html .= "
    <div class=\"endpoint\">
        <span class=\"method {$methodClass}\">{$method}</span>
        <span class=\"path\">{$path}</span>
        <div class=\"description\">{$description}</div>
    </div>";
    }

    $html .= '
    <script>
        async function testEndpoint(method, path) {
            const resultDiv = document.getElementById("test-result");
            resultDiv.style.display = "block";
            resultDiv.textContent = "Loading...";
            
            try {
                const response = await fetch(`http://127.0.0.1:8000${path}`, {
                    method: method,
                    headers: {
                        "Accept": "application/json",
                        "Content-Type": "application/json"
                    }
                });
                
                const data = await response.json();
                resultDiv.textContent = `Status: ${response.status}\n\nResponse:\n${JSON.stringify(data, null, 2)}`;
            } catch (error) {
                resultDiv.textContent = `Error: ${error.message}`;
            }
        }
        
        async function testPostEndpoint() {
            const resultDiv = document.getElementById("test-result");
            resultDiv.style.display = "block";
            resultDiv.textContent = "Loading...";
            
            try {
                const response = await fetch("http://127.0.0.1:8000/api/test/post", {
                    method: "POST",
                    headers: {
                        "Accept": "application/json",
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        name: "Test User",
                        email: "test@example.com"
                    })
                });
                
                const data = await response.json();
                resultDiv.textContent = `Status: ${response.status}\n\nResponse:\n${JSON.stringify(data, null, 2)}`;
            } catch (error) {
                resultDiv.textContent = `Error: ${error.message}`;
            }
        }
    </script>
</body>
</html>';

    return $html;
});
