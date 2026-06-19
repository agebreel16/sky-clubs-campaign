<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailySnapshot extends Model
{
    use HasUuids;

    protected $primaryKey = 'snapshot_id';

    public $timestamps = false;

    protected $fillable = [
        'import_id',
        'data_date',
        'agent_id',
        'baseline_count',
        'pre_campaign_count',
        'current_total',
        'transfer_count',
        'new_line_count',
        'club_id_at_date',
    ];

    protected function casts(): array
    {
        return [
            'data_date'  => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(DataImport::class, 'import_id', 'import_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id', 'agent_id');
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class, 'club_id_at_date', 'club_id');
    }

    public function getCampaignIncreaseAttribute(): int
    {
        return $this->transfer_count + $this->new_line_count;
    }
}
