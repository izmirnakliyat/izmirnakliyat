<?php
/**
 * LLM SEO migrasyonu — tek script.
 *
 * Çalıştığında:
 *   1) settings.id'ye AUTO_INCREMENT ekler (gerekirse).
 *   2) settings.id=0 satırını "google_places_api_key" ise temizler veya yeni
 *      id atar (önceki seed çakışmasını düzeltir).
 *   3) authors tablosunu oluşturur (yoksa).
 *   4) blog_posts.author_id sütununu ekler (yoksa).
 *   5) Default yazarı authors tablosuna seed eder.
 *   6) blog_default_author_* anahtarlarını kurumsal içerik kaynağıyla set eder.
 *   7) Puan ve yorum verisini değiştirmez; doğrulanmış veri Places Details API ile senkronize edilir.
 *   8) blog_posts.author_id IS NULL satırlarına default author_id atar.
 *
 * Idempotent: defalarca çalıştırılabilir.
 *
 *   php scripts/llm_seo_migrate.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}

require __DIR__ . '/../config/db.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    throw new RuntimeException('Veritabanı bağlantısı kurulamadı.');
}

foreach ($argv ?? [] as $argument) {
    if (str_starts_with($argument, '--rating=') || str_starts_with($argument, '--reviews=')) {
        fwrite(STDERR, "Manuel puan/yorum kabul edilmez; scripts/seed_gbp_settings.php kullanın.\n");
        exit(1);
    }
}

echo "== LLM SEO migrasyonu ==\n";
echo "GBP puanı değiştirilmeden devam ediliyor.\n\n";

/* ------------------------------------------------------------------ */
/* 1) settings.id AUTO_INCREMENT garantisi                            */
/* ------------------------------------------------------------------ */
$col = $conn->query("SHOW COLUMNS FROM settings WHERE Field = 'id'");
$idExtra = '';
if ($col && $row = $col->fetch_assoc()) {
    $idExtra = strtolower((string) $row['Extra']);
}
if (strpos($idExtra, 'auto_increment') === false) {
    echo "[1] settings.id AUTO_INCREMENT ekleniyor...\n";

    // id=0 satırları varsa yeni id ata (PK çakışmasını engelle).
    $maxRow = $conn->query('SELECT IFNULL(MAX(id),0) AS m FROM settings WHERE id > 0');
    $maxId = 0;
    if ($maxRow && $r = $maxRow->fetch_assoc()) {
        $maxId = (int) $r['m'];
    }
    $zeros = $conn->query('SELECT id, name FROM settings WHERE id = 0');
    if ($zeros) {
        while ($zr = $zeros->fetch_assoc()) {
            $newId = ++$maxId;
            $stmt = $conn->prepare('UPDATE settings SET id = ? WHERE id = 0 AND name = ? LIMIT 1');
            $stmt->bind_param('is', $newId, $zr['name']);
            $stmt->execute();
            $stmt->close();
            echo "    settings.id=0 ('{$zr['name']}') → id={$newId}\n";
        }
    }

    if (!$conn->query('ALTER TABLE settings MODIFY id INT NOT NULL AUTO_INCREMENT')) {
        echo "    HATA AUTO_INCREMENT: " . $conn->error . "\n";
    } else {
        echo "    settings.id AUTO_INCREMENT aktif.\n";
    }
} else {
    echo "[1] settings.id zaten AUTO_INCREMENT.\n";
}

/* ------------------------------------------------------------------ */
/* 2) google_places_api_key alanı yanlışlıkla "265" mi?                */
/* ------------------------------------------------------------------ */
$r = $conn->query("SELECT id, value FROM settings WHERE name = 'google_places_api_key' LIMIT 1");
if ($r && $row = $r->fetch_assoc()) {
    $v = trim((string) $row['value']);
    if ($v === '265' || $v === '5' || $v === '5.0') {
        $stmt = $conn->prepare("UPDATE settings SET value = '' WHERE id = ?");
        $stmt->bind_param('i', $row['id']);
        $stmt->execute();
        $stmt->close();
        echo "[2] google_places_api_key yanlış değeri ('{$v}') temizlendi.\n";
    } else {
        echo "[2] google_places_api_key dokunulmadı (mevcut değer korunuyor).\n";
    }
} else {
    echo "[2] google_places_api_key kaydı yok (atlandı).\n";
}

/* ------------------------------------------------------------------ */
/* 3) authors tablosu                                                  */
/* ------------------------------------------------------------------ */
$createAuthors = <<<'SQL'
CREATE TABLE IF NOT EXISTS authors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    title VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    url VARCHAR(500) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    photo_url VARCHAR(500) DEFAULT NULL,
    linkedin VARCHAR(500) DEFAULT NULL,
    twitter VARCHAR(500) DEFAULT NULL,
    knows_about TEXT DEFAULT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_authors_slug (slug),
    KEY idx_authors_status (status),
    KEY idx_authors_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
if ($conn->query($createAuthors)) {
    echo "[3] authors tablosu hazır.\n";
} else {
    echo "[3] HATA authors tablosu: " . $conn->error . "\n";
}

/* ------------------------------------------------------------------ */
/* 4) blog_posts.author_id                                             */
/* ------------------------------------------------------------------ */
$colCheck = $conn->query("SHOW COLUMNS FROM blog_posts LIKE 'author_id'");
if ($colCheck && $colCheck->num_rows === 0) {
    $alterSql = "ALTER TABLE blog_posts ADD COLUMN author_id INT DEFAULT NULL AFTER kategori_id, ADD KEY idx_blog_posts_author (author_id)";
    if ($conn->query($alterSql)) {
        echo "[4] blog_posts.author_id sütunu eklendi.\n";
    } else {
        echo "[4] HATA ALTER blog_posts: " . $conn->error . "\n";
    }
} else {
    echo "[4] blog_posts.author_id zaten mevcut.\n";
}

/* ------------------------------------------------------------------ */
/* 5) Default yazar seed                                               */
/* ------------------------------------------------------------------ */
$author = [
    'name' => 'MY Nakliyat İçerik Ekibi',
    'slug' => 'my-nakliyat-musteri-iliskileri',
    'title' => 'Kurumsal İçerik Birimi',
    'bio' => 'MY Nakliyat hizmetleri, taşıma hazırlığı, fiyatlama etkenleri ve müşteri süreçleri hakkında kurumsal rehber içerikleri hazırlar.',
    'url' => '/hakkimizda',
    'email' => 'info@mynakliyat.com.tr',
    'photo_url' => '',
    'linkedin' => '',
    'twitter' => '',
    'knows_about' => 'izmir evden eve nakliyat, şehirler arası nakliyat, parça eşya taşıma, asansörlü taşıma, ofis taşıma, eşya depolama, izmir nakliye',
];

$existing = $conn->query("SELECT id FROM authors WHERE slug = '" . $conn->real_escape_string($author['slug']) . "' LIMIT 1");
$authorId = 0;
if ($existing && $existing->num_rows > 0) {
    $row = $existing->fetch_assoc();
    $authorId = (int) $row['id'];
    $stmt = $conn->prepare(
        'UPDATE authors SET name=?, title=?, bio=?, url=?, email=?, photo_url=?, linkedin=?, twitter=?, knows_about=?, is_default=1, status=1 WHERE id=?'
    );
    $stmt->bind_param(
        'sssssssssi',
        $author['name'],
        $author['title'],
        $author['bio'],
        $author['url'],
        $author['email'],
        $author['photo_url'],
        $author['linkedin'],
        $author['twitter'],
        $author['knows_about'],
        $authorId
    );
    $stmt->execute();
    $stmt->close();
    echo "[5] Default yazar GÜNCELLENDİ (id={$authorId}).\n";
} else {
    $stmt = $conn->prepare(
        'INSERT INTO authors (name, slug, title, bio, url, email, photo_url, linkedin, twitter, knows_about, is_default, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1)'
    );
    $stmt->bind_param(
        'ssssssssss',
        $author['name'],
        $author['slug'],
        $author['title'],
        $author['bio'],
        $author['url'],
        $author['email'],
        $author['photo_url'],
        $author['linkedin'],
        $author['twitter'],
        $author['knows_about']
    );
    $stmt->execute();
    $authorId = (int) $conn->insert_id;
    $stmt->close();
    echo "[5] Default yazar EKLENDİ (id={$authorId}).\n";
}

// Birden fazla is_default=1 varsa sadece bu kalsın.
$conn->query("UPDATE authors SET is_default = 0 WHERE id != {$authorId}");

/* ------------------------------------------------------------------ */
/* 6) settings: default author meta                                   */
/* ------------------------------------------------------------------ */
function mynak_settings_upsert(mysqli $conn, string $name, string $value, string $description): void
{
    $check = $conn->prepare('SELECT id FROM settings WHERE name = ? LIMIT 1');
    $check->bind_param('s', $name);
    $check->execute();
    $res = $check->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $check->close();
    if ($row && isset($row['id'])) {
        $id = (int) $row['id'];
        $stmt = $conn->prepare('UPDATE settings SET value = ? WHERE id = ?');
        $stmt->bind_param('si', $value, $id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare('INSERT INTO settings (name, value, description) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $name, $value, $description);
        $stmt->execute();
        $stmt->close();
    }
}

$siteUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
if ($siteUrl === '') {
    // .env yokken yereldeki SITE_URL tahmin: localhost
    $siteUrl = 'http://localhost/mynakliyat';
}

$settingsPairs = [
    ['blog_default_author_name', $author['name'], 'BlogPosting.author varsayılan ad'],
    ['blog_default_author_url', $siteUrl . $author['url'], 'BlogPosting.author varsayılan URL'],
    ['blog_default_author_email', $author['email'], 'BlogPosting.author varsayılan e-posta'],
    ['blog_default_author_title', $author['title'], 'BlogPosting.author varsayılan jobTitle'],
    ['blog_default_author_bio', $author['bio'], 'BlogPosting.author varsayılan biyografi (description)'],
    ['blog_default_author_knows_about', $author['knows_about'], 'BlogPosting.author knowsAbout listesi (virgülle)'],
];
foreach ($settingsPairs as [$n, $v, $d]) {
    mynak_settings_upsert($conn, $n, $v, $d);
    echo "  settings: {$n} = " . substr($v, 0, 80) . "\n";
}

echo "[7] GBP cache ve puan ayarları değiştirilmedi.\n";

/* ------------------------------------------------------------------ */
/* 8) blog_posts: author_id IS NULL satırlarına default ata             */
/* ------------------------------------------------------------------ */
$updateOld = $conn->prepare('UPDATE blog_posts SET author_id = ? WHERE author_id IS NULL');
$updateOld->bind_param('i', $authorId);
$updateOld->execute();
$affected = $conn->affected_rows;
$updateOld->close();
echo "[8] Eski blog_posts kayıtlarına default author_id atandı: {$affected} satır.\n";

echo "\nOK. Default author id = {$authorId}\n";
