<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasUuids;

    protected $primaryKey = 'audit_id';

    public $timestamps = false;

    // Immutable — no updates or deletes from application code
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'description',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected function performUpdate(\Illuminate\Database\Eloquent\Builder $query): bool
    {
        throw new \LogicException('AuditLog is immutable — updates are forbidden.');
    }

    protected function performDeleteOnModel(): bool
    {
        throw new \LogicException('AuditLog is immutable — deletes are forbidden.');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
