<?php

declare(strict_types=1);

namespace MarketplaceIntegrationCore\Core\Flows;

use MarketplaceIntegrationCore\Core\Context\Context;
use MarketplaceIntegrationCore\Core\Context\ContextGuard;
use MarketplaceIntegrationCore\Core\Contracts\OrderIngestionContract;
use MarketplaceIntegrationCore\Core\Contracts\IntegrationAuditLogContract;

final class OrderIngestFlow
{
    public function __construct(
        private OrderIngestionContract $orderIngestion,
        private IntegrationAuditLogContract $auditLog
    ) {}

    public function ingest(
        Context $ctx,
        int $branchId,
        int $connectionId,
        array $orderPayload,
        string $deduplicationKey
    ): int {
        if (!ContextGuard::isValid($ctx)) {
            $this->auditLog->error($ctx, $branchId, 'order_ingest.invalid_context', [
                'connectionId' => $connectionId,
            ]);
            return 0;
        }

        try {
            if ($this->orderIngestion->hasOrder(
                $ctx,
                $branchId,
                $connectionId,
                $deduplicationKey
            )) {
                $this->auditLog->info($ctx, $branchId, 'order_ingest.deduped', [
                    'connectionId' => $connectionId,
                    'deduplicationKey' => $deduplicationKey,
                    'result' => 'deduped',
                ]);
                return 0;
            }

            $orderId = $this->orderIngestion->ingestOrder(
                $ctx,
                $branchId,
                $connectionId,
                $orderPayload,
                $deduplicationKey
            );

            $this->auditLog->info($ctx, $branchId, 'order_ingest.done', [
                'connectionId' => $connectionId,
                'orderId' => $orderId,
            ]);

            return $orderId;
        } catch (\Throwable $e) {
            $this->auditLog->error($ctx, $branchId, 'order_ingest.error', [
                'connectionId' => $connectionId,
                'message' => $e->getMessage(),
            ]);
            return 0;
        }
    }
}
