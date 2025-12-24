<?php

declare(strict_types=1);

namespace MarketplaceIntegrationCore\Core\Contracts;

use MarketplaceIntegrationCore\Core\Context\Context;

interface ProductChangeSetContract
{
    public function pullProductChanges(
        Context $ctx,
        int $branchId,
        int $connectionId,
        array $cursor
    ): array;

    public function pushProductChanges(
        Context $ctx,
        int $branchId,
        int $connectionId,
        array $changes,
        string $idempotencyKey
    ): void;
}
