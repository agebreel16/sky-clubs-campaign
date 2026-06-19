@php
    $logoUrl = asset('images/sky-logo.png');
@endphp

<div id="sc-distributor-login-root">
<style>
/* ── Reset ── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* ── CSS Variables ── */
:root, [data-theme="dark"] {
  --bg: #0a0e13;
  --surface: #131920;
  --surface2: #1a2230;
  --surface3: #1f2a3a;
  --border: rgba(255,255,255,0.07);
  --border2: rgba(255,255,255,0.13);
  --text: #e8edf5;
  --text2: #8b9ab5;
  --text3: #4e5f7a;
  --shadow: 0 2px 12px rgba(0,0,0,0.4);
  --shadow-lg: 0 30px 80px rgba(0,0,0,0.6);
  --dot-color: rgba(255,255,255,0.025);
  --input-bg: rgba(255,255,255,0.03);
  --input-bg-focus: rgba(255,255,255,0.05);
  --glass-bg: rgba(19,25,32,0.65);
  --glass-border: rgba(255,255,255,0.08);
}
[data-theme="light"] {
  --bg: #eef1f7;
  --surface: #ffffff;
  --surface2: #f5f7fb;
  --surface3: #eaecf3;
  --border: rgba(0,0,0,0.08);
  --border2: rgba(0,0,0,0.14);
  --text: #0a1119;
  --text2: #4a5670;
  --text3: #8a96b0;
  --shadow: 0 2px 12px rgba(0,0,0,0.08);
  --shadow-lg: 0 30px 80px rgba(20,40,80,0.18);
  --dot-color: rgba(0,0,0,0.05);
  --input-bg: rgba(0,0,0,0.025);
  --input-bg-focus: #ffffff;
  --glass-bg: rgba(255,255,255,0.7);
  --glass-border: rgba(0,0,0,0.06);
}
:root {
  /* ── Emerald Green for Distributors ── */
  --accent:  oklch(0.62 0.16 165);
  --accent2: oklch(0.52 0.16 180);
  --accent3: oklch(0.72 0.14 145);
  --accent-glow: oklch(0.62 0.16 165 / 0.35);
  --gold:  oklch(0.78 0.15 82);
  --green: oklch(0.70 0.18 155);
  --red:   oklch(0.65 0.22 25);
}

/* ── Base ── */
html, body { height: 100%; overflow: hidden; font-family: 'Alexandria', sans-serif; }
body.sc-login-body {
  background: var(--bg);
  color: var(--text);
  font-size: 14px;
  line-height: 1.5;
  transition: background 0.3s, color 0.3s;
  direction: rtl;
}

/* ── Live gradient bar ── */
.sc-live-bar {
  position: fixed; top: 0; right: 0; left: 0;
  height: 3px;
  background: linear-gradient(90deg, var(--accent), var(--accent3), var(--gold), var(--accent));
  background-size: 300% 100%;
  animation: sc-liveFlow 5s linear infinite;
  z-index: 60;
}
@keyframes sc-liveFlow {
  0%   { background-position: 0% 50%; }
  100% { background-position: 300% 50%; }
}

/* ── Page layout ── */
.sc-page {
  height: 100vh; display: flex;
  position: relative; overflow: hidden;
}

/* ─────────── BRAND PANEL ─────────── */
.sc-brand-panel {
  flex: 1.15;
  position: relative; overflow: hidden;
  display: flex; flex-direction: column;
  padding: 48px 56px;
  background: var(--surface);
  border-left: 1px solid var(--border);
}

/* Aurora */
.sc-aurora {
  position: absolute; inset: 0;
  pointer-events: none; overflow: hidden;
}
.sc-aurora::before, .sc-aurora::after {
  content: ''; position: absolute;
  border-radius: 50%; filter: blur(90px);
}
.sc-aurora::before {
  width: 600px; height: 600px;
  background: var(--accent);
  top: -150px; right: -100px;
  opacity: 0.42;
  animation: sc-aurora1 18s ease-in-out infinite;
}
.sc-aurora::after {
  width: 500px; height: 500px;
  background: var(--accent2);
  bottom: -150px; left: -100px;
  opacity: 0.38;
  animation: sc-aurora2 22s ease-in-out infinite;
}
[data-theme="light"] .sc-aurora::before { opacity: 0.28; }
[data-theme="light"] .sc-aurora::after  { opacity: 0.22; }
.sc-aurora-extra {
  position: absolute;
  width: 380px; height: 380px;
  background: var(--accent3);
  border-radius: 50%; filter: blur(100px);
  top: 35%; left: 25%; opacity: 0.25;
  animation: sc-aurora3 25s ease-in-out infinite;
}
[data-theme="light"] .sc-aurora-extra { opacity: 0.18; }
@keyframes sc-aurora1 {
  0%,100% { transform: translate(0,0) scale(1); }
  33%      { transform: translate(-30px,40px) scale(1.05); }
  66%      { transform: translate(20px,-30px) scale(0.95); }
}
@keyframes sc-aurora2 {
  0%,100% { transform: translate(0,0) scale(1); }
  33%      { transform: translate(40px,-30px) scale(0.9); }
  66%      { transform: translate(-20px,40px) scale(1.1); }
}
@keyframes sc-aurora3 {
  0%,100% { transform: translate(0,0) scale(1); }
  50%     { transform: translate(60px,-40px) scale(1.15); }
}

/* Grid overlay */
.sc-grid-overlay {
  position: absolute; inset: 0; pointer-events: none;
  background-image:
    linear-gradient(var(--dot-color) 1px, transparent 1px),
    linear-gradient(90deg, var(--dot-color) 1px, transparent 1px);
  background-size: 50px 50px;
  mask-image: radial-gradient(ellipse at center, black 0%, transparent 75%);
  -webkit-mask-image: radial-gradient(ellipse at center, black 0%, transparent 75%);
}

/* Particles */
.sc-particle {
  position: absolute; width: 3px; height: 3px;
  border-radius: 50%; background: var(--accent);
  opacity: 0.6; pointer-events: none;
  box-shadow: 0 0 8px var(--accent);
}
.sc-particle.p1 { top: 18%; left: 12%; animation: sc-float1 14s ease-in-out infinite; }
.sc-particle.p2 { top: 35%; left: 78%; animation: sc-float2 16s ease-in-out infinite; background: var(--gold); box-shadow: 0 0 8px var(--gold); }
.sc-particle.p3 { top: 65%; left: 25%; animation: sc-float1 18s ease-in-out infinite reverse; background: var(--accent3); box-shadow: 0 0 8px var(--accent3); }
.sc-particle.p4 { top: 80%; left: 65%; animation: sc-float2 13s ease-in-out infinite; }
.sc-particle.p5 { top: 50%; left: 50%; animation: sc-float1 20s ease-in-out infinite; background: var(--green); box-shadow: 0 0 8px var(--green); }
.sc-particle.p6 { top: 25%; left: 55%; animation: sc-float2 17s ease-in-out infinite reverse; background: var(--gold); box-shadow: 0 0 8px var(--gold); opacity: 0.45; }
@keyframes sc-float1 {
  0%,100% { transform: translate(0,0); opacity: 0.6; }
  50%     { transform: translate(60px,-80px); opacity: 0.9; }
}
@keyframes sc-float2 {
  0%,100% { transform: translate(0,0); opacity: 0.5; }
  50%     { transform: translate(-80px,60px); opacity: 0.85; }
}

/* Brand content */
.sc-brand-content {
  position: relative; z-index: 2;
  display: flex; flex-direction: column;
  height: 100%;
}
.sc-brand-header {
  display: flex; align-items: center; gap: 14px;
  animation: sc-slideDown 0.7s cubic-bezier(0.16,1,0.3,1) both;
}

/* Portal badge */
.sc-portal-badge {
  margin-right: auto;
  display: inline-flex; align-items: center; gap: 6px;
  padding: 7px 13px;
  background: linear-gradient(135deg, color-mix(in oklch, var(--accent) 18%, transparent), color-mix(in oklch, var(--accent2) 14%, transparent));
  border: 1px solid color-mix(in oklch, var(--accent) 30%, transparent);
  border-radius: 30px;
  font-size: 11.5px; font-weight: 800; color: var(--accent);
  letter-spacing: 0.3px;
  box-shadow: 0 4px 16px color-mix(in oklch, var(--accent) 18%, transparent);
  backdrop-filter: blur(8px);
}
.sc-portal-badge svg { filter: drop-shadow(0 0 4px var(--accent-glow)); }

/* Watermark logo */
.sc-brand-watermark {
  position: absolute; bottom: -80px; left: -80px;
  width: 620px; height: 620px;
  pointer-events: none; z-index: 1;
  opacity: 0.08;
  animation: sc-watermarkFloat 14s ease-in-out infinite;
  filter: drop-shadow(0 12px 40px var(--accent-glow));
}
[data-theme="light"] .sc-brand-watermark { opacity: 0.12; }
.sc-brand-watermark img { width: 100%; height: 100%; object-fit: contain; }
@keyframes sc-watermarkFloat {
  0%,100% { transform: translate(0,0) rotate(0deg) scale(1); }
  50%     { transform: translate(14px,-16px) rotate(-2deg) scale(1.02); }
}

/* Accent logo */
.sc-brand-accent-logo {
  position: absolute; top: 12%; right: 8%;
  width: 180px; height: 180px; opacity: 0.12;
  pointer-events: none; z-index: 1;
  animation: sc-accentFloat 11s ease-in-out infinite;
  filter: blur(0.5px);
}
[data-theme="light"] .sc-brand-accent-logo { opacity: 0.10; }
.sc-brand-accent-logo img { width: 100%; height: 100%; object-fit: contain; }
@keyframes sc-accentFloat {
  0%,100% { transform: translate(0,0) rotate(0); }
  50%     { transform: translate(-12px,18px) rotate(8deg); }
}
@keyframes sc-slideDown {
  from { opacity: 0; transform: translateY(-12px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Logo mark */
.sc-logo-mark {
  width: 52px; height: 52px;
  background: linear-gradient(135deg, var(--accent), var(--accent2));
  border-radius: 13px;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 12px 30px var(--accent-glow);
  position: relative; overflow: hidden; padding: 6px;
}
.sc-logo-mark img {
  width: 100%; height: 100%; object-fit: contain;
  filter: brightness(0) invert(1);
  position: relative; z-index: 1;
}
.sc-logo-mark::after {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(135deg, transparent, rgba(255,255,255,0.25), transparent);
  animation: sc-shine 3.5s ease-in-out infinite;
}
@keyframes sc-shine {
  0%,100% { transform: translateX(100%); }
  50%     { transform: translateX(-100%); }
}
.sc-brand-name { font-weight: 900; font-size: 19px; letter-spacing: 1px; }
.sc-brand-tagline { font-size: 11px; color: var(--text3); margin-top: 3px; font-weight: 500; letter-spacing: 0.3px; }

/* Hero */
.sc-brand-hero {
  flex: 1;
  display: flex; flex-direction: column; justify-content: center;
  max-width: 520px; padding: 40px 0;
}
.sc-brand-eyebrow {
  display: inline-flex; align-items: center; gap: 8px;
  background: var(--surface2); border: 1px solid var(--border);
  border-radius: 30px; padding: 7px 14px;
  font-size: 11px; font-weight: 700; color: var(--text2);
  text-transform: uppercase; letter-spacing: 1.2px;
  width: fit-content; margin-bottom: 26px;
  animation: sc-slideUp 0.7s cubic-bezier(0.16,1,0.3,1) 0.1s both;
}
.sc-eyebrow-dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: var(--green);
  animation: sc-pulse 2s ease-in-out infinite;
}
@keyframes sc-pulse {
  0%,100% { box-shadow: 0 0 0 0 oklch(0.70 0.18 155 / 0.55); }
  50%      { box-shadow: 0 0 0 7px oklch(0.70 0.18 155 / 0); }
}
.sc-brand-title {
  font-size: 72px; font-weight: 900; line-height: 1.0;
  letter-spacing: -2.5px; margin-bottom: 18px;
  color: var(--text);
  animation: sc-slideUp 0.7s cubic-bezier(0.16,1,0.3,1) 0.2s both;
}
.sc-brand-title-accent {
  background: linear-gradient(135deg, var(--accent), var(--accent3), var(--accent2));
  background-size: 200% 100%;
  -webkit-background-clip: text; background-clip: text;
  -webkit-text-fill-color: transparent;
  animation: sc-gradShift 6s ease-in-out infinite;
}
@keyframes sc-gradShift {
  0%,100% { background-position: 0% 50%; }
  50%      { background-position: 100% 50%; }
}
.sc-brand-desc {
  font-size: 15px; color: var(--text3);
  line-height: 1.6; max-width: 380px;
  font-weight: 500;
  animation: sc-slideUp 0.7s cubic-bezier(0.16,1,0.3,1) 0.3s both;
}
@keyframes sc-slideUp {
  from { opacity: 0; transform: translateY(16px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Brand footer */
.sc-brand-footer {
  display: flex; justify-content: space-between; align-items: center;
  margin-top: auto;
  animation: sc-slideUp 0.7s cubic-bezier(0.16,1,0.3,1) 0.5s both;
}
.sc-brand-foot-text { font-size: 12px; color: var(--text3); }
.sc-brand-foot-links { display: flex; gap: 18px; }
.sc-brand-foot-links a {
  color: var(--text3); text-decoration: none;
  font-size: 12px; font-weight: 600; transition: color 0.15s;
}
.sc-brand-foot-links a:hover { color: var(--accent); }

/* ─────────── FORM PANEL ─────────── */
.sc-form-panel {
  flex: 1;
  display: flex; align-items: center; justify-content: center;
  padding: 40px; position: relative;
  background: var(--bg);
  background-image: radial-gradient(var(--dot-color) 1px, transparent 1px);
  background-size: 24px 24px;
}
.sc-form-card {
  width: 100%; max-width: 440px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 24px; padding: 48px 44px;
  box-shadow: var(--shadow-lg);
  position: relative;
  animation: sc-cardIn 0.7s cubic-bezier(0.16,1,0.3,1) 0.2s both;
}
.sc-form-card::before {
  content: ''; position: absolute; top: 0; right: 0; left: 0;
  height: 3px;
  background: linear-gradient(90deg, var(--accent), var(--accent2), var(--accent3));
  background-size: 200% 100%;
  border-radius: 22px 22px 0 0;
  animation: sc-gradShift 6s ease-in-out infinite;
}
@keyframes sc-cardIn {
  from { opacity: 0; transform: translateY(24px) scale(0.98); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}

.sc-form-header { margin-bottom: 36px; }
.sc-form-title { font-size: 30px; font-weight: 900; letter-spacing: -0.7px; line-height: 1.15; color: var(--text); }
.sc-form-sub { font-size: 14px; color: var(--text3); margin-top: 8px; }

/* Error */
.sc-field-error {
  font-size: 12px; color: var(--red);
  margin-top: 5px; display: flex; align-items: center; gap: 4px;
}

/* Field */
.sc-field { margin-bottom: 18px; position: relative; }
.sc-field-label {
  display: flex; align-items: center; justify-content: space-between;
  font-size: 12.5px; font-weight: 600;
  color: var(--text); margin-bottom: 8px;
}
.sc-field-label .sc-req { color: var(--red); font-size: 14px; line-height: 0; }
.sc-input-wrap {
  position: relative; display: flex; align-items: center;
  background: var(--input-bg);
  border: 1.5px solid var(--border);
  border-radius: 12px;
  transition: border-color 0.25s, box-shadow 0.25s, background 0.25s;
}
.sc-input-wrap:focus-within {
  border-color: var(--accent);
  box-shadow: 0 0 0 4px var(--accent-glow);
  background: var(--input-bg-focus);
}
.sc-input-wrap.sc-has-error { border-color: var(--red); }
.sc-input-icon {
  display: flex; align-items: center; justify-content: center;
  width: 44px; height: 48px; color: var(--text3); flex-shrink: 0;
  transition: color 0.25s, transform 0.25s;
}
.sc-input-wrap:focus-within .sc-input-icon { color: var(--accent); transform: scale(1.08); }
.sc-input-wrap input {
  flex: 1; background: none; border: none; outline: none;
  font-family: 'Alexandria', sans-serif;
  font-size: 14.5px; color: var(--text);
  padding: 13px 14px 13px 0;
  width: 100%; min-width: 0; font-weight: 500;
}
.sc-input-wrap input::placeholder { color: var(--text3); font-weight: 400; }
.sc-input-toggle {
  background: none; border: none; cursor: pointer;
  width: 44px; height: 48px;
  display: flex; align-items: center; justify-content: center;
  color: var(--text3); transition: color 0.15s; flex-shrink: 0;
}
.sc-input-toggle:hover { color: var(--accent); }

/* Caps lock */
.sc-caps-warn {
  display: none; align-items: center; gap: 5px;
  font-size: 11px; color: var(--gold);
  margin-top: 6px; font-weight: 600;
}
.sc-caps-warn.show { display: flex; animation: sc-shake 0.3s ease; }
@keyframes sc-shake {
  0%,100% { transform: translateX(0); }
  25%      { transform: translateX(-3px); }
  75%      { transform: translateX(3px); }
}

/* Field row */
.sc-field-row {
  display: flex; align-items: center; justify-content: space-between;
  margin: 8px 0 24px;
}
.sc-check {
  display: inline-flex; align-items: center; gap: 8px;
  cursor: pointer; user-select: none;
  font-size: 13px; color: var(--text2); font-weight: 500;
}
.sc-check input { display: none; }
.sc-check-box {
  width: 18px; height: 18px;
  border: 1.5px solid var(--border2);
  border-radius: 5px;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.2s; background: var(--input-bg);
}
.sc-check input:checked + .sc-check-box {
  background: var(--accent); border-color: var(--accent);
  transform: scale(1.05);
}
.sc-check-box svg { opacity: 0; transition: opacity 0.15s; transform: scale(0.7); }
.sc-check input:checked + .sc-check-box svg { opacity: 1; transform: scale(1); transition: opacity 0.15s, transform 0.2s; }

/* Button */
.sc-btn-primary {
  width: 100%;
  background: linear-gradient(135deg, var(--accent), var(--accent2));
  color: #fff; border: none; border-radius: 12px; padding: 14px;
  font-family: 'Alexandria', sans-serif;
  font-size: 14.5px; font-weight: 700;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  transition: transform 0.2s, box-shadow 0.25s, filter 0.15s;
  box-shadow: 0 10px 26px var(--accent-glow);
  position: relative; overflow: hidden;
}
.sc-btn-primary::before {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(135deg, transparent, rgba(255,255,255,0.2), transparent);
  transform: translateX(100%); transition: transform 0.6s;
}
.sc-btn-primary:hover { transform: translateY(-2px); filter: brightness(1.08); box-shadow: 0 14px 32px var(--accent-glow); }
.sc-btn-primary:hover::before { transform: translateX(-100%); }
.sc-btn-primary:active { transform: translateY(0); }
.sc-btn-primary svg { transition: transform 0.2s; }
.sc-btn-primary:hover svg { transform: translateX(-3px); }

/* Form bottom */
.sc-form-bottom {
  text-align: center; margin-top: 28px;
  font-size: 12.5px; color: var(--text3);
  display: flex; align-items: center; justify-content: center; gap: 5px;
}
.sc-form-bottom svg { color: var(--accent); flex-shrink: 0; }

/* Theme FAB */
.sc-theme-fab {
  position: fixed; top: 18px; left: 18px;
  width: 42px; height: 42px;
  background: var(--surface); border: 1px solid var(--border);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; color: var(--text2);
  transition: all 0.2s; z-index: 60;
  box-shadow: var(--shadow);
}
.sc-theme-fab:hover { background: var(--surface2); color: var(--accent); transform: rotate(15deg); }

/* Help pill */
.sc-help-pill {
  position: absolute; bottom: 18px; left: 18px;
  display: flex; align-items: center; gap: 8px;
  font-size: 11.5px; color: var(--text3);
  padding: 8px 14px;
  background: var(--surface); border: 1px solid var(--border);
  border-radius: 30px; box-shadow: var(--shadow);
}
.sc-help-pill svg { color: var(--accent); }

/* Spinner */
@keyframes sc-spin { from { transform: rotate(0); } to { transform: rotate(360deg); } }
.sc-spin { animation: sc-spin 0.9s linear infinite; }

/* Responsive */
@media (max-width: 980px) {
  .sc-brand-panel { display: none; }
  .sc-form-panel { padding: 24px 18px; }
}
@media (max-width: 480px) {
  .sc-form-card { padding: 32px 22px; border-radius: 18px; }
  .sc-form-title { font-size: 22px; }
  .sc-help-pill { display: none; }
}
</style>

{{-- Override body class --}}
<script>document.body.classList.add('sc-login-body');</script>

<div class="sc-live-bar"></div>

<button class="sc-theme-fab" id="scThemeFab" title="تبديل الوضع" aria-label="theme">
  <svg id="scThemeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
</button>

<div class="sc-page">

  {{-- BRAND PANEL --}}
  <div class="sc-brand-panel">
    <div class="sc-aurora"></div>
    <div class="sc-aurora-extra"></div>
    <div class="sc-grid-overlay"></div>

    <div class="sc-particle p1"></div>
    <div class="sc-particle p2"></div>
    <div class="sc-particle p3"></div>
    <div class="sc-particle p4"></div>
    <div class="sc-particle p5"></div>
    <div class="sc-particle p6"></div>

    <div class="sc-brand-content">
      <div class="sc-brand-watermark" aria-hidden="true">
        <img src="{{ $logoUrl }}" alt="" />
      </div>
      <div class="sc-brand-accent-logo" aria-hidden="true">
        <img src="{{ $logoUrl }}" alt="" />
      </div>

      <div class="sc-brand-header">
        <div class="sc-logo-mark">
          <img src="{{ $logoUrl }}" alt="SKY" />
        </div>
        <div>
          <div class="sc-brand-name">SKY CLUB</div>
          <div class="sc-brand-tagline">لوحة الموزعين</div>
        </div>
        <div class="sc-portal-badge">
          {{-- Map / distributor icon --}}
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>
          <span>بوابة الموزعين</span>
        </div>
      </div>

      <div class="sc-brand-hero">
        <div class="sc-brand-eyebrow">
          <span class="sc-eyebrow-dot"></span>
          <span>اتصال آمن ومشفّر</span>
        </div>
        <h1 class="sc-brand-title">
          أهلاً<br/>
          <span class="sc-brand-title-accent">بعودتك.</span>
        </h1>
        <p class="sc-brand-desc">سجّل دخولك للمتابعة.</p>
      </div>

      <div class="sc-brand-footer">
        <div class="sc-brand-foot-text">© {{ date('Y') }} SKY CLUB</div>
        <div class="sc-brand-foot-links">
          <a href="#">الخصوصية</a>
          <a href="#">الشروط</a>
        </div>
      </div>
    </div>
  </div>

  {{-- FORM PANEL --}}
  <div class="sc-form-panel">
    <form
      class="sc-form-card"
      wire:submit.prevent="authenticate"
    >
      <div class="sc-form-header">
        <h2 class="sc-form-title">تسجيل الدخول</h2>
        <p class="sc-form-sub">الوصول إلى لوحة الموزعين</p>
      </div>

      {{-- Email --}}
      <div class="sc-field">
        <label class="sc-field-label" for="scEmail">
          <span>البريد الإلكتروني <span class="sc-req">*</span></span>
        </label>
        <div class="sc-input-wrap @error('data.email') sc-has-error @enderror">
          <span class="sc-input-icon">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
          </span>
          <input
            type="email"
            id="scEmail"
            wire:model="data.email"
            placeholder="name@example.com"
            autocomplete="email"
            required
          />
        </div>
        @error('data.email')
          <div class="sc-field-error">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            {{ $message }}
          </div>
        @enderror
      </div>

      {{-- Password --}}
      <div class="sc-field">
        <label class="sc-field-label" for="scPassword">
          <span>كلمة المرور <span class="sc-req">*</span></span>
        </label>
        <div class="sc-input-wrap @error('data.password') sc-has-error @enderror">
          <span class="sc-input-icon">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
              <path d="M7 11V7a5 5 0 0110 0v4"/>
            </svg>
          </span>
          <input
            type="password"
            id="scPassword"
            wire:model="data.password"
            placeholder="••••••••"
            autocomplete="current-password"
            required
          />
          <button type="button" class="sc-input-toggle" id="scTogglePwd" aria-label="إظهار كلمة المرور">
            <svg id="scIconEye" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
        <div class="sc-caps-warn" id="scCapsWarn">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          <span>Caps Lock مُفعّل</span>
        </div>
        @error('data.password')
          <div class="sc-field-error">{{ $message }}</div>
        @enderror
      </div>

      {{-- Remember --}}
      <div class="sc-field-row">
        <label class="sc-check">
          <input type="checkbox" wire:model.boolean="data.remember" id="scRemember" checked />
          <span class="sc-check-box">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </span>
          <span>تذكرني لمدة 30 يوماً</span>
        </label>
      </div>

      {{-- Submit --}}
      <button type="submit" class="sc-btn-primary">
        <span wire:loading.remove wire:target="authenticate">تسجيل الدخول</span>
        <svg wire:loading.remove wire:target="authenticate" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="19" y1="12" x2="5" y2="12"/>
          <polyline points="12 19 5 12 12 5"/>
        </svg>
        <span wire:loading wire:target="authenticate">جاري التحقق...</span>
        <svg wire:loading wire:target="authenticate" class="sc-spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 12a9 9 0 11-6.219-8.56"/>
        </svg>
      </button>

      <div class="sc-form-bottom">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
        <span>اتصالك محمي بتشفير SSL</span>
      </div>
    </form>

    <div class="sc-help-pill">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/>
        <path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/>
        <line x1="12" y1="17" x2="12.01" y2="17"/>
      </svg>
      <span>تحتاج مساعدة؟ تواصل مع الدعم الفني</span>
    </div>
  </div>

</div>

<script>
(function () {
  /* ── Theme ── */
  const fab = document.getElementById('scThemeFab');
  const sunSVG  = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
  const moonSVG = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>';

  function applyTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    fab.innerHTML = t === 'dark' ? moonSVG : sunSVG;
    try { localStorage.setItem('sc-theme', t); } catch {}
  }
  let theme = 'dark';
  try { theme = localStorage.getItem('sc-theme') || 'dark'; } catch {}
  applyTheme(theme);
  fab.addEventListener('click', function () {
    theme = theme === 'dark' ? 'light' : 'dark';
    applyTheme(theme);
  });

  /* ── Password toggle ── */
  const toggleBtn = document.getElementById('scTogglePwd');
  const pwdInput  = document.getElementById('scPassword');
  const eyeOpen   = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
  const eyeClosed = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
  if (toggleBtn && pwdInput) {
    toggleBtn.addEventListener('click', function () {
      const showing = pwdInput.type === 'text';
      pwdInput.type = showing ? 'password' : 'text';
      toggleBtn.innerHTML = showing ? eyeOpen : eyeClosed;
    });
  }

  /* ── Caps Lock ── */
  const capsWarn = document.getElementById('scCapsWarn');
  if (pwdInput && capsWarn) {
    pwdInput.addEventListener('keydown', function (e) {
      if (e.getModifierState && e.getModifierState('CapsLock')) capsWarn.classList.add('show');
      else capsWarn.classList.remove('show');
    });
    pwdInput.addEventListener('blur', function () { capsWarn.classList.remove('show'); });
  }
})();
</script>
</div>{{-- #sc-distributor-login-root --}}
