# MarketplaceIntegrationCore

**Version:** 1.0.0  
**PHP Requirement:** >= 8.1 (PHP 8.3 önerilir)  
**Framework:** Framework-agnostic (Laravel örnekleri ile test edilmiş)  

---

## 1. Proje Hakkında

`MarketplaceIntegrationCore`, **pazaryeri entegrasyonlarını yönetmek** için geliştirilmiş, framework bağımsız bir çekirdek pakettir.  

**Özellikler:**
- Ürün senkronizasyonu  
- Stok senkronizasyonu  
- Sipariş alma ve işleme  
- Adapter tabanlı idempotent ve lock mekanizmaları  
- Audit log ve olay kaydı  

**Amaç:** Marketplace entegrasyonlarını merkezi bir şekilde kontrol etmek ve tekrar kullanılabilir bir yapı sunmak.

---

## 2. Mevcut Durum

- Paket yapısı oluşturuldu: `packages/marketplace-integration-core/`  
- **Core adapter**: `ReferenceAdapter` implement edildi  
- **Unit testler**: Adapter seviyesinde tamamlandı (`tests/ReferenceAdapterTest.php`)  
- **Integration testler**: Core Flow senaryoları hazır (`tests/Integration/CoreFlowIntegrationTest.php`)  
- Composer autoload ve PSR-4 doğrulandı (`composer.json`)  

> Not: Testler, mevcut ortamda PHPUnit olmadığı için henüz çalıştırılamadı. PHP 8.3 ortamında çalıştırılması önerilir.  

---

## 3. Yapılanlar / Adımlar

1. **Core Package Oluşturuldu**  
   - Context, Contracts, Flows dizinleri  
   - PSR-4 autoload yapılandırıldı  

2. **Reference Adapter Yazıldı**  
   - Product / Stock / Order / Lock / Audit log contract implementasyonu  
   - Cache, Logger, idempotency, cursor mekanizmaları  

3. **Unit Testler Hazırlandı**  
   - Adapter fonksiyonları test edildi  
   - Lock, cursor, idempotency, audit log, feed append senaryoları  

4. **Integration Testler Hazırlandı**  
   - ProductSyncFlow, StockSyncFlow, OrderIngestFlow  
   - Tüm core flow senaryoları test edildi  

---

## 4. Sonraki Adımlar

- PHP 8.3 ortamında **Composer yüklemesi ve PHPUnit testi** çalıştırılmalı:  
  ```bash
  composer install
  composer dump-autoload
  vendor/bin/phpunit -c tests/phpunit.xml
