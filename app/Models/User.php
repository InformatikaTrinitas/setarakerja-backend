<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'title',
        'avatar',
        'disability_type',
        'company_name',
        'phone',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function jobListings(): HasMany
    {
        return $this->hasMany(JobListing::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function needs(): HasMany
    {
        return $this->hasMany(Need::class);
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(Portfolio::class);
    }

    /**
     * Shape untuk frontend.
     */
    public function toApi(): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'title' => $this->title,
            'avatar' => $this->avatar ?? '',
            'disabilityType' => $this->disability_type,
            'companyName' => $this->company_name,
            'phone' => $this->phone,
        ];
    }
}
