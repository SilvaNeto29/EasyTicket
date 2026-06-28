<x-app-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <h1 class="text-2xl font-bold text-gray-900">Profile</h1>

            <div class="p-4 sm:p-8 bg-white border border-gray-200 rounded-xl shadow-sm">
                <div class="max-w-xl">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white border border-gray-200 rounded-xl shadow-sm">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white border border-gray-200 rounded-xl shadow-sm">
                <div class="max-w-xl">
                    <livewire:profile.delete-user-form />
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white border border-gray-200 rounded-xl shadow-sm">
                <div class="max-w-xl">
                    <livewire:profile.api-tokens />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
