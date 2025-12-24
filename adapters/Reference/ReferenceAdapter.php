<?php

declare(strict_types=1);

namespace MarketplaceIntegrationCore\Adapters\Reference;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

use MarketplaceIntegrationCore\Core\Context\Context;
use MarketplaceIntegrationCore\Core\Context\ContextGuard;

use MarketplaceIntegrationCore\Core\Contracts\ProductChangeSetContract;
use MarketplaceIntegrationCore\Core\Contracts\StockChangeSetContract;
use MarketplaceIntegrationCore\Core\Contracts\OrderIngestionContract;
use MarketplaceIntegrationCore\Core\Contracts\SyncOrchestratorContract;
use MarketplaceIntegrationCore\Core\Contracts\IntegrationAuditLogContract;

final class ReferenceAdapter implements
    ProductChangeSetContract,
    StockChangeSetContract,
    OrderIngestionContract,
    SyncOrchestratorContract,
    IntegrationAuditLogContract
{
    public function __construct(
        private CacheRepository $cache,
        private LoggerInterface $logger,
        private ?object $httpClient = null,
        private ?object $queue = null,
        private ?object $scheduler = null
    ) {}

    private function scope(Context $ctx): string
    {
        if (!ContextGuard::isValid($ctx)) {
            return 'invalid:0:0';
        }

        return $ctx->companyId . ':' . $ctx->branchId;
    }

    private function k(Context $ctx, string $suffix): string
    {
        return 'mic:' . $this->scope($ctx) . ':' . $suffix;
    }

    private function now(): int
    {
        return time();
    }

    private function cursorPos(array $cursor): int
    {
        $pos = $cursor['pos'] ?? 0;
        return (is_int($pos) && $pos >= 0) ? $pos : 0;
    }

    // SyncOrchestratorContract

    public function acquireLock(Context $ctx, int $branchId, string $lockKey, int $ttlSeconds): bool
    {
        $key = $this->k($ctx, "lock:$branchId:$lockKey");
        return $this->cache->add($key, ['at' => $this->now()], max(1, $ttlSeconds));
    }

    public function releaseLock(Context $ctx, int $branchId, string $lockKey): void
    {
        $key = $this->k($ctx, "lock:$branchId:$lockKey");
        $this->cache->forget($key);
    }

    // ProductChangeSetContract

    public function pullProductChanges(Context $ctx, int $branchId, int $connectionId, array $cursor): array
    {
        $pos = $this->cursorPos($cursor);

        $feedKey = $this->k($ctx, "feed:product:$connectionId");
        $feed = $this->cache->get($feedKey, []);
        $feedArr = is_array($feed) ? $feed : [];

        $items = array_slice($feedArr, $pos, 50);
        $newPos = $pos + count($items);

        return [
            'cursor' => ['pos' => $newPos],
            'items'  => $items,
        ];
    }

    public function pushProductChanges(Context $ctx, int $branchId, int $connectionId, array $changes, string $idempotencyKey): void
    {
        $idemKey = $this->k($ctx, "idem:product:$connectionId:$idempotencyKey");
        if ($this->cache->add($idemKey, true, 3600) === false) {
            return;
        }

        $appliedKey = $this->k($ctx, "applied:product:$connectionId");
        $applied = $this->cache->get($appliedKey, []);
        $appliedArr = is_array($applied) ? $applied : [];

        $appliedArr[] = [
            'ts' => $this->now(),
            'idempotencyKey' => $idempotencyKey,
            'count' => is_array($changes) ? count($changes) : 0,
        ];

        $this->cache->forever($appliedKey, $appliedArr);
    }

    // StockChangeSetContract

    public function pullStockChanges(Context $ctx, int $branchId, int $connectionId, array $cursor): array
    {
        $pos = $this->cursorPos($cursor);

        $feedKey = $this->k($ctx, "feed:stock:$connectionId");
        $feed = $this->cache->get($feedKey, []);
        $feedArr = is_array($feed) ? $feed : [];

        $items = array_slice($feedArr, $pos, 50);
        $newPos = $pos + count($items);

        return [
            'cursor' => ['pos' => $newPos],
            'items'  => $items,
        ];
    }

    public function pushStockChanges(Context $ctx, int $branchId, int $connectionId, array $changes, string $idempotencyKey): void
    {
        $idemKey = $this->k($ctx, "idem:stock:$connectionId:$idempotencyKey");
        if ($this->cache->add($idemKey, true, 3600) === false) {
            return;
        }

        $appliedKey = $this->k($ctx, "applied:stock:$connectionId");
        $applied = $this->cache->get($appliedKey, []);
        $appliedArr = is_array($applied) ? $applied : [];

        $appliedArr[] = [
            'ts' => $this->now(),
            'idempotencyKey' => $idempotencyKey,
            'count' => is_array($changes) ? count($changes) : 0,
        ];

        $this->cache->forever($appliedKey, $appliedArr);
    }

    // OrderIngestionContract

    public function hasOrder(Context $ctx, int $branchId, int $connectionId, string $deduplicationKey): bool
    {
        $key = $this->k($ctx, "dedupe:order:$connectionId:$deduplicationKey");
        return is_int($this->cache->get($key, null));
    }

    public function ingestOrder(Context $ctx, int $branchId, int $connectionId, array $orderPayload, string $deduplicationKey): int
    {
        $dedupeKey = $this->k($ctx, "dedupe:order:$connectionId:$deduplicationKey");

        $existing = $this->cache->get($dedupeKey, null);
        if (is_int($existing)) {
            return $existing;
        }

        $seqKey = $this->k($ctx, "orders:seq");
        $orderId = (int) $this->cache->increment($seqKey);

        $orderKey = $this->k($ctx, "orders:item:$orderId");
        $this->cache->forever($orderKey, [
            'id' => $orderId,
            'branchId' => $branchId,
            'connectionId' => $connectionId,
            'deduplicationKey' => $deduplicationKey,
            'payload' => $orderPayload,
            'createdAt' => $this->now(),
        ]);

        $this->cache->forever($dedupeKey, $orderId);

        return $orderId;
    }

    // IntegrationAuditLogContract

    public function info(Context $ctx, int $branchId, string $eventType, array $data): void
    {
        $this->writeLog($ctx, $branchId, 'info', $eventType, $data);
    }

    public function warning(Context $ctx, int $branchId, string $eventType, array $data): void
    {
        $this->writeLog($ctx, $branchId, 'warning', $eventType, $data);
    }

    public function error(Context $ctx, int $branchId, string $eventType, array $data): void
    {
        $this->writeLog($ctx, $branchId, 'error', $eventType, $data);
    }

    private function writeLog(Context $ctx, int $branchId, string $level, string $eventType, array $data): void
    {
        $scope = $this->scope($ctx);

        $key = $this->k($ctx, "audit:log:$branchId");
        $logs = $this->cache->get($key, []);
        $logsArr = is_array($logs) ? $logs : [];

        $logsArr[] = [
            'ts' => $this->now(),
            'scope' => $scope,
            'branchId' => $branchId,
            'level' => $level,
            'eventType' => $eventType,
            'data' => $data,
        ];

        $max = 200;
        if (count($logsArr) > $max) {
            $logsArr = array_slice($logsArr, -$max);
        }

        $this->cache->forever($key, $logsArr);

        $this->logger->log($level, $eventType, ['scope' => $scope, 'branchId' => $branchId] + $data);
    }

    // Adapter-side helpers

    public function appendProductFeed(Context $ctx, int $connectionId, array $change): void
    {
        $key = $this->k($ctx, "feed:product:$connectionId");
        $feed = $this->cache->get($key, []);
        $feedArr = is_array($feed) ? $feed : [];
        $feedArr[] = $change;
        $this->cache->forever($key, $feedArr);
    }

    public function appendStockFeed(Context $ctx, int $connectionId, array $change): void
    {
        $key = $this->k($ctx, "feed:stock:$connectionId");
        $feed = $this->cache->get($key, []);
        $feedArr = is_array($feed) ? $feed : [];
        $feedArr[] = $change;
        $this->cache->forever($key, $feedArr);
    }

    public function readAuditLogs(Context $ctx, int $branchId): array
    {
        $key = $this->k($ctx, "audit:log:$branchId");
        $logs = $this->cache->get($key, []);
        return is_array($logs) ? $logs : [];
    }
}
