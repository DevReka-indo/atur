<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="text-2xl font-bold mb-4">Welcome to Project Management Tool</h1>
                    <p>Hello, {{ Auth::user()->name }}!</p>
                    <p class="text-gray-600">{{ Auth::user()->job_title }} - {{ Auth::user()->department }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
