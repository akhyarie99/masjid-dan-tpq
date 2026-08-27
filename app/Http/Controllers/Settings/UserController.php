<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::where('masjid_id', $request->user()->masjid_id)
            ->with('roles:id,name')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Settings/Users/Index', [
            'users' => $users,
            'roles' => Role::pluck('name'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Settings/Users/Form', [
            'roles' => Role::pluck('name'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateUser($request);

        $user = User::create([
            'masjid_id' => $request->user()->masjid_id,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'is_active' => $data['is_active'] ?? true,
        ]);

        $user->assignRole($data['role']);

        return redirect()->route('admin.settings.pengguna.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(Request $request, User $pengguna): Response
    {
        $this->authorizeSameMasjid($request, $pengguna);

        return Inertia::render('Settings/Users/Form', [
            'user' => $pengguna->load('roles:id,name'),
            'roles' => Role::pluck('name'),
        ]);
    }

    public function update(Request $request, User $pengguna): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $pengguna);

        $data = $this->validateUser($request, $pengguna->id);

        $pengguna->update([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'is_active' => $data['is_active'] ?? true,
            ...(! empty($data['password']) ? ['password' => Hash::make($data['password'])] : []),
        ]);

        $pengguna->syncRoles([$data['role']]);

        return redirect()->route('admin.settings.pengguna.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $pengguna): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $pengguna);

        if ($pengguna->id === request()->user()->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $pengguna->delete();

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }

    /**
     * User tidak punya global scope tenant — route-model binding cuma cari
     * berdasarkan id, jadi tanpa ini admin tenant A yang tahu/menebak UUID
     * pengurus tenant B bisa mengganti password/role-nya, atau menghapusnya,
     * lewat URL langsung. users.masjid_id NOT NULL, jadi tidak ada kasus
     * null === null yang lolos diam-diam.
     */
    private function authorizeSameMasjid(Request $request, User $user): void
    {
        abort_unless($user->masjid_id === $request->user()->masjid_id, 404);
    }

    private function validateUser(Request $request, ?string $userId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['required', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($userId)],
            'password' => [$userId ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::in(Role::pluck('name'))],
            'is_active' => ['boolean'],
        ]);
    }
}
