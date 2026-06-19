<x-filament-panels::page>

<style>
@keyframes _deals_spin { to { transform: rotate(360deg); } }
._deals_spin { animation: _deals_spin .9s linear infinite; }
._deals_sync_btn:hover:not(:disabled) { background: rgba(255,255,255,0.26) !important; transform: translateY(-1px); }
._deals_sync_btn:active:not(:disabled) { transform: translateY(0); }
._deals_sync_btn:disabled { opacity: .55; cursor: not-allowed; }
@keyframes _deals_progress_glow {
    0%, 100% { box-shadow: 0 0 6px rgba(96,165,250,.5); }
    50%       { box-shadow: 0 0 14px rgba(96,165,250,.85); }
}
._deals_progress_fill {
    animation: _deals_progress_glow 1.6s ease-in-out infinite;
}
</style>

@php
    $sync = $this->getLastSync();
    $isProcessing = $this->isProcessing();
    $isSuccess    = $sync && ($sync['is_recent'] ?? false);
@endphp

{{-- polling نشط فقط أثناء المعالجة --}}
@if ($isProcessing)
<div wire:poll.3000ms="$refresh" style="display:none;"></div>
@endif

{{-- ── Hero Banner ── --}}
<div style="background:linear-gradient(135deg, #0f2460 0%, #1e3a8a 40%, #2563eb 100%);
     border-radius:20px; padding:28px 28px 24px; box-shadow:0 12px 32px rgba(15,36,96,0.35);
     position:relative; overflow:hidden;">

    {{-- خلفية زخرفية --}}
    <div style="position:absolute;inset:0;background:radial-gradient(ellipse at top right, rgba(96,165,250,0.12) 0%, transparent 60%);pointer-events:none;"></div>

    <div style="position:relative; display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:20px;">

        {{-- اليسار: المعلومات --}}
        <div style="flex:1; min-width:0;">

            <p style="margin:0 0 10px; font-size:10.5px; font-weight:700; letter-spacing:2.5px; text-transform:uppercase; color:rgba(147,197,253,0.8);">
                API خطوط الوكلاء 
            </p>

            {{-- حالة الاتصال --}}
            @if ($this->isConfigured())
                <div style="display:flex; align-items:center; gap:10px;">
                    <span style="position:relative;width:10px;height:10px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <span style="position:absolute;width:100%;height:100%;border-radius:50%;background:#86efac;opacity:.4;animation:_deals_spin 0s linear;animation:_sbping 1.8s ease-out infinite;"></span>
                        <span style="width:8px;height:8px;border-radius:50%;background:#86efac;box-shadow:0 0 6px rgba(134,239,172,.6);"></span>
                    </span>
                    <div>
                        <span style="font-size:22px; font-weight:800; color:#fff; letter-spacing:-.3px;">مزامنة أرقام الوكلاء</span>
                        <div style="margin-top:4px; font-size:13px; font-weight:500; color:rgba(147,197,253,0.85);">
                            @if($deals_sync_enabled)
                                مزامنة تلقائية كل
                                @if($deals_sync_interval_minutes >= 60)
                                    {{ floor($deals_sync_interval_minutes / 60) }} ساعة{{ $deals_sync_interval_minutes % 60 > 0 ? ' و' . ($deals_sync_interval_minutes % 60) . ' د' : '' }}
                                @else
                                    {{ $deals_sync_interval_minutes }} دقيقة
                                @endif
                            @else
                                المزامنة التلقائية <span style="color:#fca5a5; font-weight:700;">معطّلة</span>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div style="display:flex; align-items:center; gap:10px;">
                    <span style="width:8px;height:8px;border-radius:50%;background:#f87171;box-shadow:0 0 6px rgba(248,113,113,.5);flex-shrink:0;display:inline-block;"></span>
                    <span style="font-size:22px; font-weight:800; color:#fff;">غير مُهيَّأ</span>
                </div>
            @endif

            {{-- ── حالة أثناء المعالجة ── --}}
            @if ($sync && in_array($sync['status'], ['pending', 'processing']))
                <div style="margin-top:18px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            @if($sync['status'] === 'pending')
                                <span style="width:6px;height:6px;border-radius:50%;background:#fbbf24;animation:_deals_spin 0s;flex-shrink:0;
                                             box-shadow:0 0 0 0 rgba(251,191,36,.5);animation:_sbpulse 1.4s ease-in-out infinite;"></span>
                            @else
                                <svg class="_deals_spin" style="width:14px;height:14px;color:#93c5fd;" viewBox="0 0 24 24" fill="none">
                                    <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                                    <path style="opacity:.9" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                            @endif
                            <span style="font-size:13px; color:rgba(191,219,254,0.95); font-weight:700;">
                                {{ $sync['status'] === 'pending' ? 'في الانتظار...' : 'جارٍ المعالجة...' }}
                            </span>
                        </div>
                        <span style="font-size:20px; color:#fff; font-weight:800; font-variant-numeric:tabular-nums; font-family:monospace;">
                            {{ $sync['progress'] }}<span style="font-size:13px;opacity:.7;">%</span>
                        </span>
                    </div>
                    {{-- شريط تقدم محسّن --}}
                    <div style="background:rgba(255,255,255,0.12); border-radius:999px; height:8px; overflow:visible; position:relative;">
                        <div class="{{ $sync['status'] === 'processing' ? '_deals_progress_fill' : '' }}"
                             style="height:100%; border-radius:999px;
                                    background:linear-gradient(90deg, #3b82f6 0%, #60a5fa 50%, #93c5fd 100%);
                                    width:{{ max(4, $sync['progress']) }}%;
                                    transition:width .6s ease;
                                    position:relative;">
                        </div>
                    </div>
                    @if($sync['status'] === 'processing' && $sync['progress'] > 0 && $sync['progress'] < 100)
                        <p style="margin:6px 0 0; font-size:11.5px; color:rgba(147,197,253,.7);">
                            تمت معالجة حوالي {{ round($sync['progress']) }}% من الوكلاء
                        </p>
                    @endif
                </div>

            {{-- ── بطاقات الإحصائيات بعد الانتهاء ── --}}
            @elseif ($sync && in_array($sync['status'], ['success', 'failed']))
                <div style="margin-top:16px; display:grid; grid-template-columns:repeat(4,1fr); gap:10px; max-width:520px;">

                    {{-- إجمالي --}}
                    <div style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.12);border-radius:12px;padding:10px 12px;">
                        <p style="margin:0 0 3px;font-size:10px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:rgba(147,197,253,.8);">إجمالي</p>
                        <p style="margin:0;font-size:20px;font-weight:800;color:#fff;font-variant-numeric:tabular-nums;line-height:1.1;">{{ number_format($sync['processed']) }}</p>
                        <p style="margin:3px 0 0;font-size:10px;color:rgba(191,219,254,.6);">وكيل</p>
                    </div>

                    {{-- ترقيات --}}
                    <div style="background:rgba(52,211,153,0.1);border:1px solid rgba(52,211,153,0.25);border-radius:12px;padding:10px 12px;">
                        <p style="margin:0 0 3px;font-size:10px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:rgba(110,231,183,.8);">ترقيات</p>
                        <p style="margin:0;font-size:20px;font-weight:800;color:#6ee7b7;font-variant-numeric:tabular-nums;line-height:1.1;">{{ number_format($sync['promotions']) }}</p>
                        <p style="margin:3px 0 0;font-size:10px;color:rgba(110,231,183,.5);">↑ جديد</p>
                    </div>

                    {{-- تهبيطات --}}
                    <div style="background:rgba(251,113,133,0.1);border:1px solid rgba(251,113,133,0.22);border-radius:12px;padding:10px 12px;">
                        <p style="margin:0 0 3px;font-size:10px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:rgba(253,164,175,.8);">تهبيطات</p>
                        <p style="margin:0;font-size:20px;font-weight:800;color:#fda4af;font-variant-numeric:tabular-nums;line-height:1.1;">{{ number_format($sync['demotions']) }}</p>
                        <p style="margin:3px 0 0;font-size:10px;color:rgba(253,164,175,.5);">↓ منخفض</p>
                    </div>

                    {{-- التاريخ --}}
                    <div style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:10px 12px;">
                        <p style="margin:0 0 3px;font-size:10px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:rgba(147,197,253,.7);">آخر مزامنة</p>
                        <p style="margin:0;font-size:13px;font-weight:700;color:#fff;line-height:1.3;">{{ $sync['time'] }}</p>
                        @if($sync['status'] === 'failed')
                            <p style="margin:3px 0 0;font-size:10px;color:#fca5a5;">فشلت</p>
                        @else
                            <p style="margin:3px 0 0;font-size:10px;color:rgba(110,231,183,.7);">✓ نجحت</p>
                        @endif
                    </div>

                </div>

            @else
                <p style="margin:12px 0 0; font-size:13px; color:rgba(147,197,253,.65);">لم تُجرَ أي مزامنة بعد</p>
            @endif
        </div>

        {{-- اليمين: زر المزامنة --}}
        <div style="flex-shrink:0; display:flex; align-items:center;">
            @php
                $btnDisabled = $isProcessing;
                $btnStyle = 'flex-shrink:0; display:inline-flex; align-items:center; justify-content:center; gap:8px;
                    padding:10px 22px; border-radius:14px; font-size:14px; font-weight:700; cursor:pointer;
                    transition:all .2s; backdrop-filter:blur(8px);';
                if ($isProcessing) {
                    $btnStyle .= 'background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);color:rgba(255,255,255,.45);';
                } elseif ($isSuccess) {
                    $btnStyle .= 'background:rgba(52,211,153,0.2);border:1px solid rgba(52,211,153,0.4);color:#6ee7b7;';
                } else {
                    $btnStyle .= 'background:rgba(255,255,255,0.18);border:1px solid rgba(255,255,255,0.32);color:#fff;';
                }
            @endphp

            <button wire:click="syncNow"
                    {{ $btnDisabled ? 'disabled' : '' }}
                    type="button"
                    class="_deals_sync_btn"
                    style="{{ $btnStyle }}">

                @if ($isProcessing)
                    <svg class="_deals_spin" style="width:15px;height:15px;opacity:.6;" viewBox="0 0 24 24" fill="none">
                        <circle style="opacity:.3" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                        <path style="opacity:.9" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <span>جارٍ التزامن</span>
                @elseif ($isSuccess)
                    <svg style="width:15px;height:15px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    <span>اكتملت</span>
                @else
                    <svg style="width:15px;height:15px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0117.8-4.3M22 12.5a10 10 0 01-17.8 4.2"/>
                    </svg>
                    <span wire:loading.remove wire:target="syncNow">مزامنة الآن</span>
                    <span wire:loading wire:target="syncNow">جارٍ...</span>
                @endif

            </button>
        </div>

    </div>
</div>

{{-- ── Form Card ── --}}
<div style="background:#fff; border:1px solid #e5e7eb; border-radius:16px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,0.06);">

    <div style="padding:16px 24px; border-bottom:1px solid #f3f4f6; background:#f9fafb;">
        <p style="margin:0; font-size:14px; font-weight:600; color:#111827;">إعدادات الاتصال</p>

    </div>

    <div style="padding:24px; display:flex; flex-direction:column; gap:20px; max-width:560px;">

        {{-- URL --}}
        <div>
            <label style="display:block; font-size:13px; font-weight:500; color:#374151; margin-bottom:6px;">رابط API</label>
            <x-filament::input.wrapper>
                <x-filament::input type="url" wire:model="deals_api_url" placeholder="https://sales.sky5g.net:8888/ipa/apis/json/..." dir="ltr" />
            </x-filament::input.wrapper>
        </div>

        {{-- Username --}}
        <div>
            <label style="display:block; font-size:13px; font-weight:500; color:#374151; margin-bottom:6px;">اسم المستخدم</label>
            <x-filament::input.wrapper>
                <x-filament::input type="text" wire:model="deals_api_username" placeholder="deal_check" dir="ltr" />
            </x-filament::input.wrapper>
        </div>

        {{-- Password --}}
        <div x-data="{ show: false }">
            <label style="display:block; font-size:13px; font-weight:500; color:#374151; margin-bottom:6px;">كلمة المرور</label>
            <x-filament::input.wrapper>
                <x-filament::input x-bind:type="show ? 'text' : 'password'" wire:model="deals_api_password" placeholder="••••••••" dir="ltr" class="font-mono" />
                <x-slot name="suffix">
                    <button type="button" @click="show=!show" style="font-size:12px;font-weight:500;color:#6b7280;padding:0 12px;cursor:pointer;white-space:nowrap;border:none;background:none;transition:color .15s;" onmouseover="this.style.color='#1e3a8a'" onmouseout="this.style.color='#6b7280'">
                        <span x-show="!show">إظهار</span>
                        <span x-show="show" x-cloak>إخفاء</span>
                    </button>
                </x-slot>
            </x-filament::input.wrapper>
        </div>

        {{-- Campaign Start Date --}}
        <div>
            <label style="display:block; font-size:13px; font-weight:500; color:#374151; margin-bottom:6px;">تاريخ بداية الحملة</label>
            <x-filament::input.wrapper style="display:inline-flex; width:auto;">
                <x-filament::input type="date" wire:model="deals_campaign_start_date" dir="ltr" style="width:180px;" />
            </x-filament::input.wrapper>
        </div>

        {{-- جدول المزامنة التلقائية --}}
        <div style="border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">

            {{-- Header --}}
            <div style="padding:12px 16px; background:#f9fafb; border-bottom:1px solid #f3f4f6;
                        display:flex; align-items:center; justify-content:space-between;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <svg style="width:15px;height:15px;color:#6b7280;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
                    </svg>
                    <span style="font-size:13px; font-weight:600; color:#111827;">المزامنة التلقائية</span>
                </div>

                {{-- Toggle --}}
                <button type="button"
                        wire:click="$set('deals_sync_enabled', {{ $deals_sync_enabled ? 'false' : 'true' }})"
                        style="position:relative; display:inline-flex; align-items:center; width:40px; height:22px;
                               border-radius:999px; border:none; cursor:pointer; transition:background .25s;
                               background:{{ $deals_sync_enabled ? '#2563eb' : '#d1d5db' }};">
                    <span style="position:absolute; top:2px; width:18px; height:18px; border-radius:50%;
                                 background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.25); transition:transform .25s;
                                 transform:{{ $deals_sync_enabled ? 'translateX(20px)' : 'translateX(2px)' }};"></span>
                </button>
            </div>

            {{-- Body --}}
            <div style="padding:14px 16px;">
                @if($deals_sync_enabled)
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span style="font-size:13px; color:#374151; font-weight:500; white-space:nowrap;">كل</span>
                        <x-filament::input.wrapper style="display:inline-flex; width:auto;">
                            <x-filament::input type="number"
                                               wire:model.live="deals_sync_interval_minutes"
                                               min="5" max="1440"
                                               dir="ltr"
                                               style="width:80px; text-align:center;" />
                        </x-filament::input.wrapper>
                        <span style="font-size:13px; color:#374151; font-weight:500; white-space:nowrap;">دقيقة</span>

                        @if($deals_sync_interval_minutes >= 60)
                            <span style="font-size:12px; color:#6b7280; background:#f3f4f6; padding:3px 10px; border-radius:999px;">
                                = {{ floor($deals_sync_interval_minutes / 60) }} ساعة{{ $deals_sync_interval_minutes % 60 > 0 ? ' و' . ($deals_sync_interval_minutes % 60) . ' د' : '' }}
                            </span>
                        @endif
                    </div>
                    <p style="margin:8px 0 0; font-size:11.5px; color:#9ca3af;">الحد الأدنى 5 دقائق</p>
                @else
                    <p style="margin:0; font-size:13px; color:#9ca3af;">المزامنة التلقائية معطّلة — فعّلها للتشغيل التلقائي</p>
                @endif
            </div>

        </div>

        {{-- Save + Test Connection --}}
        <div style="padding-top:12px; border-top:1px solid #f3f4f6; display:flex; flex-wrap:wrap; align-items:center; gap:16px;">

            <x-filament::button wire:click="save" wire:loading.attr="disabled" color="primary">
                <span wire:loading.remove wire:target="save">حفظ الإعدادات</span>
                <span wire:loading wire:target="save">جارٍ الحفظ...</span>
            </x-filament::button>

            <button wire:click="testConnection" wire:loading.attr="disabled"
                    type="button"
                    style="display:inline-flex;align-items:center;gap:7px;padding:7px 16px;
                           border-radius:8px;border:1px solid #d1d5db;background:#fff;
                           color:#374151;font-size:13px;font-weight:500;cursor:pointer;
                           transition:all .15s;"
                    onmouseover="this.style.borderColor='#6b7280';this.style.background='#f9fafb';"
                    onmouseout="this.style.borderColor='#d1d5db';this.style.background='#fff';">

                {{-- أيقونة plug --}}
                <svg wire:loading.remove wire:target="testConnection"
                     style="width:15px;height:15px;color:#6b7280;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M18.36 6.64a9 9 0 1 1-12.73 0M12 2v10"/>
                </svg>
                {{-- spinner --}}
                <svg wire:loading wire:target="testConnection"
                     style="width:14px;height:14px;animation:_deals_spin .9s linear infinite;" viewBox="0 0 24 24" fill="none">
                    <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                    <path style="opacity:.85" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>

                <span wire:loading.remove wire:target="testConnection">اختبار الاتصال</span>
                <span wire:loading wire:target="testConnection">جارٍ الاختبار...</span>
            </button>

            {{-- نتيجة الاختبار --}}
            @if($connectionStatus === 'success')
                <span style="display:inline-flex;align-items:center;gap:5px;color:#16a34a;font-size:13px;font-weight:600;">
                    <svg style="width:15px;height:15px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    الاتصال ناجح
                </span>
            @elseif($connectionStatus === 'failed')
                <span style="display:inline-flex;align-items:flex-start;gap:5px;color:#dc2626;font-size:12px;max-width:280px;line-height:1.4;">
                    <svg style="width:14px;height:14px;flex-shrink:0;margin-top:1px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                    <span><strong>فشل</strong> — {{ $connectionError }}</span>
                </span>
            @endif

        </div>

    </div>
</div>

<style>
@keyframes _sbping   { 0% { transform:scale(1);opacity:.4; } 70% { transform:scale(2.5);opacity:0; } 100% { opacity:0; } }
@keyframes _sbpulse  { 0%,100% { opacity:1; } 50% { opacity:.2; } }
</style>

</x-filament-panels::page>
