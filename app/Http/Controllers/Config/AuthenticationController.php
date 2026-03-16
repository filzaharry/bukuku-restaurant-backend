<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Helpers\ResponseHelper;
use App\Models\User;
use App\Models\UserDevice;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Mail\PasswordResetOtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class AuthenticationController extends Controller
{

    public function googleLogin(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required|string',
                'device' => 'required|array'
            ]);

            // Get user info from Google token
            $client = new \Google_Client(['client_id' => config('services.google.client_id')]);
            $payload = $client->verifyIdToken($request->token);

            if (!$payload) {
                return ResponseHelper::jsonResponse(401, 'Invalid Google token');
            }

            $email = $payload['email'];
            $name = $payload['name'];
            $googleId = $payload['sub'];

            // Check if user exists
            $user = User::where('email', $email)->first();

            if (!$user) {
                // Create new user with random password
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'google_id' => $googleId,
                    'phone' => '0',
                    'level_id' => 3, // Default level User Regular
                    'status' => 1,
                    'password' => Hash::make(Str::random(16)) // Generate random password
                ]);
            } else {
                // Update existing user data
                $user->update([
                    'name' => $name,
                    'google_id' => $googleId,
                    'status' => 1
                ]);
            }

            // Generate JWT token
            $token = auth('jwt')->login($user);

            // Update or create device info
            if ($request->has('device')) {
                UserDevice::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'device_name' => $request->device['device_name'] ?? null,
                        'device_os' => $request->device['device_os'] ?? null,
                        'device_platform' => $request->device['device_platform'] ?? null
                    ],
                    [
                        'firebase_id' => $request->device['firebase_id'] ?? null,
                        'device_imei' => $request->device['device_imei'] ?? null,
                        'app_version' => $request->device['app_version'] ?? null
                    ]
                );
            }

            return ResponseHelper::jsonResponse(200, 'Login successful', [
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('jwt')->factory()->getTTL() * 60,
                'user' => $user
            ]);
        } catch (ValidationException $e) {
            return ResponseHelper::jsonResponse(422, 'Validation error', $e->errors());
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Login failed', $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // REGISTER
    // -------------------------------------------------------------------------

    public function register(RegisterRequest $request)
    {
        try {
            $validated = $request->validated();

            $user = User::create([
                'username' => $validated['username'],
                'name'     => $validated['fullname'],
                'email'    => $validated['email'],
                'phone'    => $validated['phone'],
                'level_id' => 2,
                'status'   => 1,
                'password' => Hash::make($validated['password']),
            ]);

            return ResponseHelper::jsonResponse(201, 'Registrasi berhasil. Silakan login.', $user);

        } catch (ValidationException $e) {
            return ResponseHelper::jsonResponse(422, 'Validasi gagal', $e->errors());
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Registrasi gagal', $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // LOGIN
    // Flow: login() → kirim OTP → verifyOtp(purpose:login) → dapat JWT token
    // -------------------------------------------------------------------------

    public function login(LoginRequest $request)
    {
        try {
            $credentials = $request->validated();

            $user = User::where('email', $credentials['email'])->first();

            if (!$user) {
                return ResponseHelper::jsonResponse(401, 'Email tidak ditemukan.');
            }

            if (!Hash::check($credentials['password'], $user->password)) {
                return ResponseHelper::jsonResponse(401, 'Password yang kamu masukkan salah.');
            }

            $this->generateAndSendOtp($credentials['email'], 'login');

            return ResponseHelper::jsonResponse(200, 'Kode OTP telah dikirim ke email kamu. Silakan verifikasi untuk melanjutkan.', [
                'next_step' => 'verify_otp',
            ]);

        } catch (ValidationException $e) {
            $flattenedErrors = collect($e->errors())->flatten()->all();
            return ResponseHelper::jsonResponse(422, 'Validasi gagal', $flattenedErrors);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Login gagal', $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // VERIFY OTP
    // purpose: login        → langsung return JWT token
    // purpose: forgot_password → mark verified, lanjut ke reset password
    // -------------------------------------------------------------------------

    public function verifyOtp(Request $request)
    {
        try {
            $request->validate([
                'email'   => 'required|email|exists:users,email',
                'otp'     => 'required|numeric',
                'purpose' => 'required|in:login,forgot_password',
            ]);

            $record = DB::table('password_resets')
                ->where('email', $request->email)
                ->where('otp', $request->otp)
                ->where('purpose', $request->purpose)
                ->first();

            if (!$record) {
                return ResponseHelper::jsonResponse(401, 'Kode OTP yang kamu masukkan salah. Silakan cek email kamu kembali.');
            }

            if (now()->isAfter($record->expires_at)) {
                DB::table('password_resets')->where('email', $request->email)->delete();
                return ResponseHelper::jsonResponse(401, 'Kode OTP sudah kadaluarsa. Silakan ulangi proses dari awal.');
            }

            if ($request->purpose === 'login') {
                $user  = User::where('email', $request->email)->first();
                $token = auth('jwt')->tokenById($user->id);

                // Simpan device jika ada
                if ($request->has('device')) {
                    UserDevice::create([
                        'user_id'         => $user->id,
                        'firebase_id'     => $request->device['firebase_id'] ?? null,
                        'device_imei'     => $request->device['device_imei'] ?? null,
                        'device_name'     => $request->device['device_name'] ?? null,
                        'device_os'       => $request->device['device_os'] ?? null,
                        'device_platform' => $request->device['device_platform'] ?? null,
                        'app_version'     => $request->device['app_version'] ?? null,
                    ]);
                }

                DB::table('password_resets')->where('email', $request->email)->delete();

                return ResponseHelper::jsonResponse(200, 'Verifikasi berhasil. Selamat datang!', [
                    'token'      => $token,
                    'token_type' => 'bearer',
                    'expires_in' => auth('jwt')->factory()->getTTL() * 60,
                    'user'       => $user,
                ]);
            }

            // forgot_password → mark verified, extend expiry untuk reset password
            DB::table('password_resets')
                ->where('email', $request->email)
                ->where('otp', $request->otp)
                ->update([
                    'verified'   => true,
                    'expires_at' => now()->addMinutes(30),
                    'updated_at' => now(),
                ]);

            return ResponseHelper::jsonResponse(200, 'OTP berhasil diverifikasi. Silakan buat password baru kamu.', [
                'next_step' => 'reset_password',
            ]);

        } catch (ValidationException $e) {
            return ResponseHelper::jsonResponse(422, 'Validasi gagal', $e->errors());
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Gagal memverifikasi OTP', $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // FORGOT PASSWORD
    // Flow: forgotPassword() → kirim OTP → verifyOtp(purpose:forgot_password) → resetPassword()
    // -------------------------------------------------------------------------

    public function forgotPassword(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email|exists:users,email']);

            $this->generateAndSendOtp($request->email, 'forgot_password');

            return ResponseHelper::jsonResponse(200, 'Kode OTP telah dikirim ke email kamu. Silakan cek inbox atau folder spam.');

        } catch (ValidationException $e) {
            return ResponseHelper::jsonResponse(422, 'Validasi gagal', $e->errors());
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Gagal mengirim OTP', $e->getMessage());
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'email'    => 'required|email|exists:users,email',
                'otp'      => 'required|numeric',
                'password' => 'required|min:6|confirmed',
            ]);

            $record = DB::table('password_resets')
                ->where('email', $request->email)
                ->where('otp', $request->otp)
                ->where('verified', true)
                ->where('purpose', 'forgot_password')
                ->first();

            if (!$record) {
                return ResponseHelper::jsonResponse(401, 'Token tidak valid. Pastikan kamu sudah verifikasi OTP terlebih dahulu.');
            }

            if (now()->isAfter($record->expires_at)) {
                DB::table('password_resets')->where('email', $request->email)->delete();
                return ResponseHelper::jsonResponse(401, 'Sesi reset password sudah kadaluarsa. Silakan ulangi proses dari awal.');
            }

            $user           = User::where('email', $request->email)->first();
            $user->password = Hash::make($request->password);
            $user->save();

            DB::table('password_resets')->where('email', $request->email)->delete();

            return ResponseHelper::jsonResponse(200, 'Password berhasil direset. Silakan login dengan password baru kamu.');

        } catch (ValidationException $e) {
            return ResponseHelper::jsonResponse(422, 'Validasi gagal', $e->errors());
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Gagal reset password', $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // PRIVATE HELPER
    // -------------------------------------------------------------------------

    private function generateAndSendOtp(string $email, string $purpose): void
    {
        $otp = rand(100000, 999999);

        DB::table('password_resets')->where('email', $email)->delete();

        DB::table('password_resets')->insert([
            'email'      => $email,
            'otp'        => $otp,
            'purpose'    => $purpose,
            'verified'   => false,
            'expires_at' => now()->addMinutes(15),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Mail::to($email)->send(new PasswordResetOtpMail($otp));
    }
}