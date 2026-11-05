<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ForgotPasswordRequest;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Mail\AdminPasswordResetMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function showForgotPasswordForm(): View
    {
        return view('admin.forgot-password');
    }

    public function sendResetLinkEmail(ForgotPasswordRequest $request): RedirectResponse
    {
        $email = (string) $request->validated('email');
        $user = User::where('email', $email)->first();

        // Generic response prevents user enumeration while only mailing registered accounts
        if ($user !== null) {
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'email'      => $email,
                    'token'      => Hash::make($token),
                    'created_at' => now(),
                ]
            );

            $resetUrl = route('admin.password.reset', ['token' => $token]) . '?email=' . urlencode($email);

            try {
                Mail::to($email)->send(new AdminPasswordResetMail($resetUrl));
            } catch (\Throwable $e) {
                Log::error('Admin password reset mail error: ' . $e->getMessage(), ['email' => $email]);
            }
        }

        return back()->with('status', 'Eğer e-posta adresi sistemde kayıtlıysa şifre sıfırlama bağlantısı gönderildi.');
    }

    public function showResetPasswordForm(Request $request, string $token): View
    {
        return view('admin.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $email = (string) $validated['email'];
        $token = (string) $validated['token'];

        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if ($passwordReset === null || !Hash::check($token, (string) $passwordReset->token)) {
            return back()->withErrors(['email' => 'Geçersiz token veya e-posta adresi.']);
        }

        if (Carbon::parse((string) $passwordReset->created_at)->diffInHours(now()) > 24) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return back()->withErrors(['email' => 'Şifre sıfırlama linkinin süresi dolmuş.']);
        }

        $user = User::where('email', $email)->firstOrFail();
        $user->password = (string) $validated['password'];
        $user->setRememberToken(Str::random(60));
        $user->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return redirect()->route('admin.login')->with(
            'status',
            'Şifreniz başarıyla sıfırlandı. Yeni şifrenizle giriş yapabilirsiniz.'
        );
    }
}
