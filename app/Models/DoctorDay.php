<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'day_id',
        'duration',
        'start_time_am',
        'end_time_am',
        'start_time_pm',
        'end_time_pm',
    ];

    // Define the relationship with the Doctor model
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    // Define the relationship with the Day model
    public function day()
    {
        return $this->belongsTo(Day::class);
    }

}
