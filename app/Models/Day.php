<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Day extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    // Define the relationship with the DoctorDay model
    public function doctorDays()
    {
        return $this->hasMany(DoctorDay::class);
    }
}
