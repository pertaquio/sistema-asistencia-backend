<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Auth extends BaseConfig
{
    public string $jwtSecretKey = 'GIhNm7knPIQw6GL0yb51X1rjhFuqFkjr';
    
    public string $jwtAlgorithm = 'HS256';
    
    public int $accessTokenExpiration = 86400;
    
    public int $refreshTokenExpiration = 2592000;
    
    public string $issuer = 'tu-aplicacion.com';
    
    public int $maxLoginAttempts = 5;
    
    public int $loginAttemptsWindow = 900;
}
