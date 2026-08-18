@extends('layouts.app')
@section('title', 'New task') @section('page-title', 'New task')
@section('content')<div class="mx-auto max-w-3xl"><p class="text-sm font-semibold uppercase tracking-widest text-indigo-600">{{ $project->name }}</p><h2 class="mt-2 text-3xl font-semibold">Turn a project goal into focused work.</h2><div class="mt-7 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">@include('tasks::_form')</div></div>@endsection
