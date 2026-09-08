<x-admin-layout title="View User">
    <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-[#111827] dark:text-slate-100">
        <div x-cloak x-show="menuOpen" @click="menuOpen = false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div><x-admin-sidebar />
        <main class="min-w-0 lg:pl-72"><x-admin-topbar /><div class="mx-auto max-w-3xl p-5 sm:p-8">
            <a href="{{ route('users.index') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">← Back to users</a>
            <section class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-[#161f2e]">
                <div class="bg-gradient-to-r from-indigo-500 to-violet-600 px-6 py-8 text-white"><div class="flex items-center gap-4">@if ($user->profile_image_path)<img src="{{ asset('storage/'.$user->profile_image_path) }}" alt="{{ $user->name }}" class="h-20 w-20 rounded-2xl border-2 border-white/40 object-cover">@else<div class="grid h-20 w-20 place-items-center rounded-2xl bg-white/20 text-3xl font-bold">{{ strtoupper(substr($user->name, 0, 1)) }}</div>@endif<div><h1 class="text-2xl font-bold">{{ $user->name }}</h1><p class="mt-1 text-indigo-100">{{ $user->email }}</p></div></div></div>
                <div class="grid gap-6 p-6 text-sm sm:grid-cols-2"><div><p class="text-slate-500">Role</p><p class="mt-1 font-semibold capitalize">{{ $user->role->value }}</p></div><div><p class="text-slate-500">Email status</p><p class="mt-1 font-semibold">{{ $user->email_verified_at ? 'Verified' : 'Pending verification' }}</p></div><div><p class="text-slate-500">Joined</p><p class="mt-1 font-semibold">{{ $user->created_at->format('F d, Y') }}</p></div><div><p class="text-slate-500">Last updated</p><p class="mt-1 font-semibold">{{ $user->updated_at->diffForHumans() }}</p></div><div class="flex items-end justify-start sm:col-span-2 sm:justify-end"><a href="{{ route('users.edit', $user) }}" class="rounded-xl bg-indigo-600 px-4 py-2.5 font-semibold text-white">Edit user</a></div></div>
            </section>
        </div></main>
    </div>
</x-admin-layout>
