-- Phase B production — Batch 1 (pages) + Batch 2 (blog_posts)
-- phpMyAdmin'de çalıştırın. Sadece seo_title + meta_description.
-- Rollback: logs/backups/ veya aşağıdaki eski değerleri tersine çevirin.

START TRANSACTION;

-- BATCH 1 — pages (status=1)
UPDATE `pages` SET seo_title = 'İzmir Evden Eve Nakliyat | MY Nakliyat'
WHERE slug = 'izmir-evden-eve-nakliyat' AND status = 1;

UPDATE `pages` SET meta_description = 'İzmir evden eve nakliyat: sigortalı taşıma, ücretsiz keşif ve yazılı sözleşme. 30 ilçe ve 81 il hizmeti.'
WHERE slug = 'izmir-evden-eve-nakliyat' AND status = 1;

UPDATE `pages` SET seo_title = 'Şehiriçi Nakliyat İzmir | MY Nakliyat'
WHERE slug = 'sehirici-nakliyat' AND status = 1;

UPDATE `pages` SET meta_description = 'İzmir şehiriçi nakliyat: aynı gün planlama, sigortalı ekip ve asansörlü taşıma. Ücretsiz ekspertiz için arayın.'
WHERE slug = 'sehirici-nakliyat' AND status = 1;

-- BATCH 2 — blog_posts (durum=3)
UPDATE `blog_posts` SET seo_title = '2026 İzmir Nakliyat Fiyat Rehberi | MY Nakliyat'
WHERE slug = 'izmir-evden-eve-nakliyat-fiyatlari-2026' AND durum = 3;

UPDATE `blog_posts` SET meta_description = '2026 İzmir nakliyat fiyatları: daire tipi, kat ve mesafeye göre şeffaf tablo. Ücretsiz keşif ile net teklif alın.'
WHERE slug = 'izmir-evden-eve-nakliyat-fiyatlari-2026' AND durum = 3;

UPDATE `blog_posts` SET seo_title = 'İzmir Nakliyat Firmaları Rehberi | MY Nakliyat'
WHERE slug = 'izmir-evden-eve-nakliyat-firmalar' AND durum = 3;

UPDATE `blog_posts` SET seo_title = 'Mobilya Taşıma Rehberi | MY Nakliyat'
WHERE slug = 'mobilya-beyaz-esya-tasimaciligi-rehberi' AND durum = 3;

UPDATE `blog_posts` SET seo_title = '2026 Taşınma Trendleri | MY Nakliyat'
WHERE slug = 'izmir-evden-eve-nakliyat-guncel-tasinma-trendleri' AND durum = 3;

COMMIT;
