<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'EasyTicket') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-gray-900 to-indigo-950 px-4 py-12">

            {{-- Brand --}}
            <a href="/" wire:navigate class="flex items-center gap-3 mb-8">
                <x-application-logo class="h-10 w-10 text-indigo-400" />
                <span class="text-2xl font-bold text-white tracking-tight">EasyTicket</span>
            </a>

            {{-- Auth card --}}
            <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl px-8 py-8">
                {{ $slot }}
            </div>

            <p class="mt-6 text-sm text-gray-500">Your AI-powered ticket manager</p>
        </div>
    </body>
</html>
