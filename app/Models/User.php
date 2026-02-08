<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, \Spatie\Permission\Traits\HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'profile_photo_url',
        'average_rating',
        'birth_date',
        'phone',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Eager load roles relationship for API consistency.
     */
    protected $with = ['roles'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function assignedAppointments()
    {
        return $this->hasMany(Appointment::class, 'assigned_to');
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'service_user');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'user_id');
    }
}
