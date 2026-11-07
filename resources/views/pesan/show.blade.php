<x-app-layout title="ReLoop · Chat" bodyClass="bg-white">
    <livewire:chat-room :conversation="$conversation" :buka-tawar="request()->boolean('tawar')" />
</x-app-layout>
