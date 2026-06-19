<x-filament-widgets::widget>
    <div style="direction: rtl;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
            <h2 style="font-size: 1.5rem; font-weight: 800; color: #111827; display: flex; align-items: center; gap: 0.75rem; margin: 0;">
                <span style="padding: 0.5rem; background: rgba(var(--primary-500), 0.1); border-radius: 0.5rem; color: rgb(var(--primary-600)); display: flex;">
                    <x-heroicon-o-flag style="width: 1.5rem; height: 1.5rem;"/>
                </span>
                وكلاؤك في الأندية
            </h2>
            <div style="font-size: 0.875rem; color: #6b7280;">
                توزيع وكلاؤك على الأندية
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
            @foreach($clubs as $item)
                @php
                    $club         = $item['club'];
                    $myCount      = $item['myCount'];
                    $firstArrivals = $item['firstArrivals'];
                    $latestMember = $item['latestMember'];
                    $myPercentage = $item['myPercentage'];
                    $totalInClub  = $item['totalInClub'];

                    $gradientCss = match($club->club_name) {
                        'نادي الإنطلاق'   => 'linear-gradient(135deg, #2563eb 0%, #38bdf8 100%)',
                        'نادي التفوق'     => 'linear-gradient(135deg, #d97706 0%, #fbbf24 100%)',
                        'نادي القمة'      => 'linear-gradient(135deg, #4338ca 0%, #8b5cf6 100%)',
                        default           => 'linear-gradient(135deg, #4b5563 0%, #9ca3af 100%)',
                    };
                    $shadowColor = match($club->club_name) {
                        'نادي الإنطلاق'   => 'rgba(37, 99, 235, 0.2)',
                        'نادي التفوق'     => 'rgba(217, 119, 6, 0.2)',
                        'نادي القمة'      => 'rgba(67, 56, 202, 0.2)',
                        default           => 'rgba(75, 85, 99, 0.2)',
                    };
                    $textColor = match($club->club_name) {
                        'نادي الإنطلاق'   => '#2563eb',
                        'نادي التفوق'     => '#d97706',
                        'نادي القمة'      => '#4338ca',
                        default           => '#4b5563',
                    };
                    $icon = match($club->club_name) {
                        'نادي الإنطلاق'   => 'heroicon-o-rocket-launch',
                        'نادي التفوق'     => 'heroicon-o-sparkles',
                        'نادي القمة'      => 'heroicon-o-trophy',
                        default           => 'heroicon-o-star',
                    };
                @endphp

                <div style="position: relative; overflow: hidden; border-radius: 1.25rem; background: #ffffff; border: 1px solid #f3f4f6; box-shadow: 0 20px 25px -5px {{ $shadowColor }}; transition: all 0.3s ease;">
                    {{-- Decorative blur --}}
                    <div style="position: absolute; top: -2rem; right: -2rem; width: 8rem; height: 8rem; background: {{ $gradientCss }}; opacity: 0.05; border-radius: 50%; filter: blur(40px);"></div>

                    <div style="padding: 1.5rem; position: relative;">
                        {{-- Header --}}
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                            <div style="padding: 0.75rem; border-radius: 0.75rem; background: {{ $gradientCss }}; color: #ffffff; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); display: flex; flex-shrink: 0;">
                                <x-dynamic-component :component="$icon" style="width: 1.5rem; height: 1.5rem;"/>
                            </div>
                            <div>
                                <h3 style="font-size: 1.125rem; font-weight: 700; color: #111827; margin: 0;">{{ $club->club_name }}</h3>
                                <span style="font-size: 0.75rem; color: #9ca3af;">الشرط: {{ $club->required_increase }} رقم • {{ ($club->required_transfer_percentage * 100) }}% تحويل</span>
                            </div>
                        </div>

                        {{-- My Count vs Total --}}
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem; text-align: center;">
                            <div style="padding: 0.75rem; background: #f9fafb; border-radius: 0.75rem;">
                                <p style="font-size: 1.75rem; font-weight: 900; color: #111827; margin: 0; line-height: 1;">{{ $myCount }}</p>
                                <p style="font-size: 0.65rem; font-weight: 600; color: #6b7280; margin: 0.25rem 0 0; text-transform: uppercase;">وكلاؤك</p>
                            </div>
                            <div style="padding: 0.75rem; background: #f9fafb; border-radius: 0.75rem;">
                                <p style="font-size: 1.75rem; font-weight: 900; color: #d97706; margin: 0; line-height: 1;">{{ $firstArrivals }}</p>
                                <p style="font-size: 0.65rem; font-weight: 600; color: #6b7280; margin: 0.25rem 0 0; text-transform: uppercase;">من الأوائل</p>
                            </div>
                            <div style="padding: 0.75rem; background: #f9fafb; border-radius: 0.75rem;">
                                <p style="font-size: 1.75rem; font-weight: 900; color: #6b7280; margin: 0; line-height: 1;">{{ $totalInClub }}</p>
                                <p style="font-size: 0.65rem; font-weight: 600; color: #6b7280; margin: 0.25rem 0 0; text-transform: uppercase;">إجمالي النادي</p>
                            </div>
                        </div>

                        {{-- Progress Bar --}}
                        <div style="margin-bottom: 1.25rem;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.5rem;">
                                <span style="color: {{ $textColor }}">نسبة مقاعدك</span>
                                <span style="color: #111827;">{{ $myPercentage }}%</span>
                            </div>
                            <div style="height: 0.625rem; width: 100%; background: #f3f4f6; border-radius: 9999px; overflow: hidden;">
                                <div style="height: 100%; background: {{ $gradientCss }}; border-radius: 9999px; width: {{ min($myPercentage, 100) }}%; transition: width 1s ease-out;"></div>
                            </div>
                            <p style="font-size: 0.7rem; color: #9ca3af; margin: 0.375rem 0 0; text-align: left;">من {{ $club->seat_capacity }} مقعد كحد أدنى لليانصيب</p>
                        </div>

                        {{-- Latest member --}}
                        @if($latestMember)
                            <div style="padding-top: 1rem; border-top: 1px solid #f3f4f6; display: flex; align-items: center; gap: 0.75rem;">
                                <div style="width: 2rem; height: 2rem; border-radius: 50%; background: {{ $gradientCss }}; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <x-heroicon-m-user style="width: 1rem; height: 1rem; color: white;"/>
                                </div>
                                <div>
                                    <p style="font-size: 0.65rem; color: #9ca3af; margin: 0;">آخر انضمام</p>
                                    <p style="font-size: 0.875rem; font-weight: 700; color: #374151; margin: 0;">{{ Str::limit($latestMember->agent_name, 20) }}</p>
                                </div>
                            </div>
                        @else
                            <div style="padding-top: 1rem; border-top: 1px solid #f3f4f6; text-align: center; color: #9ca3af; font-size: 0.8rem;">
                                لا يوجد وكلاء منك في هذا النادي بعد
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <style>
        [style*="border-radius: 1.25rem"]:hover { transform: translateY(-3px); }
    </style>
</x-filament-widgets::widget>
