@extends('layouts.app')

@section('title', 'Create Project')

@section('content')
    <div class="fixed inset-0 -z-10 bg-gradient-to-br from-gray-50 to-gray-100/50"></div>

    <div class="mx-auto max-w-7xl px-4 pb-10 pt-3 sm:px-6 lg:px-8">
        <nav class="mb-5 flex items-center gap-2 text-sm text-gray-500" aria-label="Breadcrumb">
            <a href="{{ route('dashboard') }}" class="transition-colors hover:text-indigo-600">Home</a>
            <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
            <a href="{{ route('projects.index') }}" class="transition-colors hover:text-indigo-600">Projects</a>
            <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
            <span class="font-medium text-gray-700">Create</span>
        </nav>

        <div class="mb-6">
            <h1 class="bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-3xl font-bold text-transparent">
                Create Project
            </h1>
            <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-gray-600">
                Lengkapi informasi project, tentukan template pekerjaan, dan atur timeline pelaksanaannya.
            </p>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200/70 bg-white/95 shadow-xl backdrop-blur-sm">
            <div class="h-1.5 bg-[#219ebc]"></div>

            <form method="POST" action="{{ route('projects.store') }}">
                @csrf

                <div class="space-y-8 p-6 sm:p-8">
                    @include('projects.partials.create._form-errors')
                    @include('projects.partials.create._project-information')
                    @include('projects.partials.create._template-selector')
                    @include('projects.partials.create._template-preview')
                    @include('projects.partials.create._project-timeline')
                    @include('projects.partials.create._project-description')
                </div>

                @include('projects.partials.create._form-actions')
                @include('projects.partials.create._template-picker-modal')
            </form>
        </div>
    </div>
@endsection
