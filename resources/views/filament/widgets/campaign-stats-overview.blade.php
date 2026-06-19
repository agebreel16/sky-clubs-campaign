{{-- ══════════════════════════════════════════════════
     SKY CLUB — Campaign Stats Overview (لوحة التحكم)
     7 بطاقات KPI: إجمالي الوكلاء · في الأندية · خارج الأندية
                   المخالفون · طلبات معلّقة · أيام متبقية · نسبة الإنجاز
     ══════════════════════════════════════════════════ --}}
<div class="sc-stats-grid-6 px-1 pt-1">

    {{-- 1: إجمالي الوكلاء — أزرق --}}
    <div class="sc-stat-card" style="--sc-c: var(--sc-accent)">
        <div class="sc-stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                <path d="M16 3.13a4 4 0 010 7.75"/>
            </svg>
        </div>
        <div class="sc-stat-content">
            <div class="sc-stat-num">{{ number_format($totalAgents) }}</div>
            <div class="sc-stat-lbl">إجمالي الوكلاء</div>
        </div>
    </div>

    {{-- 2: في الأندية — أخضر --}}
    <div class="sc-stat-card" style="--sc-c: var(--sc-green)">
        <div class="sc-stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 9H4a2 2 0 01-2-2V5h4"/>
                <path d="M18 9h2a2 2 0 002-2V5h-4"/>
                <path d="M6 2h12v7a6 6 0 01-12 0V2z"/>
                <path d="M12 15v4M8 22h8"/>
            </svg>
        </div>
        <div class="sc-stat-content">
            <div class="sc-stat-num">{{ number_format($agentsInClubs) }}</div>
            <div class="sc-stat-lbl">في الأندية</div>
        </div>
    </div>

    {{-- 3: خارج الأندية — رمادي --}}
    <div class="sc-stat-card" style="--sc-c: var(--sc-text3)">
        <div class="sc-stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <line x1="22" y1="11" x2="16" y2="11"/>
            </svg>
        </div>
        <div class="sc-stat-content">
            <div class="sc-stat-num">{{ number_format($agentsOut) }}</div>
            <div class="sc-stat-lbl">خارج الأندية</div>
        </div>
    </div>

    {{-- 4: المخالفون — أحمر --}}
    <div class="sc-stat-card" style="--sc-c: var(--sc-red)">
        <div class="sc-stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
        </div>
        <div class="sc-stat-content">
            <div class="sc-stat-num">{{ number_format($violators) }}</div>
            <div class="sc-stat-lbl">المخالفون</div>
        </div>
    </div>

    {{-- 4b: طلبات معلّقة — برتقالي (تُدرج قبل الأيام المتبقية) --}}
    <div class="sc-stat-card" style="--sc-c: var(--sc-orange)">
        <div class="sc-stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 11l3 3L22 4"/>
                <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
            </svg>
        </div>
        <div class="sc-stat-content">
            <div class="sc-stat-num">{{ number_format($pendingCount) }}</div>
            <div class="sc-stat-lbl">طلبات معلّقة</div>
        </div>
    </div>

    {{-- 5: أيام متبقية — برتقالي --}}
    <div class="sc-stat-card" style="--sc-c: var(--sc-orange)">
        <div class="sc-stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
        </div>
        <div class="sc-stat-content">
            <div class="sc-stat-num">{{ number_format($daysRemaining) }}</div>
            <div class="sc-stat-lbl">أيام متبقية</div>
        </div>
    </div>

    {{-- 6: نسبة الإنجاز الزمنية — بنفسجي --}}
    <div class="sc-stat-card" style="--sc-c: var(--sc-purple)">
        <div class="sc-stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="20" x2="18" y2="10"/>
                <line x1="12" y1="20" x2="12" y2="4"/>
                <line x1="6" y1="20" x2="6" y2="14"/>
            </svg>
        </div>
        <div class="sc-stat-content">
            <div class="sc-stat-num">{{ $progress }}%</div>
            <div class="sc-stat-lbl">نسبة الإنجاز الزمنية</div>
        </div>
    </div>

</div>
