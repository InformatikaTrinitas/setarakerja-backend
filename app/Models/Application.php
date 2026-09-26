<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    protected $fillable = [
        'user_id',
        'job_listing_id',
        'applicant_name',
        'applicant_email',
        'disability',
        'accommodation',
        'status',
        'anonymous_code',
        'ai_score',
        'skills',
        'is_revealed',
        'revealed_name',
        'revealed_disability',
        'revealed_accommodations',
        'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'revealed_accommodations' => 'array',
            'is_revealed' => 'boolean',
            'applied_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(JobListing::class, 'job_listing_id');
    }

    /**
     * Shape untuk kandidat (src/types/index.ts -> Application).
     */
    public function toApi(): array
    {
        return [
            'id' => (string) $this->id,
            'jobId' => (string) $this->job_listing_id,
            'jobTitle' => $this->job?->title,
            'company' => $this->job?->company,
            'status' => $this->status,
            'appliedAt' => $this->applied_at?->toIso8601String(),
            'anonymousId' => $this->anonymous_code,
            'accommodation' => $this->accommodation,
            'disability' => $this->disability,
            'aiScore' => (int) $this->ai_score,
            'skills' => $this->job?->skills ?? [],
            'skillScores' => collect($this->skills ?? [])->map(fn ($skill) => [
                'skill' => is_array($skill) ? ($skill['skill'] ?? '') : '',
                'score' => is_array($skill) ? (int) ($skill['score'] ?? 0) : 0,
                'verifiedAt' => is_array($skill) ? ($skill['verifiedAt'] ?? '') : '',
            ])->values()->all(),
        ];
    }

    /**
     * Shape untuk HRD (src/pages/HRDDashboard.tsx -> Candidate).
     */
    public function toCandidateApi(): array
    {
        $skills = $this->skills ?? [];

        return [
            'id' => (string) $this->id,
            'code' => $this->anonymous_code,
            'aiScore' => (int) $this->ai_score,
            'skills' => $skills,
            'radarData' => array_map(
                fn ($skill) => [
                    'subject' => is_array($skill) ? ($skill['skill'] ?? '') : ($skill['skill'] ?? ''),
                    'score' => is_array($skill) ? (int) ($skill['score'] ?? 0) : 0,
                ],
                $skills
            ),
            'appliedAt' => $this->applied_at?->format('Y-m-d') ?? $this->created_at?->format('Y-m-d'),
            'status' => $this->status,
            'isRevealed' => (bool) $this->is_revealed,
            'revealedData' => $this->is_revealed
                ? [
                    'name' => $this->revealed_name,
                    'disability' => $this->revealed_disability,
                    'accommodations' => $this->revealed_accommodations ?? [],
                ]
                : null,
            'jobTitle' => $this->job?->title,
            'company' => $this->job?->company,
        ];
    }
}
