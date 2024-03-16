<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorsModel extends Model
{
    use HasFactory;

    protected $table = "doctors";
    protected $fillable = ['full_name', 'education', 'specialization', 'email_id', 'contact_number', 'profile_image', 'hospital_name', 'hospital_address', 'hosptial_starts_at', 'hospital_ends_at', 'consultation_fees', 'otp', 'is_profile_completed'];
    protected $hidden = ['created_at', 'updated_at'];

    public function create_doctor($data)
    {
        $result = DoctorsModel::create($data);
        return $result;
    }

    public function get_doctor($queryCondition)
    {
        $result = DoctorsModel::select('doctors.*')
            ->where($queryCondition)
            ->first();
        
        return $result;
    }

    public function update_doctor($queryCondition, $editData)
    {
        $result = DoctorsModel::where($queryCondition)
            ->update($editData);

        return $result;
    }

    public function doctor_exists($queryCondition)
    {
        $result = DoctorsModel::where($queryCondition)
            ->exists();
        
        return $result;
    }
}
