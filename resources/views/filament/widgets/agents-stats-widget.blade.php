{{-- ══════════════════════════════════════════════════
     SKY CLUB — Agents Stats Widget
     4 بطاقات KPI فوق جدول الوكلاء — متوافقة مع
     تصميم Sky Club Agents.html
     ══════════════════════════════════════════════════ --}}
<div class="sc-stats-grid px-1 pt-1">

    {{-- بطاقة 1: إجمالي الوكلاء — أزرق --}}
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
            <div class="sc-stat-num">{{ number_format($total) }}</div>
            <div class="sc-stat-lbl">إجمالي الوكلاء</div>
        </div>
    </div>

    {{-- بطاقة 2: في الأندية — أخضر --}}
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
            <div class="sc-stat-num">{{ number_format($inClubs) }}</div>
            <div class="sc-stat-lbl">في الأندية</div>
        </div>
    </div>

    {{-- بطاقة 3: المخالفون — أحمر --}}
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

    {{-- بطاقة 4: أوائل الأندية — ذهبي --}}
    <div class="sc-stat-card" style="--sc-c: var(--sc-gold)">
        <div class="sc-stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"
                 stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>
        </div>
        <div class="sc-stat-content">
            <div class="sc-stat-num">{{ number_format($firstArrival) }}</div>
            <div class="sc-stat-lbl">أوائل الأندية</div>
        </div>
    </div>

</div>
