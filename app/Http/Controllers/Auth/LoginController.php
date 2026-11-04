<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return response()
            ->view('auth.login')
            ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sun, 02 Jan 1990 00:00:00 GMT');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();
            $membership = $user->activeMemberships()->first();

            if (! $membership) {
                Auth::logout();
                return back()->withErrors(['email' => 'Your account does not belong to an active organization.']);
            }

            $org = $membership->organization;

            if ($org->status->value === 'pending' && ! $org->isPlatform()) {
                Auth::logout();
                return back()->withErrors([
                    'email' => "The access request for '{$org->name}' is currently pending verification by NODIVA Platform Operations.",
                ])->onlyInput('email');
            }

            if (! $org->isActive() && ! $org->isPlatform()) {
                Auth::logout();
                return back()->withErrors([
                    'email' => "Institutional access for '{$org->name}' is currently {$org->status->value}.",
                ])->onlyInput('email');
            }

            session(['active_membership_id' => $membership->id]);
            $route = match ($org->type->value) {
                'hospital' => 'hospital.dashboard',
                'laboratory' => 'lab.dashboard',
                'corporate' => 'corporate.dashboard',
                'platform' => 'ops.dashboard',
                default => 'home',
            };

            return redirect()->route($route);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our institutional records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
