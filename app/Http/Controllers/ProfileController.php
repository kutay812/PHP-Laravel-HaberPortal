<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function __construct(
        protected readonly UserService $userService,
    ) {
        $this->middleware('auth');
    }

    public function show(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $roles = $user->roles->pluck('name')->all();

        return view('profile', compact('user', 'roles'));
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $this->userService->profileEdit($user, $request->validated());

        return redirect('/')->with('success', 'Profiliniz başarıyla güncellendi.');
    }
}
