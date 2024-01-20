<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Hash;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assis ts in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $token = Str::random(length: 64);
        DB::table(table: 'password_resets')->insert([
            'email' => $request->email,
            'token' => $token,
            'created_at' => Carbon::now()
        ]);
        Mail::send(
            'auth.forget-password',
            ['token' => $token, 'email' => $request->email],
            function ($message) use ($request) {
                $message->to($request->email);
                $message->subject("Reset Password");
            }
        );
        return response()->json(['message' => 'Password reset link sent to your email']);

        // $status = $this->broker()->sendResetLink(
        //     $request->only('email'),
        //     function ($user, $token) {
        //         $this->sendResetLinkEmailMessage($user, $token);
        //     }
        // );
    }

    // protected function sendResetLinkEmailMessage($user, $token)
    // {
    //     // Use the Mail facade directly to send the email
    //     Mail::send(
    //         ['emails.password_reset', 'emails.password_reset_plain'],
    //         compact('token', 'user'),
    //         function ($message) use ($user) {
    //             $message->to($user->email);
    //             $message->subject('Your Password Reset Subject'); // Set your subject here
    //             $message->from('achirayaj63@nu.ac.th', 'WT');
    //         }
    //     );
    // }


    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'current_password' => 'required|string|min:8|confirmed',
            'new_password' => 'required'
        ]);

        $updatePassword = DB::table('password_resets')->where([
            'email' => $request->email,
            'token' => $request->token
        ])->first();

        if (!$updatePassword) {
            return response()->json(['error' => 'Invalid reset token'], 400);
        }

        User::where("email", $request->email)->update(["password" => Hash::make($request->new_password)]);

        DB::table('password_resets')->where(["email" => $request->email])->delete();

        return response()->json(['message' => 'Password reset successfully']);
        // $status = Password::reset(
        //     $request->only('email', 'password', 'password_confirmation', 'token'),
        //     function ($user, $password) {
        //         $user->forceFill([
        //             'password' => bcrypt($password),
        //         ])->save();
        //     }
        // );

        // return $status === Password::PASSWORD_RESET
        //     ? response()->json(['message' => 'Password changed successfully'])
        //     : response()->json(['error' => 'Unable to reset password'], 400);
    }

    public function showResetForm(Request $request, $token = null, $email = null)
    {
        return view('auth.passwords.confirm')->with(['token' => $token, 'email' => $email]);
    }
}
