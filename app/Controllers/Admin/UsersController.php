<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;

final class UsersController extends AdminController
{
    public function index(Request $request): Response
    {
        return $this->render('admin.users.index', [
            'users' => User::all('created_at ASC'),
        ], 'users');
    }

    public function store(Request $request): Response
    {
        $email = trim((string) $request->input('email', ''));
        $firstName = trim((string) $request->input('first_name', ''));
        $lastName = trim((string) $request->input('last_name', ''));
        $password = (string) $request->input('password', '');
        $role = (string) $request->input('role', 'editor');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $firstName === '' || strlen($password) < 10 || !in_array($role, ['super_admin', 'admin', 'editor'], true)) {
            Session::flash('_errors', ['form' => 'Veuillez verifier les champs (mot de passe >= 10 caracteres, e-mail valide).']);

            return Response::redirect('/admin/users');
        }

        if (User::first(['email' => $email]) !== null) {
            Session::flash('_errors', ['form' => 'Cette adresse e-mail est deja utilisee.']);

            return Response::redirect('/admin/users');
        }

        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password_hash' => password_hash($password, config('security.password_algo')),
            'role' => $role,
            'is_active' => true,
        ]);

        $this->log('user.create', 'User', $user->id(), "Role : {$role}");
        Session::flash('success', 'Utilisateur cree.');

        return Response::redirect('/admin/users');
    }

    public function toggleActive(Request $request, array $params): Response
    {
        $user = User::find((int) $params['id']);
        if ($user !== null && $user->id() !== $this->auth->user()?->id()) {
            $user->setAttribute('is_active', !$user->getAttribute('is_active'));
            $user->save();
            $this->log('user.toggle_active', 'User', $user->id());
        }

        return Response::redirect('/admin/users');
    }
}
