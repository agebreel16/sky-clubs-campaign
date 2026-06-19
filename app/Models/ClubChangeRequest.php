<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ClubChangeRequest extends Model
{
    use HasUuids;

    protected $primaryKey = 'id';

    protected $fillable = [
        'agent_id',
        'import_id',
        'from_club_id',
        'to_club_id',
        'change_type',
        'agent_stats_snapshot',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'agent_stats_snapshot' => 'array',
            'reviewed_at'          => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id', 'agent_id');
    }

    public function fromClub(): BelongsTo
    {
        return $this->belongsTo(Club::class, 'from_club_id', 'club_id');
    }

    public function toClub(): BelongsTo
    {
        return $this->belongsTo(Club::class, 'to_club_id', 'club_id');
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(DataImport::class, 'import_id', 'import_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPromotion(): bool
    {
        return $this->change_type === 'promotion';
    }
}
