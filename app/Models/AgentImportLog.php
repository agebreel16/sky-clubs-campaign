<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AgentImportLog extends Model
{
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'source_type',
        'stored_filepath',
        'original_filename',
        'api_url',
        'api_token',
        'status',
        'total_rows',
        'created_count',
        'skipped_count',
        'rejected_count',
        'errors_count',
        'error_message',
        'error_details',
        'success_details',
        'processing_duration_ms',
        'imported_by',
    ];

    protected $casts = [
        'error_details'   => 'array',
        'success_details' => 'array',
        'api_token'       => 'encrypted',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id ??= (string) Str::uuid());
    }

    public function importer()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
