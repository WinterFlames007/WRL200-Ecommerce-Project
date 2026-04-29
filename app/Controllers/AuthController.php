<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Mailer;

class AuthController extends Controller
{
    public function loginForm(): void
    {
        $this->view('auth/login');
    }

    public function registerForm(): void
    {
        $this->view('auth/register');
    }



    public function register(): void
    {
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        // $role = $_POST['role'] ?? 'customer';
        $role = 'customer';

        $errors = [];

        if ($name === '') {
            $errors[] = 'Full name is required.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email is required.';
        } 


        $db = Database::connect();
        $minimumPasswordLength = 8;

        $settingsStmt = mysqli_prepare(
            $db,
            "SELECT setting_value
            FROM platform_settings
            WHERE setting_key = 'minimum_password_length'
            LIMIT 1"
        );

        mysqli_stmt_execute($settingsStmt);
        $settingsResult = mysqli_stmt_get_result($settingsStmt);
        $settingsRow = mysqli_fetch_assoc($settingsResult);

        if (!empty($settingsRow['setting_value'])) {
            $minimumPasswordLength = (int) $settingsRow['setting_value'];
        }

        if (strlen($password) < $minimumPasswordLength) {
            $errors[] = 'Password must be at least ' . $minimumPasswordLength . ' characters.';
        }



        // if (!in_array($role, ['customer', 'seller'], true)) {
        //     $role = 'customer';
        // }


        $checkStmt = mysqli_prepare($db, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($checkStmt, "s", $email);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);

        if (mysqli_num_rows($checkResult) > 0) {
            $errors[] = 'Email already exists.';
        }

        if (!empty($errors)) {
            $this->view('auth/register', ['errors' => $errors]);
            return;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $status = 'active';

        $stmt = mysqli_prepare(
            $db,
            "INSERT INTO users (full_name, email, phone, password_hash, role, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ssssss",
            $name,
            $email,
            $phone,
            $passwordHash,
            $role,
            $status
        );

        if (mysqli_stmt_execute($stmt)) {
            header('Location: /login');
            exit;
        }

        $this->view('auth/register', ['errors' => ['Registration failed. Please try again.']]);
    }




    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $errors = [];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email is required.';
        }

        if ($password === '') {
            $errors[] = 'Password is required.';
        }

        if (!empty($errors)) {
            $this->view('auth/login', ['errors' => $errors]);
            return;
        }

        $db = \App\Core\Database::connect();

        $stmt = mysqli_prepare($db, "SELECT * FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $user = mysqli_fetch_assoc($result);

        if (!$user) {
            $this->view('auth/login', ['errors' => ['Invalid email or password.']]);
            return;
        }

        if ($user['status'] !== 'active') {
            $this->view('auth/login', ['errors' => ['This account is inactive.']]);
            return;
        }

        if (!password_verify($password, $user['password_hash'])) {
            $this->view('auth/login', ['errors' => ['Invalid email or password.']]);
            return;
        }

        $updateLoginStmt = mysqli_prepare($db, "UPDATE users SET last_login_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($updateLoginStmt, "i", $user['id']);
        mysqli_stmt_execute($updateLoginStmt);

        $_SESSION['user'] = [
            'id' => $user['id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        if ($user['role'] === 'customer') {
            require_once __DIR__ . '/CartController.php';
            $cartController = new \App\Controllers\CartController();
            $cartController->mergeSessionCartToDatabase((int)$user['id']);
        }

        if ($user['role'] === 'admin') {
            header('Location: /admin/dashboard');
            exit;
        }

        if ($user['role'] === 'seller') {
            header('Location: /seller/dashboard');
            exit;
        }

        header('Location: /');
        exit;
    }


    public function logout(): void
    {
        session_unset();
        session_destroy();
        header('Location: /login');
        exit;
    }


    public function forgotPasswordForm(): void
    {
        $this->view('auth/forgot-password');
    }

    public function sendResetLink(): void
    {
        $email = trim($_POST['email'] ?? '');
        $errors = [];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email required.';
        }

        $db = Database::connect();

        $stmt = mysqli_prepare($db, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if (!$user) {
            $errors[] = 'No account found with that email.';
        }

        if (!empty($errors)) {
            $this->view('auth/forgot-password', ['errors' => $errors]);
            return;
        }

        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $updateStmt = mysqli_prepare(
            $db,
            "UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?"
        );

        mysqli_stmt_bind_param($updateStmt, "ssi", $token, $expires, $user['id']);
        mysqli_stmt_execute($updateStmt);

        // Reset link
        $resetLink = "https://yourdomain.com/reset-password?token=$token";

        // TODO: SEND EMAIL (next step)
        

        $resetLink = "https://s2209682.ncgrp.xyz/reset-password?token=$token";

        $emailBody = "
            <h2>Password Reset Request</h2>
            <p>Hello,</p>
            <p>You requested to reset your password.</p>
            <p>Click the link below to create a new password:</p>
            <p><a href='{$resetLink}'>Reset Password</a></p>
            <p>This link will expire in 1 hour.</p>
            <p>If you did not request this, you can ignore this email.</p>
        ";

        Mailer::send($email, 'Reset Your Password', $emailBody);


        $this->view('auth/forgot-password', [
            'success' => 'Reset link sent to your email.'
        ]);
    }


    public function resetPasswordForm(): void
    {
        $token = $_GET['token'] ?? '';

        if ($token === '') {
            echo 'Invalid or missing token.';
            return;
        }

        $db = Database::connect();

        $stmt = mysqli_prepare(
            $db,
            "SELECT id, reset_token_expires_at FROM users WHERE reset_token = ? LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if (!$user) {
            echo 'Invalid token.';
            return;
        }

        if (strtotime($user['reset_token_expires_at']) < time()) {
            echo 'Token has expired.';
            return;
        }

        $this->view('auth/reset-password', ['token' => $token]);
    }




    public function resetPassword(): void
    {
        $token = $_POST['token'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $errors = [];

        if ($token === '') {
            $errors[] = 'Invalid request.';
        }

        if (strlen($newPassword) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        $db = Database::connect();

        $stmt = mysqli_prepare(
            $db,
            "SELECT id, reset_token_expires_at FROM users WHERE reset_token = ? LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if (!$user) {
            $errors[] = 'Invalid token.';
        } elseif (strtotime($user['reset_token_expires_at']) < time()) {
            $errors[] = 'Token expired.';
        }

        if (!empty($errors)) {
            $this->view('auth/reset-password', [
                'errors' => $errors,
                'token' => $token
            ]);
            return;
        }

        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $updateStmt = mysqli_prepare(
            $db,
            "UPDATE users 
            SET password_hash = ?, reset_token = NULL, reset_token_expires_at = NULL 
            WHERE id = ?"
        );

        mysqli_stmt_bind_param($updateStmt, "si", $newPasswordHash, $user['id']);
        mysqli_stmt_execute($updateStmt);

        header('Location: /login');
        exit;
    }


}