<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Need extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'category',
        'priority',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Shape untuk frontend (KandidatKebutuhanPribadi di src/App.tsx).
     */
    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'desc' => $this->description,
            'category' => $this->category,
            'priority' => $this->priority,
            'status' => $this->status,
        ];
    }
}
