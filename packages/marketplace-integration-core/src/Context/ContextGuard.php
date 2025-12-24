<?php

declare(strict_types=1);

namespace MarketplaceIntegrationCore\Core\Context;

final class ContextGuard
{
    public static function isValid(Context $ctx): bool
    {
        if ($ctx->companyId <= 0) {
            return false;
        }

        if ($ctx->branchId <= 0) {
            return false;
        }

        return true;
    }
}
