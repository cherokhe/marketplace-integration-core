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

## Installation Testing

```bash
composer install
composer dump-autoload

## Testing

vendor/bin/phpunit -c tests/phpunit.xml

## Recommended environment

PHP 8.3
Composer
PHPUnit


## Use Cases

Marketplace product synchronization
Marketplace stock synchronization
Marketplace order ingestion
ERP and POS marketplace integration
E-commerce integration middleware
XML and REST API based data integration
Reusable integration core for Laravel or PHP projects


## Tech Stack

PHP 8.1+
PHP 8.3 recommended
Composer
PSR-4 autoloading
PHPUnit
Laravel-tested examples
XML / REST API integration-ready architecture


## Project Goal

The goal of MarketplaceIntegrationCore is to provide a reusable, reliable and extensible integration core for marketplace operations.
It helps reduce repeated integration logic by centralizing product, stock, order, lock, idempotency and audit log workflows.

## License
GNU General Public License v3.0

