<div>
    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
        {{ $label }}
    </label>
    <div class="flex items-center rounded-lg shadow-sm ring-1 ring-gray-950/10 focus-within:ring-2 focus-within:ring-primary-500 dark:ring-white/20 bg-white dark:bg-white/5 transition">
        @if (($type ?? 'text') === 'url')
            <div class="flex items-center px-3 text-gray-400 border-e border-gray-200 dark:border-white/10">
                {{-- heroicon-o-link --}}
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1rem;height:1rem;flex-shrink:0;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                </svg>
            </div>
        @endif
        <input
            type="{{ $type ?? 'text' }}"
            wire:model="{{ $model }}"
            placeholder="{{ $placeholder ?? '' }}"
            dir="ltr"
            class="block w-full border-none bg-transparent py-2 px-3 text-sm text-gray-950 placeholder-gray-400 outline-none dark:text-white dark:placeholder-gray-500"
        />
    </div>
    @isset($hint)
        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $hint }}</p>
    @endisset
</div>
