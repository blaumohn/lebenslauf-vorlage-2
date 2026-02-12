<?php

namespace App\Http\Security;

final class IpSaltActionPlan
{
    private IpSaltResetExecutor $resetExecutor;

    public function __construct(IpSaltResetExecutor $resetExecutor)
    {
        $this->resetExecutor = $resetExecutor;
    }

    public function execute(TriggerReason $reason, IpSaltState $state): IpSaltState
    {
        if ($reason === TriggerReason::CLEAN) {
            return $state;
        }
        $inProgress = $this->resetExecutor->markInProgress($state);
        $ready = $this->resetExecutor->rotateAndClear($inProgress);
        return $ready;
    }
}
