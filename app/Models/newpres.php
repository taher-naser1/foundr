<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class newpres extends Model
{
    use HasFactory;
    protected $fillable = ['appointment_id', 'drug_name', 'period', 'times', 'notes'];

    // Define the relationship to the appointment
    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }
}