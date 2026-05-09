<x-layouts.app>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Assign Pages & Agents</h2>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $supportQueue->name }}</p>
            </div>
            <a href="{{ route('admin.support-queues.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-700 text-white rounded-md hover:bg-gray-600">Back to queues</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-6">Manage Pages & Agents</h3>
                    <form action="{{ route('admin.support-queues.assign', $supportQueue) }}" method="POST" class="space-y-6">
                        @csrf

                        <div class="grid gap-6 lg:grid-cols-2">
                            <div>
                                <label for="page_ids" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Pages</label>
                                <select id="page_ids" name="page_ids[]" multiple class="select2 block w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500">
                                    @foreach($pages as $page)
                                        <option value="{{ $page->id }}" @selected($supportQueue->facebookPages->contains($page))>{{ $page->page_name }} ({{ $page->page_id }})</option>
                                    @endforeach
                                </select>
                                <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Currently Assigned Pages</h4>
                                    <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
                                        @forelse($supportQueue->facebookPages as $page)
                                            <li class="flex items-center"><span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>{{ $page->page_name }} ({{ $page->page_id }})</li>
                                        @empty
                                            <li class="italic text-gray-500">No pages assigned yet.</li>
                                        @endforelse
                                    </ul>
                                </div>
                            </div>

                            <div>
                                <label for="agent_ids" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Agents</label>
                                <select id="agent_ids" name="agent_ids[]" multiple class="select2 block w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500">
                                    @foreach($agents as $agent)
                                        <option value="{{ $agent->id }}" @selected($supportQueue->users->contains($agent))>{{ $agent->name }} ({{ $agent->email }})</option>
                                    @endforeach
                                </select>
                                <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Currently Assigned Agents</h4>
                                    <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
                                        @forelse($supportQueue->users as $agent)
                                            <li class="flex items-center"><span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>{{ $agent->name }} ({{ $agent->email }})</li>
                                        @empty
                                            <li class="italic text-gray-500">No agents assigned yet.</li>
                                        @endforelse
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-medium">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Queue Overview</h3>
                    <div class="space-y-4 text-sm text-gray-600 dark:text-gray-300">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                            <span class="font-medium">Queue Name</span>
                            <span class="text-right">{{ $supportQueue->name }}</span>
                        </div>
                        <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                            <span class="font-medium">Pages Assigned</span>
                            <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-xs font-bold">{{ $supportQueue->facebookPages->count() }}</span>
                        </div>
                        <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                            <span class="font-medium">Agents Assigned</span>
                            <span class="inline-flex items-center justify-center w-6 h-6 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-xs font-bold">{{ $supportQueue->users->count() }}</span>
                        </div>
                        <div class="pt-2">
                            <span class="font-medium block text-gray-700 dark:text-gray-300 mb-2">Description</span>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $supportQueue->description ?? 'No description provided.' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <style>
            .select2-container .select2-selection--multiple {
                min-height: 3rem;
                border-radius: 0.75rem;
                border-color: #d1d5db;
                background-color: #fff;
            }
            .select2-container--default .select2-selection--multiple .select2-selection__choice {
                background-color: #e0f2fe;
                color: #0369a1;
                border: none;
                border-radius: 9999px;
                margin-top: 0.35rem;
            }
            .select2-container--default .select2-selection--multiple .select2-selection__rendered {
                padding: 0.5rem 0.75rem;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                $('.select2').select2({
                    width: '100%',
                    placeholder: 'Select options',
                });
            });
        </script>
    @endpush
</x-layouts.app>
