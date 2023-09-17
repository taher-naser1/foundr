<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceDoctor extends Model
{
    use HasFactory;
    protected $table = 'service_doctor'; // Specify the table name if it's different from the default naming convention.
    public $timestamps = false;

    protected $fillable = [
        'doctor_id',
        'service_id',
        'activated',
        'price',
        // Add other columns with data as needed
    ];

    // Define the relationships
    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
