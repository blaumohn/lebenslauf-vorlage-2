<?php

namespace App\Http\Security;

use App\Http\Storage\FileStorage;

final class IpSaltRuntime
{
    private const LOCK_KEY = 'ip_salt_runtime';

    private RuntimeLockRunner $lockRunner;
    private IpSaltStateReader $stateReader;
    private IpSaltDecisionPolicy $decisionPolicy;
    private IpSaltActionPlan $actionPlan;

    public function __construct(
        FileStorage $storage,
        RuntimeLockRunner $lockRunner,
        RuntimeAtomicWriter $writer,
        string $stateDir,
        string $captchaDir,
        string $rateLimitDir
    ) {
        $this->lockRunner = $lockRunner;
        $validator = new IpSaltStateValidator();
        $this->stateReader = new IpSaltStateReader($storage, $stateDir);
        $this->decisionPolicy = new IpSaltDecisionPolicy($validator);
        $resetExecutor = new IpSaltResetExecutor(
            $writer,
            $validator,
            $stateDir,
            $captchaDir,
            $rateLimitDir
        );
        $this->actionPlan = new IpSaltActionPlan($resetExecutor);
    }

    public function resolveSalt(): string
    {
        $result = $this->lockRunner->runWithLock(self::LOCK_KEY, [$this, 'resolveSaltLocked']);
        return $this->requireString($result, 'resolveSalt');
    }

    public function resetSalt(): string
    {
        $result = $this->lockRunner->runWithLock(self::LOCK_KEY, [$this, 'resetSaltLocked']);
        return $this->requireString($result, 'resetSalt');
    }

    private function requireString(mixed $value, string $method): string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }
        throw new \RuntimeException("Ungueltiges Ergebnis fuer {$method}.");
    }

    public function resolveSaltLocked(): string
    {
        $state = $this->stateReader->readState();
        $reason = $this->decisionPolicy->decideForResolve($state);
        return $this->actionPlan->execute($reason, $state->salt());
    }

    public function resetSaltLocked(): string
    {
        $reason = $this->decisionPolicy->decideForReset();
        return $this->actionPlan->execute($reason, null);
    }
}
