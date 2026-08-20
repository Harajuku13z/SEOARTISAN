<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\Auth\AuthService;

final class AuthController
{
    public function __construct(private AuthService $auth)
    {
    }

    public function showLogin(Request $request): Response
    {
        if ($this->auth->check()) {
            return Response::redirect('/admin');
        }

        return Response::html(view('admin.auth.login'));
    }

    public function login(Request $request): Response
    {
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');

        if ($email === '' || $password === '') {
            Session::flash('_errors', ['form' => 'Veuillez renseigner votre e-mail et votre mot de passe.']);
            Session::flash('_old_input', ['email' => $email]);

            return Response::redirect('/admin/login');
        }

        if (!$this->auth->attempt($email, $password, $request->ip())) {
            Session::flash('_errors', ['form' => 'Identifiants incorrects ou compte inactif.']);
            Session::flash('_old_input', ['email' => $email]);

            return Response::redirect('/admin/login');
        }

        return Response::redirect('/admin');
    }

    public function logout(Request $request): Response
    {
        $this->auth->logout();

        return Response::redirect('/admin/login');
    }
}
