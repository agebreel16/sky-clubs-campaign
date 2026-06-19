<div style="direction: rtl; margin-bottom: 0.25rem;" x-data="{ open: false }">
    <div style="background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%); border: 1px solid #bfdbfe; border-radius: 1rem; overflow: hidden;">

        {{-- Header (always visible, clickable) --}}
        <button type="button"
            @click="open = !open"
            style="width: 100%; background: linear-gradient(90deg, #1e40af, #2563eb); padding: 1rem 1.5rem; display: flex; align-items: center; gap: 0.75rem; cursor: pointer; border: none; text-align: right;">
            <div style="background: rgba(255,255,255,0.18); padding: 0.5rem; border-radius: 0.625rem; display: flex; flex-shrink: 0;">
                <x-heroicon-o-cloud-arrow-down style="width: 1.25rem; height: 1.25rem; color: #fff;"/>
            </div>
            <div style="flex: 1; text-align: right;">
                <h3 style="font-size: 0.9375rem; font-weight: 800; color: #fff; margin: 0; letter-spacing: -0.01em;">
                    البنية المطلوبة لاستجابة الـ API
                </h3>
                <p style="font-size: 0.75rem; color: #bfdbfe; margin: 0.2rem 0 0;">
                    اضغط لعرض أو إخفاء التفاصيل
                </p>
            </div>
            <div style="flex-shrink: 0; transition: transform 0.2s;" :style="open ? 'transform: rotate(180deg)' : ''">
                <x-heroicon-m-chevron-down style="width: 1.25rem; height: 1.25rem; color: #bfdbfe;"/>
            </div>
        </button>

        {{-- Collapsible body --}}
        <div x-show="open" x-collapse style="padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 1rem;">

            {{-- Auth info --}}
            <div style="background: #fff; border: 1px solid #dbeafe; border-radius: 0.75rem; padding: 1rem; display: flex; align-items: flex-start; gap: 0.75rem;">
                <x-heroicon-m-key style="width: 1.125rem; height: 1.125rem; color: #2563eb; flex-shrink: 0; margin-top: 0.1rem;"/>
                <div>
                    <p style="font-size: 0.8125rem; font-weight: 700; color: #1e3a8a; margin: 0 0 0.25rem;">المصادقة</p>
                    <p style="font-size: 0.75rem; color: #64748b; margin: 0; line-height: 1.6;">
                        يُرسَل الـ Token في هيدر الطلب تلقائياً:
                        <code style="background: #eff6ff; color: #1d4ed8; padding: 0.1rem 0.4rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 700;">Authorization: Bearer {token}</code>
                    </p>
                </div>
            </div>

            {{-- Accepted shapes --}}
            <div>
                <p style="font-size: 0.8125rem; font-weight: 700; color: #1e3a8a; margin: 0 0 0.625rem;">أشكال الاستجابة المقبولة (أي منها يعمل):</p>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 0.75rem;">

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 0.875rem;">
                        <p style="font-size: 0.75rem; font-weight: 700; color: #475569; margin: 0 0 0.5rem;">الشكل 1 — مصفوفة مباشرة</p>
                        <pre style="margin: 0; font-size: 0.7rem; color: #334155; line-height: 1.7; overflow-x: auto;">[
  { "agent_id": "uuid", ... },
  { "agent_id": "uuid", ... }
]</pre>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 0.875rem;">
                        <p style="font-size: 0.75rem; font-weight: 700; color: #475569; margin: 0 0 0.5rem;">الشكل 2 — مفتاح "data"</p>
                        <pre style="margin: 0; font-size: 0.7rem; color: #334155; line-height: 1.7; overflow-x: auto;">{
  "data": [
    { "agent_id": "uuid", ... }
  ]
}</pre>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 0.875rem;">
                        <p style="font-size: 0.75rem; font-weight: 700; color: #475569; margin: 0 0 0.5rem;">الشكل 3 — مفتاح "agents"</p>
                        <pre style="margin: 0; font-size: 0.7rem; color: #334155; line-height: 1.7; overflow-x: auto;">{
  "agents": [
    { "agent_id": "uuid", ... }
  ]
}</pre>
                    </div>

                </div>
            </div>

            {{-- Fields table --}}
            <div>
                <p style="font-size: 0.8125rem; font-weight: 700; color: #1e3a8a; margin: 0 0 0.625rem;">حقول كل وكيل في المصفوفة:</p>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 0.75rem;">

                    <div style="background: #ffffff; border: 1px solid #e0f2fe; border-radius: 0.75rem; padding: 0.875rem; border-right: 4px solid #0ea5e9;">
                        <code style="font-size: 0.8rem; font-weight: 700; color: #0c4a6e; background: #e0f2fe; padding: 0.1rem 0.45rem; border-radius: 0.3rem;">agent_id</code>
                        <p style="font-size: 0.75rem; color: #64748b; margin: 0.35rem 0 0; line-height: 1.5;"><span style="color:#dc2626; font-weight:700;">مطلوب</span> — UUID من النظام الخارجي</p>
                    </div>

                    <div style="background: #ffffff; border: 1px solid #ede9fe; border-radius: 0.75rem; padding: 0.875rem; border-right: 4px solid #7c3aed;">
                        <code style="font-size: 0.8rem; font-weight: 700; color: #4c1d95; background: #ede9fe; padding: 0.1rem 0.45rem; border-radius: 0.3rem;">agent_name</code>
                        <p style="font-size: 0.75rem; color: #64748b; margin: 0.35rem 0 0; line-height: 1.5;"><span style="color:#dc2626; font-weight:700;">مطلوب</span> — اسم الوكيل</p>
                    </div>

                    <div style="background: #ffffff; border: 1px solid #fee2e2; border-radius: 0.75rem; padding: 0.875rem; border-right: 4px solid #ef4444;">
                        <code style="font-size: 0.8rem; font-weight: 700; color: #7f1d1d; background: #fee2e2; padding: 0.1rem 0.45rem; border-radius: 0.3rem;">baseline_count</code>
                        <p style="font-size: 0.75rem; color: #64748b; margin: 0.35rem 0 0; line-height: 1.5;"><span style="color:#dc2626; font-weight:700;">مطلوب</span> — رقم صحيح > 0</p>
                    </div>

                    <div style="background: #ffffff; border: 1px solid #ffedd5; border-radius: 0.75rem; padding: 0.875rem; border-right: 4px solid #f97316;">
                        <code style="font-size: 0.8rem; font-weight: 700; color: #7c2d12; background: #ffedd5; padding: 0.1rem 0.45rem; border-radius: 0.3rem;">pre_campaign_count</code>
                        <p style="font-size: 0.75rem; color: #64748b; margin: 0.35rem 0 0; line-height: 1.5;"><span style="color:#dc2626; font-weight:700;">مطلوب</span> — رقم ≥ 0 ، ≤ baseline</p>
                    </div>

                    <div style="background: #ffffff; border: 1px solid #dcfce7; border-radius: 0.75rem; padding: 0.875rem; border-right: 4px solid #22c55e;">
                        <code style="font-size: 0.8rem; font-weight: 700; color: #14532d; background: #dcfce7; padding: 0.1rem 0.45rem; border-radius: 0.3rem;">current_total</code>
                        <p style="font-size: 0.75rem; color: #64748b; margin: 0.35rem 0 0; line-height: 1.5;"><span style="color:#dc2626; font-weight:700;">مطلوب</span> — رقم ≥ pre_campaign</p>
                    </div>

                    <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 0.75rem; padding: 0.875rem;">
                        <p style="font-size: 0.75rem; font-weight: 700; color: #475569; margin: 0 0 0.4rem;">اختيارية (default = 0 أو null)</p>
                        <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                            <code style="font-size: 0.75rem; color: #64748b; background: #e2e8f0; padding: 0.1rem 0.4rem; border-radius: 0.25rem; width: fit-content;">transfer_count</code>
                            <code style="font-size: 0.75rem; color: #64748b; background: #e2e8f0; padding: 0.1rem 0.4rem; border-radius: 0.25rem; width: fit-content;">new_line_count</code>
                            <code style="font-size: 0.75rem; color: #64748b; background: #e2e8f0; padding: 0.1rem 0.4rem; border-radius: 0.25rem; width: fit-content;">distributor_id</code>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        {{-- Footer --}}
        <div x-show="open" style="padding: 0.875rem 1.5rem; background: #fefce8; border-top: 1px dashed #fde68a; display: flex; align-items: flex-start; gap: 0.625rem;">
            <x-heroicon-m-exclamation-triangle style="width: 1.125rem; height: 1.125rem; color: #ca8a04; flex-shrink: 0; margin-top: 0.1rem;"/>
            <p style="font-size: 0.8125rem; color: #713f12; margin: 0; font-weight: 600; line-height: 1.6;">
                الوكلاء ذوو الـ UUID الموجود مسبقاً في النظام يُتخطون تلقائياً.
                الصفوف ذات البيانات الخاطئة تُسجَّل في تفاصيل الخطأ وتُتخطى دون إيقاف الاستيراد.
            </p>
        </div>

    </div>
</div>
