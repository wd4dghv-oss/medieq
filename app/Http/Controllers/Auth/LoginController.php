<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function authenticated(Request $request, $user)
{
    if ($user->role === 'admin') {
        return redirect('/admin/dashboard');
    } else {
        return redirect('/user/dashboard');
    }
}
public function showLoginForm()
    {
        return view('auth.login'); // Ensure you have this Blade file
    }

    // Handle the login process
    public function login(Request $request)
    {
        // Validate the request
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        // Attempt to log the user in
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password], $request->remember)) {
            return redirect()->intended('/usee/dashboard')->with('success', 'You are logged in!');
        }

        return back()->withErrors(['email' => 'Invalid email or password'])->withInput();
    }

    // Handle logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        return redirect('/login')->with('success', 'You have been logged out.');
    }

}
