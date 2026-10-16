<x-employer-dashboard-layout title="Conversation" active="messages" subtitle="Private messages about this job application.">
    <x-message-thread :conversation="$conversation" :messages="$messages" :user="$user" :route-prefix="$routePrefix" />
</x-employer-dashboard-layout>
