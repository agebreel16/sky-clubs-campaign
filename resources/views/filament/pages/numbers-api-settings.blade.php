<x-filament-panels::page>

<style>
@keyframes _api_spin { to { transform: rotate(360deg); } }
._api_spin { animation: _api_spin 1s linear infinite; }
._api_sync_btn:hover { background: rgba(255,255,255,0.28) !important; }
</style>

@php $sync = $this->getLastSync(); @endphp

{{-- ── Hero Banner ── --}}
<div style="background: linear-gradient(135deg, #065f46 0%, #10b981 100%); border-radius: 16px; padding: 24px; box-shadow: 0 8px 24px rgba(6,95,70,0.25); margin-bottom: 0;">
    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:16px;">

        <div style="flex:1; min-width:0;">
            <p style="margin:0 0 8px; font-size:11px; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:rgba(167,243,208,0.9);">
                API الأرقام اليومية
            </p>

            @if ($this->isConfigured())
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="width:10px;height:10px;border-radius:50%;background:#fff;box-shadow:0 0 8px rgba(255,255,255,0.6);flex-shrink:0;display:inline-block;"></span>
                    <span style="font-size:24px; font-weight:800; color:#fff;">متصل · يومياً {{ $daily_sync_time }}</span>
                </div>
            @else
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="width:10px;height:10px;border-radius:50%;background:#f87171;flex-shrink:0;display:inline-block;"></span>
                    <span style="font-size:24px; font-weight:800; color:#fff;">غير مُهيَّأ</span>
                </div>
            @endif

            @if ($sync)
                <div style="margin-top:10px; display:flex; flex-wrap:wrap; gap:4px 20px; font-size:13px; color:rgba(167,243,208,0.85);">
                    <span>آخر مزامنة: <strong style="color:#fff;">{{ $sync['time'] }}</strong></span>
                    <span>عولج: <strong style="color:#fff;">{{ $sync['processed'] }}</strong></span>
                    <span style="color:#a7f3d0;">↑ ترقيات: <strong style="color:#fff;">{{ $sync['promotions'] }}</strong></span>
                    <span style="color:#fca5a5;">↓ تهبيطات: <strong style="color:#fff;">{{ $sync['demotions'] }}</strong></span>
                </div>
            @else
                <p style="margin:10px 0 0; font-size:13px; color:rgba(167,243,208,0.75);">لم تُجرَ أي مزامنة بعد</p>
            @endif
        </div>

        <button wire:click="syncNow" wire:loading.attr="disabled" type="button" class="_api_sync_btn"
            style="flex-shrink:0; display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:10px 22px; border-radius:12px; background:rgba(255,255,255,0.18); border:1px solid rgba(255,255,255,0.35); color:#fff; font-size:14px; font-weight:600; cursor:pointer; transition:background 0.15s; backdrop-filter:blur(4px);">
            <span wire:loading.remove wire:target="syncNow">مزامنة الآن</span>
            <span wire:loading wire:target="syncNow" style="display:flex;align-items:center;gap:8px;">
                <svg class="_api_spin" style="width:16px;height:16px;" viewBox="0 0 24 24" fill="none">
                    <circle style="opacity:.3" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                    <path style="opacity:.9" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                جارٍ...
            </span>
        </button>

    </div>
</div>

{{-- ── Form Card ── --}}
<div style="background:#fff; border:1px solid #e5e7eb; border-radius:16px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,0.06);">

    <div style="padding:16px 24px; border-bottom:1px solid #f3f4f6; background:#f9fafb;">
        <p style="margin:0; font-size:14px; font-weight:600; color:#111827;">إعدادات الاتصال والجدول</p>
        <p style="margin:4px 0 0; font-size:12px; color:#6b7280;">
            يجب أن يُرجع API مصفوفة بـ
            <code style="background:#e5e7eb;padding:1px 5px;border-radius:4px;font-size:11px;">agent_id</code>
            <code style="background:#e5e7eb;padding:1px 5px;border-radius:4px;font-size:11px;">current_total</code>
            <code style="background:#e5e7eb;padding:1px 5px;border-radius:4px;font-size:11px;">transfer_count</code>
            <code style="background:#e5e7eb;padding:1px 5px;border-radius:4px;font-size:11px;">new_line_count</code>
        </p>
    </div>

    <div style="padding:24px; display:flex; flex-direction:column; gap:20px; max-width:560px;">

        {{-- URL --}}
        <div>
            <label style="display:block; font-size:13px; font-weight:500; color:#374151; margin-bottom:6px;">رابط API</label>
            <x-filament::input.wrapper>
                <x-filament::input type="url" wire:model="api_url" placeholder="https://example.com/api/agent-stats" dir="ltr" />
            </x-filament::input.wrapper>
        </div>

        {{-- Token --}}
        <div x-data="{ show: false }">
            <label style="display:block; font-size:13px; font-weight:500; color:#374151; margin-bottom:6px;">توكن المصادقة</label>
            <x-filament::input.wrapper>
                <x-filament::input x-bind:type="show ? 'text' : 'password'" wire:model="api_token" placeholder="Bearer ..." dir="ltr" class="font-mono" />
                <x-slot name="suffix">
                    <button type="button" @click="show=!show" style="font-size:12px;font-weight:500;color:#6b7280;padding:0 12px;cursor:pointer;white-space:nowrap;border:none;background:none;transition:color .15s;" onmouseover="this.style.color='#065f46'" onmouseout="this.style.color='#6b7280'">
                        <span x-show="!show">إظهار</span>
                        <span x-show="show" x-cloak>إخفاء</span>
                    </button>
                </x-slot>
            </x-filament::input.wrapper>
            <p style="margin:6px 0 0; font-size:12px; color:#9ca3af;">يُخزَّن مشفراً في قاعدة البيانات</p>
        </div>

        {{-- Sync Time --}}
        <div>
            <label style="display:block; font-size:13px; font-weight:500; color:#374151; margin-bottom:6px;">وقت المزامنة التلقائية اليومية</label>
            <x-filament::input.wrapper style="display:inline-flex; width:auto;">
                <x-filament::input type="time" wire:model="daily_sync_time" dir="ltr" style="width:130px;" />
            </x-filament::input.wrapper>
            <p style="margin:6px 0 0; font-size:12px; color:#9ca3af;">يُطلق تلقائياً كل يوم عبر Laravel Scheduler</p>
        </div>

        {{-- Save --}}
        <div style="padding-top:12px; border-top:1px solid #f3f4f6;">
            <x-filament::button wire:click="save" wire:loading.attr="disabled" color="success">
                <span wire:loading.remove wire:target="save">حفظ الإعدادات</span>
                <span wire:loading wire:target="save">جارٍ الحفظ...</span>
            </x-filament::button>
        </div>

    </div>
</div>

</x-filament-panels::page>
