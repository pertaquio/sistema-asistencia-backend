<?php namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class ForceHttps implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $isSecure = $request->isSecure();

        if (! $isSecure) {
            $proto = $request->getHeaderLine('X-Forwarded-Proto');
            if (strtolower($proto) === 'https') {
                $isSecure = true;
            }
        }

        if ($isSecure) {
            return; 
        }

        $uri = current_url(true)->setScheme('https');

        return redirect()->to((string) $uri);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
