<?php

namespace App\Http\Security;

final class IpSaltActionPlan
{
    private IpSaltResetExecutor $resetExecutor;

    public function __construct(IpSaltResetExecutor $resetExecutor)
    {
        $this->resetExecutor = $resetExecutor;
    }

    public function execute(TriggerReason $reason, ?string $existingSalt): string
    {
        if ($reason === TriggerReason::CLEAN && is_string($existingSalt) && $existingSalt !== '') {
            return $existingSalt;
        }
        return $this->resetExecutor->rotateAndClear();
    }
}
