<?php

declare(strict_types=1);

namespace MarketplaceIntegrationCore\Core\Contracts;

use MarketplaceIntegrationCore\Core\Context\Context;

interface IntegrationAuditLogContract
{
    public function info(
        Context $ctx,
        int $branchId,
        string $eventType,
        array $data
    ): void;

    public function warning(
        Context $ctx,
        int $branchId,
        string $eventType,
        array $data
    ): void;

    public function error(
        Context $ctx,
        int $branchId,
        string $eventType,
        array $data
    ): void;
}
