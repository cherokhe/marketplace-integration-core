<?php

declare(strict_types=1);

namespace MarketplaceIntegrationCore\Tests;

use PHPUnit\Framework\TestCase;

use MarketplaceIntegrationCore\Core\Context\Context;
use MarketplaceIntegrationCore\Adapters\Reference\ReferenceAdapter;
use MarketplaceIntegrationCore\Tests\Support\InMemoryCache;
use MarketplaceIntegrationCore\Tests\Support\TestLogger;

final class ReferenceAdapterTest extends TestCase
{
    private InMemoryCache $cache;
    private TestLogger $logger;
    private ReferenceAdapter $adapter;

    protected function setUp(): void
    {
        $this->cache = new InMemoryCache(true);
        $this->cache->flush();

        $this->logger = new TestLogger();
        $this->logger->records = [];

        $this->adapter = new ReferenceAdapter($this->cache, $this->logger);
    }

    public function test_product_changes_pull_push_cursor_and_idempotency(): void
    {
        $ctx = new Context(1, 10);
        $branchId = 10;
        $connectionId = 7;

        $this->adapter->appendProductFeed($ctx, $connectionId, ['id' => 101]);
        $this->adapter->appendProductFeed($ctx, $connectionId, ['id' => 102]);
        $this->adapter->appendProductFeed($ctx, $connectionId, ['id' => 103]);

        $res1 = $this->adapter->pullProductChanges($ctx, $branchId, $connectionId, ['pos' => 0]);

        $this->assertSame(3, count($res1['items'] ?? []));
        $this->assertSame(['pos' => 3], $res1['cursor'] ?? []);

        $this->adapter->pushProductChanges($ctx, $branchId, $connectionId, $res1['items'], 'idem-prod-1');
        $this->adapter->pushProductChanges($ctx, $branchId, $connectionId, $res1['items'], 'idem-prod-1');

        $scope = "mic:{$ctx->companyId}:{$ctx->branchId}";
        $appliedKey = $scope . ":applied:product:$connectionId";
        $applied = $this->cache->get($appliedKey, []);
        $this->assertSame(1, count($applied));
    }

    public function test_stock_changes_pull_push_cursor_and_idempotency(): void
    {
        $ctx = new Context(1, 10);
        $branchId = 10;
        $connectionId = 9;

        $this->adapter->appendStockFeed($ctx, $connectionId, ['sku' => 'A', 'qty' => 1]);
        $this->adapter->appendStockFeed($ctx, $connectionId, ['sku' => 'B', 'qty' => 0]);

        $res1 = $this->adapter->pullStockChanges($ctx, $branchId, $connectionId, ['pos' => 0]);

        $this->assertSame(2, count($res1['items'] ?? []));
        $this->assertSame(['pos' => 2], $res1['cursor'] ?? []);

        $this->adapter->pushStockChanges($ctx, $branchId, $connectionId, $res1['items'], 'idem-stock-1');
        $this->adapter->pushStockChanges($ctx, $branchId, $connectionId, $res1['items'], 'idem-stock-1');

        $scope = "mic:{$ctx->companyId}:{$ctx->branchId}";
        $appliedKey = $scope . ":applied:stock:$connectionId";
        $applied = $this->cache->get($appliedKey, []);
        $this->assertSame(1, count($applied));
    }

    public function test_order_ingest_respects_deduplication_key(): void
    {
        $ctx = new Context(1, 10);
        $branchId = 10;
        $connectionId = 3;

        $payload = ['orderRef' => 'X-1'];
        $dedupeKey = 'dedupe:X-1';

        $this->assertFalse($this->adapter->hasOrder($ctx, $branchId, $connectionId, $dedupeKey));

        $id1 = $this->adapter->ingestOrder($ctx, $branchId, $connectionId, $payload, $dedupeKey);
        $this->assertGreaterThan(0, $id1);

        $id2 = $this->adapter->ingestOrder($ctx, $branchId, $connectionId, $payload, $dedupeKey);
        $this->assertSame($id1, $id2);
    }

    public function test_lock_acquire_release_works(): void
    {
        $ctx = new Context(1, 10);
        $branchId = 10;

        $lockKey = 'sync:product:branch=10:connection=7';

        $this->assertTrue($this->adapter->acquireLock($ctx, $branchId, $lockKey, 30));
        $this->assertFalse($this->adapter->acquireLock($ctx, $branchId, $lockKey, 30));

        $this->adapter->releaseLock($ctx, $branchId, $lockKey);
        $this->assertTrue($this->adapter->acquireLock($ctx, $branchId, $lockKey, 30));
    }

    public function test_audit_log_info_warning_error_and_logger_records(): void
    {
        $ctx = new Context(1, 10);
        $branchId = 10;

        $this->adapter->info($ctx, $branchId, 'evt.info', ['a' => 1]);
        $this->adapter->warning($ctx, $branchId, 'evt.warn', ['b' => 2]);
        $this->adapter->error($ctx, $branchId, 'evt.err', ['c' => 3]);

        $logs = $this->adapter->readAuditLogs($ctx, $branchId);
        $this->assertSame(3, count($logs));

        $this->assertSame(3, count($this->logger->records));
    }
}
