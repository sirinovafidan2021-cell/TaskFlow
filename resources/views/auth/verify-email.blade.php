<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify email | TaskFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen place-items-center bg-slate-950 px-5 text-slate-900 antialiased">
    <main class="w-full max-w-md rounded-3xl bg-white p-8 shadow-2xl shadow-slate-950/30 sm:p-10">
        <span class="grid size-10 place-items-center rounded-xl bg-indigo-600 text-sm font-black text-white">T</span>
        <h1 class="mt-6 text-2xl font-semibold tracking-tight">Verify your email address</h1>
        <p class="mt-3 text-sm leading-6 text-slate-600">We sent a verification link to <span class="font-semibold text-slate-900">{{ auth()->user()->email }}</span>. Open it to activate your workspace.</p>

        @if (session('status'))
            <p role="status" class="mt-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('verification.send') }}" class="mt-7">
            @csrf
            <button type="submit" class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">Resend verification email</button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="w-full rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 hover:text-slate-950">Sign out</button>
        </form>
    </main>
</body>
</html>
