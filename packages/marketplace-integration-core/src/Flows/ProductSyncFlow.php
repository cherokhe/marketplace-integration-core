<?php

declare(strict_types=1);

namespace MarketplaceIntegrationCore\Core\Flows;

use MarketplaceIntegrationCore\Core\Context\Context;
use MarketplaceIntegrationCore\Core\Context\ContextGuard;
use MarketplaceIntegrationCore\Core\Contracts\ProductChangeSetContract;
use MarketplaceIntegrationCore\Core\Contracts\SyncOrchestratorContract;
use MarketplaceIntegrationCore\Core\Contracts\IntegrationAuditLogContract;

final class ProductSyncFlow
{
    public function __construct(
        private ProductChangeSetContract $productChangeSet,
        private SyncOrchestratorContract $orchestrator,
        private IntegrationAuditLogContract $auditLog
    ) {}

    public function run(
        Context $ctx,
        int $branchId,
        int $connectionId,
        array $cursor,
        string $idempotencyKey
    ): array {
        if (!ContextGuard::isValid($ctx)) {
            $this->auditLog->error($ctx, $branchId, 'product_sync.invalid_context', [
                'connectionId' => $connectionId,
            ]);
            return $cursor;
        }

        $lockKey = "product-sync:$branchId:$connectionId";

        if (!$this->orchestrator->acquireLock($ctx, $branchId, $lockKey, 30)) {
            $this->auditLog->warning($ctx, $branchId, 'product_sync.lock_denied', [
                'connectionId' => $connectionId,
            ]);
            return $cursor;
        }

        try {
            $this->auditLog->info($ctx, $branchId, 'product_sync.start', [
                'connectionId' => $connectionId,
                'cursor' => $cursor,
            ]);

            $result = $this->productChangeSet->pullProductChanges(
                $ctx,
                $branchId,
                $connectionId,
                $cursor
            );

            $items = $result['items'] ?? [];
            $newCursor = $result['cursor'] ?? $cursor;

            if (is_array($items) && count($items) > 0) {
                $this->productChangeSet->pushProductChanges(
                    $ctx,
                    $branchId,
                    $connectionId,
                    $items,
                    $idempotencyKey
                );
            }

            $this->auditLog->info($ctx, $branchId, 'product_sync.done', [
                'connectionId' => $connectionId,
                'cursorOut' => $newCursor,
            ]);

            return is_array($newCursor) ? $newCursor : $cursor;
        } catch (\Throwable $e) {
            $this->auditLog->error($ctx, $branchId, 'product_sync.error', [
                'connectionId' => $connectionId,
                'message' => $e->getMessage(),
            ]);
            return $cursor;
        } finally {
            try {
                $this->orchestrator->releaseLock($ctx, $branchId, $lockKey);
            } catch (\Throwable $e) {
                $this->auditLog->error($ctx, $branchId, 'product_sync.lock_release_error', [
                    'connectionId' => $connectionId,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }
}
