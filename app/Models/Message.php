<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'user_id',
        'application_id',
        'job_id',
        'thread',
        'author',
        'author_name',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(JobListing::class);
    }

    /**
     * Shape untuk frontend (src/pages/FeedbackPage.tsx -> Message).
     */
    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'author' => $this->author === 'hrd' ? 'HRD' : 'Kandidat',
            'role' => $this->author,
            'text' => $this->body,
            'thread' => $this->thread,
            'applicationId' => $this->application_id ? (string) $this->application_id : null,
            'jobId' => $this->job_id ? (string) $this->job_id : null,
            'time' => $this->created_at->format('H.i'),
            'date' => $this->created_at->format('Y-m-d'),
        ];
    }
}
