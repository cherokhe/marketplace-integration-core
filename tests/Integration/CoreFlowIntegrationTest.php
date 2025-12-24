<?php

declare(strict_types=1);

namespace MarketplaceIntegrationCore\Tests\Integration;

use PHPUnit\Framework\TestCase;

use MarketplaceIntegrationCore\Core\Context\Context;
use MarketplaceIntegrationCore\Core\Flows\ProductSyncFlow;
use MarketplaceIntegrationCore\Core\Flows\StockSyncFlow;
use MarketplaceIntegrationCore\Core\Flows\OrderIngestFlow;

use MarketplaceIntegrationCore\Adapters\Reference\ReferenceAdapter;
use MarketplaceIntegrationCore\Tests\Support\InMemoryCache;
use MarketplaceIntegrationCore\Tests\Support\TestLogger;

final class CoreFlowIntegrationTest extends TestCase
{
    private InMemoryCache $cache;
    private TestLogger $logger;
    private ReferenceAdapter $adapter;

    private ProductSyncFlow $productFlow;
    private StockSyncFlow $stockFlow;
    private OrderIngestFlow $orderFlow;

    protected function setUp(): void
    {
        $this->cache = new InMemoryCache(true);
        $this->cache->flush();

        $this->logger = new TestLogger();
        $this->logger->records = [];

        $this->adapter = new ReferenceAdapter($this->cache, $this->logger);

        $this->productFlow = new ProductSyncFlow($this->adapter, $this->adapter, $this->adapter);
        $this->stockFlow   = new StockSyncFlow($this->adapter, $this->adapter, $this->adapter);
        $this->orderFlow   = new OrderIngestFlow($this->adapter, $this->adapter);
    }

    private function scope(Context $ctx): string
    {
        return "mic:{$ctx->companyId}:{$ctx->branchId}";
    }

    public function test_product_sync_flow_pull_push_cursor_and_idempotency(): void
    {
        $ctx = new Context(1, 10);
        $branchId = 10;
        $connectionId = 101;

        $this->adapter->appendProductFeed($ctx, $connectionId, ['id' => 1]);
        $this->adapter->appendProductFeed($ctx, $connectionId, ['id' => 2]);
        $this->adapter->appendProductFeed($ctx, $connectionId, ['id' => 3]);

        $cursor0 = ['pos' => 0];

        $out1 = $this->productFlow->run($ctx, $branchId, $connectionId, $cursor0, 'idem-prod-1');
        $this->assertSame(['pos' => 3], $out1);

        $out2 = $this->productFlow->run($ctx, $branchId, $connectionId, $cursor0, 'idem-prod-1');
        $this->assertSame(['pos' => 3], $out2);

        $appliedKey = $this->scope($ctx) . ":applied:product:$connectionId";
        $applied = $this->cache->get($appliedKey, []);
        $this->assertIsArray($applied);
        $this->assertSame(1, count($applied));
        $this->assertSame('idem-prod-1', $applied[0]['idempotencyKey'] ?? null);

        $audit = $this->adapter->readAuditLogs($ctx, $branchId);
        $eventTypes = array_map(static fn ($x) => (string)($x['eventType'] ?? ''), $audit);

        $this->assertContains('product_sync.start', $eventTypes);
        $this->assertContains('product_sync.done', $eventTypes);

        $this->assertNotEmpty($this->logger->records);
    }

    public function test_stock_sync_flow_pull_push_cursor_and_idempotency(): void
    {
        $ctx = new Context(1, 10);
        $branchId = 10;
        $connectionId = 202;

        $this->adapter->appendStockFeed($ctx, $connectionId, ['sku' => 'A', 'qty' => 5]);
        $this->adapter->appendStockFeed($ctx, $connectionId, ['sku' => 'B', 'qty' => 0]);

        $cursor0 = ['pos' => 0];

        $out1 = $this->stockFlow->run($ctx, $branchId, $connectionId, $cursor0, 'idem-stock-1');
        $this->assertSame(['pos' => 2], $out1);

        $out2 = $this->stockFlow->run($ctx, $branchId, $connectionId, $cursor0, 'idem-stock-1');
        $this->assertSame(['pos' => 2], $out2);

        $appliedKey = $this->scope($ctx) . ":applied:stock:$connectionId";
        $applied = $this->cache->get($appliedKey, []);
        $this->assertIsArray($applied);
        $this->assertSame(1, count($applied));
        $this->assertSame('idem-stock-1', $applied[0]['idempotencyKey'] ?? null);

        $audit = $this->adapter->readAuditLogs($ctx, $branchId);
        $eventTypes = array_map(static fn ($x) => (string)($x['eventType'] ?? ''), $audit);

        $this->assertContains('stock_sync.start', $eventTypes);
        $this->assertContains('stock_sync.done', $eventTypes);

        $this->assertNotEmpty($this->logger->records);
    }

    public function test_order_ingest_flow_deduplication_and_ingestion(): void
    {
        $ctx = new Context(1, 10);
        $branchId = 10;
        $connectionId = 303;

        $payload = ['orderRef' => 'ORD-1'];
        $dedupeKey = 'dedupe:ORD-1';

        $id1 = $this->orderFlow->ingest($ctx, $branchId, $connectionId, $payload, $dedupeKey);
        $this->assertGreaterThan(0, $id1);

        $id2 = $this->orderFlow->ingest($ctx, $branchId, $connectionId, $payload, $dedupeKey);
        $this->assertSame(0, $id2);

        $this->assertTrue($this->adapter->hasOrder($ctx, $branchId, $connectionId, $dedupeKey));

        $audit = $this->adapter->readAuditLogs($ctx, $branchId);
        $eventTypes = array_map(static fn ($x) => (string)($x['eventType'] ?? ''), $audit);

        $this->assertContains('order_ingest.done', $eventTypes);
        $this->assertContains('order_ingest.deduped', $eventTypes);

        $this->assertNotEmpty($this->logger->records);
    }

    public function test_lock_acquire_release_via_flow_and_denied_path(): void
    {
        $ctx = new Context(1, 10);
        $branchId = 10;
        $connectionId = 404;

        $this->adapter->appendProductFeed($ctx, $connectionId, ['id' => 9]);

        $lockKey = "product-sync:$branchId:$connectionId";

        $this->assertTrue($this->adapter->acquireLock($ctx, $branchId, $lockKey, 30));

        $cursorDenied = $this->productFlow->run($ctx, $branchId, $connectionId, ['pos' => 0], 'idem-denied');
        $this->assertSame(['pos' => 0], $cursorDenied);

        $appliedKey = $this->scope($ctx) . ":applied:product:$connectionId";
        $this->assertSame([], $this->cache->get($appliedKey, []));

        $this->adapter->releaseLock($ctx, $branchId, $lockKey);

        $cursorOk = $this->productFlow->run($ctx, $branchId, $connectionId, ['pos' => 0], 'idem-ok');
        $this->assertSame(['pos' => 1], $cursorOk);

        $applied = $this->cache->get($appliedKey, []);
        $this->assertIsArray($applied);
        $this->assertSame(1, count($applied));

        $audit = $this->adapter->readAuditLogs($ctx, $branchId);
        $eventTypes = array_map(static fn ($x) => (string)($x['eventType'] ?? ''), $audit);
        $this->assertContains('product_sync.lock_denied', $eventTypes);
    }

    public function test_audit_log_events_and_test_logger_records(): void
    {
        $ctx = new Context(1, 10);
        $branchId = 10;
        $connectionId = 505;

        $this->adapter->appendStockFeed($ctx, $connectionId, ['sku' => 'X', 'qty' => 1]);

        $this->stockFlow->run($ctx, $branchId, $connectionId, ['pos' => 0], 'idem-audit');

        $audit = $this->adapter->readAuditLogs($ctx, $branchId);
        $this->assertIsArray($audit);
        $this->assertGreaterThanOrEqual(2, count($audit));

        $levels = array_map(static fn ($x) => (string)($x['level'] ?? ''), $audit);
        $this->assertContains('info', $levels);

        $this->assertNotEmpty($this->logger->records);

        $messages = array_map(static fn ($r) => (string)($r['message'] ?? ''), $this->logger->records);
        $this->assertContains('stock_sync.start', $messages);
        $this->assertContains('stock_sync.done', $messages);
    }

    public function test_invalid_context_no_cache_and_no_audit_logs_and_no_logger_records(): void
    {
        $ctxInvalid = new Context(0, 0);
        $branchId = 0;
        $connectionId = 606;

        $this->adapter->appendProductFeed($ctxInvalid, $connectionId, ['id' => 1]);
        $this->adapter->appendStockFeed($ctxInvalid, $connectionId, ['sku' => 'A', 'qty' => 1]);

        $p = $this->productFlow->run($ctxInvalid, $branchId, $connectionId, ['pos' => 0], 'idem-x');
        $s = $this->stockFlow->run($ctxInvalid, $branchId, $connectionId, ['pos' => 0], 'idem-y');
        $o = $this->orderFlow->ingest($ctxInvalid, $branchId, $connectionId, ['orderRef' => 'Z'], 'dedupe-Z');

        $this->assertSame(['pos' => 0], $p);
        $this->assertSame(['pos' => 0], $s);
        $this->assertSame(0, $o);

        $invalidScope = "mic:invalid:0:0";

        $this->assertSame([], $this->cache->get($invalidScope . ":feed:product:$connectionId", []));
        $this->assertSame([], $this->cache->get($invalidScope . ":feed:stock:$connectionId", []));
        $this->assertSame([], $this->cache->get($invalidScope . ":applied:product:$connectionId", []));
        $this->assertSame([], $this->cache->get($invalidScope . ":applied:stock:$connectionId", []));
        $this->assertSame([], $this->cache->get($invalidScope . ":audit:log:$branchId", []));

        $this->assertSame([], $this->logger->records);
    }
}
