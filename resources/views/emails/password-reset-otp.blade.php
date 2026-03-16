<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode OTP Bukuku</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        .otp-box {
            background-color: #f8f9fa;
            border: 2px dashed #007bff;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .otp-code {
            font-size: 32px;
            font-weight: bold;
            color: #007bff;
            letter-spacing: 5px;
            margin: 10px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Bukuku</div>
            <h2>Kode Verifikasi OTP</h2>
        </div>
        
        <p>Halo,</p>
        <p>Anda telah meminta kode OTP untuk keperluan verifikasi akun Bukuku Anda. Berikut adalah kode OTP Anda:</p>
        
        <div class="otp-box">
            <div class="otp-code">{{ $otp }}</div>
        </div>
        
        <div class="warning">
            <strong>Penting:</strong> Jangan bagikan kode OTP ini kepada siapa pun. Kode ini akan kadaluarsa dalam 15 menit.
        </div>
        
        <p>Jika Anda tidak meminta kode ini, silakan abaikan email ini.</p>
        
        <div class="footer">
            <p>© 2026 Bukuku. Semua hak dilindungi.</p>
            <p>Ini adalah email otomatis. Mohon jangan balas email ini.</p>
        </div>
    </div>
</body>
</html>
