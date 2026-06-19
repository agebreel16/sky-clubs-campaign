<div style="direction: rtl; margin-bottom: 0.25rem;" x-data="{ open: false }">
    <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #bbf7d0; border-radius: 1rem; overflow: hidden;">

        {{-- Header (always visible, clickable) --}}
        <button type="button"
            @click="open = !open"
            style="width: 100%; background: linear-gradient(90deg, #15803d, #16a34a); padding: 1rem 1.5rem; display: flex; align-items: center; gap: 0.75rem; cursor: pointer; border: none; text-align: right;">
            <div style="background: rgba(255,255,255,0.18); padding: 0.5rem; border-radius: 0.625rem; display: flex; flex-shrink: 0;">
                <x-heroicon-o-user-plus style="width: 1.25rem; height: 1.25rem; color: #fff;"/>
            </div>
            <div style="flex: 1; text-align: right;">
                <h3 style="font-size: 0.9375rem; font-weight: 800; color: #fff; margin: 0; letter-spacing: -0.01em;">
                    الحقول المطلوبة في ملف Excel
                </h3>
                <p style="font-size: 0.75rem; color: #bbf7d0; margin: 0.2rem 0 0;">
                    اضغط لعرض أو إخفاء التفاصيل
                </p>
            </div>
            <div style="flex-shrink: 0; transition: transform 0.2s;" :style="open ? 'transform: rotate(180deg)' : ''">
                <x-heroicon-m-chevron-down style="width: 1.25rem; height: 1.25rem; color: #bbf7d0;"/>
            </div>
        </button>

        {{-- Collapsible body --}}
        <div x-show="open" x-collapse>
        <div style="padding: 1.25rem 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 0.875rem;">

            {{-- agent_id --}}
            <div style="background: #ffffff; border: 1px solid #e0f2fe; border-radius: 0.75rem; padding: 1rem; border-right: 4px solid #0ea5e9;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <x-heroicon-m-identification style="width: 1rem; height: 1rem; color: #0ea5e9; flex-shrink: 0;"/>
                    <code style="font-size: 0.8rem; font-weight: 700; color: #0c4a6e; background: #e0f2fe; padding: 0.1rem 0.45rem; border-radius: 0.3rem;">agent_id</code>
                </div>
                <p style="font-size: 0.8125rem; font-weight: 700; color: #0c4a6e; margin: 0 0 0.25rem;">معرّف الوكيل</p>
                <p style="font-size: 0.75rem; color: #64748b; margin: 0; line-height: 1.5;">UUID من النظام الخارجي — يُحفظ كما هو</p>
            </div>

            {{-- agent_name --}}
            <div style="background: #ffffff; border: 1px solid #ede9fe; border-radius: 0.75rem; padding: 1rem; border-right: 4px solid #7c3aed;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <x-heroicon-m-user style="width: 1rem; height: 1rem; color: #7c3aed; flex-shrink: 0;"/>
                    <code style="font-size: 0.8rem; font-weight: 700; color: #4c1d95; background: #ede9fe; padding: 0.1rem 0.45rem; border-radius: 0.3rem;">agent_name</code>
                </div>
                <p style="font-size: 0.8125rem; font-weight: 700; color: #4c1d95; margin: 0 0 0.25rem;">اسم الوكيل</p>
                <p style="font-size: 0.75rem; color: #64748b; margin: 0; line-height: 1.5;">نص — الاسم الكامل للوكيل</p>
            </div>

            {{-- baseline_count --}}
            <div style="background: #ffffff; border: 1px solid #fee2e2; border-radius: 0.75rem; padding: 1rem; border-right: 4px solid #ef4444;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <x-heroicon-m-signal style="width: 1rem; height: 1rem; color: #ef4444; flex-shrink: 0;"/>
                    <code style="font-size: 0.8rem; font-weight: 700; color: #7f1d1d; background: #fee2e2; padding: 0.1rem 0.45rem; border-radius: 0.3rem;">baseline_count</code>
                </div>
                <p style="font-size: 0.8125rem; font-weight: 700; color: #7f1d1d; margin: 0 0 0.25rem;">الخطوط عند بداية الحملة</p>
                <p style="font-size: 0.75rem; color: #64748b; margin: 0; line-height: 1.5;">رقم صحيح > 0 — ثابت طوال الحملة</p>
            </div>

            {{-- pre_campaign_count --}}
            <div style="background: #ffffff; border: 1px solid #ffedd5; border-radius: 0.75rem; padding: 1rem; border-right: 4px solid #f97316;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <x-heroicon-m-minus-circle style="width: 1rem; height: 1rem; color: #f97316; flex-shrink: 0;"/>
                    <code style="font-size: 0.8rem; font-weight: 700; color: #7c2d12; background: #ffedd5; padding: 0.1rem 0.45rem; border-radius: 0.3rem;">pre_campaign_count</code>
                </div>
                <p style="font-size: 0.8125rem; font-weight: 700; color: #7c2d12; margin: 0 0 0.25rem;">الخطوط النشطة قبل الحملة</p>
                <p style="font-size: 0.75rem; color: #64748b; margin: 0; line-height: 1.5;">رقم صحيح ≥ 0 ، يجب ألا يتجاوز baseline</p>
            </div>

            {{-- current_total --}}
            <div style="background: #ffffff; border: 1px solid #dcfce7; border-radius: 0.75rem; padding: 1rem; border-right: 4px solid #22c55e;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <x-heroicon-m-chart-bar style="width: 1rem; height: 1rem; color: #22c55e; flex-shrink: 0;"/>
                    <code style="font-size: 0.8rem; font-weight: 700; color: #14532d; background: #dcfce7; padding: 0.1rem 0.45rem; border-radius: 0.3rem;">current_total</code>
                </div>
                <p style="font-size: 0.8125rem; font-weight: 700; color: #14532d; margin: 0 0 0.25rem;">إجمالي الخطوط الحالي</p>
                <p style="font-size: 0.75rem; color: #64748b; margin: 0; line-height: 1.5;">رقم صحيح ≥ pre_campaign_count</p>
            </div>

            {{-- Optional fields --}}
            <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 0.75rem; padding: 1rem;">
                <p style="font-size: 0.75rem; font-weight: 700; color: #475569; margin: 0 0 0.5rem;">حقول اختيارية (default = 0 أو فارغ)</p>
                <div style="display: flex; flex-direction: column; gap: 0.3rem;">
                    <code style="font-size: 0.75rem; color: #64748b; background: #e2e8f0; padding: 0.1rem 0.4rem; border-radius: 0.25rem; width: fit-content;">transfer_count</code>
                    <code style="font-size: 0.75rem; color: #64748b; background: #e2e8f0; padding: 0.1rem 0.4rem; border-radius: 0.25rem; width: fit-content;">new_line_count</code>
                    <code style="font-size: 0.75rem; color: #64748b; background: #e2e8f0; padding: 0.1rem 0.4rem; border-radius: 0.25rem; width: fit-content;">distributor_id</code>
                </div>
            </div>

        </div>
        </div>

        {{-- Footer warning --}}
        <div x-show="open" style="padding: 0.875rem 1.5rem; background: #fefce8; border-top: 1px dashed #fde68a; display: flex; align-items: flex-start; gap: 0.625rem;">
            <x-heroicon-m-exclamation-triangle style="width: 1.125rem; height: 1.125rem; color: #ca8a04; flex-shrink: 0; margin-top: 0.1rem;"/>
            <p style="font-size: 0.8125rem; color: #713f12; margin: 0; font-weight: 600; line-height: 1.6;">
                أسماء الأعمدة حساسة لحالة الأحرف — يجب أن تطابق ما هو مكتوب أعلاه تماماً.
                يُقبل الملف بامتداد
                <code style="background: #fef08a; padding: 0.1rem 0.35rem; border-radius: 0.25rem; font-weight: 700;">.xlsx</code>
                فقط. الوكلاء ذوو الـ UUID الموجود مسبقاً يُتخطون تلقائياً.
            </p>
        </div>

    </div>
</div>
