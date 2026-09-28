<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Candidate Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Welcome, {{ auth()->user()->name }}!</h3>
                    <p class="text-gray-600">You are logged in as a <strong>Job Seeker</strong>.</p>

                    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="border rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-indigo-600">0</div>
                            <div class="text-sm text-gray-500 mt-1">Applications Submitted</div>
                        </div>
                        <div class="border rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-indigo-600">0</div>
                            <div class="text-sm text-gray-500 mt-1">Saved Jobs</div>
                        </div>
                        <div class="border rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-indigo-600">0</div>
                            <div class="text-sm text-gray-500 mt-1">Profile Views</div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <a href="{{ route('home') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition ease-in-out duration-150">
                            Browse Jobs
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
