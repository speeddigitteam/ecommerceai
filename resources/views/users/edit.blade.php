<x-admin-layout title="Edit User">
    <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-[#111827] dark:text-slate-100">
        <div x-cloak x-show="menuOpen" @click="menuOpen = false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div>
        <x-admin-sidebar />
        <main class="min-w-0 lg:pl-72">
            <x-admin-topbar />
            <div class="mx-auto max-w-3xl p-5 sm:p-8">
                <a href="{{ route('users.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 dark:text-indigo-400"><span aria-hidden="true">←</span> Back to users</a>
                <section class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-[#161f2e]">
                    <div class="border-b border-indigo-100 bg-indigo-50 px-5 py-5 dark:border-indigo-500/20 dark:bg-indigo-500/10 sm:px-7">
                        <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">User account</p>
                        <h1 class="mt-1 text-2xl font-bold">Edit {{ $user->name }}</h1>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Update account details or set a new password.</p>
                    </div>
                    <form data-ds-editable data-ds-editable-start="edit" method="POST" action="{{ route('users.update', $user) }}" enctype="multipart/form-data" x-data="{ profileImagePreview: null }">
                        @csrf
                        @method('PUT')
                        <div class="space-y-5 p-5 sm:p-7">
                            <div>
                                <label for="profile_image" class="text-sm font-semibold">Profile image <span class="font-normal text-slate-400">(optional)</span></label>
                                <div class="mt-2 flex items-center gap-4 rounded-xl border border-dashed border-slate-300 p-4 dark:border-slate-700">
                                    <div class="grid h-20 w-20 shrink-0 place-items-center overflow-hidden rounded-xl bg-indigo-50 text-xl font-bold text-indigo-500 dark:bg-indigo-500/15">@if ($user->profile_image_path)<img x-show="!profileImagePreview" src="{{ asset('storage/'.$user->profile_image_path) }}" alt="{{ $user->name }}" class="h-full w-full object-cover">@else<span x-show="!profileImagePreview">{{ strtoupper(substr($user->name, 0, 1)) }}</span>@endif<img x-cloak x-show="profileImagePreview" :src="profileImagePreview" alt="New profile preview" class="h-full w-full object-cover"></div>
                                    <input id="profile_image" name="profile_image" type="file" accept="image/png,image/jpeg,image/webp" @change="profileImagePreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null" class="min-w-0 text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-semibold file:text-indigo-700 dark:file:bg-indigo-500/15 dark:file:text-indigo-300">
                                </div>
                                <p class="mt-2 text-xs text-slate-500">PNG, JPG or WebP. Maximum 2 MB.</p>
                                @error('profile_image')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div><label for="name" class="text-sm font-semibold">Name</label><input id="name" name="name" value="{{ old('name', $user->name) }}" required class="mt-2 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800">@error('name')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                            <div><label for="email" class="text-sm font-semibold">Email address</label><input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="mt-2 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800">@error('email')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                            <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800/50">
                                <p class="mb-4 text-sm font-semibold">Change password <span class="font-normal text-slate-400">(optional)</span></p>
                                <div class="grid gap-5 sm:grid-cols-2"><div><label for="password" class="text-sm font-semibold">New password</label><input id="password" name="password" type="password" class="mt-2 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800">@error('password')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror</div><div><label for="password_confirmation" class="text-sm font-semibold">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" class="mt-2 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800"></div></div>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 border-t border-slate-200 bg-slate-50/70 px-5 py-4 dark:border-slate-700 dark:bg-slate-900/20 sm:px-7"><a href="{{ route('users.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800">Cancel</a><button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">Save changes</button></div>
                    </form>
                </section>
            </div>
        </main>
    </div>
</x-admin-layout>
