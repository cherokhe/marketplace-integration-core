<?php

declare(strict_types=1);

namespace MarketplaceIntegrationCore\Core\Context;

final class Context
{
    public function __construct(
        public readonly int $companyId,
        public readonly int $branchId
    ) {}
}
