<x-admin-layout title="Users">
    <div x-data="{ addUserOpen: @js($errors->any()), profileImagePreview: null }" class="min-h-screen bg-slate-50 text-slate-900 dark:bg-[#111827] dark:text-slate-100">
        <div x-cloak x-show="menuOpen" @click="menuOpen = false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div>
        <x-admin-sidebar />
        <main class="min-w-0 lg:pl-72">
            <x-admin-topbar />
            <div class="mx-auto max-w-7xl p-5 sm:p-8">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div><p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">Admin panel</p><h1 class="mt-1 text-2xl font-bold">Admin users</h1><p class="mt-2 text-sm text-slate-500 dark:text-slate-400">View and manage users who can access the admin panel.</p></div>
                    <button @click="addUserOpen = true" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-700"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>Add new user</button>
                </div>

                @if (session('status'))<div class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">{{ session('status') }}</div>@endif
                @if (session('error'))<div class="mb-6 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">{{ session('error') }}</div>@endif

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-[#161f2e]">
                    <div class="flex items-center justify-between border-b border-slate-200 p-5 dark:border-slate-800"><div><h2 class="font-bold">Admin panel users</h2><p class="mt-1 text-xs text-slate-500">{{ $users->total() }} {{ Str::plural('administrator', $users->total()) }}</p></div></div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[680px] text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-800/60 dark:text-slate-400"><tr><th class="px-6 py-4">User</th><th class="px-6 py-4">Email</th><th class="px-6 py-4">Status</th><th class="px-6 py-4">Joined</th><th class="px-6 py-4 text-right">Actions</th></tr></thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @forelse ($users as $user)
                                    <tr class="transition hover:bg-slate-50/70 dark:hover:bg-slate-800/30">
                                        <td class="px-6 py-4"><div class="flex items-center gap-3">@if ($user->profile_image_path)<img src="{{ asset('storage/'.$user->profile_image_path) }}" alt="{{ $user->name }}" class="h-10 w-10 rounded-full object-cover">@else<div class="grid h-10 w-10 rounded-full bg-indigo-100 font-bold text-indigo-700 place-items-center dark:bg-indigo-500/15 dark:text-indigo-300">{{ strtoupper(substr($user->name, 0, 1)) }}</div>@endif<span class="font-semibold">{{ $user->name }}</span></div></td>
                                        <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ $user->email }}</td>
                                        <td class="px-6 py-4">@if ($user->email_verified_at)<span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">Verified</span>@else<span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">Pending</span>@endif</td>
                                        <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ $user->created_at->format('M d, Y') }}</td>
                                        <td class="px-6 py-4"><div class="flex items-center justify-end gap-2"><a href="{{ route('users.show', $user) }}" title="View user" class="grid h-8 w-8 place-items-center rounded-full bg-slate-200 text-slate-700 transition hover:bg-blue-500 hover:text-white dark:bg-slate-700 dark:text-slate-200"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5" stroke-width="2"/></svg></a><a href="{{ route('users.edit', $user) }}" title="Edit user" class="grid h-8 w-8 place-items-center rounded-full bg-slate-200 text-slate-700 transition hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="m4 20 4.2-1 9.6-9.6a2.1 2.1 0 0 0-3-3L5.2 16 4 20Z"/></svg></a>@unless ($user->is(auth()->user()))<form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Delete this user?');">@csrf @method('DELETE')<button type="submit" title="Delete user" class="grid h-8 w-8 place-items-center rounded-full bg-slate-200 text-slate-700 transition hover:bg-rose-500 hover:text-white dark:bg-slate-700 dark:text-slate-200"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M6 7h12m-8 4v5m4-5v5M9 7l1-3h4l1 3m-8 0 1 13h8l1-13"/></svg></button></form>@endunless</div></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500">No admin users found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($users->hasPages())<div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $users->links() }}</div>@endif
                </section>
            </div>
        </main>

        <div x-cloak x-show="addUserOpen" x-transition.opacity @keydown.escape.window="addUserOpen = false" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
            <div role="dialog" aria-modal="true" aria-labelledby="add-user-title" @click.outside="addUserOpen = false" x-transition class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-[#161f2e]">
                <div class="sticky top-0 z-10 flex items-center justify-between border-b border-indigo-100 bg-indigo-50 px-5 py-4 dark:border-indigo-500/20 dark:bg-indigo-500/10">
                    <div><p class="text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">New account</p><h2 id="add-user-title" class="mt-1 text-xl font-bold">Add new user</h2></div>
                    <button type="button" @click="addUserOpen = false" class="rounded-lg p-2 text-slate-400 transition hover:bg-white hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200" aria-label="Close"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="m6 18 12-12M6 6l12 12"/></svg></button>
                </div>
                <form method="POST" action="{{ route('users.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="space-y-5 p-5 sm:p-7">
                        <div>
                            <label for="profile_image" class="text-sm font-semibold">Profile image <span class="font-normal text-slate-400">(optional)</span></label>
                            <div class="mt-2 flex items-center gap-4 rounded-xl border border-dashed border-slate-300 p-4 dark:border-slate-700">
                                <div class="grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-xl bg-indigo-50 text-indigo-400 dark:bg-indigo-500/15"><img x-cloak x-show="profileImagePreview" :src="profileImagePreview" alt="Profile preview" class="h-full w-full object-cover"><svg x-show="!profileImagePreview" class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M4 21a8 8 0 0 1 16 0"/></svg></div>
                                <input id="profile_image" name="profile_image" type="file" accept="image/png,image/jpeg,image/webp" @change="profileImagePreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null" class="min-w-0 text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-semibold file:text-indigo-700 dark:file:bg-indigo-500/15 dark:file:text-indigo-300">
                            </div>
                            <p class="mt-2 text-xs text-slate-500">PNG, JPG or WebP. Maximum 2 MB.</p>
                            @error('profile_image')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div><label for="name" class="text-sm font-semibold">Name</label><input id="name" name="name" value="{{ old('name') }}" required class="mt-2 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800">@error('name')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                        <div><label for="email" class="text-sm font-semibold">Email address</label><input id="email" name="email" type="email" value="{{ old('email') }}" required class="mt-2 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800">@error('email')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                        <div class="grid gap-5 sm:grid-cols-2"><div><label for="password" class="text-sm font-semibold">Password</label><input id="password" name="password" type="password" required class="mt-2 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800">@error('password')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror</div><div><label for="password_confirmation" class="text-sm font-semibold">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" required class="mt-2 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800"></div></div>
                    </div>
                    <div class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-200 bg-white px-5 py-4 dark:border-slate-700 dark:bg-[#161f2e]"><button type="button" @click="addUserOpen = false" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold transition hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Cancel</button><button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">Create user</button></div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
