<div style="padding: 8px 0;" x-data="{ copied: false }">
    <p style="margin: 0 0 12px; color: #6b7280; font-size: 14px;">
        شارك هذا الرابط مع الوكيل. الرابط صالح حتى يتم تجديده.
    </p>

    <div style="display: flex; gap: 8px; align-items: center;">
        <input
            type="text"
            value="{{ $url }}"
            readonly
            id="portal-url-input"
            style="flex: 1; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; font-family: monospace; direction: ltr; background: #f9fafb;"
            onclick="this.select()"
        >
        <button
            type="button"
            @click="
                navigator.clipboard.writeText('{{ $url }}').then(() => {
                    copied = true;
                    setTimeout(() => copied = false, 2000);
                });
            "
            style="padding: 10px 16px; background: #0ea5e9; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; white-space: nowrap; font-family: inherit; transition: background 0.2s;"
            :style="copied ? 'background: #10b981;' : ''"
        >
            <span x-show="!copied">نسخ الرابط</span>
            <span x-show="copied">✓ تم النسخ</span>
        </button>
    </div>

    @if(!$url)
        <p style="color: #ef4444; font-size: 13px; margin-top: 8px;">
            لم يتم توليد الرابط بعد. جرّب إعادة فتح هذه النافذة.
        </p>
    @endif
</div>
