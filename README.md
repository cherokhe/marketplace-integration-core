# MarketplaceIntegrationCore

![PHP](https://img.shields.io/badge/PHP-%3E%3D%208.0-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Composer](https://img.shields.io/badge/Composer-Package-885630?style=for-the-badge&logo=composer&logoColor=white)
![Architecture](https://img.shields.io/badge/Architecture-Adapter--Based-2563EB?style=for-the-badge)
![Framework](https://img.shields.io/badge/Framework-Agnostic-059669?style=for-the-badge)
![License](https://img.shields.io/badge/License-MIT-111827?style=for-the-badge)

**MarketplaceIntegrationCore** is a framework-agnostic PHP core package for marketplace integration workflows.

**MarketplaceIntegrationCore**, pazaryeri entegrasyon süreçlerini merkezi, tekrar kullanılabilir ve adapter tabanlı bir yapı üzerinden yönetmek için geliştirilmiş framework bağımsız bir PHP çekirdek paketidir.

---

## Developer / Geliştirici

**Coşkun KOÇ**  
Full-Stack Developer | Laravel Developer | ERP/POS System Developer

[![Website](https://img.shields.io/badge/Website-laragon.com.tr-0A66C2?style=flat-square)](https://laragon.com.tr)
[![Email](https://img.shields.io/badge/Email-entegrame%40gmail.com-EA4335?style=flat-square&logo=gmail&logoColor=white)](mailto:entegrame@gmail.com)
[![LinkedIn](https://img.shields.io/badge/LinkedIn-Co%C5%9Fkun%20KO%C3%87-0A66C2?style=flat-square&logo=linkedin&logoColor=white)](https://www.linkedin.com/in/coşkun-koç-bb6981426)

---

## Overview / Genel Bakış

This package provides a reusable core layer for marketplace integration systems.

It helps organize product synchronization, stock synchronization, price updates, order ingestion, idempotent operations, lock handling and audit logging under a clean adapter-based architecture.

Bu paket, pazaryeri entegrasyon sistemleri için tekrar kullanılabilir bir core katman sunar.

Ürün senkronizasyonu, stok senkronizasyonu, fiyat güncelleme, sipariş alma, idempotent işlem kontrolü, lock mekanizması ve audit log süreçlerini temiz bir adapter tabanlı mimari altında toplamayı hedefler.

---

## Core Features / Temel Özellikler

| Feature | Açıklama |
|---|---|
| Product Sync | Ürün senkronizasyonu |
| Stock Sync | Stok senkronizasyonu |
| Price Update Flow | Fiyat güncelleme akışı |
| Order Ingestion | Sipariş alma ve işleme |
| Adapter Architecture | Adapter tabanlı mimari |
| Idempotency Support | Tekrarlı işlem kontrolü |
| Lock Mechanism | Kilit mekanizması |
| Audit Logging | Audit log ve olay kaydı |
| Cursor Based Sync | Cursor tabanlı artımlı senkronizasyon |
| Batch Support | Toplu işlem desteği |
| Framework Agnostic | Framework bağımsız yapı |
| Laravel Compatible | Laravel uyumlu kullanım deseni |

---

## Architecture / Mimari

```text
Host Business Application
        |
        v
Application Adapter
        |
        v
MarketplaceIntegrationCore
        |
        v
Product / Stock / Order Flows
        |
        v
Marketplace Driver Layer
        |
        v
Marketplace APIs
```

MarketplaceIntegrationCore works through contracts and flow classes.

The host application implements the required contracts using its own adapter. The core flow layer then calls this adapter without depending directly on the host application's database, framework or business logic.

MarketplaceIntegrationCore, contract ve flow sınıfları üzerinden çalışır.

Ana uygulama gerekli contract yapısını kendi adapter sınıfı ile implemente eder. Core flow katmanı, uygulamanın veritabanına, framework detaylarına veya iş mantığına doğrudan bağımlı olmadan bu adapter üzerinden çalışır.

---

## Integration Flows / Entegrasyon Akışları

### Order Ingestion / Sipariş Alma

Order payloads from polling jobs or webhooks are passed into the core order ingestion flow.

The host adapter processes orders using an upsert model. This prevents duplicate records while still allowing later updates such as status, cargo and tracking information.

Polling job veya webhook üzerinden gelen sipariş payload verileri core sipariş alma akışına iletilir.

Ana uygulama adapter katmanı, siparişleri upsert modeliyle işler. Böylece aynı sipariş için tekrar kayıt oluşmaz; ancak durum, kargo ve takip bilgisi gibi sonradan gelen güncellemeler işlenmeye devam eder.

---

### Product Synchronization / Ürün Senkronizasyonu

Product changes are pulled through the adapter using cursor-based tracking.

Changed products can then be pushed to marketplace drivers. Successful and failed operations can be recorded through the audit log layer.

Ürün değişiklikleri adapter üzerinden cursor tabanlı takip ile çekilir.

Değişen ürünler pazaryeri driver katmanına gönderilebilir. Başarılı ve hatalı işlemler audit log katmanında kayıt altına alınabilir.

---

### Stock & Price Synchronization / Stok ve Fiyat Senkronizasyonu

Stock and price changes are pulled from the host system and sent to marketplace drivers.

The flow can support stock safety buffer, maximum display stock, batch updates, single item fallback, idempotency keys and partial failure handling.

Stok ve fiyat değişiklikleri ana sistemden çekilerek pazaryeri driver katmanına gönderilir.

Akış; stok güvenlik tamponu, maksimum gösterilecek stok, toplu güncelleme, tekil işlem yedeği, idempotency key ve kısmi hata yönetimi gibi yapıları destekleyebilir.

---

## Adapter Responsibilities / Adapter Sorumlulukları

| Adapter Responsibility | Türkçe |
|---|---|
| Pull product changes | Ürün değişikliklerini çekme |
| Push product changes | Ürün değişikliklerini gönderme |
| Pull stock changes | Stok değişikliklerini çekme |
| Push stock changes | Stok değişikliklerini gönderme |
| Ingest orders | Siparişleri işleme |
| Acquire / release locks | Lock alma ve bırakma |
| Write audit logs | Audit log yazma |
| Manage idempotency | Tekrarlı işlem kontrolü yapma |

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

```text
checkConnection
getOrders
updateStock
updateStockAndPrice
getCategories
createProduct
getMarketplaceProducts
updateOrderStatus
fetchOrdersBetween
updateStockAndPriceBatch
```

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

### Queue Friendly Sync / Kuyruk Uyumlu Senkronizasyon

Long-running synchronization tasks should run through queue workers.

Heavy product, stock or order sync operations should not block normal web requests in production.

Uzun süren senkronizasyon işlemleri queue worker üzerinden çalıştırılmalıdır.

Yoğun ürün, stok veya sipariş senkronizasyonları canlı ortamda normal web request süreçlerini bloklamamalıdır.

---

## Recommended Tests / Önerilen Testler

| Test Area | Açıklama |
|---|---|
| Contract Binding | Contract binding testleri |
| Product Sync Flow | Ürün sync flow testleri |
| Stock Sync Flow | Stok sync flow testleri |
| Order Upsert | Sipariş upsert testleri |
| Idempotency | Idempotency testleri |
| Lock Behavior | Lock davranışı testleri |
| Driver Status Mapping | Driver status mapping testleri |
| Webhook Payload Parsing | Webhook payload parse testleri |

---

## Tech Stack / Teknoloji

![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white)
![Composer](https://img.shields.io/badge/Composer-885630?style=flat-square&logo=composer&logoColor=white)
![PSR-4](https://img.shields.io/badge/PSR--4-Autoloading-111827?style=flat-square)
![REST API](https://img.shields.io/badge/REST_API-2563EB?style=flat-square)
![XML](https://img.shields.io/badge/XML-FF6600?style=flat-square)
![Laravel](https://img.shields.io/badge/Laravel-Compatible-F55247?style=flat-square&logo=laravel&logoColor=white)

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

## Security Note / Güvenlik Notu

This repository should not include marketplace credentials, customer data, production endpoints, API tokens or private business data.

Bu repo; pazaryeri credential bilgileri, müşteri verileri, canlı endpoint bilgileri, API tokenları veya özel ticari veriler içermemelidir.

---

## Package Information / Paket Bilgisi

```text
Package: cherokhe/marketplace-integration-core
Type: library
Version: 2.0.0
PHP: >= 8.0
License: MIT
```

---

## Contact / İletişim

**Coşkun KOÇ**  
Full-Stack Developer | Laravel Developer | ERP/POS System Developer

Website: [https://laragon.com.tr](https://laragon.com.tr)  
Email: [entegrame@gmail.com](mailto:entegrame@gmail.com)  
LinkedIn: [Coşkun KOÇ](https://www.linkedin.com/in/coşkun-koç-bb6981426)
