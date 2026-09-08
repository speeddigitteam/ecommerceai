@if($user->isAdmin())
<x-admin-layout title="Profile"><div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-[#111827] dark:text-slate-100"><div x-cloak x-show="menuOpen" @click="menuOpen=false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div><x-admin-sidebar/><main class="min-w-0 lg:pl-72"><x-admin-topbar/><div class="mx-auto max-w-6xl p-5 sm:p-8">@include('profile.content')</div></main></div></x-admin-layout>
@else
<x-customer-layout title="Profile Settings">@include('profile.content')</x-customer-layout>
@endif
