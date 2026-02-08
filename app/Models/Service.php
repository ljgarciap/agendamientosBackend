<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'detail',
        'icon',
        'category',
        'company_id',
        'price',
        'duration_minutes',
        'location_type',
        'image_url' // Added image_url
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'service_user');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
