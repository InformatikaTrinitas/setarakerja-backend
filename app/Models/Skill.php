<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Skill extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'score',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Shape untuk frontend (KandidatPassport di src/App.tsx).
     */
    public function toApi(): array
    {
        return [
            'id' => (string) $this->id,
            'skill' => $this->name,
            'score' => (int) $this->score,
            'verifiedAt' => $this->verified_at?->toDateString(),
            'createdAt' => $this->created_at?->toDateString(),
            'source' => 'manual',
        ];
    }
}
