<x-layouts.app>
    <!-- Breadcrumbs -->
    <div class="mb-6 flex items-center text-sm">
        <a href="{{ route('dashboard') }}"
            class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('Dashboard') }}</a>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mx-2 text-gray-400" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
        <a href="{{ route('settings.facebook.edit') }}"
            class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('Settings') }}</a>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mx-2 text-gray-400" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
        <span class="text-gray-500 dark:text-gray-400">{{ __('Facebook') }}</span>
    </div>

    <!-- Page Title -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ __('Facebook Settings') }}</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('Configure your Facebook API credentials and tokens') }}</p>
    </div>

    <div class="p-6">
        <div class="flex flex-col md:flex-row gap-6">
            <!-- Sidebar Navigation -->
            @include('settings.partials.navigation')

            <!-- Facebook Content -->
            <div class="flex-1">
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
                    <div class="p-6">
                        <!-- Facebook Form -->
                        <form class="max-w-md mb-10" action="{{ route('settings.facebook.update') }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-4">
                                <x-forms.input label="App ID" name="app_id" type="text"
                                    value="{{ old('app_id', $app_id) }}" required />
                            </div>

                            <div class="mb-4">
                                <x-forms.input label="App Secret" name="app_secret" type="password"
                                    value="{{ old('app_secret', $app_secret) }}" required />
                            </div>

                            <div class="mb-4">
                                <x-forms.input label="Access Token" name="access_token" type="password"
                                    value="{{ old('access_token', $access_token) }}" required />
                            </div>

                            <div class="mb-6">
                                <x-forms.input label="Verify Token" name="verify_token" type="text"
                                    value="{{ old('verify_token', $verify_token) }}" required />
                            </div>

                            <div>
                                <x-button type="primary">{{ __('Save') }}</x-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
