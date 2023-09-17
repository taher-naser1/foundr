<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */ 
    public function store(LoginRequest $request)
    {
        

        if ($request->expectsJson()) {
            $data = [
                'email' => $request->email,
                'password' => $request->password,
            ];
            if (auth()->attempt($data)) {
                $token = auth()->user()->createToken('abdallah')->accessToken;
                return response()->json(['token' => $token], 200);
            } else {
                return response()->json(['error' => 'unathorised'], 401);
            }
        }
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(getDashboardURL());
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
