# MarketplaceIntegrationCore

**Version:** 1.0.0  
**PHP Requirement:** >= 8.1  
**Recommended PHP Version:** 8.3  
**Framework:** Framework-agnostic, tested with Laravel examples

## Overview

MarketplaceIntegrationCore is a framework-agnostic PHP core package built to manage marketplace integrations from a centralized and reusable structure.

The package is designed for product synchronization, stock synchronization, order ingestion, idempotent adapter flows, locking, audit logging and event tracking.

## Türkçe Açıklama

MarketplaceIntegrationCore, pazaryeri entegrasyonlarını merkezi şekilde yönetmek için geliştirilmiş framework bağımsız bir PHP çekirdek paketidir.

Laravel projeleriyle test edilmiş olsa da, yapı olarak framework bağımsız tasarlanmıştır. Amaç; ürün, stok, sipariş, loglama, idempotency ve lock süreçlerini tekrar kullanılabilir bir çekirdek üzerinden yönetmektir.

## Features

- Product synchronization
- Stock synchronization
- Order ingestion and processing
- Adapter-based architecture
- Idempotent operation support
- Lock mechanism support
- Audit log and event tracking
- Cursor-based feed handling
- Cache and logger support
- Framework-agnostic package structure
- Laravel-compatible usage examples

## Current Status

- Core package structure created under `packages/marketplace-integration-core/`
- `Context`, `Contracts` and `Flows` directories prepared
- PSR-4 autoload structure configured
- `ReferenceAdapter` implemented
- Product, stock, order, lock and audit log contracts implemented
- Cache, logger, idempotency and cursor mechanisms prepared
- Unit tests completed for adapter-level scenarios
- Integration tests prepared for core flow scenarios
- Tested with Laravel examples

## Tested Flows

- `ProductSyncFlow`
- `StockSyncFlow`
- `OrderIngestFlow`
- Adapter lock scenarios
- Cursor handling
- Idempotency checks
- Audit log records
- Feed append scenarios

## Installation

```bash
composer install
composer dump-autoload
