<?php

namespace App\Http\Controllers;

use App\Models\UsersModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Helpers\TokenHelper;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function verifyUserEmail(Request $request)
    {
        // First get all the data sent from the client side
        $data = $request->all();

        // Next, validate these fields since these fields are mandatory to be filled by the user
        $rules = array(
            'email_id' => 'required|email',
        );

        // Validate the fields
        $validation = Validator::make($data, $rules);

        // Now, check whether validation fails or validate it as true
        if (!$validation->fails()) {
            try {
                // Begin transaction
                DB::beginTransaction();

                $usersModel = new UsersModel(); // Initialize UsersModel()

                // Users Data that need to be inserted into the database table named 'users'
                $users_data = array(
                    'email_id' => $data['email_id'],
                );

                // First thing, check whether the email_id is already present in the database table or not, this query will return a value 1(if found) or 0(if not found)
                $does_email_id_exists = $usersModel->user_exists(['email_id' => $data['email_id']]);
                
                // If the returned value is 1(Found) then return with an error saying "email_id already in use. Choose another"
                if (isset($does_email_id_exists) && $does_email_id_exists == 1) {
                    DB::rollBack();
                    $result = array(
                        'success' => false,
                        'error' => array(
                            'error_code' => 'E004',
                            'error_message' => 'Email ID already in use. Choose another'
                        )
                    );
                } 
                // If the returned value is 0(Not Found) then create new user into the database table
                else {
                    // Create new user query
                    $user_result = $usersModel->create_user($users_data);
                    if (!empty($user_result) && $user_result != "" && isset($user_result['id']) && $user_result['id'] != "") {
                        DB::commit();
                        $result = array(
                            'success' => true,
                            'data' => array(
                                'message' => 'User Created Successfully.'
                            )
                        );
                    } else {
                        DB::rollBack();
                        $result = array(
                            'success' => false,
                            'error' => array(
                                'error_code' => 'E003',
                                'error_message' => 'Error While Creating User.'
                            )
                        );
                    }
                }                
            } catch (\Exception $e) {
                DB::rollBack();
                Log::info('Exception while validating email id.');
                Log::info($e->getMessage());
                Log::info($e);

                $result = array(
                    'success' => false,
                    'error' => array(
                        'error_code' => 'E002',
                        'error_message' => 'Error while validating email id.'
                    )
                );
            }
        } 
        // If validation fails, return with an error saying some fields are mandatory that need to be filled before creation of new user
        else {
            $result = array(
                'success' => false,
                'error' => array(
                    'error_code' => 'E001',
                    'error_message' => $validation->errors()
                )
            );
        }

        return json_encode($result);
    }
    public function createUser(Request $request)
    {
        // First get all the data sent from the client side
        $data = $request->all();

        // Next, validate these fields since these fields are mandatory to be filled by the user & they are used to create a new user
        $rules = array(
            'full_name' => 'required',
            'username' => 'required',
            'email_id' => 'required|email',
            'contact_number' => 'required',
            'dob' => 'required',
            'gender' => 'required',
            'password' => 'required'
        );

        // Validate the fields
        $validation = Validator::make($data, $rules);

        // Now, check whether validation fails or validate it as true
        if (!$validation->fails()) {
            try {
                // Begin transaction
                DB::beginTransaction();

                $usersModel = new UsersModel(); // Initialize UsersModel()

                // Users Data that need to be inserted into the database table named 'users'
                $users_data = array(
                    'full_name' => $data['full_name'],
                    'username' => $data['username'],
                    'contact_number' => $data['contact_number'],
                    'dob' => date('Y-m-d', strtotime($data['dob'])),
                    'gender' => $data['gender'],
                    'password' => md5(trim($data['password'])),
                );

                // Update User Data Query
                $user_result = $usersModel->update_user(['email_id' => $data['email_id']], $users_data);
                if (!empty($user_result) && $user_result != "") {
                    DB::commit();
                    $result = array(
                        'success' => true,
                        'data' => array(
                            'message' => 'User Updated Successfully.'
                        )
                    );
                } else {
                    DB::rollBack();
                    $result = array(
                        'success' => false,
                        'error' => array(
                            'error_code' => 'E003',
                            'error_message' => 'Error While Updating User.'
                        )
                    );
                }
            } catch (\Exception $e) {
                DB::rollBack();
                Log::info('Exception During User Updation.');
                Log::info($e->getMessage());
                Log::info($e);

                $result = array(
                    'success' => false,
                    'error' => array(
                        'error_code' => 'E002',
                        'error_message' => 'Error while updating user.'
                    )
                );
            }
        } 
        // If validation fails, return with an error saying some fields are mandatory that need to be filled before creation of new user
        else {
            $result = array(
                'success' => false,
                'error' => array(
                    'error_code' => 'E001',
                    'error_message' => $validation->errors()
                )
            );
        }

        return json_encode($result);
    }
    public function userLogin(Request $request)
    {
        // First get all the data sent from the client side
        $data = $request->all();

        // Next, validate these fields since these fields are mandatory to be filled by the user to login into the system
        $rules = array(
            'email_id' => 'required|email',
            'password' => 'required',
        );

        // Validate the fields
        $validation = Validator::make($data, $rules);

        // Now, check whether validation fails or validate it as true
        if (!$validation->fails()) {
            try {
                // Begin transaction
                DB::beginTransaction();

                $usersModel = new UsersModel(); // Initialize UsersModel()

                // User Login Data
                $user_login_data = array(
                    'email_id' => $data['email_id'],
                    'password' => md5(trim($data['password'])),
                );

                $user_info = $usersModel->get_user($user_login_data);
                
                if (!empty($user_info) && $user_info != "") {

                    //Token Payload Data 
                    $payload = array(
                        'user_info' => array(
                            'user_id' => (isset($user_info['user_id'])) ? $user_info['user_id'] : "",
                            'full_name' => (isset($user_info['full_name'])) ? $user_info['full_name'] : "", 
                            'username' => (isset($user_info['username'])) ? $user_info['username'] : "",
                            'email_id' => (isset($user_info['email_id'])) ? $user_info['email_id'] : "",
                            'user_role' => 'patient',
                        )
                    );

                    // Generate Token
                    $token = TokenHelper::generateToken($payload);

                    // Add this generated token into client_user_info array
                    $user_info['token'] = $token;
                    $result = array(
                        'success' => true,
                        'data' => array(
                            'message' => 'Logged In SuccessFully',
                            'user_info' => $user_info,
                        )
                    );
                } else {
                    return json_encode([
                        'success' => false,
                        'error' => [
                            'error_code' => 'E003',
                            'error_message' => 'Invalid Credentials.'
                        ],
                    ]);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                Log::info('Exception During User Login.');
                Log::info($e->getMessage());
                Log::info($e);

                $result = array(
                    'success' => false,
                    'error' => array(
                        'error_code' => 'E002',
                        'error_message' => 'Error while user login.'
                    )
                );
            }
        } 
        // If validation fails, return with an error saying some fields are mandatory that need to be filled before login
        else {
            $result = array(
                'success' => false,
                'error' => array(
                    'error_code' => 'E001',
                    'error_message' => $validation->errors()
                )
            );
        }

        return json_encode($result);
    }
}
