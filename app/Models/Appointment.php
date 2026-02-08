<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'user_id',
        'company_id',
        'assigned_to',
        'scheduled_at',
        'status',
        'location',
        'latitude',
        'longitude',
        'employee_latitude',
        'employee_longitude',
        'approval_token',
        'rating',
        'review_comment',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
