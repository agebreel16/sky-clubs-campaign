<div x-data="{ {{ $alpine }}: false }">
    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
        {{ $label }}
    </label>
    <div class="flex items-center rounded-lg shadow-sm ring-1 ring-gray-950/10 focus-within:ring-2 focus-within:ring-primary-500 dark:ring-white/20 bg-white dark:bg-white/5 transition">
        <div class="flex items-center px-3 text-gray-400 border-e border-gray-200 dark:border-white/10">
            {{-- heroicon-o-key --}}
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1rem;height:1rem;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 0 1 21.75 8.25Z" />
            </svg>
        </div>
        <input
            :type="{{ $alpine }} ? 'text' : 'password'"
            wire:model="{{ $model }}"
            placeholder="Bearer ..."
            dir="ltr"
            class="block w-full border-none bg-transparent py-2 ps-3 text-sm text-gray-950 placeholder-gray-400 outline-none dark:text-white dark:placeholder-gray-500 font-mono"
        />
        <button
            type="button"
            @click="{{ $alpine }} = !{{ $alpine }}"
            class="flex items-center px-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition"
        >
            {{-- heroicon-o-eye --}}
            <svg x-show="!{{ $alpine }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1rem;height:1rem;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>
            {{-- heroicon-o-eye-slash --}}
            <svg x-show="{{ $alpine }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1rem;height:1rem;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
            </svg>
        </button>
    </div>
    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">يُخزَّن مشفراً في قاعدة البيانات</p>
</div>
