<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check() && (Auth::user()?->hasAnyRole(RoleName::staffRoles()) ?? false)) {
            return redirect()->route('admin.index');
        }

        return view('admin.login');
    }

    public function login(AdminLoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            if ($user === null || !$user->hasAnyRole(RoleName::staffRoles())) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                $request->session()->save();

                return redirect()->back()->withErrors([
                    'email' => 'İzniniz yok. Admin paneline erişemezsiniz.',
                ])->onlyInput('email');
            }

            $intended = $request->session()->pull('url.intended');
            $request->session()->regenerate();
            $request->session()->save();

            $targetUrl = route('admin.index');
            if (is_string($intended) && $intended !== '') {
                $path = (string) (parse_url($intended, PHP_URL_PATH) ?? '');
                if ($path === '/admin' || str_starts_with($path, '/admin/') || str_contains($path, '/admin')) {
                    $targetUrl = $intended;
                }
            }

            return redirect()->to($targetUrl, 303);
        }

        return back()->withErrors([
            'email' => 'Girdiğiniz bilgiler hatalı.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->save();

        return redirect()->route('admin.login', status: 303);
    }
}
