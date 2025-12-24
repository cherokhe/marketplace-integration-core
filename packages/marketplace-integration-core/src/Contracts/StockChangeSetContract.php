<?php

declare(strict_types=1);

namespace MarketplaceIntegrationCore\Core\Contracts;

use MarketplaceIntegrationCore\Core\Context\Context;

interface StockChangeSetContract
{
    public function pullStockChanges(
        Context $ctx,
        int $branchId,
        int $connectionId,
        array $cursor
    ): array;

    public function pushStockChanges(
        Context $ctx,
        int $branchId,
        int $connectionId,
        array $changes,
        string $idempotencyKey
    ): void;
}
