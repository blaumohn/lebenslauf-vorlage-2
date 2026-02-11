<?php

namespace App\Http\Security;

final class IpSaltState
{
    private ?string $salt;
    private ?string $fingerprint;

    public function __construct(?string $salt, ?string $fingerprint)
    {
        $this->salt = $salt;
        $this->fingerprint = $fingerprint;
    }

    public function salt(): ?string
    {
        return $this->salt;
    }

    public function fingerprint(): ?string
    {
        return $this->fingerprint;
    }
}
