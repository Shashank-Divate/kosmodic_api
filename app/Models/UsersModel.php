<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersModel extends Model
{
    use HasFactory;

    protected $table = "users";
    protected $fillable = ['full_name', 'username', 'email_id', 'contact_number', 'dob', 'gender', 'password'];
    protected $hidden = ['password', 'created_at', 'updated_at'];

    public function create_user($data)
    {
        $result = UsersModel::create($data);
        return $result;
    }

    public function get_user($queryCondition)
    {
        $result = UsersModel::select('users.*')
            ->where($queryCondition)
            ->first();
        
        return $result;
    }

    public function update_user($queryCondition, $editData)
    {
        $result = UsersModel::where($queryCondition)
            ->update($editData);

        return $result;
    }

    public function user_exists($queryCondition)
    {
        $result = UsersModel::where($queryCondition)
            ->exists();
        
        return $result;
    }
}
