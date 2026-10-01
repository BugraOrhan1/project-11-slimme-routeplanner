<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * US25/US26 – Gebruikers en rollen (alleen beheerder en bedrijfsleider).
 */
class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Users/Index', [
            'users' => User::with('inspector:id,user_id,name')->orderBy('name')->get()->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'roleLabel' => $u->roleLabel(),
                'inspector' => $u->inspector?->name,
            ]),
            'roles' => User::ROLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => ['required', Password::min(8)],
        ], [], ['name' => 'naam', 'role' => 'rol', 'password' => 'wachtwoord']);

        User::create($data);

        return back()->with('success', "Gebruiker {$data['name']} is aangemaakt.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => ['nullable', Password::min(8)],
        ], [], ['name' => 'naam', 'role' => 'rol', 'password' => 'wachtwoord']);

        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);

        return back()->with('success', 'Gebruiker is opgeslagen.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Je kunt jezelf niet verwijderen.');
        }
        $user->delete();

        return back()->with('success', "Gebruiker {$user->name} is verwijderd.");
    }
}
