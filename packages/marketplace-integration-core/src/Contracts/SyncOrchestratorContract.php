<?php

declare(strict_types=1);

namespace MarketplaceIntegrationCore\Core\Contracts;

use MarketplaceIntegrationCore\Core\Context\Context;

interface SyncOrchestratorContract
{
    public function acquireLock(
        Context $ctx,
        int $branchId,
        string $lockKey,
        int $ttlSeconds
    ): bool;

    public function releaseLock(
        Context $ctx,
        int $branchId,
        string $lockKey
    ): void;
}
