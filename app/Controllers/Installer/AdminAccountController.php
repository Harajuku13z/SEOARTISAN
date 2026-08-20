<?php

declare(strict_types=1);

namespace App\Controllers\Installer;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;

final class AdminAccountController
{
    public function show(Request $request): Response
    {
        return Response::html(view_layout('installer.layout', 'installer.admin_account', [
            'stepKey' => 'admin-account',
        ]));
    }

    public function store(Request $request): Response
    {
        $firstName = trim((string) $request->input('first_name', ''));
        $lastName = trim((string) $request->input('last_name', ''));
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');
        $confirmation = (string) $request->input('password_confirmation', '');

        $errors = [];
        if ($firstName === '' || $lastName === '') {
            $errors[] = 'Le prenom et le nom sont obligatoires.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Adresse e-mail invalide.';
        }
        if (strlen($password) < 10) {
            $errors[] = 'Le mot de passe doit contenir au moins 10 caracteres.';
        }
        if ($password !== $confirmation) {
            $errors[] = 'La confirmation du mot de passe ne correspond pas.';
        }

        $existingWithEmail = User::first(['email' => $email]);
        $existingAdmin = User::first(['role' => 'super_admin']);
        if ($existingWithEmail !== null && (!$existingAdmin || $existingWithEmail->id() !== $existingAdmin->id())) {
            $errors[] = 'Cette adresse e-mail est deja utilisee.';
        }

        if ($errors !== []) {
            Session::flash('_errors', ['form' => implode(' ', $errors)]);
            Session::flash('_old_input', ['first_name' => $firstName, 'last_name' => $lastName, 'email' => $email]);

            return Response::redirect('/install/admin-account');
        }

        $admin = $existingAdmin ?? new User();
        $admin->fill([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password_hash' => password_hash($password, config('security.password_algo')),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $admin->save();

        return Response::redirect('/install/company');
    }
}
