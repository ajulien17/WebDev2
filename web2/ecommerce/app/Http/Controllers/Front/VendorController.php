<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use App\Models\Vendor;
use App\Models\Admin;

class VendorController extends Controller
{
    public function loginRegister() { 
        return view('front.vendors.login_register');
    }

    public function vendorRegister(Request $request) {   
        if ($request->isMethod('post')) { 
            $data = $request->all();
            
            $rules = [
                
                            'name'          => 'required',
                            'email'         => 'required|email|unique:admins|unique:vendors', 
                            'mobile'        => 'required|min:10|numeric|unique:admins|unique:vendors', 
                            'accept'        => 'required'
            ];

            $customMessages = [ 
                                'name.required'             => 'Name is required',
                                'email.required'            => 'Email is required',
                                'email.unique'              => 'Email alreay exists',
                                'mobile.required'           => 'Mobile is required',
                                'mobile.unique'             => 'Mobile alreay exists',
                                'accept.required'           => 'Please accept Terms & Conditions',
            ];

            $validator = Validator::make($data, $rules, $customMessages); 
            if ($validator->fails()) {
                return \Illuminate\Support\Facades\Redirect::back()->withErrors($validator);
            }

            DB::beginTransaction();

            
            $vendor = new Vendor; 
            $vendor->name   = $data['name'];
            $vendor->mobile = $data['mobile'];
            $vendor->email  = $data['email'];
            $vendor->status = 0; 
            date_default_timezone_set('Asia/Beirut'); 
            $vendor->created_at = date('Y-m-d H:i:s'); 
            $vendor->updated_at = date('Y-m-d H:i:s'); 
            $vendor->save();

            $vendor_id = DB::getPdo()->lastInsertId(); 
            $admin = new Admin;
            $admin->type      = 'vendor';
            $admin->vendor_id = $vendor_id; 
            $admin->name      = $data['name'];
            $admin->mobile    = $data['mobile'];
            $admin->email     = $data['email'];
            $admin->password  = bcrypt($data['password']);
            $admin->status    = 0;
            date_default_timezone_set('Asia/Beirut'); 
            $admin->created_at = date('Y-m-d H:i:s'); 
            $admin->updated_at = date('Y-m-d H:i:s'); 
            $admin->save();

            $email = $data['email']; 

            $messageData = [
                'email' => $data['email'],
                'name'  => $data['name'],
                'code'  => base64_encode($data['email']) 
            ];

            \Illuminate\Support\Facades\Mail::send('emails.vendor_confirmation', $messageData, function ($message) use ($email) {
                $message->to($email)->subject('Confirm your Vendor Account');
            });


            DB::commit();

            $message = 'Thanks for registering as Vendor. Please confirm your email to activate your account.';
            return redirect()->back()->with('success_message', $message);
        }
    }

    public function confirmVendor($email) { 

        $email = base64_decode($email); 

        $vendorCount = Vendor::where('email', $email)->count();
        if ($vendorCount > 0) {
            $vendorDetails = Vendor::where('email', $email)->first();
            if ($vendorDetails->confirm == 'Yes') { 
                $message = 'Your Vendor Account is already confirmed. You can login';
                return redirect('vendor/login-register')->with('error_message', $message);

            } else { 
                Admin::where( 'email', $email)->update(['confirm' => 'Yes']);
                Vendor::where('email', $email)->update(['confirm' => 'Yes']);

                $messageData = [
                    'email'  => $email,
                    'name'   => $vendorDetails->name,
                    'mobile' => $vendorDetails->mobile
                ];
                \Illuminate\Support\Facades\Mail::send('emails.vendor_confirmed', $messageData, function ($message) use ($email) { 
                    $message->to($email)->subject('You Vendor Account Confirmed');
                });

                $message = 'Your Vendor Email account is confirmed. You can login and add your personal, business and bank details to activate your Vendor Account to add products';
                return redirect('vendor/login-register')->with('success_message', $message);
            }
        } else { 
            abort(404);
        }
    }
}