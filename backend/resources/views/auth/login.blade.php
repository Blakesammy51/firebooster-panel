<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign in · Firebooster</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-slate-950 text-white">
<div class="fixed inset-0 bg-cover bg-center" style="background-image:url('/images/firebooster-banner.png')"></div><div class="fixed inset-0 bg-slate-950/75"></div>
<main class="relative mx-auto flex min-h-screen max-w-6xl items-center justify-center gap-10 px-4 py-8 lg:px-8">
<section class="hidden max-w-xl flex-1 lg:block"><a href="/" class="inline-flex rounded-full border border-white/20 bg-slate-950/50 px-4 py-2 text-sm font-semibold text-violet-200 backdrop-blur">← Firebooster home</a><h1 class="mt-8 text-5xl font-black leading-tight">Welcome back<br><span class="text-fuchsia-400">Link up. Level up.</span></h1><p class="mt-4 max-w-md text-lg text-slate-200">Sign in to manage your social media campaigns in one place.</p></section>
<section class="w-full max-w-md rounded-3xl border border-violet-300/25 bg-slate-950/90 p-6 shadow-2xl shadow-violet-950/50 backdrop-blur-xl sm:p-8">
<a href="/" class="text-sm font-semibold text-violet-300">← Firebooster</a><div class="mt-5 text-xs font-bold uppercase tracking-[.25em] text-fuchsia-300">Your growth workspace</div><h1 class="mt-2 text-3xl font-black">Welcome back</h1><p class="mt-2 text-sm text-slate-300">Sign in to your account.</p>
@if($errors->any())<div class="mt-5 rounded-xl border border-red-500/30 bg-red-950/80 p-3 text-sm text-red-200">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">@csrf
<label class="block text-sm font-medium text-slate-200">Email<input name="email" type="email" required autofocus value="{{ old('email') }}" autocomplete="email" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900/90 p-3 text-white outline-none focus:border-fuchsia-400 focus:ring-2 focus:ring-fuchsia-500/20"></label>
<label class="block text-sm font-medium text-slate-200">Password<input name="password" type="password" required autocomplete="current-password" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900/90 p-3 text-white outline-none focus:border-fuchsia-400 focus:ring-2 focus:ring-fuchsia-500/20"></label>
<label class="flex items-center gap-2 text-sm text-slate-300"><input type="checkbox" name="remember" class="rounded border-slate-600 bg-slate-900 text-violet-500"> Remember me</label>
<button class="w-full rounded-xl bg-gradient-to-r from-violet-600 to-fuchsia-600 p-3.5 font-bold shadow-lg shadow-violet-950/40 transition hover:brightness-110">Sign in</button></form>
<p class="mt-6 text-center text-sm text-slate-300">New here? <a class="font-semibold text-fuchsia-300 hover:text-fuchsia-200" href="{{ route('register') }}">Create an account</a></p>
</section></main></body></html>