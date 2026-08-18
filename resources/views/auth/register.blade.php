<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create account | TaskFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-900 antialiased">
    <main class="relative isolate grid min-h-screen overflow-hidden lg:grid-cols-[minmax(0,1.15fr)_minmax(420px,0.85fr)]">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_18%_18%,rgba(99,102,241,0.22),transparent_30%),radial-gradient(circle_at_72%_78%,rgba(14,165,233,0.12),transparent_28%),linear-gradient(135deg,#020617_0%,#0f172a_55%,#172554_100%)]"></div>

        <section class="relative hidden px-12 py-14 lg:flex lg:flex-col lg:justify-between xl:px-20">
            <div>
                <div class="inline-flex items-center gap-3 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-slate-200 backdrop-blur">
                    <span class="grid size-7 place-items-center rounded-lg bg-indigo-500 text-xs font-black text-white">T</span>
                    TaskFlow workspace
                </div>

                <div class="mt-20 max-w-xl">
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-indigo-300">Work, clarified</p>
                    <h1 class="mt-5 text-5xl font-semibold tracking-tight text-white xl:text-6xl">Bring your work into focus.</h1>
                    <p class="mt-7 max-w-lg text-lg leading-8 text-slate-300">Create your TaskFlow account to join the projects and work that matter to your team.</p>
                </div>
            </div>
        </section>

        <section class="flex items-center justify-center bg-white/95 px-5 py-10 sm:px-10 lg:bg-white/90 lg:px-12 xl:px-20">
            <div class="w-full max-w-md">
                <div class="mb-10 flex items-center gap-3 lg:hidden">
                    <span class="grid size-10 place-items-center rounded-xl bg-indigo-600 text-sm font-black text-white shadow-lg shadow-indigo-600/25">T</span>
                    <span class="text-lg font-bold tracking-tight text-slate-950">TaskFlow</span>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-7 shadow-2xl shadow-slate-900/10 sm:p-9">
                    <div class="mb-8">
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-indigo-600">TaskFlow</p>
                        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">Create your account</h1>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Get started with your TaskFlow workspace.</p>
                    </div>

                    <form method="POST" action="{{ route('register.store') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="name" class="mb-2 block text-sm font-semibold text-slate-700">Name</label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required autofocus class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 @error('name') border-rose-400 focus:border-rose-500 focus:ring-rose-500/15 @enderror">
                            @error('name')<p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email address</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 @error('email') border-rose-400 focus:border-rose-500 focus:ring-rose-500/15 @enderror">
                            @error('email')<p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Password</label>
                            <input id="password" name="password" type="password" autocomplete="new-password" required class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 @error('password') border-rose-400 focus:border-rose-500 focus:ring-rose-500/15 @enderror">
                            @error('password')<p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-700">Confirm password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15">
                        </div>

                        <button type="submit" class="w-full rounded-xl bg-indigo-600 px-4 py-3 font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/25">Create account</button>
                    </form>
                </div>

                <p class="mt-6 text-center text-sm text-slate-500">Already have an account? <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-500">Sign in</a></p>
            </div>
        </section>
    </main>
</body>
</html>
