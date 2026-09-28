<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Employer Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Welcome, {{ auth()->user()->name }}!</h3>
                    <p class="text-gray-600">You are logged in as an <strong>Employer</strong>.</p>

                    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="border rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-green-600">0</div>
                            <div class="text-sm text-gray-500 mt-1">Active Job Posts</div>
                        </div>
                        <div class="border rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-green-600">0</div>
                            <div class="text-sm text-gray-500 mt-1">Total Applications</div>
                        </div>
                        <div class="border rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-green-600">0</div>
                            <div class="text-sm text-gray-500 mt-1">Shortlisted Candidates</div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <a href="#" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition ease-in-out duration-150">
                            Post a Job
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
