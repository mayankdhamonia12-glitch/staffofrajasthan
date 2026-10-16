<x-candidate-dashboard-layout title="Conversation" active="messages" subtitle="Private messages for a job application.">
    <x-message-thread :conversation="$conversation" :messages="$messages" :user="$user" :route-prefix="$routePrefix" />
</x-candidate-dashboard-layout>
