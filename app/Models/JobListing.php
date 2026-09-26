<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobListing extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'company',
        'company_size',
        'location',
        'location_lat',
        'location_lng',
        'type',
            'salary',
            'skills',
            'accessible',
        'accommodations',
        'categories',
        'job_coach',
        'slots',
        'verified',
        'description',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'location_lat' => 'float',
            'location_lng' => 'float',
            'accommodations' => 'array',
            'categories' => 'array',
            'accessible' => 'boolean',
            'verified' => 'boolean',
            'posted_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Shape yang dipakai frontend (src/pages/JobMatchingPage.tsx).
     */
    public function toApi(): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'company' => $this->company,
            'companySize' => $this->company_size,
            'location' => $this->location,
            'locationLat' => $this->location_lat,
            'locationLng' => $this->location_lng,
            'type' => $this->type,
            'salary' => $this->salary,
            'skills' => $this->skills ?? [],
            'postedDays' => $this->posted_at ? max(0, now()->diffInDays($this->posted_at)) : 0,
            'accessible' => (bool) $this->accessible,
            'accommodations' => $this->accommodations ?? [],
            'category' => $this->categories ?? [],
            'jobCoach' => $this->job_coach,
            'slots' => (int) $this->slots,
            'verified' => (bool) $this->verified,
            'description' => $this->description,
        ];
    }
}
