<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Portfolio extends Model
{
    protected $fillable = [
        'user_id',
        'original_name',
        'stored_name',
        'path',
        'size',
        'mime_type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toApi(): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->original_name,
            'size' => (int) $this->size,
            'mimeType' => $this->mime_type,
            'url' => '/storage/'.$this->path,
            'uploadedAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
