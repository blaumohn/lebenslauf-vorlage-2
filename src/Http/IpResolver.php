<?php

namespace App\Http;

use Psr\Http\Message\ServerRequestInterface;

final class IpResolver
{
    public function resolve(ServerRequestInterface $request, bool $trustProxy): string
    {
        if ($trustProxy) {
            $forwardedIp = $this->forwardedIp($request);
            if ($forwardedIp !== null) {
                return $forwardedIp;
            }
        }

        $server = $request->getServerParams();
        return $server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private function forwardedIp(ServerRequestInterface $request): ?string
    {
        $forwarded = $request->getHeaderLine('X-Forwarded-For');
        if ($forwarded === '') {
            return null;
        }

        $parts = explode(',', $forwarded);
        $ip = trim($parts[0]);
        return $ip === '' ? null : $ip;
    }
}
