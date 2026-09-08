<x-customer-layout title="Change Password">
    <section class="mx-auto max-w-2xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50/70 px-5 py-5 sm:px-7">
            <div class="flex items-center gap-3"><span class="grid h-11 w-11 place-items-center rounded-xl bg-sky-50 text-sky-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="11" rx="2" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span><div><h2 class="text-lg font-bold">Change your password</h2><p class="mt-1 text-sm text-slate-500">Use a strong password that you do not use elsewhere.</p></div></div>
        </div>
        <div class="p-5 sm:p-7">@include('profile.partials.update-password-form')</div>
    </section>
</x-customer-layout>
