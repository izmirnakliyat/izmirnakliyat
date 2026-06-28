<?php
require_once 'includes/header.php';

$page_title = "Yazarlar (BlogPosting.author)";
$success = '';
$error = '';

// authors tablosu var mı?
$tbl_check = $conn->query("SHOW TABLES LIKE 'authors'");
if (!$tbl_check || $tbl_check->num_rows === 0) {
    echo '<div class="alert alert-danger">authors tablosu yok. Lütfen <code>php scripts/llm_seo_migrate.php</code> komutunu çalıştırın.</div>';
    require_once 'includes/footer.php';
    exit;
}

// Silme
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $row = $conn->query("SELECT is_default FROM authors WHERE id = $id")->fetch_assoc();
    if (!$row) {
        $error = "Yazar bulunamadı.";
    } elseif ((int) $row['is_default'] === 1) {
        $error = "Varsayılan yazar silinemez. Önce başka bir yazarı varsayılan yapın.";
    } else {
        // Bu yazara bağlı blog yazılarını default yazara taşı
        $defaultRow = $conn->query("SELECT id FROM authors WHERE is_default = 1 LIMIT 1")->fetch_assoc();
        $defaultId = $defaultRow ? (int) $defaultRow['id'] : null;
        if ($defaultId) {
            $stmt = $conn->prepare("UPDATE blog_posts SET author_id = ? WHERE author_id = ?");
            $stmt->bind_param("ii", $defaultId, $id);
            $stmt->execute();
            $stmt->close();
        } else {
            $conn->query("UPDATE blog_posts SET author_id = NULL WHERE author_id = $id");
        }
        if ($conn->query("DELETE FROM authors WHERE id = $id")) {
            $success = "Yazar silindi. Bağlı yazılar varsayılan yazara taşındı.";
        } else {
            $error = "Yazar silinirken hata: " . $conn->error;
        }
    }
}

// Varsayılan yapma
if (isset($_GET['set_default'])) {
    $id = (int) $_GET['set_default'];
    if ($conn->query("UPDATE authors SET is_default = 0")) {
        $stmt = $conn->prepare("UPDATE authors SET is_default = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Varsayılan yazar güncellendi.";
        } else {
            $error = "Varsayılan ayarlanırken hata: " . $stmt->error;
        }
        $stmt->close();
    }
}

function mynak_authors_make_slug(string $name): string
{
    $tr = ['ı' => 'i', 'İ' => 'i', 'ç' => 'c', 'Ç' => 'c', 'ğ' => 'g', 'Ğ' => 'g', 'ö' => 'o', 'Ö' => 'o', 'ş' => 's', 'Ş' => 's', 'ü' => 'u', 'Ü' => 'u'];
    $name = strtr($name, $tr);
    $s = strtolower((string) preg_replace('/[^A-Za-z0-9\s-]/', '', $name));
    $s = (string) preg_replace('/[\s-]+/', '-', trim($s));
    return trim($s, '-') ?: 'yazar';
}

// Ekleme / güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $name = trim((string) $_POST['name']);
    $title = trim((string) ($_POST['title'] ?? ''));
    $bio = trim((string) ($_POST['bio'] ?? ''));
    $url = trim((string) ($_POST['url'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $photo = trim((string) ($_POST['photo_url'] ?? ''));
    $linkedin = trim((string) ($_POST['linkedin'] ?? ''));
    $twitter = trim((string) ($_POST['twitter'] ?? ''));
    $knowsAbout = trim((string) ($_POST['knows_about'] ?? ''));
    $status = isset($_POST['status']) ? 1 : 0;

    if ($name === '') {
        $error = "Ad alanı boş olamaz.";
    } else {
        try {
            if ($id > 0) {
                $stmt = $conn->prepare("UPDATE authors SET name=?, title=?, bio=?, url=?, email=?, photo_url=?, linkedin=?, twitter=?, knows_about=?, status=? WHERE id=?");
                $stmt->bind_param("sssssssssii", $name, $title, $bio, $url, $email, $photo, $linkedin, $twitter, $knowsAbout, $status, $id);
                if ($stmt->execute()) {
                    $success = "Yazar güncellendi.";
                } else {
                    $error = "Güncelleme hatası: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $slug = mynak_authors_make_slug($name);
                $base = $slug;
                $i = 2;
                while (true) {
                    $r = $conn->query("SELECT id FROM authors WHERE slug = '" . $conn->real_escape_string($slug) . "' LIMIT 1");
                    if (!$r || $r->num_rows === 0) {
                        break;
                    }
                    $slug = $base . '-' . ($i++);
                }
                $stmt = $conn->prepare("INSERT INTO authors (name, slug, title, bio, url, email, photo_url, linkedin, twitter, knows_about, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssssi", $name, $slug, $title, $bio, $url, $email, $photo, $linkedin, $twitter, $knowsAbout, $status);
                if ($stmt->execute()) {
                    $success = "Yazar eklendi.";
                } else {
                    $error = "Ekleme hatası: " . $stmt->error;
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            $error = "İşlem hatası: " . $e->getMessage();
        }
    }
}

$authors = $conn->query("SELECT a.*, (SELECT COUNT(*) FROM blog_posts WHERE author_id = a.id) AS post_count FROM authors a ORDER BY a.is_default DESC, a.name ASC");
?>

<div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-users me-2"></i>Yazarlar
            <small class="text-muted ms-2">BlogPosting.author için E-E-A-T sinyali</small>
        </h6>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#authorModal" id="btn-new-author">
            <i class="fas fa-plus"></i> Yeni Yazar
        </button>
    </div>
    <div class="card-body">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <div class="alert alert-info py-2 px-3" style="font-size:13px;">
            <i class="fas fa-info-circle me-1"></i>
            Bu sayfa <code>authors</code> tablosunu yönetir. Blog yazısı açılırken <code>blog_posts.author_id</code> bu kayıtlardan birini seçer.
            Hiç seçilmezse <strong>varsayılan yazar</strong> kullanılır. Schema.org <code>BlogPosting.author</code> (Person) bu bilgileri kullanır.
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ad</th>
                        <th>Unvan</th>
                        <th>E-posta</th>
                        <th>Yazı Sayısı</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($authors && $authors->num_rows > 0): ?>
                        <?php while ($a = $authors->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo (int) $a['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars((string) $a['name']); ?>
                                    <?php if ((int) $a['is_default'] === 1): ?>
                                        <span class="badge bg-success ms-1">Varsayılan</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars((string) ($a['title'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars((string) ($a['email'] ?? '')); ?></td>
                                <td><?php echo (int) ($a['post_count'] ?? 0); ?></td>
                                <td>
                                    <?php if ((int) $a['status'] === 1): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Pasif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary edit-author"
                                            data-author='<?php echo htmlspecialchars(json_encode($a, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES); ?>'
                                            data-bs-toggle="modal" data-bs-target="#authorModal" title="Düzenle">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ((int) $a['is_default'] !== 1): ?>
                                        <a href="?set_default=<?php echo (int) $a['id']; ?>" class="btn btn-sm btn-warning" title="Varsayılan yap"
                                           onclick="return confirm('Bu yazarı varsayılan yapmak istiyor musunuz?');">
                                            <i class="fas fa-star"></i>
                                        </a>
                                        <a href="?delete=<?php echo (int) $a['id']; ?>" class="btn btn-sm btn-danger" title="Sil"
                                           onclick="return confirm('Bu yazarı silmek istediğinizden emin misiniz? Bağlı yazılar varsayılan yazara taşınacak.');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center">Henüz yazar yok.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="authorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="authorForm">
                <input type="hidden" name="id" id="author_id">
                <div class="modal-header">
                    <h5 class="modal-title">Yazar Ekle / Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Ad Soyad / Takım Adı *</label>
                            <input type="text" class="form-control" name="name" id="f_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unvan / Pozisyon (jobTitle)</label>
                            <input type="text" class="form-control" name="title" id="f_title" placeholder="örn: Nakliyat Uzmanları">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Biyografi (description)</label>
                            <textarea class="form-control" name="bio" id="f_bio" rows="3" placeholder="2-3 cümlelik kısa biyografi (E-E-A-T sinyali)..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Profil URL'i</label>
                            <input type="text" class="form-control" name="url" id="f_url" placeholder="/hakkimizda veya tam URL">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-posta</label>
                            <input type="email" class="form-control" name="email" id="f_email" placeholder="info@mynakliyat.com.tr">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Profil Foto URL</label>
                            <input type="text" class="form-control" name="photo_url" id="f_photo" placeholder="https://...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">LinkedIn</label>
                            <input type="text" class="form-control" name="linkedin" id="f_linkedin" placeholder="https://linkedin.com/in/...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Twitter / X</label>
                            <input type="text" class="form-control" name="twitter" id="f_twitter" placeholder="https://x.com/...">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Yetkinlikler (knowsAbout) — virgülle ayırın</label>
                            <textarea class="form-control" name="knows_about" id="f_knows_about" rows="2" placeholder="izmir evden eve nakliyat, asansörlü taşıma, ofis taşıma, ..."></textarea>
                            <small class="text-muted">Schema.org Person.knowsAbout listesi — yapay zeka modelleri için uzmanlık alanları.</small>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="status" id="f_status" value="1" checked>
                                <label class="form-check-label" for="f_status">Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function(){
    function fill(a){
        document.getElementById('author_id').value = a.id || '';
        document.getElementById('f_name').value = a.name || '';
        document.getElementById('f_title').value = a.title || '';
        document.getElementById('f_bio').value = a.bio || '';
        document.getElementById('f_url').value = a.url || '';
        document.getElementById('f_email').value = a.email || '';
        document.getElementById('f_photo').value = a.photo_url || '';
        document.getElementById('f_linkedin').value = a.linkedin || '';
        document.getElementById('f_twitter').value = a.twitter || '';
        document.getElementById('f_knows_about').value = a.knows_about || '';
        document.getElementById('f_status').checked = (parseInt(a.status, 10) === 1);
    }
    function clearForm(){
        fill({ id:'', name:'', title:'', bio:'', url:'', email:'', photo_url:'', linkedin:'', twitter:'', knows_about:'', status:'1' });
        document.getElementById('f_status').checked = true;
    }
    document.addEventListener('click', function(e){
        var btn = e.target.closest('.edit-author');
        if (btn) {
            try { fill(JSON.parse(btn.getAttribute('data-author'))); } catch (err) {}
        }
        if (e.target.closest('#btn-new-author')) {
            clearForm();
        }
    });
    var modal = document.getElementById('authorModal');
    if (modal) {
        modal.addEventListener('hidden.bs.modal', clearForm);
    }
})();
</script>

<?php require_once 'includes/footer.php'; ?>
