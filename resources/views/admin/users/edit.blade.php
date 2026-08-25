@extends('layouts.app')

@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('content')
    <section class="max-w-3xl rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-indigo-600">Administration</p>
        <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">Edit {{ $managedUser->name }}</h2>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-500">Manage account identity and global TaskFlow access. Project membership roles remain independent and are not edited here.</p>
        <form method="POST" action="{{ route('admin.users.update', $managedUser) }}" class="mt-8 space-y-7">@csrf @method('PUT')
            @include('admin.users._form', ['creating' => false])
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><a href="{{ route('admin.users.index') }}" class="rounded-xl border border-slate-200 px-4 py-3 text-center text-sm font-semibold text-slate-700">Cancel</a><button type="submit" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-500">Save changes</button></div>
        </form>
    </section>
@endsection
