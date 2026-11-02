<x-layouts::app :title="__('Tasks')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="relative flex-1 overflow-hidden rounded-xl border border-neutral-200 p-6 dark:border-neutral-700">
            <h1 class="mb-4 text-2xl font-semibold">{{ __('Tasks') }}</h1>

            <ul class="space-y-2">
                @foreach ($tasks as $task)
                    <li>
                        <a href="{{ route('tasks.show', $task) }}" class="text-blue-600 hover:underline dark:text-blue-400">
                            {{ $task->title }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</x-layouts::app>
