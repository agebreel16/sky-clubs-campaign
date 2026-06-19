<div wire:init="runSync"
     style="min-height:60vh;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:24px;text-align:center;padding:40px 20px;">

    <div style="width:64px;height:64px;border-radius:50%;border:4px solid rgba(14,165,233,.2);border-top-color:#0ea5e9;animation:sync-spin 0.9s linear infinite;flex-shrink:0;"></div>

    <div>
        <div style="font-size:20px;font-weight:700;color:var(--slate-900,#0f172a);margin-bottom:8px;">
            جارٍ تحديث بياناتك...
        </div>
        <div style="font-size:14px;color:var(--slate-500,#64748b);">
            يتم جلب أحدث أرقامك، لحظة من فضلك
        </div>
    </div>

    <style>
        @keyframes sync-spin { to { transform: rotate(360deg); } }
    </style>
</div>
