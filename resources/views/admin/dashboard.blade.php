<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">
                        Welcome, {{ auth()->user()->name }}!
                        <span class="text-sm font-normal text-gray-500">({{ ucfirst(auth()->user()->role->value) }})</span>
                    </h3>
                    <p class="text-gray-600">You have access to the administrative panel.</p>

                    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
                        <div class="border rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-red-600">0</div>
                            <div class="text-sm text-gray-500 mt-1">Total Users</div>
                        </div>
                        <div class="border rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-red-600">0</div>
                            <div class="text-sm text-gray-500 mt-1">Active Jobs</div>
                        </div>
                        <div class="border rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-red-600">0</div>
                            <div class="text-sm text-gray-500 mt-1">Applications</div>
                        </div>
                        <div class="border rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-red-600">0</div>
                            <div class="text-sm text-gray-500 mt-1">Employers</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
