<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Repository\UserRepository;
use App\Service\JwtService;

final class AuthController
{
    private UserRepository $userRepo;
    private JwtService $jwt;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
        $this->jwt = new JwtService();
    }

    public function login(Request $request): Response
    {
        $data = $request->input('email');
        $email = $request->input('email');
        $password = $request->input('password');

        if (!$email || !$password) {
            return Response::validation(['message' => 'Email and password required']);
        }

        $user = $this->userRepo->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            return Response::unauthorized('Invalid credentials');
        }

        $token = $this->jwt->generate((int)$user['id'], $user['role']);

        return Response::success([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
    }

    public function register(Request $request): Response
    {
        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');

        if (!$name || !$email || !$password) {
            return Response::validation(['message' => 'Name, email, and password required']);
        }

        if (strlen($password) < 8) {
            return Response::validation(['password' => 'Min 8 characters']);
        }

        if ($this->userRepo->emailExists($email)) {
            return Response::error('EMAIL_EXISTS', 'Email already registered', 409);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $id = $this->userRepo->create($name, $email, $hash);

        $token = $this->jwt->generate($id, 'user');

        return Response::success([
            'message' => 'User registered successfully',
            'token' => $token,
            'user' => [
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'role' => 'user'
            ]
        ], 201);
    }
    
    public function me(Request $request): Response
    {
        // User ID is set by AuthMiddleware
        $userId = $request->getAttribute('user_id');
        $user = $this->userRepo->findById((int)$userId);
        
        if (!$user) {
            return Response::notFound('User not found');
        }
        
        unset($user['password']); // Don't send password hash
        
        return Response::success(['user' => $user]);
    }
}
