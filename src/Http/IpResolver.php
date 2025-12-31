<?php

namespace App\Http;

use Psr\Http\Message\ServerRequestInterface;

final class IpResolver
{
    public function resolve(ServerRequestInterface $request, bool $trustProxy): string
    {
        if ($trustProxy) {
            $forwarded = $request->getHeaderLine('X-Forwarded-For');
            if ($forwarded !== '') {
                $parts = explode(',', $forwarded);
                $ip = trim($parts[0]);
                if ($ip !== '') {
                    return $ip;
                }
            }
        }

        $server = $request->getServerParams();
        return $server['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
