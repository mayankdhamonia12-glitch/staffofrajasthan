@if($candidate)
    <x-candidate-dashboard-layout title="Notifications" active="notifications" subtitle="A private inbox for your job and application updates.">
        @include('dashboard.notifications.list')
    </x-candidate-dashboard-layout>
@else
    <x-employer-dashboard-layout title="Notifications" active="notifications" subtitle="A private inbox for new applications to your jobs.">
        @include('dashboard.notifications.list')
    </x-employer-dashboard-layout>
@endif
