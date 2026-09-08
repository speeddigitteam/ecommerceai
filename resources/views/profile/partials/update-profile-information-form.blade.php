<section>
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form data-ds-editable data-ds-editable-start="edit" method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @method('patch')

        <div>
            <label for="profile_image" class="text-sm font-semibold">Profile image</label>
            <div class="mt-2 flex items-center gap-4 rounded-xl border border-dashed border-slate-300 p-4 dark:border-slate-700">
                @if ($user->profile_image_path)
                    <img src="{{ asset('storage/'.$user->profile_image_path) }}" alt="{{ $user->name }}" class="h-16 w-16 shrink-0 rounded-xl object-cover">
                @else
                    <div class="grid h-16 w-16 shrink-0 place-items-center rounded-xl bg-indigo-100 text-xl font-bold text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                @endif
                <input id="profile_image" name="profile_image" type="file" accept="image/png,image/jpeg,image/webp" class="min-w-0 text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-semibold file:text-indigo-700 dark:file:bg-indigo-500/15 dark:file:text-indigo-300">
            </div>
            <p class="mt-2 text-xs text-slate-500">PNG, JPG or WebP. Maximum 2 MB.</p>
            @error('profile_image')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="name" class="text-sm font-semibold">Name</label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" class="mt-2 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800">
            @error('name')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="text-sm font-semibold">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username" class="mt-2 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800">
            @error('email')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4 border-t border-slate-100 pt-5 dark:border-slate-800">
            <button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">Save changes</button>

            @if (session('status') === 'profile-updated')
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
