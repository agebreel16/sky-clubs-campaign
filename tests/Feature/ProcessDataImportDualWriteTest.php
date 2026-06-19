<?php

namespace Tests\Feature;

use App\Jobs\ProcessDataImport;
use App\Models\Agent;
use App\Models\Club;
use App\Models\DataImport;
use App\Models\HistoryLog;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * يُثبت هذا الاختبار مشكلتين ناتجتين عن "Dual Write Path":
 *
 * قبل الإصلاح (بدون withoutEvents):
 *  1. $agent->update([stats]) يُطلق AgentObserver::updated()
 *  2. Observer يستدعي checkAndApplyClubChanges() → يُرقّي الوكيل عبر QB + ينشئ base reward فقط
 *  3. Job يُنفّذ $agent->refresh() → يرى الوكيل مُرقّى بالفعل
 *  4. Job يتجاوز promotion block (newOrder == currentOrder)
 *  نتيجة: first_arrival reward ضائع، Opportunities ناقصة
 *
 * بعد الإصلاح (مع withoutEvents):
 *  1. Observer لا يُطلق أثناء الـ Import
 *  2. Job ينشئ كل السجلات بشكل كامل: base + first_arrival + Opportunities
 */
class ProcessDataImportDualWriteTest extends TestCase
{
    use RefreshDatabase;

    private Club $club;
    private Agent $agent;
    private DataImport $import;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();

        $this->club = Club::create([
            'club_name'                    => 'نادي الانطلاق',
            'club_order'                   => 1,
            'required_increase'            => 25,
            'required_transfer_count'      => 15,
            'required_transfer_percentage' => 0.60,
            'base_reward_amount'           => 300,
            'first_arrival_reward_amount'  => 600,
            'first_arrival_count'          => 10,
            'seat_capacity'                => 90,
            'grand_prize_amount'           => 15000,
            'entry_opportunities'          => 1,
            'demotion_timer_days'          => 7,
            'has_bonus_opportunities'      => false,
            'is_active'                    => true,
        ]);

        // وكيل لا ينتمي لأي نادي، زيادة الحملة = 0
        $this->agent = Agent::create([
            'agent_name'         => 'TEST_AGENT',
            'baseline_count'     => 100,
            'pre_campaign_count' => 100,
            'current_total'      => 100,
            'transfer_count'     => 0,
            'new_line_count'     => 0,
            'current_club_id'    => null,
            'is_first_arrival'   => false,
        ]);

        $this->import = DataImport::create([
            'data_date'        => '2026-05-15',
            'source_type'      => 'excel',
            'status'           => 'pending',
            'uploaded_by'      => $user->id,
            'total_agents'     => 1,
            'processed'        => 0,
            'rejected'         => 0,
            'promotions_count' => 0,
            'demotions_count'  => 0,
            'warnings_count'   => 0,
            'errors_count'     => 0,
        ]);
    }

    /**
     * بعد الإصلاح: withoutEvents يُعطّل Observer أثناء Import.
     * الـ Job ينشئ جميع السجلات بشكل كامل:
     *   - base reward (300)
     *   - first_arrival reward (600) — كان ضائعاً قبل الإصلاح
     *   - HistoryLog (promotion)
     */
    public function test_job_creates_complete_records_with_fix(): void
    {
        $job = $this->makeJob([
            'agent_id'       => $this->agent->agent_id,
            'current_total'  => 130,   // زيادة = 30 > required=25 → ترقية
            'transfer_count' => 20,
            'new_line_count' => 5,
        ]);
        $job->handle();

        // تأكد أن الـ Job نجح (لم يُبتلع خطأ داخلي)
        $fresh = $this->import->fresh();
        $this->assertEquals('success', $fresh->status,
            "Job فشل: " . ($fresh->error_message ?? 'لا رسالة'));
        $this->assertEquals(0, $fresh->errors_count,
            "Row رُفضت بخطأ داخلي — راجع storage/logs/laravel.log");

        // تأكد من اكتمال السجلات
        $baseRewards  = Reward::where('agent_id', $this->agent->agent_id)
                              ->where('is_first_arrival', false)->count();
        $firstRewards = Reward::where('agent_id', $this->agent->agent_id)
                              ->where('is_first_arrival', true)->count();
        $promoLogs    = HistoryLog::where('agent_id', $this->agent->agent_id)
                                  ->where('event_type', 'promotion')->count();

        $this->assertEquals(1, $baseRewards,  "يجب مكافأة base واحدة");
        $this->assertEquals(1, $firstRewards, "يجب مكافأة first_arrival واحدة (كانت ضائعة قبل الإصلاح)");
        $this->assertEquals(1, $promoLogs,    "يجب سجل ترقية واحد");
    }

    /**
     * قبل الإصلاح: Observer يُرقّي الوكيل عبر checkAndApplyClubChanges()،
     * ثم الـ Job يُعيد تحميل الوكيل ويراه مُرقّى بالفعل فيتجاوز كتل الترقية.
     *
     * النتيجة:
     *   - base reward موجود (من Observer)
     *   - first_arrival reward ضائع (Observer لا ينشئه، Job لا يصل إليه)
     */
    public function test_observer_creates_incomplete_records_without_fix(): void
    {
        $row   = [
            'agent_id'       => $this->agent->agent_id,
            'current_total'  => 130,
            'transfer_count' => 20,
            'new_line_count' => 5,
        ];
        $clubs = Club::where('is_active', true)->orderBy('club_order')->get();
        $stats = ['processed'=>0,'promotions'=>0,'demotions'=>0,'warnings'=>0,'errors'=>0,'rejected'=>0];

        // استدعاء مباشر بدون withoutEvents لمحاكاة الكود القديم
        $job = $this->makeJob($row);
        $job->callProcessAgentRow($row, $clubs, $stats);

        $baseRewards  = Reward::where('agent_id', $this->agent->agent_id)
                              ->where('is_first_arrival', false)->count();
        $firstRewards = Reward::where('agent_id', $this->agent->agent_id)
                              ->where('is_first_arrival', true)->count();

        // Observer ينشئ base reward فقط
        $this->assertEquals(1, $baseRewards,
            "Observer ينشئ base reward");

        // first_arrival reward ضائع — Observer لا ينشئه، Job لا يصله لأن الوكيل صار مُرقّى
        $this->assertEquals(0, $firstRewards,
            "البق: first_arrival reward ضائع عند تشغيل Observer قبل الـ Job");
    }

    /**
     * وكيل لا يستحق ترقية: لا مكافآت ولا سجلات.
     */
    public function test_no_promotion_no_records(): void
    {
        $job = $this->makeJob([
            'agent_id'       => $this->agent->agent_id,
            'current_total'  => 110,   // زيادة = 10 < required=25
            'transfer_count' => 5,
            'new_line_count' => 2,
        ]);
        $job->handle();

        $this->assertEquals(0, Reward::where('agent_id', $this->agent->agent_id)->count(),
            "لا مكافآت لوكيل لم يُرقَّ");
        $this->assertEquals(0, HistoryLog::where('agent_id', $this->agent->agent_id)->count(),
            "لا سجلات لوكيل لم تتغير حالته");
    }

    private function makeJob(array $row): ProcessDataImport
    {
        return new class($this->import, [$row]) extends ProcessDataImport {
            private array $testRows;

            public function __construct(DataImport $import, array $testRows)
            {
                parent::__construct($import);
                $this->testRows = $testRows;
            }

            protected function readExcelFile(): array
            {
                return $this->testRows;
            }

            // يكشف processAgentRow للاختبار بدون withoutEvents
            public function callProcessAgentRow(array $row, $clubs, array &$stats): void
            {
                $this->processAgentRow($row, $clubs, $stats);
            }
        };
    }
}
