<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorAppointmentsModel extends Model
{
    use HasFactory;

    protected $table = "doctor_appointments";
    protected $fillable = ['doctor_id', 'patient_id', 'appointment_slot', 'appointment_date'];
    protected $hidden = ['created_at', 'updated_at'];

    public function create_doctor_appointment($data)
    {
        $result = DoctorAppointmentsModel::create($data);

        return $result;
    }

    public function get_doctor_appointments($queryCondition)
    {
        $result = DoctorAppointmentsModel::select('doctor_appointments.*')
            ->where($queryCondition)
            ->get();
        
        return $result;
    }

    public function appointment_exists($queryCondition)
    {
        $result = DoctorAppointmentsModel::where($queryCondition)
            ->exists();
        
        return $result;
    }
}
