<div>
    {{-- Rewards Hero --}}
    <div class="rewards-hero">
        <div class="rewards-hero-eyebrow">💰 مجموع مكافآتك</div>
        <div class="rewards-hero-amounts">
            <div>
                <div class="rew-amt-big" x-data="countUp({{ $total }})" x-init="init()">
                    <span x-text="formatted"></span><small>₪</small>
                </div>
                <div class="rew-amt-label">الإجمالي</div>
            </div>
            <div>
                <div class="rew-amt-big" style="color:var(--success);" x-data="countUp({{ $paid }})" x-init="init()">
                    <span x-text="formatted"></span><small>₪</small>
                </div>
                <div class="rew-amt-label">المدفوع</div>
            </div>
            <div>
                <div class="rew-amt-big" style="color:var(--warning);" x-data="countUp({{ $total - $paid }})" x-init="init()">
                    <span x-text="formatted"></span><small>₪</small>
                </div>
                <div class="rew-amt-label">المعلّق</div>
            </div>
        </div>
        @if($total > 0)
        <div class="rewards-progress">
            <div class="progress-track">
                <div class="progress-fill" style="width:0%;transition:width 1s;" data-fill-width="{{ round($paid / $total * 100) }}%"></div>
            </div>
            <div class="rewards-progress-meta">
                <span>{{ round($paid / $total * 100) }}% مُحصَّل</span>
                <span>متوقع الصرف خلال 7 أيام</span>
            </div>
        </div>
        @endif
    </div>

    {{-- Rewards List --}}
    <div class="card card-pad" style="margin-top:18px;">
        <div class="section-head" style="margin:0 0 4px;">
            <div>
                <h2>قائمة المكافآت</h2>
                <div class="card-subtitle">{{ $rewards->count() }} مكافآت · مرتّبة من الأحدث</div>
            </div>
        </div>
        @forelse($rewards as $reward)
            @php
                $statusMap = ['paid' => ['label' => 'مدفوعة', 'icon' => '✓', 'class' => 'paid'], 'pending' => ['label' => 'معلّقة', 'icon' => '⏳', 'class' => 'pending'], 'failed' => ['label' => 'فشلت', 'icon' => '✗', 'class' => 'failed']];
                $st = $statusMap[$reward->payment_status] ?? $statusMap['pending'];
            @endphp
            <div class="reward-row">
                <div class="reward-icon-circle">💰</div>
                <div class="reward-info">
                    <div class="reward-amount">
                        <span x-data="countUp({{ $reward->amount }})" x-init="init()" x-text="formatted"></span><small>₪</small>
                    </div>
                    <div class="reward-meta">{{ $reward->club?->club_name ?? '—' }} · {{ $reward->created_at?->format('Y-m-d') }}</div>
                    <div class="reward-tags">
                        @if($reward->is_first_arrival)
                            <span class="tag gold">⭐ أول وصول</span>
                        @endif
                    </div>
                    <div class="reward-timeline">
                        <div class="rt-step done"><div class="rt-dot"></div><span>استحقاق</span></div>
                        <div class="rt-line {{ $reward->payment_status === 'paid' ? 'done' : '' }}"></div>
                        <div class="rt-step {{ $reward->payment_status === 'paid' ? 'done' : ($reward->payment_status === 'pending' ? 'active' : '') }}">
                            <div class="rt-dot"></div>
                            <span>{{ $reward->payment_status === 'failed' ? 'فشل' : 'مدفوعة' }}</span>
                        </div>
                    </div>
                </div>
                <div>
                    <span class="status {{ $st['class'] }}">
                        <span>{{ $st['icon'] }}</span>{{ $st['label'] }}
                    </span>
                </div>
            </div>
        @empty
            <div class="empty">💰 لا توجد مكافآت بعد</div>
        @endforelse
    </div>
</div>
