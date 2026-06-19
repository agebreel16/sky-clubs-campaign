<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;
use App\Models\Distributor;

class Agent extends Model
{
    use HasUuids, SoftDeletes, Notifiable, HasPushSubscriptions;

    protected $primaryKey = 'agent_id';

    protected $fillable = [
        'agent_name',
        'phone',
        'baseline_count',
        'pre_campaign_count',
        'current_total',
        'transfer_count',
        'new_line_count',
        'current_club_id',
        'distributor_id',
        'entry_date',
        'is_first_arrival',
        'notes',
        'portal_token',
        'is_violator',
        'violator_since',
        'violator_reason',
        'last_self_sync_at',
    ];

    protected function casts(): array
    {
        return [
            'entry_date'     => 'datetime',
            'is_first_arrival' => 'boolean',
            'is_violator'      => 'boolean',
            'violator_since'   => 'datetime',
            'last_self_sync_at' => 'datetime',
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class, 'current_club_id', 'club_id');
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class, 'distributor_id', 'id');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'agent_id', 'agent_id');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class, 'agent_id', 'agent_id');
    }

    public function historyLogs(): HasMany
    {
        return $this->hasMany(HistoryLog::class, 'agent_id', 'agent_id');
    }

    public function dailySnapshots(): HasMany
    {
        return $this->hasMany(DailySnapshot::class, 'agent_id', 'agent_id');
    }

    // renamed from notifications() — Notifiable trait adds notifications() automatically
    public function agentNotifications(): HasMany
    {
        return $this->hasMany(AgentNotification::class, 'agent_id', 'agent_id');
    }

    public function clubChangeRequests(): HasMany
    {
        return $this->hasMany(ClubChangeRequest::class, 'agent_id', 'agent_id');
    }

    public function generatePortalToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->update(['portal_token' => $token]);
        return $token;
    }

    public function getPortalUrl(): string
    {
        return route('agent.portal.enter', [
            'uuid'  => $this->agent_id,
            'token' => $this->portal_token,
        ]);
    }

    public function routeNotificationForSms(): ?string
    {
        return $this->phone;
    }

    public function getCampaignIncreaseAttribute(): int
    {
        return $this->transfer_count + $this->new_line_count;
    }

    public function getTransferPercentageAttribute(): float
    {
        $required = $this->club ? $this->club->required_increase : 0;
        if ($required === 0) {
            return 0.0;
        }

        return round(($this->transfer_count / $required) * 100, 2);
    }

    public function getBaselineLossAttribute(): int
    {
        return $this->baseline_count - $this->pre_campaign_count;
    }
}
