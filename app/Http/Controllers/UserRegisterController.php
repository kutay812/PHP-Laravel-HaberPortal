<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserRegisterController extends Controller
{
    /**
     * Show registration form
     */
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    /**
     * User registration action
     */
    public function register(RegisterRequest $request)
    {
        DB::transaction(function () use ($request) {
            User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
            ]);
        });

        return redirect('/')->with('register_success', 'Kayıt başarılı! Giriş yapabilirsiniz.');
    }
}
