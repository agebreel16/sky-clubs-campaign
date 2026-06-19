<div style="direction: rtl; margin-bottom: 0.25rem;">
    <div style="background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%); border: 1px solid #bfdbfe; border-radius: 1rem; overflow: hidden;">

        {{-- Header --}}
        <div style="background: linear-gradient(90deg, #1d4ed8, #2563eb); padding: 1rem 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <div style="background: rgba(255,255,255,0.18); padding: 0.5rem; border-radius: 0.625rem; display: flex; flex-shrink: 0;">
                <x-heroicon-o-table-cells style="width: 1.25rem; height: 1.25rem; color: #fff;"/>
            </div>
            <div>
                <h3 style="font-size: 0.9375rem; font-weight: 800; color: #fff; margin: 0; letter-spacing: -0.01em;">
                    الحقول المطلوبة في ملف Excel
                </h3>
                <p style="font-size: 0.75rem; color: #bfdbfe; margin: 0.2rem 0 0;">
                    يجب أن يحتوي الصف الأول (headers) على هذه الأعمدة بالأسماء التالية تماماً
                </p>
            </div>
        </div>

        {{-- Columns Grid --}}
        <div style="padding: 1.25rem 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 0.875rem;">

            {{-- agent_id --}}
            <div style="background: #ffffff; border: 1px solid #e0f2fe; border-radius: 0.75rem; padding: 1rem; border-right: 4px solid #0ea5e9;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <x-heroicon-m-identification style="width: 1rem; height: 1rem; color: #0ea5e9; flex-shrink: 0;"/>
                    <code style="font-size: 0.8rem; font-weight: 700; color: #0c4a6e; background: #e0f2fe; padding: 0.1rem 0.45rem; border-radius: 0.3rem; letter-spacing: 0.02em;">agent_id</code>
                </div>
                <p style="font-size: 0.8125rem; font-weight: 700; color: #0c4a6e; margin: 0 0 0.25rem;">معرّف الوكيل</p>
                <p style="font-size: 0.75rem; color: #64748b; margin: 0; line-height: 1.5;">UUID أو اسم الوكيل — يُستخدم للبحث عنه في النظام</p>
            </div>

            {{-- current_total --}}
            <div style="background: #ffffff; border: 1px solid #dcfce7; border-radius: 0.75rem; padding: 1rem; border-right: 4px solid #22c55e;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <x-heroicon-m-chart-bar style="width: 1rem; height: 1rem; color: #22c55e; flex-shrink: 0;"/>
                    <code style="font-size: 0.8rem; font-weight: 700; color: #14532d; background: #dcfce7; padding: 0.1rem 0.45rem; border-radius: 0.3rem; letter-spacing: 0.02em;">current_total</code>
                </div>
                <p style="font-size: 0.8125rem; font-weight: 700; color: #14532d; margin: 0 0 0.25rem;">إجمالي الخطوط الحالية</p>
                <p style="font-size: 0.75rem; color: #64748b; margin: 0; line-height: 1.5;">رقم صحيح — إجمالي خطوط الوكيل حتى تاريخ الاستيراد</p>
            </div>

            {{-- transfer_count --}}
            <div style="background: #ffffff; border: 1px solid #fef3c7; border-radius: 0.75rem; padding: 1rem; border-right: 4px solid #f59e0b;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <x-heroicon-m-arrows-right-left style="width: 1rem; height: 1rem; color: #f59e0b; flex-shrink: 0;"/>
                    <code style="font-size: 0.8rem; font-weight: 700; color: #78350f; background: #fef3c7; padding: 0.1rem 0.45rem; border-radius: 0.3rem; letter-spacing: 0.02em;">transfer_count</code>
                </div>
                <p style="font-size: 0.8125rem; font-weight: 700; color: #78350f; margin: 0 0 0.25rem;">عدد التحويلات</p>
                <p style="font-size: 0.75rem; color: #64748b; margin: 0; line-height: 1.5;">رقم صحيح — عدد خطوط التحويل للوكيل في الفترة</p>
            </div>

            {{-- new_line_count --}}
            <div style="background: #ffffff; border: 1px solid #fce7f3; border-radius: 0.75rem; padding: 1rem; border-right: 4px solid #ec4899;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <x-heroicon-m-plus-circle style="width: 1rem; height: 1rem; color: #ec4899; flex-shrink: 0;"/>
                    <code style="font-size: 0.8rem; font-weight: 700; color: #831843; background: #fce7f3; padding: 0.1rem 0.45rem; border-radius: 0.3rem; letter-spacing: 0.02em;">new_line_count</code>
                </div>
                <p style="font-size: 0.8125rem; font-weight: 700; color: #831843; margin: 0 0 0.25rem;">عدد الخطوط الجديدة</p>
                <p style="font-size: 0.75rem; color: #64748b; margin: 0; line-height: 1.5;">رقم صحيح — عدد الخطوط الجديدة المضافة للوكيل</p>
            </div>

        </div>

        {{-- Footer warning --}}
        <div style="padding: 0.875rem 1.5rem; background: #fefce8; border-top: 1px dashed #fde68a; display: flex; align-items: flex-start; gap: 0.625rem;">
            <x-heroicon-m-exclamation-triangle style="width: 1.125rem; height: 1.125rem; color: #ca8a04; flex-shrink: 0; margin-top: 0.1rem;"/>
            <p style="font-size: 0.8125rem; color: #713f12; margin: 0; font-weight: 600; line-height: 1.6;">
                أسماء الأعمدة حساسة لحالة الأحرف — يجب أن تطابق ما هو مكتوب أعلاه تماماً.
                يُقبل الملف بامتداد
                <code style="background: #fef08a; padding: 0.1rem 0.35rem; border-radius: 0.25rem; font-weight: 700;">.xlsx</code>
                فقط، والبيانات تبدأ من الصف الثاني.
            </p>
        </div>

    </div>
</div>
