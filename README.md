# MarketplaceIntegrationCore

**MarketplaceIntegrationCore** is a framework-agnostic PHP core package for marketplace integration workflows.

**MarketplaceIntegrationCore**, pazaryeri entegrasyon süreçlerini merkezi, tekrar kullanılabilir ve adapter tabanlı bir yapı üzerinden yönetmek için geliştirilmiş framework bağımsız bir PHP çekirdek paketidir.

---

## Overview

This package provides a reusable core layer for product synchronization, stock synchronization, price updates, order ingestion, idempotent operations, lock handling and audit logging.

It is designed to be integrated with Laravel or any PHP-based business application through adapter contracts.

## Türkçe Açıklama

Bu paket; ürün senkronizasyonu, stok senkronizasyonu, fiyat güncelleme, sipariş alma, idempotent işlem kontrolü, lock mekanizması ve audit log süreçleri için tekrar kullanılabilir bir core katman sunar.

Laravel veya farklı PHP tabanlı iş uygulamalarına adapter contract yapısı üzerinden entegre edilecek şekilde tasarlanmıştır.

---

## Core Features / Temel Özellikler

- Product synchronization / Ürün senkronizasyonu
- Stock synchronization / Stok senkronizasyonu
- Price update flow / Fiyat güncelleme akışı
- Order ingestion / Sipariş alma ve işleme
- Adapter-based architecture / Adapter tabanlı mimari
- Idempotency support / Tekrarlı işlem kontrolü
- Lock mechanism / Kilit mekanizması
- Audit logging / Audit log ve olay kaydı
- Cursor-based incremental sync / Cursor tabanlı artımlı senkronizasyon
- Batch operation support / Toplu işlem desteği
- Framework-agnostic structure / Framework bağımsız yapı
- Laravel-compatible implementation pattern / Laravel uyumlu kullanım deseni

---

## Architecture / Mimari

MarketplaceIntegrationCore works through contracts and flow classes.

The host application implements the required contracts using its own adapter. The core flow layer then calls this adapter without depending directly on the host application's database, framework or business logic.

MarketplaceIntegrationCore, contract ve flow sınıfları üzerinden çalışır.

Ana uygulama, gerekli contract yapısını kendi adapter sınıfı ile implemente eder. Core flow katmanı, uygulamanın veritabanına, framework detaylarına veya iş mantığına doğrudan bağımlı olmadan bu adapter üzerinden çalışır.

### Main Layers / Ana Katmanlar

- Core contracts / Core contract yapısı
- Flow classes / Akış sınıfları
- Host application adapter / Ana uygulama adapter katmanı
- Service bindings / Servis binding yapısı
- Marketplace drivers / Pazaryeri driver katmanı
- Queue jobs / Kuyruk işleri
- Webhook handlers / Webhook işleyicileri
- Audit logging / Audit log katmanı

---

## Integration Flows / Entegrasyon Akışları

### 1. Order Ingestion / Sipariş Alma

Order payloads from polling jobs or webhooks are passed into the core order ingestion flow.

The host adapter processes orders using an upsert model. This prevents duplicate records while still allowing later updates such as status, cargo and tracking information.

Polling job veya webhook üzerinden gelen sipariş payload verileri core sipariş alma akışına iletilir.

Ana uygulama adapter katmanı, siparişleri upsert modeliyle işler. Böylece aynı sipariş için tekrar kayıt oluşmaz; ancak durum, kargo ve takip bilgisi gibi sonradan gelen güncellemeler işlenmeye devam eder.

---

### 2. Product Synchronization / Ürün Senkronizasyonu

Product changes are pulled through the adapter using cursor-based tracking.

Changed products can then be pushed to marketplace drivers. Successful and failed operations can be recorded through the audit log layer.

Ürün değişiklikleri adapter üzerinden cursor tabanlı takip ile çekilir.

Değişen ürünler pazaryeri driver katmanına gönderilebilir. Başarılı ve hatalı işlemler audit log katmanında kayıt altına alınabilir.

---

### 3. Stock and Price Synchronization / Stok ve Fiyat Senkronizasyonu

Stock and price changes are pulled from the host system and sent to marketplace drivers.

The flow can support stock safety buffer, maximum display stock, batch updates, single item fallback, idempotency keys and partial failure handling.

Stok ve fiyat değişiklikleri ana sistemden çekilerek pazaryeri driver katmanına gönderilir.

Akış; stok güvenlik tamponu, maksimum gösterilecek stok, toplu güncelleme, tekil işlem yedeği, idempotency key ve kısmi hata yönetimi gibi yapıları destekleyebilir.

---

## Adapter Contract Responsibilities / Adapter Sorumlulukları

A host application adapter can implement the following responsibilities:

- Pull product changes / Ürün değişikliklerini çekme
- Push product changes / Ürün değişikliklerini gönderme
- Pull stock changes / Stok değişikliklerini çekme
- Push stock changes / Stok değişikliklerini gönderme
- Ingest orders / Siparişleri işleme
- Acquire and release locks / Lock alma ve bırakma
- Write audit logs / Audit log yazma
- Manage idempotent operations / Tekrarlı işlem kontrolü yapma

---

## Marketplace Driver Checklist / Pazaryeri Driver Kontrol Listesi

When adding a new marketplace driver, the host application should provide:

Yeni bir pazaryeri driver eklenirken ana uygulamada aşağıdaki yapılar hazırlanmalıdır:

- Driver class / Driver sınıfı
- Driver manager registration / Driver manager kaydı
- Account credential fields / Hesap credential alanları
- Configuration entries / Konfigürasyon kayıtları
- Status mapping rules / Durum eşleme kuralları
- Optional webhook controller / Opsiyonel webhook controller
- Product sync support / Ürün senkronizasyon desteği
- Stock sync support / Stok senkronizasyon desteği
- Order sync support / Sipariş senkronizasyon desteği
- Audit log support / Audit log desteği

Recommended driver methods:

Önerilen driver metodları:

- checkConnection
- getOrders
- updateStock
- updateStockAndPrice
- getCategories
- createProduct
- getMarketplaceProducts
- updateOrderStatus
- fetchOrdersBetween
- updateStockAndPriceBatch

---

## Design Principles / Tasarım Prensipleri

### Upsert Instead of Simple Dedupe / Basit Dedupe Yerine Upsert

Order ingestion should not skip an incoming order only because it already exists.

Marketplace orders may receive later updates such as status, cargo provider or tracking number. The recommended behavior is to upsert the order and keep update events processable.

Sipariş alma akışı, bir sipariş zaten var diye gelen veriyi doğrudan atlamamalıdır.

Pazaryeri siparişleri daha sonra durum, kargo firması veya takip numarası gibi güncellemeler alabilir. Önerilen davranış, siparişi upsert etmek ve güncelleme olaylarını işlemeye devam etmektir.

### Polling and Webhook Consistency / Polling ve Webhook Tutarlılığı

Polling and webhook payloads should use consistent status mapping.

When a marketplace sends multiple status fields, the adapter should define a clear priority rule.

Polling ve webhook payload verileri aynı status mapping mantığıyla işlenmelidir.

Bir pazaryeri birden fazla durum alanı gönderiyorsa adapter katmanı net bir öncelik kuralı tanımlamalıdır.

### Vendor Safety / Vendor Güvenliği

Changes under `vendor/` are not permanent.

Custom behavior should live in the host module, adapter layer or package repository instead of directly editing installed vendor files.

`vendor/` altındaki değişiklikler kalıcı değildir.

Özel davranışlar doğrudan kurulu vendor dosyalarına yazılmak yerine ana modül, adapter katmanı veya paket reposu içinde tutulmalıdır.

### Queue-Friendly Sync / Kuyruk Uyumlu Senkronizasyon

Long-running synchronization tasks should run through queue workers.

Heavy product, stock or order sync operations should not block normal web requests in production.

Uzun süren senkronizasyon işlemleri queue worker üzerinden çalıştırılmalıdır.

Yoğun ürün, stok veya sipariş senkronizasyonları canlı ortamda normal web request süreçlerini bloklamamalıdır.

---

## Recommended Tests / Önerilen Testler

- Contract binding tests / Contract binding testleri
- Product sync flow tests / Ürün sync flow testleri
- Stock sync flow tests / Stok sync flow testleri
- Order upsert tests / Sipariş upsert testleri
- Idempotency tests / Idempotency testleri
- Lock behavior tests / Lock davranışı testleri
- Driver status mapping tests / Driver status mapping testleri
- Webhook payload parsing tests / Webhook payload parse testleri

---

## Tech Stack / Teknoloji

- PHP
- Composer
- PSR-4 autoloading
- Laravel-compatible adapter pattern
- REST API integration-ready architecture
- XML integration-ready architecture
- Marketplace driver architecture

---

## Use Cases / Kullanım Alanları

- ERP marketplace integration / ERP pazaryeri entegrasyonu
- POS marketplace integration / POS pazaryeri entegrasyonu
- E-commerce middleware / E-ticaret ara katmanı
- Product, stock and price synchronization / Ürün, stok ve fiyat senkronizasyonu
- Order ingestion from marketplaces / Pazaryerlerinden sipariş alma
- Multi-marketplace business automation / Çoklu pazaryeri iş otomasyonu
- Reusable integration core for PHP applications / PHP uygulamaları için tekrar kullanılabilir entegrasyon çekirdeği

---

## Package Information / Paket Bilgisi

```text
Package: cherokhe/marketplace-integration-core
Type: library
Version: 2.0.0
PHP: >= 8.0
License: MIT


Contact / İletişim
Developer: Coşkun KOÇ
Website: https://laragon.com.tr
Email: entegrame@gmail.com
LinkedIn: Coşkun KOÇ
