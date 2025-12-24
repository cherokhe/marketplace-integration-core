<?php

declare(strict_types=1);

namespace MarketplaceIntegrationCore\Core\Contracts;

use MarketplaceIntegrationCore\Core\Context\Context;

interface OrderIngestionContract
{
    public function hasOrder(
        Context $ctx,
        int $branchId,
        int $connectionId,
        string $deduplicationKey
    ): bool;

    public function ingestOrder(
        Context $ctx,
        int $branchId,
        int $connectionId,
        array $orderPayload,
        string $deduplicationKey
    ): int;
}
