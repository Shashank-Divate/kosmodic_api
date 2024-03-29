<?php

namespace App\Http\Controllers;

use App\Models\UsersModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Helpers\TokenHelper;
use Illuminate\Support\Facades\Http;

class UserController extends Controller
{
    public function userLogin(Request $request)
    {
        // First get all the data sent from the client side
        $data = $request->all();

        // Next, validate these fields since these fields are mandatory to be filled by the user to login into the system
        $rules = array(
            'contact_number' => 'required',
        );

        // Validate the fields
        $validation = Validator::make($data, $rules);

        // Now, check whether validation fails or validate it as true
        if (!$validation->fails()) {
            try {
                // Begin transaction
                DB::beginTransaction();

                $usersModel = new UsersModel(); // Initialize UsersModel()

                // Creating random 4 digit otp numbers for authentication
                $otp = rand(1000, 9999);

                // Check whether user exists or not
                $user_exists = $usersModel->user_exists(['contact_number' => $data['contact_number']]);

                // If User does not exist then create a new one
                if (isset($user_exists) && $user_exists == false) {
                    $usersModel->create_user(['contact_number' => $data['contact_number']]);
                }

                // Update user otp into the database table for further verification
                $user_info = $usersModel->update_user(['contact_number' => $data['contact_number']], ['otp' => $otp]);
                
                // Execute this if condition if the current otp for the particular user is updated
                if (isset($user_info) && $user_info != "" && $user_info != 0) {

                    // Now prepare to send a otp to the particular user by calling a particular sms provider called "Bulk Sms"
                    $user_name = "ryves";
                    $password = "6338/*-!!@dfjsldRR9";

                    // Encoded Key is generated by combining the user_name and password
                    $encoded_key = base64_encode($user_name.':'.$password);

                    $send_otp_api_url = "https://api.bulksms.com/v1/messages";

                    // Create Authorization by comibing Basic and encoded
                    $authorization = "Basic ".$encoded_key ;
        
                    // Create Headers
                    $headers = [
                        'Authorization' => $authorization,
                    ];

                    // Create OTP Message
                    $body = "Hello, your Skinova OTP: $otp. Keep it private.";

                    // Create Data that need to posted
                    $postData = [
                        'to' => '+91'.$data['contact_number'],
                        'routingGroup' => "ECONOMY",
                        'encoding' => "TEXT",
                        'body' => $body,
                        'userSuppliedId' => rand(100, 999),
                    ];
        
                    // Get the response from the bulksms
                    $response = Http::asForm()
                        ->withHeaders($headers)
                        ->post($send_otp_api_url, $postData);
                    
                    //Execute this if condition if the returned response is 201 (Created) 
                    if (isset($response) && $response->status() == 201) {
                        $result = array(
                            'success' => true,
                            'message' => 'OTP Message Sent Successfully'
                        );
                    } else {
                        $result = array(
                            'success' => false,
                            'error' => array(
                                'error_code' => 'E002',
                                'error_message' => 'Error while user login.'
                            )
                        );
                    }
                    // Commit DB Transaction, if everything went perfect
                    DB::commit();
                } else {
                    DB::rollBack();
                    $result = array(
                        'success' => false,
                        'error' => array(
                            'error_code' => 'E003',
                            'error_message' => 'Error while updating user otp.'
                        )
                    );
                }
            } catch (\Exception $e) {
                DB::rollBack();
                Log::info('Exception While Sending OTP');
                Log::info($e->getMessage());
                Log::info($e);

                $result = array(
                    'success' => false,
                    'error' => array(
                        'error_code' => 'E002',
                        'error_message' => 'Error while sending otp.'
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

    public function verifyUserOTP(Request $request)
    {
        // First get all the data sent from the client side
        $data = $request->all();

        // Next, validate these fields since these fields are mandatory to be filled by the user
        $rules = array(
            'contact_number' => 'required',
            'otp' => 'required',
        );

        // Validate the fields
        $validation = Validator::make($data, $rules);

        // Now, check whether validation fails or validate it as true
        if (!$validation->fails()) {
            try {
                // Begin transaction
                DB::beginTransaction();

                $usersModel = new UsersModel(); // Initialize UsersModel()

                // Users Data that need to be verified
                $users_data = array(
                    'contact_number' => $data['contact_number'],
                    'otp' => $data['otp'],
                );

                // Fetch particular user data by passing the above $users_data
                $user_info = $usersModel->get_user($users_data);
                
                if (isset($user_info) && $user_info != "") {

                    // Since otp is verified, re-update the otp field to null
                    $usersModel->update_user(['contact_number' => $data['contact_number']], ['otp' => null]);

                    // Commit the changes
                    DB::commit();

                    //Token Payload Data 
                    $payload = array(
                        'user_info' => $user_info
                    );

                    // Generate Token
                    $token = TokenHelper::generateToken($payload);

                    // Add this generated token into user_info array
                    $user_info['token'] = $token;

                    $result = array(
                        'success' => true,
                        'data' => array(
                            'message' => 'OTP Verified Successfully',
                            'user_info' => $user_info,
                        )
                    );
                } else {
                    DB::rollBack();
                    $result = array(
                        'success' => false,
                        'error' => array(
                            'error_code' => 'E003',
                            'error_message' => 'Invalid OTP'
                        )
                    );
                }                
            } catch (\Exception $e) {
                DB::rollBack();
                Log::info('Exception while validating otp.');
                Log::info($e->getMessage());
                Log::info($e);

                $result = array(
                    'success' => false,
                    'error' => array(
                        'error_code' => 'E002',
                        'error_message' => 'Error while validating otp.'
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

    public function userUpdateProfile(Request $request)
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
                    'email_id' => $data['email_id'],
                    'contact_number' => $data['contact_number'],
                    'dob' => date('Y-m-d', strtotime($data['dob'])),
                    'is_profile_completed' => 1,
                );

                // Update User Data Query
                $user_result = $usersModel->update_user(['contact_number' => $data['contact_number']], $users_data);
                if (!empty($user_result) && $user_result != "" && $user_result != 0) {

                    // Commit the changes into the database
                    DB::commit();

                    $user_info = $usersModel->get_user(['contact_number' => $data['contact_number']]);

                    //Token Payload Data 
                    $payload = array(
                        'user_info' => $user_info
                    );

                    // Generate Token
                    $token = TokenHelper::generateToken($payload);

                    // Add this generated token into user_info array
                    $user_info['token'] = $token;

                    $result = array(
                        'success' => true,
                        'data' => array(
                            'message' => 'User Updated Successfully.',
                            'user_info' => $user_info
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
}
