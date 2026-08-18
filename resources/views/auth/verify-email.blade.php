<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Verify Your Email | TaskFlow</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-950 text-slate-900 antialiased">

    <div class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10 sm:px-6">

        {{-- Background decoration --}}
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -left-32 -top-32 h-96 w-96 rounded-full bg-indigo-600/20 blur-3xl"></div>
            <div class="absolute -bottom-32 -right-32 h-96 w-96 rounded-full bg-violet-600/20 blur-3xl"></div>
        </div>

        {{-- Verification Card --}}
        <main class="relative w-full max-w-md">

            <div class="overflow-hidden rounded-3xl border border-white/10 bg-white shadow-2xl shadow-black/30">

                {{-- Top accent --}}
                <div class="h-1.5 bg-gradient-to-r from-indigo-500 via-violet-500 to-indigo-600"></div>

                <div class="px-7 py-8 sm:px-10 sm:py-10">

                    {{-- Logo --}}
                    <div class="flex justify-center">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-600 shadow-lg shadow-indigo-600/25">
                            <span class="text-xl font-black text-white">T</span>
                        </div>
                    </div>

                    {{-- Icon --}}
                    <div class="mt-7 flex justify-center">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-indigo-50">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-8 w-8 text-indigo-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3 8.25l8.06 5.37a1.7 1.7 0 001.88 0L21 8.25M5.25 19.5h13.5A2.25 2.25 0 0021 17.25V6.75a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6.75v10.5a2.25 2.25 0 002.25 2.25z"
                                />
                            </svg>
                        </div>
                    </div>

                    {{-- Heading --}}
                    <div class="mt-6 text-center">
                        <h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                            Verify your email
                        </h1>

                        <p class="mx-auto mt-3 max-w-sm text-sm leading-6 text-slate-600">
                            We've sent a verification link to
                        </p>

                        <p class="mt-1 break-all text-sm font-semibold text-slate-950">
                            {{ auth()->user()->email }}
                        </p>
                    </div>

                    {{-- Information box --}}
                    <div class="mt-7 rounded-2xl border border-indigo-100 bg-indigo-50/70 p-4">
                        <div class="flex gap-3">
                            <div class="mt-0.5 shrink-0">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-indigo-600"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-slate-900">
                                    Check your inbox
                                </p>

                                <p class="mt-1 text-xs leading-5 text-slate-600">
                                    Click the verification link in the email to activate your TaskFlow account.
                                    Don't forget to check your spam or junk folder.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Success message --}}
                    @if (session('status'))
                        <div
                            role="status"
                            class="mt-5 flex items-start gap-3 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3.5"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>

                            <p class="text-sm leading-5 text-emerald-800">
                                {{ session('status') }}
                            </p>
                        </div>
                    @endif

                    {{-- Resend --}}
                    <form method="POST" action="{{ route('verification.send') }}" class="mt-7">
                        @csrf

                        <button
                            type="submit"
                            class="group flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-3.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 transition duration-200 hover:bg-indigo-500 hover:shadow-indigo-600/30 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4 transition-transform duration-200 group-hover:-rotate-12"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M21.75 6.75v10.5A2.25 2.25 0 0119.5 19.5h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0L12 13.5 2.25 6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25"
                                />
                            </svg>

                            Resend verification email
                        </button>
                    </form>

                    {{-- Logout --}}
                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                        @csrf

                        <button
                            type="submit"
                            class="w-full rounded-xl px-5 py-3 text-sm font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200"
                        >
                            Sign out
                        </button>
                    </form>

                    {{-- Footer --}}
                    <div class="mt-8 border-t border-slate-100 pt-6 text-center">
                        <p class="text-xs text-slate-400">
                            Having trouble?
                            <span class="font-medium text-slate-500">
                                Request a new verification email.
                            </span>
                        </p>
                    </div>

                </div>
            </div>

            {{-- Brand footer --}}
            <p class="mt-6 text-center text-xs text-slate-500">
                © {{ date('Y') }} TaskFlow. All rights reserved.
            </p>

        </main>
    </div>

</body>
</html>