<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectTo(
            guests: '/login',
            users: fn () => auth()->user()?->dashboardUrl() ?? '/'
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() === 419) {
                if ($request->is('login')) {
                    return redirect()->route('login')
                        ->withInput($request->only('email'))
                        ->with('warning', 'Your session expired due to inactivity. A fresh security token has been generated — please sign in.');
                }

                if ($request->is('logout')) {
                    return redirect()->route('login')
                        ->with('status', 'You have been signed out.');
                }

                return redirect()->back()
                    ->withInput($request->except('_token', 'password', 'password_confirmation'))
                    ->with('warning', 'Your session expired due to inactivity. Please submit your request again.');
            }
        });

        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            if ($request->is('login')) {
                return redirect()->route('login')
                    ->withInput($request->only('email'))
                    ->with('warning', 'Your session expired due to inactivity. A fresh security token has been generated — please sign in.');
            }

            if ($request->is('logout')) {
                return redirect()->route('login')
                    ->with('status', 'You have been signed out.');
            }

            return redirect()->back()
                ->withInput($request->except('_token', 'password', 'password_confirmation'))
                ->with('warning', 'Your session expired due to inactivity. Please submit your request again.');
        });
    })->create();
