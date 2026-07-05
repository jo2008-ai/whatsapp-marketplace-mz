<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SuspiciousActivity
{
    private const SUSPICIOUS_PATTERNS = [
        'sql_injection' => '/(\bunion\b.*\bselect\b|\bselect\b.*\bfrom\b|\binsert\b.*\binto\b|\bdelete\b.*\bfrom\b|\bdrop\b.*\btable\b|\bor\b.*\b1\s*=\s*1\b|\band\b.*\b1\s*=\s*1\b)/i',
        'xss' => '/<script[\s>]|javascript\s*:|on\w+\s*=|<iframe|<object|<embed|<applet|<form.*action\s*=/i',
        'path_traversal' => '/(\.\.\/|\.\.\\|%2e%2e%2f|%2e%2e\/|\.\.%2f|%2e%2e%5c)/i',
        'command_injection' => '/(;|\||`|\$\(|%0a|%0d)/i',
    ];

    private const SUSPICIOUS_UA_PATTERNS = [
        'sqlmap', 'nikto', 'nmap', 'masscan', 'zmap', 'dirbuster',
        'gobuster', 'wfuzz', 'ffuf', 'burpsuite', 'owasp',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $this->detectSuspiciousInput($request);
        $this->detectSuspiciousUserAgent($request);

        $response = $next($request);

        $this->detectSuspiciousResponse($request, $response);

        return $response;
    }

    private function detectSuspiciousInput(Request $request): void
    {
        $allInput = array_merge(
            $request->query(),
            $request->post(),
            $request->route() ? $request->route()->parameters() : [],
        );

        $inputString = json_encode($allInput);

        foreach (self::SUSPICIOUS_PATTERNS as $type => $pattern) {
            if (preg_match($pattern, $inputString)) {
                try {
                    Log::channel('security')->warning('Atividade suspeita bloqueada', [
                        'tipo' => $type,
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'metodo' => $request->method(),
                        'path' => $request->path(),
                        'input' => substr($inputString, 0, 500),
                        'timestamp' => now()->toIso8601String(),
                    ]);
                } catch (\Exception $e) {
                    // Silently fail if security log channel is unavailable
                }
                abort(403, 'Atividade suspeita detectada.');
            }
        }
    }

    private function detectSuspiciousUserAgent(Request $request): void
    {
        $userAgent = strtolower($request->userAgent() ?? '');

        if (empty($userAgent)) {
            return;
        }

        foreach (self::SUSPICIOUS_UA_PATTERNS as $pattern) {
            if (str_contains($userAgent, $pattern)) {
                try {
                    Log::channel('security')->warning('User-Agent suspeito bloqueado', [
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'pattern' => $pattern,
                        'metodo' => $request->method(),
                        'path' => $request->path(),
                        'timestamp' => now()->toIso8601String(),
                    ]);
                } catch (\Exception $e) {
                    // Silently fail if security log channel is unavailable
                }
                abort(403, 'Atividade suspeita detectada.');
            }
        }
    }

    private function detectSuspiciousResponse(Request $request, Response $response): void
    {
        if ($response->getStatusCode() >= 400) {
            try {
                $logLevel = $response->getStatusCode() >= 500 ? 'error' : 'info';
                Log::channel('security')->$logLevel('Resposta de erro', [
                    'status' => $response->getStatusCode(),
                    'ip' => $request->ip(),
                    'metodo' => $request->method(),
                    'path' => $request->path(),
                    'timestamp' => now()->toIso8601String(),
                ]);
            } catch (\Exception $e) {
                // Silently fail if security log channel is unavailable
            }
        }
    }
}
