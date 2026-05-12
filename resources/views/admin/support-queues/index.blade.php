<x-layouts.app>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Support Queue Management') }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-300">Create and manage support queues for automatic ticket assignment.</p>
            </div>
            <a href="{{ route('support-queues.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                <span>+ Add Queue</span>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="grid gap-6">
                    @forelse($queues as $queue)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-6 bg-gray-50 dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $queue->name }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ $queue->description ?? 'No description provided.' }}</p>
                                    <div class="flex gap-3 mt-3 text-xs">
                                        <span class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-3 py-1 font-medium">
                                            {{ $queue->facebookPages->count() }} page(s)
                                        </span>
                                        <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-3 py-1 font-medium">
                                            {{ $queue->users->count() }} agent(s)
                                        </span>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <a href="{{ route('support-queues.show', $queue) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                                        Assign Pages & Agents
                                    </a>
                                    <a href="{{ route('support-queues.edit', $queue) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm rounded-md hover:bg-gray-700">
                                        Edit
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-12 text-center">
                            <p class="text-gray-700 dark:text-gray-300 mb-4">No support queues found.</p>
                            <a href="{{ route('support-queues.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                Create Your First Queue
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
