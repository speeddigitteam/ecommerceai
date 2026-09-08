<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::query()
                ->where('role', UserRole::Admin)
                ->latest()
                ->paginate(15),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->safe()->except('profile_image');
        $validated['role'] = UserRole::Admin;

        if ($request->hasFile('profile_image')) {
            $validated['profile_image_path'] = $request->file('profile_image')->store('profiles', 'public');
        }

        User::query()->create($validated);

        return to_route('users.index')->with('status', 'User created successfully.');
    }

    public function show(User $user): View
    {
        abort_unless($user->isAdmin(), 404);

        return view('users.show', ['user' => $user]);
    }

    public function edit(User $user): View
    {
        abort_unless($user->isAdmin(), 404);

        return view('users.edit', ['user' => $user]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->isAdmin(), 404);

        $validated = $request->safe()->except(['password', 'profile_image']);

        if ($request->hasFile('profile_image')) {
            if ($user->profile_image_path) {
                Storage::disk('public')->delete($user->profile_image_path);
            }

            $validated['profile_image_path'] = $request->file('profile_image')->store('profiles', 'public');
        }

        if ($request->filled('password')) {
            $validated['password'] = $request->validated('password');
        }

        $user->update($validated);

        return to_route('users.index')->with('status', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->isAdmin(), 404);

        if ($user->is(auth()->user())) {
            return to_route('users.index')->with('error', 'You cannot delete your own account here.');
        }

        if ($user->profile_image_path) {
            Storage::disk('public')->delete($user->profile_image_path);
        }

        $user->delete();

        return to_route('users.index')->with('status', 'User deleted successfully.');
    }
}
