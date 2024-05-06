<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use App\Models\User;
use App\Models\Cart;



class UserController extends Controller
{
     
    public function loginRegister() {
        return view('front.users.login_register');
    }

    public function userRegister(Request $request) {
        if ($request->ajax()) { 
            $data = $request->all(); 

             
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
              
                'name'     => 'required|string|max:100',
                'mobile'   => 'required|numeric|digits:8',
                'email'    => 'required|email|max:150|unique:users',
                'password' => 'required|min:6',
                'accept'   => 'required'

            ], [ 
                'accept.required' => 'Please accept our Terms & Conditions'
            ]);


            if ($validator->passes()) { 
                $user = new User;

                $user->name     = $data['name'];   
                $user->mobile   = $data['mobile']; 
                $user->email    = $data['email'];  
                $user->password = bcrypt($data['password']); 
                $user->status   = 0;  
                $user->save();


                $email = $data['email'];

           
                $messageData = [
                    'name'   => $data['name'],  
                    'email'  => $data['email'],  
                    'code'   => base64_encode($data['email']) 
                ];
                \Illuminate\Support\Facades\Mail::send('emails.confirmation', $messageData, function ($message) use ($email) {
                    $message->to($email)->subject('Confirm your E-commerce Application Account');
                });

                $redirectTo = url('user/login-register'); 

                return response()->json([ 
                    'type'    => 'success',
                    'url'     => $redirectTo,
                    'message' => 'Please confirm your email to activate your account!'
                ]);

            } else { 
                return response()->json([ 
                    'type'   => 'error',
                    'errors' => $validator->messages()   
                ]);
            }
        }
    }

     
    public function userLogin(Request $request) {
        if ($request->ajax()) {
            $data = $request->all();
   
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                
                'email'    => 'required|email|max:150|exists:users', 
                'password' => 'required|min:6'
            ]);


            if ($validator->passes()) {
                if (Auth::attempt([ 
                    'email'    => $data['email'], 
                    'password' => $data['password']
                ])) {
                  
                    if (Auth::user()->status == 0) {
                        Auth::logout(); 

                       
                        return response()->json([ 
                            'type'    => 'inactive',
                            
                            'message' => 'Your account is not activated! Please confirm your account (by clicking on the Activation Link in the Confirmation Mail) to activate your account.'
                        ]);
                    }

   
                    if (!empty(Session::get('session_id'))) {
                        $user_id    = Auth::user()->id;
                        $session_id = Session::get('session_id');

                        Cart::where('session_id', $session_id)->update(['user_id' => $user_id]);
                    }

 
               
                    $redirectTo = url('cart');

                    
                    return response()->json([ 
                        'type' => 'success',
                        'url'  => $redirectTo 
                    ]);

                } else { 
                    return response()->json([
                        'type'    => 'incorrect',
                        'message' => 'Incorrect Email or Password! Wrong Credentials!'
                    ]);
                }

            } else { 
                return response()->json([
                    'type'   => 'error',
                    'errors' => $validator->messages() 
                ]);
            }
        }
    }

      
    public function userLogout() {
        Auth::logout(); 
        Session::flush(); 


        return redirect('/');
    }



       
    public function confirmAccount($code) { 
        $email = base64_decode($code); 

        $userCount = User::where('email', $email)->count();
        if ($userCount > 0) { 
            $userDetails = User::where('email', $email)->first();
            if ($userDetails->status == 1) { 
                return redirect('user/login-register')->with('error_message', 'Your account is already activated. You can login now.');
            } else { 
                User::where('email', $email)->update([
                    'status' => 1
                ]);

                $messageData = [
                    'name'   => $userDetails->name, 
                    'mobile' => $userDetails->mobile, 
                    'email'  => $email
                ];
                \Illuminate\Support\Facades\Mail::send('emails.register', $messageData, function ($message) use ($email) { 
                    $message->to($email)->subject('Welcome to Multi-vendor E-commerce Application');
                });

                return redirect('user/login-register')->with('success_message', 'Your account is activated. You can login now.');
            }

        } else { 
            abort(404);
        }
    }


 
    
    public function userUpdatePassword(Request $request) {
        if ($request->ajax()) { 
            $data = $request->all();    
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                
                'current_password'  => 'required',
                'new_password'     => 'required|min:6',
                'confirm_password' => 'required|min:6|same:new_password' 

            ]  );


            if ($validator->passes()) {
                $current_password = $data['current_password']; 
                $checkPassword    = User::where('id', Auth::user()->id)->first();

                if (Hash::check($current_password, $checkPassword->password)) { 
                    $user = User::find(Auth::user()->id);
                    $user->password = bcrypt($data['new_password']); 
                    $user->save();

                   
                    return response()->json([ 
                        'type'    => 'success',
                        'message' => 'Account password successfully updated!'
                    ]);

                } else { 
                    return response()->json([ 
                        'type'    => 'incorrect',
                        'message' => 'Your current password is incorrect!'
                    ]);
                }

            } else { 
                return response()->json([
                    'type'   => 'error',
                    'errors' => $validator->messages() 
                ]);
            }

        }
    }

}