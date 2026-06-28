<?php
$page_title = 'Rich Snippets Yönetimi';
require_once 'includes/header.php';
require_once 'includes/permissions.php';

// Rich snippets tablosunun var olup olmadığını kontrol et
$table_check = $conn->query("SHOW TABLES LIKE 'rich_snippets'");
$table_exists = ($table_check && $table_check->num_rows > 0);

// Eğer tablo yoksa oluştur
if (!$table_exists) {
    $create_table_sql = "CREATE TABLE IF NOT EXISTS `rich_snippets` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `type` varchar(100) NOT NULL,
        `name` varchar(255) NOT NULL,
        `data` text NOT NULL,
        `page_type` varchar(100) NOT NULL,
        `status` tinyint(1) DEFAULT 1,
        `created_at` datetime DEFAULT current_timestamp(),
        `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    if ($conn->query($create_table_sql)) {
        // Varsayılan rich snippets ekle
        $default_snippets = [
            [
                'type' => 'Organization',
                'name' => 'Şirket Bilgileri',
                'data' => '{"@context":"https://schema.org","@type":"Organization","name":"MyNakliyat","url":"' . SITE_URL . '","logo":"' . SITE_URL . '/assets/img/logo.png","contactPoint":{"@type":"ContactPoint","telephone":"+90-850-203-12-52","contactType":"customer service"},"address":{"@type":"PostalAddress","streetAddress":"Seyhan Mah. 653/2 Sk. No:10 K:3","addressLocality":"Buca","addressRegion":"İzmir","addressCountry":"TR"}}',
                'page_type' => 'all',
                'status' => 1
            ],
            [
                'type' => 'LocalBusiness',
                'name' => 'Yerel İşletme',
                'data' => '{"@context":"https://schema.org","@type":"LocalBusiness","name":"MyNakliyat","image":"' . SITE_URL . '/assets/img/logo.png","telephone":"+90-850-203-12-52","address":{"@type":"PostalAddress","streetAddress":"Seyhan Mah. 653/2 Sk. No:10 K:3","addressLocality":"Buca","addressRegion":"İzmir","postalCode":"35390","addressCountry":"TR"},"geo":{"@type":"GeoCoordinates","latitude":"38.3952","longitude":"27.1840"},"url":"' . SITE_URL . '","priceRange":"$$"}',
                'page_type' => 'homepage',
                'status' => 1
            ]
        ];
        
        foreach ($default_snippets as $snippet) {
            $stmt = $conn->prepare("INSERT INTO rich_snippets (type, name, data, page_type, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->bind_param("ssssi", $snippet['type'], $snippet['name'], $snippet['data'], $snippet['page_type'], $snippet['status']);
            $stmt->execute();
        }
        
        $success_message = "Rich snippets tablosu oluşturuldu ve varsayılan veriler eklendi!";
        $table_exists = true;
    } else {
        $error_message = "Tablo oluşturulamadı: " . $conn->error;
    }
}

// Rich Snippets ekleme/güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_snippet'])) {
        $type = $_POST['type'];
        $name = $_POST['name'];
        $data = $_POST['data'];
        $page_type = $_POST['page_type'];
        $status = isset($_POST['status']) ? 1 : 0;
        
        $stmt = $conn->prepare("INSERT INTO rich_snippets (type, name, data, page_type, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("ssssi", $type, $name, $data, $page_type, $status);
        
        if ($stmt->execute()) {
            $success_message = "Rich snippet başarıyla eklendi!";
        } else {
            $error_message = "Hata: " . $stmt->error;
        }
    }
    
    if (isset($_POST['update_snippet'])) {
        $id = $_POST['id'];
        $type = $_POST['type'];
        $name = $_POST['name'];
        $data = $_POST['data'];
        $page_type = $_POST['page_type'];
        $status = isset($_POST['status']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE rich_snippets SET type = ?, name = ?, data = ?, page_type = ?, status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssssii", $type, $name, $data, $page_type, $status, $id);
        
        if ($stmt->execute()) {
            $success_message = "Rich snippet başarıyla güncellendi!";
        } else {
            $error_message = "Hata: " . $stmt->error;
        }
    }
}

// Silme işlemi
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM rich_snippets WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $success_message = "Rich snippet başarıyla silindi!";
    } else {
        $error_message = "Hata: " . $stmt->error;
    }
}

// Rich snippets listesi
$snippets = null;
if ($table_exists) {
    $snippets = $conn->query("SELECT * FROM rich_snippets ORDER BY id DESC");
}

// Düzenleme için veri çekme
$edit_snippet = null;
if (isset($_GET['edit']) && $table_exists) {
    $edit_id = $_GET['edit'];
    $edit_result = $conn->query("SELECT * FROM rich_snippets WHERE id = " . intval($edit_id));
    if ($edit_result && $edit_result->num_rows > 0) {
        $edit_snippet = $edit_result->fetch_assoc();
    }
}
?>

<div class="main-content">
    <div class="page-header">
        <h1>Rich Snippets Yönetimi</h1>
        <p>Sayfalara structured data ekleyerek SEO performansını artırın</p>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <!-- Rich Snippet Ekleme/Düzenleme Formu -->
    <div class="card mb-4">
        <div class="card-header">
            <h3><?php echo $edit_snippet ? 'Rich Snippet Düzenle' : 'Yeni Rich Snippet Ekle'; ?></h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <?php if ($edit_snippet): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_snippet['id']; ?>">
                    <input type="hidden" name="update_snippet" value="1">
                <?php else: ?>
                    <input type="hidden" name="add_snippet" value="1">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="type">Schema Türü *</label>
                            <select name="type" id="type" class="form-control" required>
                                <option value="">Tür Seçin</option>
                                <option value="Organization" <?php echo ($edit_snippet && $edit_snippet['type'] == 'Organization') ? 'selected' : ''; ?>>Organization (Kuruluş)</option>
                                <option value="LocalBusiness" <?php echo ($edit_snippet && $edit_snippet['type'] == 'LocalBusiness') ? 'selected' : ''; ?>>Local Business (Yerel İşletme)</option>
                                <option value="Article" <?php echo ($edit_snippet && $edit_snippet['type'] == 'Article') ? 'selected' : ''; ?>>Article (Makale)</option>
                                <option value="BlogPosting" <?php echo ($edit_snippet && $edit_snippet['type'] == 'BlogPosting') ? 'selected' : ''; ?>>Blog Posting (Blog Yazısı)</option>
                                <option value="Service" <?php echo ($edit_snippet && $edit_snippet['type'] == 'Service') ? 'selected' : ''; ?>>Service (Hizmet)</option>
                                <option value="Product" <?php echo ($edit_snippet && $edit_snippet['type'] == 'Product') ? 'selected' : ''; ?>>Product (Ürün)</option>
                                <option value="FAQ" <?php echo ($edit_snippet && $edit_snippet['type'] == 'FAQ') ? 'selected' : ''; ?>>FAQ (SSS)</option>
                                <option value="BreadcrumbList" <?php echo ($edit_snippet && $edit_snippet['type'] == 'BreadcrumbList') ? 'selected' : ''; ?>>Breadcrumb List</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="name">İsim *</label>
                            <input type="text" name="name" id="name" class="form-control" 
                                   value="<?php echo $edit_snippet ? htmlspecialchars($edit_snippet['name']) : ''; ?>" 
                                   placeholder="Snippet adı" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="page_type">Sayfa Türü *</label>
                            <select name="page_type" id="page_type" class="form-control" required>
                                <option value="">Sayfa Türü Seçin</option>
                                <option value="global" <?php echo ($edit_snippet && $edit_snippet['page_type'] == 'global') ? 'selected' : ''; ?>>Tüm Sayfalar</option>
                                <option value="homepage" <?php echo ($edit_snippet && $edit_snippet['page_type'] == 'homepage') ? 'selected' : ''; ?>>Ana Sayfa</option>
                                <option value="blog" <?php echo ($edit_snippet && $edit_snippet['page_type'] == 'blog') ? 'selected' : ''; ?>>Blog Sayfaları</option>
                                <option value="services" <?php echo ($edit_snippet && $edit_snippet['page_type'] == 'services') ? 'selected' : ''; ?>>Hizmet Sayfaları</option>
                                <option value="contact" <?php echo ($edit_snippet && $edit_snippet['page_type'] == 'contact') ? 'selected' : ''; ?>>İletişim Sayfası</option>
                                <option value="about" <?php echo ($edit_snippet && $edit_snippet['page_type'] == 'about') ? 'selected' : ''; ?>>Hakkımızda Sayfası</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="status">Durum</label>
                            <div class="form-check">
                                <input type="checkbox" name="status" id="status" class="form-check-input" 
                                       <?php echo (!$edit_snippet || $edit_snippet['status'] == 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="status">Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label for="data">JSON-LD Schema Verisi *</label>
                    <textarea name="data" id="data" class="form-control" rows="15" 
                              placeholder='{"@context": "https://schema.org", "@type": "Organization", "name": "MY Nakliyat"}' 
                              required><?php echo $edit_snippet ? htmlspecialchars($edit_snippet['data']) : ''; ?></textarea>
                    <small class="form-text text-muted">JSON-LD formatında schema verisi girin. <a href="https://schema.org" target="_blank">Schema.org</a> referansını kullanın.</small>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $edit_snippet ? 'Güncelle' : 'Ekle'; ?>
                    </button>
                    <?php if ($edit_snippet): ?>
                        <a href="rich_snippets.php" class="btn btn-secondary">İptal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Rich Snippets Listesi -->
    <div class="card">
        <div class="card-header">
            <h3>Mevcut Rich Snippets</h3>
        </div>
        <div class="card-body">
            <?php if ($table_exists): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>İsim</th>
                                <th>Tür</th>
                                <th>Sayfa Türü</th>
                                <th>Durum</th>
                                <th>Oluşturulma</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($snippets && $snippets->num_rows > 0): ?>
                                <?php while ($snippet = $snippets->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $snippet['id']; ?></td>
                                        <td><?php echo htmlspecialchars($snippet['name']); ?></td>
                                        <td><span class="badge bg-info"><?php echo $snippet['type']; ?></span></td>
                                        <td><span class="badge bg-secondary"><?php echo $snippet['page_type']; ?></span></td>
                                        <td>
                                            <?php if ($snippet['status'] == 1): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Pasif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($snippet['created_at'])); ?></td>
                                        <td>
                                            <a href="?edit=<?php echo $snippet['id']; ?>" class="btn btn-sm btn-warning">Düzenle</a>
                                            <a href="?delete=<?php echo $snippet['id']; ?>" class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Bu rich snippet\'i silmek istediğinizden emin misiniz?')">Sil</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">Henüz hiç rich snippet eklenmemiş.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <h5>Rich Snippets Tablosu Bulunamadı</h5>
                    <p>Rich snippets tablosu henüz oluşturulmamış. Sayfayı yenilediğinizde tablo otomatik olarak oluşturulacak.</p>
                    <a href="?" class="btn btn-primary">Sayfayı Yenile</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- JSON Doğrulama için JavaScript -->
<script>
document.getElementById('data').addEventListener('blur', function() {
    try {
        const data = this.value.trim();
        if (data) {
            JSON.parse(data);
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        }
    } catch (e) {
        this.classList.remove('is-valid');
        this.classList.add('is-invalid');
        alert('Geçersiz JSON formatı: ' + e.message);
    }
});

// Schema türüne göre örnek JSON gösterme
document.getElementById('type').addEventListener('change', function() {
    const type = this.value;
    const dataTextarea = document.getElementById('data');
    
    const examples = {
        'Organization': `{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "MY Nakliyat",
  "url": "https://www.mynakliyat.com.tr",
  "logo": "https://www.mynakliyat.com.tr/uploads/settings/logo.png",
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "+90-XXX-XXX-XXXX",
    "contactType": "customer service"
  },
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "İstanbul",
    "addressCountry": "TR"
  }
}`,
        'LocalBusiness': `{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "MY Nakliyat",
  "image": "https://www.mynakliyat.com.tr/uploads/settings/logo.png",
  "telephone": "+90-XXX-XXX-XXXX",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "Adres bilgisi",
    "addressLocality": "İstanbul",
    "addressCountry": "TR"
  },
  "openingHours": "Mo-Fr 09:00-18:00"
}`,
        'Article': `{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "Makale başlığı",
  "author": {
    "@type": "Person",
    "name": "Yazar adı"
  },
  "datePublished": "2024-01-01",
  "dateModified": "2024-01-01",
  "publisher": {
    "@type": "Organization",
    "name": "MY Nakliyat",
    "logo": {
      "@type": "ImageObject",
      "url": "https://www.mynakliyat.com.tr/uploads/settings/logo.png"
    }
  }
}`
    };
    
    if (examples[type] && !dataTextarea.value.trim()) {
        dataTextarea.value = examples[type];
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
