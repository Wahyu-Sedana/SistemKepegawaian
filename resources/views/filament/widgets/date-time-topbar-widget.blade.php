<x-filament-widgets::widget>
    <div class="flex items-center space-x-2 px-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3" />
        </svg>
        <span class="font-medium text-gray-700 dark:text-gray-200">
            {{ now()->format('d M Y, H:i') }}
        </span>
    </div>
</x-filament-widgets::widget>
