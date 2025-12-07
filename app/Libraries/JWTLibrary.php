<?php

namespace App\Libraries;

use Config\Auth as AuthConfig;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JWTLibrary
{
    protected $config;

    public function __construct()
    {
        $this->config = new AuthConfig();
    }

    public function generateAccessToken(array $payload): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->config->accessTokenExpiration;

        $tokenPayload = [
            'iss' => $this->config->issuer,
            'iat' => $issuedAt,
            'exp' => $expire,
            'data' => $payload
        ];

        return JWT::encode($tokenPayload, $this->config->jwtSecretKey, $this->config->jwtAlgorithm);
    }

    public function generateRefreshToken(array $payload): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->config->refreshTokenExpiration;

        $tokenPayload = [
            'iss' => $this->config->issuer,
            'iat' => $issuedAt,
            'exp' => $expire,
            'type' => 'refresh',
            'data' => $payload
        ];

        return JWT::encode($tokenPayload, $this->config->jwtSecretKey, $this->config->jwtAlgorithm);
    }

    public function validateToken(string $token): ?object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->config->jwtSecretKey, $this->config->jwtAlgorithm));
            return $decoded;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getTokenExpiration(string $type = 'access'): int
    {
        return $type === 'refresh' 
            ? $this->config->refreshTokenExpiration 
            : $this->config->accessTokenExpiration;
    }
}