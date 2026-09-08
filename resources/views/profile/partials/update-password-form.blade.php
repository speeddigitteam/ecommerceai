<section>
    <form method="post" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="text-sm font-semibold">Current password</label>
            <input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" class="mt-2 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800">
            @foreach ($errors->updatePassword->get('current_password') as $message)<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@endforeach
        </div>

        <div>
            <label for="update_password_password" class="text-sm font-semibold">New password</label>
            <input id="update_password_password" name="password" type="password" autocomplete="new-password" class="mt-2 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800">
            @foreach ($errors->updatePassword->get('password') as $message)<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@endforeach
        </div>

        <div>
            <label for="update_password_password_confirmation" class="text-sm font-semibold">Confirm password</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="mt-2 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800">
            @foreach ($errors->updatePassword->get('password_confirmation') as $message)<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@endforeach
        </div>

        <div class="flex items-center gap-4 border-t border-slate-100 pt-5 dark:border-slate-800">
            <button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">Update password</button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm font-medium text-emerald-600 dark:text-emerald-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
