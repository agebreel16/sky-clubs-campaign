{{-- ═══════════════════════════════════════════════════
     SKY CLUB — Admin Panel Custom Theme
     Matches the Sky Club Agents.html design system
     ═══════════════════════════════════════════════════ --}}
<style>
@verbatim
/* ── 1. Design System CSS Variables ── */
:root {
  --sc-accent:        oklch(0.60 0.22 245);
  --sc-accent2:       oklch(0.50 0.22 270);
  --sc-accent-light:  oklch(0.94 0.06 245);
  --sc-accent-glow:   oklch(0.60 0.22 245 / 0.22);
  --sc-gold:          oklch(0.78 0.15 82);
  --sc-gold-glow:     oklch(0.78 0.15 82 / 0.18);
  --sc-green:         oklch(0.70 0.18 155);
  --sc-green-glow:    oklch(0.70 0.18 155 / 0.18);
  --sc-red:           oklch(0.65 0.22 25);
  --sc-red-glow:      oklch(0.65 0.22 25 / 0.18);
  --sc-orange:        oklch(0.72 0.18 55);
  --sc-purple:        oklch(0.62 0.20 295);
  --sc-purple-glow:   oklch(0.62 0.20 295 / 0.18);
  --sc-radius:        14px;
  --sc-radius-sm:     8px;
}

/* Dark mode variables (default — site defaults to dark) */
:root,
.dark,
html.dark {
  --sc-bg:       #0a0e13;
  --sc-surface:  #131920;
  --sc-surface2: #1a2230;
  --sc-surface3: #1f2a3a;
  --sc-border:   rgba(255,255,255,0.07);
  --sc-border2:  rgba(255,255,255,0.13);
  --sc-text:     #e8edf5;
  --sc-text2:    #8b9ab5;
  --sc-text3:    #4e5f7a;
  --sc-shadow:   0 2px 12px rgba(0,0,0,0.4);
  --sc-dot-color: rgba(255,255,255,0.025);
  --sc-row-hover: rgba(255,255,255,0.02);
}

/* Light mode variables */
html:not(.dark),
html.fi:not(.dark) {
  --sc-bg:       #eef1f7;
  --sc-surface:  #ffffff;
  --sc-surface2: #eaecf5;
  --sc-surface3: #dde0ee;
  --sc-border:   rgba(0,0,0,0.09);
  --sc-border2:  rgba(0,0,0,0.16);
  --sc-text:     #0a1119;
  --sc-text2:    #3a4560;
  --sc-text3:    #7a86a0;
  --sc-shadow:   0 2px 12px rgba(0,0,0,0.08);
  --sc-dot-color: rgba(0,0,0,0.06);
  --sc-row-hover: rgba(0,0,0,0.025);
}

/* ── 2. Animated Live Bar ── */
@keyframes sc-live-flow {
  0%   { background-position: 0% 50%; }
  100% { background-position: 300% 50%; }
}
.sc-live-bar {
  height: 3px;
  background: linear-gradient(90deg, var(--sc-accent), var(--sc-green), var(--sc-gold), var(--sc-accent));
  background-size: 300% 100%;
  animation: sc-live-flow 4s linear infinite;
  flex-shrink: 0;
}

/* ── 3. Global page background with dot grid ── */
.fi-body {
  background-color: var(--sc-bg) !important;
  background-image: radial-gradient(var(--sc-dot-color) 1px, transparent 1px) !important;
  background-size: 24px 24px !important;
}

/* ── 4. Sidebar Container ── */
.fi-sidebar.fi-main-sidebar {
  background-color: var(--sc-surface) !important;
  border-inline-start: 1px solid var(--sc-border) !important;
}

/* Sidebar header / logo area */
.fi-sidebar-header {
  padding: 16px 14px 12px !important;
  border-bottom: 1px solid var(--sc-border) !important;
  display: flex !important;
  align-items: center !important;
  gap: 10px !important;
}
.fi-sidebar-header-ctn {
  border-bottom: none !important;
}

/* Logo mark (gradient icon box injected via renderHook) */
.sc-sidebar-logo-mark {
  width: 36px;
  height: 36px;
  background: linear-gradient(135deg, var(--sc-accent), var(--sc-accent2));
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  box-shadow: 0 4px 14px var(--sc-accent-glow);
  flex-shrink: 0;
}

/* Logo text — Filament's .fi-logo */
.fi-sidebar-header .fi-logo,
.fi-sidebar-header-logo-ctn .fi-logo,
.fi-sidebar-header-logo-ctn a {
  display: flex !important;
  flex-direction: column !important;
  align-items: flex-start !important;
  text-decoration: none !important;
}
.fi-sidebar-header .fi-logo,
.fi-sidebar-header-logo-ctn .fi-logo {
  font-weight: 800 !important;
  font-size: 15px !important;
  letter-spacing: 0.5px !important;
  color: var(--sc-text) !important;
  height: auto !important;
}
/* Sub-label under brand name */
.fi-sidebar-header-logo-ctn::after {
  content: 'لوحة الإدارة';
  display: block;
  font-size: 10px;
  color: var(--sc-text3);
  font-weight: 500;
  margin-top: -2px;
  padding-inline-start: 0;
}

/* ── 5. Sidebar Navigation Groups ── */
.fi-sidebar-nav {
  padding: 6px 0 !important;
}

/* Group button (clickable group header) */
.fi-sidebar-group-btn {
  padding: 12px 16px 4px !important;
  cursor: pointer;
}

/* Group label text */
.fi-sidebar-group-label {
  font-size: 10px !important;
  font-weight: 700 !important;
  color: var(--sc-text3) !important;
  text-transform: uppercase !important;
  letter-spacing: 1.2px !important;
}

/* ── 6. Sidebar Navigation Items ── */
.fi-sidebar-item-btn {
  color: var(--sc-text2) !important;
  font-size: 13px !important;
  font-weight: 500 !important;
  padding: 9px 16px !important;
  border-radius: 0 !important;
  transition: color 0.15s, background 0.15s !important;
  position: relative !important;
}

.fi-sidebar-item-btn:hover {
  color: var(--sc-text) !important;
  background: var(--sc-surface2) !important;
}

/* Active item */
.fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
  color: var(--sc-accent) !important;
  background: oklch(0.60 0.22 245 / 0.09) !important;
}
html.fi:not(.dark) .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
  background: var(--sc-accent-light) !important;
}

/* Active indicator: 3px colored strip on the inline-end (right in RTL = left visually) */
.fi-sidebar-item.fi-active > .fi-sidebar-item-btn::before {
  content: '' !important;
  position: absolute !important;
  inset-inline-end: 0 !important;
  top: 4px !important;
  bottom: 4px !important;
  width: 3px !important;
  background: var(--sc-accent) !important;
  border-radius: 3px 0 0 3px !important;
}

/* Nav item icons inherit accent color when active */
.fi-sidebar-item.fi-active .fi-sidebar-item-icon {
  color: var(--sc-accent) !important;
}

/* Nav item badges */
.fi-sidebar-item-badge {
  background: var(--sc-red) !important;
  color: #fff !important;
  font-size: 10px !important;
  font-weight: 700 !important;
  padding: 1px 6px !important;
  border-radius: 20px !important;
}

/* ── 7. Sidebar Collapse Group button ── */
.fi-sidebar-group-collapse-btn {
  color: var(--sc-text3) !important;
  opacity: 0.6;
}

/* ── 8. Topbar ── */
.fi-topbar {
  background-color: var(--sc-surface) !important;
  border-bottom: 1px solid var(--sc-border) !important;
  gap: 12px !important;
}

/* Topbar end section */
.fi-topbar-end {
  gap: 8px !important;
  align-items: center !important;
}

/* ── 9. Portal badge (injected via renderHook TOPBAR_END) ── */
.sc-portal-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 11px;
  background: oklch(0.60 0.22 245 / 0.10);
  border: 1px solid oklch(0.60 0.22 245 / 0.25);
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
  color: var(--sc-accent);
  letter-spacing: 0.3px;
  white-space: nowrap;
  order: -1; /* appears before user menu */
}
html.fi:not(.dark) .sc-portal-badge {
  background: var(--sc-accent-light);
}
.sc-portal-badge svg {
  flex-shrink: 0;
}

/* ── 10. Topbar buttons (theme toggle, notifications) ── */
.fi-icon-btn,
.fi-topbar-end .fi-icon-btn,
button[wire\:click*="toggleTheme"],
.fi-theme-switcher-btn,
[x-on\:click*="toggleTheme"] {
  background: var(--sc-surface2) !important;
  border: 1px solid var(--sc-border) !important;
  border-radius: 50% !important;
  color: var(--sc-text2) !important;
  width: 36px !important;
  height: 36px !important;
}

/* User menu trigger (chip style) */
.fi-user-menu-trigger,
.fi-user-menu button[type="button"] {
  background: var(--sc-surface2) !important;
  border: 1px solid var(--sc-border) !important;
  border-radius: 30px !important;
  transition: background 0.15s !important;
}
.fi-user-menu-trigger:hover,
.fi-user-menu button[type="button"]:hover {
  background: var(--sc-surface3) !important;
}

/* ── 11. Main content area ── */
.fi-main,
.fi-main-ctn {
  background-color: transparent !important;
}

/* ── 11b. Widget wrappers ── */
/* Filament's <x-filament-widgets::widget> renders as .fi-wi-widget
   We want our widgets to blend into the page — card chrome is handled by our own CSS */
.fi-wi-widget {
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
  border-radius: 0 !important;
}
/* Also target Livewire widget root divs */
.fi-wi {
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
}

/* ── 12. Table cards ── */
.fi-ta,
.fi-ta-ctn {
  background-color: var(--sc-surface) !important;
  border: 1px solid var(--sc-border) !important;
  border-radius: var(--sc-radius) !important;
  box-shadow: var(--sc-shadow) !important;
}

/* Table header row */
.fi-ta-header,
thead tr {
  background-color: var(--sc-surface2) !important;
}

/* Table row hover */
tbody tr:hover td {
  background-color: var(--sc-row-hover) !important;
}

/* ── 13. Stats Widget Cards ── */
@keyframes sc-slide-up {
  from { opacity: 0; transform: translateY(12px); }
  to   { opacity: 1; transform: translateY(0); }
}

.sc-stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  margin-bottom: 0;
}

@media (max-width: 1100px) {
  .sc-stats-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
  .sc-stats-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
}

.sc-stat-card {
  background: var(--sc-surface);
  border: 1px solid var(--sc-border);
  border-radius: var(--sc-radius);
  padding: 16px 18px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: var(--sc-shadow);
  position: relative;
  overflow: hidden;
  transition: border-color 0.2s, transform 0.2s;
  animation: sc-slide-up 0.5s cubic-bezier(0.16,1,0.3,1) both;
  cursor: default;
}

.sc-stat-card:hover {
  border-color: var(--sc-border2);
  transform: translateY(-2px);
}

/* Colored top border */
.sc-stat-card::before {
  content: '';
  position: absolute;
  top: 0; right: 0; left: 0;
  height: 2px;
  background: var(--sc-c);
  border-radius: var(--sc-radius) var(--sc-radius) 0 0;
}

/* Glow blob in background */
.sc-stat-card::after {
  content: '';
  position: absolute;
  top: -40px; left: -40px;
  width: 120px; height: 120px;
  border-radius: 50%;
  background: var(--sc-c);
  opacity: 0.06;
  pointer-events: none;
}

.sc-stat-card:nth-child(1) { animation-delay: 0.05s; }
.sc-stat-card:nth-child(2) { animation-delay: 0.10s; }
.sc-stat-card:nth-child(3) { animation-delay: 0.15s; }
.sc-stat-card:nth-child(4) { animation-delay: 0.20s; }

.sc-stat-icon {
  width: 40px; height: 40px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  background: color-mix(in oklch, var(--sc-c) 12%, transparent);
  color: var(--sc-c);
}

.sc-stat-content { flex: 1; min-width: 0; }

.sc-stat-num {
  font-size: 26px;
  font-weight: 900;
  line-height: 1;
  color: var(--sc-c);
  font-variant-numeric: tabular-nums;
  letter-spacing: -0.5px;
}

.sc-stat-lbl {
  font-size: 11.5px;
  color: var(--sc-text3);
  margin-top: 5px;
  font-weight: 600;
}

/* ── 14. Club-specific badge colors ── */
.sc-badge-club-1,
.fi-badge.sc-badge-club-1 {
  background-color: oklch(0.60 0.22 245 / 0.12) !important;
  color: oklch(0.60 0.22 245) !important;
  border-color: oklch(0.60 0.22 245 / 0.25) !important;
}
.dark .sc-badge-club-1 {
  background-color: oklch(0.60 0.22 245 / 0.15) !important;
}

.sc-badge-club-2,
.fi-badge.sc-badge-club-2 {
  background-color: oklch(0.78 0.15 82 / 0.12) !important;
  color: oklch(0.78 0.15 82) !important;
  border-color: oklch(0.78 0.15 82 / 0.25) !important;
}
.dark .sc-badge-club-2 {
  background-color: oklch(0.78 0.15 82 / 0.15) !important;
}

.sc-badge-club-3,
.fi-badge.sc-badge-club-3 {
  background-color: oklch(0.62 0.20 295 / 0.12) !important;
  color: oklch(0.62 0.20 295) !important;
  border-color: oklch(0.62 0.20 295 / 0.25) !important;
}
.dark .sc-badge-club-3 {
  background-color: oklch(0.62 0.20 295 / 0.15) !important;
}

.sc-badge-outside {
  background-color: var(--sc-surface2) !important;
  color: var(--sc-text3) !important;
  border-color: var(--sc-border) !important;
}

/* ── 15. Conversion % Progress Bar ── */
.sc-conv-cell {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 90px;
}
.sc-conv-bar {
  flex: 1;
  height: 5px;
  background: var(--sc-surface2);
  border-radius: 3px;
  overflow: hidden;
  max-width: 60px;
}
.sc-conv-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.4s ease;
}
.sc-conv-text {
  font-weight: 800;
  font-size: 12.5px;
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}

/* ── 16. Demotion Timer Badge ── */
.sc-demo-timer {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 10px;
  border-radius: var(--sc-radius-sm);
  font-size: 11.5px;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
}
.sc-demo-red    { background: oklch(0.65 0.22 25 / 0.15); color: oklch(0.65 0.22 25); }
.sc-demo-orange { background: oklch(0.72 0.18 55 / 0.15); color: oklch(0.72 0.18 55); }
.sc-demo-blue   { background: oklch(0.60 0.22 245 / 0.12); color: oklch(0.60 0.22 245); }
.sc-demo-none   { color: var(--sc-text3); font-size: 13px; }

/* ── 17. Growth badge ── */
.sc-growth {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 9px;
  border-radius: 6px;
  background: oklch(0.70 0.18 155 / 0.15);
  color: oklch(0.70 0.18 155);
  font-size: 12px;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
}

/* ── 18. Agent name cell with initials avatar ── */
.sc-agent-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}
.sc-agent-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 11px;
  color: #fff;
  flex-shrink: 0;
}
.sc-agent-name {
  font-weight: 700;
  font-size: 13px;
  color: var(--sc-text);
  line-height: 1.2;
}

/* ── 19. First-arrival star ── */
.sc-star-yes {
  color: oklch(0.78 0.15 82);
  filter: drop-shadow(0 0 5px oklch(0.78 0.15 82 / 0.4));
}
.sc-star-no {
  color: var(--sc-text3);
  opacity: 0.35;
}

/* ── 20. Tabs (club filter chips) ── */
.fi-tabs-tab {
  border-radius: 20px !important;
  font-size: 12px !important;
  font-weight: 600 !important;
  padding: 5px 14px !important;
}

/* ── 21. Cards and page-level panels ── */
.fi-section,
.fi-page-section {
  background: var(--sc-surface) !important;
  border: 1px solid var(--sc-border) !important;
  border-radius: var(--sc-radius) !important;
}

/* ── 22. Slide-in animation ── */
.fi-page-content > * {
  animation: sc-slide-up 0.5s cubic-bezier(0.16,1,0.3,1) both;
}

/* ── 23. Scrollbar styling ── */
::-webkit-scrollbar { width: 5px; height: 5px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: var(--sc-border2); border-radius: 4px; }

/* ── 24. Aurora blobs (decorative — top-right corner) ── */
@keyframes sc-aurora1 {
  0%,100% { transform: translate(0,0) scale(1); }
  50%      { transform: translate(-40px,60px) scale(1.1); }
}
@keyframes sc-aurora2 {
  0%,100% { transform: translate(0,0) scale(1); }
  50%      { transform: translate(50px,-30px) scale(0.92); }
}
.sc-aurora-bg {
  position: fixed;
  top: 0; left: 0;
  width: 500px; height: 500px;
  pointer-events: none;
  z-index: 0;
  overflow: hidden;
}
.sc-aurora-blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  pointer-events: none;
}
.sc-aurora-blob.a1 {
  width: 360px; height: 360px;
  top: -120px; left: -80px;
  background: var(--sc-accent);
  opacity: 0.08;
  animation: sc-aurora1 18s ease-in-out infinite;
}
.sc-aurora-blob.a2 {
  width: 280px; height: 280px;
  top: 80px; left: 140px;
  background: var(--sc-accent2);
  opacity: 0.05;
  animation: sc-aurora2 22s ease-in-out infinite;
}
.dark .sc-aurora-blob.a1 { opacity: 0.12; }
.dark .sc-aurora-blob.a2 { opacity: 0.08; }

/* ── 25. 6-card stats grid (Campaign Stats Overview Dashboard) ── */
.sc-stats-grid-6 {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}
.sc-stats-grid-6 .sc-stat-card:nth-child(1) { animation-delay: 0.04s; }
.sc-stats-grid-6 .sc-stat-card:nth-child(2) { animation-delay: 0.08s; }
.sc-stats-grid-6 .sc-stat-card:nth-child(3) { animation-delay: 0.12s; }
.sc-stats-grid-6 .sc-stat-card:nth-child(4) { animation-delay: 0.16s; }
.sc-stats-grid-6 .sc-stat-card:nth-child(5) { animation-delay: 0.20s; }
.sc-stats-grid-6 .sc-stat-card:nth-child(6) { animation-delay: 0.24s; }
@media (max-width: 1100px) { .sc-stats-grid-6 { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px)  { .sc-stats-grid-6 { grid-template-columns: 1fr; gap: 8px; } }

/* ── 26. Club Status Widget — dark/light-mode safe ── */
.sc-clubs-section-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 18px;
  padding-bottom: 14px;
  border-bottom: 1px solid var(--sc-border);
}
.sc-clubs-title {
  font-size: 16px;
  font-weight: 800;
  color: var(--sc-text);
  display: flex;
  align-items: center;
  gap: 8px;
}
.sc-clubs-subtitle {
  font-size: 11.5px;
  color: var(--sc-text3);
  margin-top: 3px;
}

.sc-clubs-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}
@media (max-width: 1100px) { .sc-clubs-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px)  { .sc-clubs-grid { grid-template-columns: 1fr; } }

.sc-club-card {
  background: var(--sc-surface);
  border: 1px solid var(--sc-border);
  border-radius: var(--sc-radius);
  padding: 20px;
  position: relative;
  overflow: hidden;
  transition: border-color 0.2s, transform 0.2s, box-shadow 0.2s;
  animation: sc-slide-up 0.5s cubic-bezier(0.16,1,0.3,1) both;
}
.sc-club-card:nth-child(1) { animation-delay: 0.05s; }
.sc-club-card:nth-child(2) { animation-delay: 0.10s; }
.sc-club-card:nth-child(3) { animation-delay: 0.15s; }
.sc-club-card:hover { transform: translateY(-3px); border-color: var(--sc-border2); box-shadow: 0 8px 28px rgba(0,0,0,0.12); }

/* Decorative glow blob behind card */
.sc-club-card::before {
  content: '';
  position: absolute;
  top: -40px; right: -40px;
  width: 140px; height: 140px;
  border-radius: 50%;
  background: var(--cc, var(--sc-accent));
  opacity: 0.05;
  pointer-events: none;
  filter: blur(30px);
}

/* Club icon box */
.sc-club-icon-box {
  width: 40px; height: 40px;
  border-radius: 10px;
  background: color-mix(in oklch, var(--cc, var(--sc-accent)) 14%, transparent);
  color: var(--cc, var(--sc-accent));
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

.sc-club-name { font-size: 15px; font-weight: 800; color: var(--sc-text); line-height: 1.2; }
.sc-club-rank { font-size: 10px; color: var(--sc-text3); font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; margin-top: 2px; }

.sc-club-badge-full {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 9px; border-radius: 20px;
  background: oklch(0.70 0.18 155 / 0.12);
  color: oklch(0.70 0.18 155);
  font-size: 10px; font-weight: 700; white-space: nowrap;
}
.sc-club-badge-active {
  display: inline-flex; align-items: center;
  padding: 3px 9px; border-radius: 20px;
  background: var(--sc-surface2);
  color: var(--sc-text3);
  font-size: 10px; font-weight: 700; white-space: nowrap;
}
.sc-club-badge-full-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: oklch(0.70 0.18 155);
  animation: sc-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
@keyframes sc-pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.4; }
}

/* SVG Ring progress */
.sc-ring-progress-circle {
  transition: stroke-dashoffset 1s ease-out;
}

/* Progress bar */
.sc-club-progress-bar {
  height: 6px;
  background: var(--sc-surface2);
  border-radius: 3px;
  overflow: hidden;
}
.sc-club-progress-fill {
  height: 100%;
  background: var(--cc, var(--sc-accent));
  border-radius: 3px;
  transition: width 1s ease-out;
}

/* Club detail link */
.sc-club-detail-btn {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 12px; font-weight: 700;
  color: var(--cc, var(--sc-accent));
  text-decoration: none;
  padding: 5px 10px; border-radius: 6px;
  border: 1px solid color-mix(in oklch, var(--cc, var(--sc-accent)) 30%, transparent);
  background: transparent;
  transition: background 0.15s;
  white-space: nowrap;
}
.sc-club-detail-btn:hover {
  background: color-mix(in oklch, var(--cc, var(--sc-accent)) 10%, transparent);
}
@endverbatim
</style>

{{-- Aurora decoration blobs --}}
<div class="sc-aurora-bg" aria-hidden="true">
  <div class="sc-aurora-blob a1"></div>
  <div class="sc-aurora-blob a2"></div>
</div>
