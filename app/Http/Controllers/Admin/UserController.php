<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:users.view');
        $this->middleware('permission:users.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:users.edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:users.delete', ['only' => ['destroy']]);
    }

    public function index(Request $request): View
    {
        $users = User::query()
            ->with('roles')
            ->when($request->filled('search'), static function (Builder $q) use ($request): void {
                $searchTerm = (string) $request->input('search');
                $q->where(static function (Builder $sub) use ($searchTerm): void {
                    $sub->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $isSuperAdmin = auth()->user()?->hasRole(RoleName::SuperAdmin->value) ?? false;
        $roles = Role::query()
            ->when(!$isSuperAdmin, static fn (Builder $q): Builder => $q->where('name', '!=', RoleName::SuperAdmin->value))
            ->get();

        return view('admin.users.create', compact('roles'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => $validated['password'],
        ]);

        $roleNames = Role::whereIn('id', $validated['roles'])->pluck('name')->all();
        // syncRoles replaces the default 'User' role assigned in User::booted()
        $user->syncRoles($roleNames);

        return redirect()->route('admin.users.index')
            ->with('success', 'Kullanıcı başarıyla oluşturuldu.');
    }

    public function edit(User $user): View|RedirectResponse
    {
        $isSuperAdmin = auth()->user()?->hasRole(RoleName::SuperAdmin->value) ?? false;

        if ($user->hasRole(RoleName::SuperAdmin->value) && !$isSuperAdmin) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Super Admin kullanıcılarını düzenleme yetkiniz yok.');
        }

        $roles = Role::query()
            ->when(!$isSuperAdmin, static fn (Builder $q): Builder => $q->where('name', '!=', RoleName::SuperAdmin->value))
            ->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $isSuperAdmin = auth()->user()?->hasRole(RoleName::SuperAdmin->value) ?? false;

        if ($user->hasRole(RoleName::SuperAdmin->value) && !$isSuperAdmin) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Super Admin kullanıcılarını düzenleme yetkiniz yok.');
        }

        $validated = $request->validated();

        $user->update([
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => $validated['password']]);
        }

        $roleNames = Role::whereIn('id', $validated['roles'])->pluck('name')->all();
        $user->syncRoles($roleNames);

        return redirect()->route('admin.users.index')
            ->with('success', 'Kullanıcı başarıyla güncellendi.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->hasRole(RoleName::SuperAdmin->value)) {
            return back()->with('error', 'Super Admin kullanıcıları silinemez.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Kendi hesabınızı silemezsiniz.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Kullanıcı başarıyla silindi.');
    }

    public function search(Request $request): JsonResponse
    {
        $search = trim((string) $request->get('search', ''));
        if ($search === '') {
            return response()->json(['items' => []], 200);
        }

        $items = User::query()
            ->with('roles')
            ->where(static function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(static fn (User $user): array => [
                'id'       => $user->id,
                'text'     => $user->name,
                'category' => $user->email,
            ]);

        return response()->json(['items' => $items], 200);
    }
}
