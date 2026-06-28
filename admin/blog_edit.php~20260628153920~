<?php
require_once '../includes/functions.php';
require_once '../config/db.php';

/**
 * SEO Score hesaplama fonksiyonu
 */
function calculateSeoScore($title, $content, $seo_title, $meta_description, $focus_keyword) {
	$score = 0;
	$max_score = 100;
	
	// Strip HTML tags from content for analysis
	$plain_content = strip_tags($content);
	$word_count = str_word_count($plain_content);
	
	// 1. Başlık kontrolü (15 puan)
	$title_length = mb_strlen($title);
	if ($title_length >= 30 && $title_length <= 60) {
		$score += 15;
	} elseif ($title_length >= 20 && $title_length <= 70) {
		$score += 10;
	} elseif ($title_length > 0) {
		$score += 5;
	}
	
	// 2. SEO Başlık kontrolü (10 puan)
	if (!empty($seo_title)) {
		$seo_title_length = mb_strlen($seo_title);
		if ($seo_title_length >= 30 && $seo_title_length <= 60) {
			$score += 10;
		} elseif ($seo_title_length > 0) {
			$score += 5;
		}
	}
	
	// 3. Meta Description kontrolü (20 puan)
	if (!empty($meta_description)) {
		$desc_length = mb_strlen($meta_description);
		if ($desc_length >= 120 && $desc_length <= 160) {
			$score += 20;
		} elseif ($desc_length >= 80 && $desc_length <= 180) {
			$score += 15;
		} elseif ($desc_length > 0) {
			$score += 8;
		}
	}
	
	// 4. İçerik uzunluğu kontrolü (20 puan)
	if ($word_count >= 300) {
		$score += 20;
	} elseif ($word_count >= 150) {
		$score += 15;
	} elseif ($word_count >= 50) {
		$score += 8;
	}
	
	// 5. Focus keyword kontrolü (15 puan)
	if (!empty($focus_keyword)) {
		$score += 5;
		// Başlıkta var mı?
		if (stripos($title, $focus_keyword) !== false) {
			$score += 5;
		}
		// İçerikte var mı?
		if (stripos($plain_content, $focus_keyword) !== false) {
			$score += 5;
		}
	}
	
	// 6. Görsel kontrolü (10 puan) - basit img tag kontrolü
	if (strpos($content, '<img') !== false) {
		$score += 10;
	}
	
	// 7. Başlık etiketleri kontrolü (10 puan)
	if (preg_match('/<h[2-3]/i', $content)) {
		$score += 10;
	}
	
	return min($score, $max_score);
}

/**
 * Resmi ölçeklendir - oranı koruyarak
 * @param string $source_path Kaynak dosya yolu
 * @param int $target_width Hedef genişlik
 * @param int $target_height Hedef yükseklik
 * @param string $ext Dosya uzantısı
 * @return bool Başarılı mı?
 */
function resizeCoverImage($source_path, $target_width, $target_height, $ext) {
	// Orijinal resim boyutlarını al
	$image_info = @getimagesize($source_path);
	if (!$image_info) return false;
	
	$orig_width = $image_info[0];
	$orig_height = $image_info[1];
	
	// Resim zaten küçükse ölçeklendirme yapma
	if ($orig_width <= $target_width && $orig_height <= $target_height) {
		return true;
	}
	
	// Oranı koru - genişlik veya yükseklikten hangisi daha büyükse ona göre ölçekle
	$ratio_w = $target_width / $orig_width;
	$ratio_h = $target_height / $orig_height;
	$ratio = min($ratio_w, $ratio_h);
	
	$new_width = round($orig_width * $ratio);
	$new_height = round($orig_height * $ratio);
	
	// Kaynak resmi yükle
	switch ($ext) {
		case 'jpg':
		case 'jpeg':
			$source_image = @imagecreatefromjpeg($source_path);
			break;
		case 'png':
			$source_image = @imagecreatefrompng($source_path);
			break;
		case 'webp':
			$source_image = @imagecreatefromwebp($source_path);
			break;
		default:
			return false;
	}
	
	if (!$source_image) {
		return false;
	}
	
	// Yeni resim oluştur
	$new_image = imagecreatetruecolor($new_width, $new_height);
	
	// PNG ve WebP için transparanlık desteği
	if ($ext == 'png' || $ext == 'webp') {
		imagealphablending($new_image, false);
		imagesavealpha($new_image, true);
		$transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
		imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
	}
	
	// Resmi ölçeklendir
	imagecopyresampled(
		$new_image, $source_image,
		0, 0, 0, 0,
		$new_width, $new_height,
		$orig_width, $orig_height
	);
	
	// Kaydet
	switch ($ext) {
		case 'jpg':
		case 'jpeg':
			$result = imagejpeg($new_image, $source_path, 90);
			break;
		case 'png':
			$result = imagepng($new_image, $source_path, 8);
			break;
		case 'webp':
			$result = imagewebp($new_image, $source_path, 90);
			break;
		default:
			$result = false;
	}
	
	// Belleği temizle
	imagedestroy($source_image);
	imagedestroy($new_image);
	
	return $result;
}

require_once 'includes/header.php';

$page_title = "Blog Yazısı Ekle/Düzenle";
$success_message = '';
$error_message = '';

// Blog yazısı düzenleme
if (isset($_GET['id'])) {
	$id = (int)$_GET['id'];
	$result = $conn->query("SELECT * FROM blog_posts WHERE id = $id");
	$blog = $result->fetch_assoc();
}

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$baslik = $_POST['baslik'];
	$icerik = $_POST['icerik'];
	
	// İçeriği işle - resimleri düzenle
	require_once '../includes/functions.php';
	$icerik = icerik_donustur($icerik);
	
	$kategori_id = (int)$_POST['kategori_id'];
	$author_id_raw = isset($_POST['author_id']) ? (int)$_POST['author_id'] : 0;
	$author_id = $author_id_raw > 0 ? $author_id_raw : null;
	$etiketler = $_POST['etiketler'];
	// Durum (v2): 0=Taslak, 1=Editör kuyruğu, 2=Revize, 3=Yayında
	if (isset($_POST['durum']) && is_numeric($_POST['durum'])) {
		$_durum_raw = (int) $_POST['durum'];
		$durum = in_array($_durum_raw, [0, 1, 2, 3], true) ? $_durum_raw : MYNAK_BLOG_STATUS_DRAFT;
	} else {
		$durum = MYNAK_BLOG_STATUS_DRAFT;
	}
	
	// SEO Alanları
	$seo_title = trim($_POST['seo_title'] ?? '');
	$meta_description = trim($_POST['meta_description'] ?? '');
	$meta_keywords = trim($_POST['meta_keywords'] ?? '');
	$og_title = trim($_POST['og_title'] ?? '');
	$og_description = trim($_POST['og_description'] ?? '');
	$og_image = trim($_POST['og_image'] ?? '');
	$canonical_url = trim($_POST['canonical_url'] ?? '');
	$focus_keyword = trim($_POST['focus_keyword'] ?? '');
	
	// SEO Score hesapla
	$seo_score = calculateSeoScore($baslik, $icerik, $seo_title, $meta_description, $focus_keyword);
	
	// Kapak fotoğrafı yükleme
	$kapak_foto = '';
	// Medya kütüphanesinden seçildi mi kontrol et
	$kapak_foto_media = trim($_POST['kapak_foto_media'] ?? '');
	if (!empty($kapak_foto_media)) {
		// Medya kütüphanesinden seçilen dosya — doğrudan kullan
		$kapak_foto = 'media/' . ltrim($kapak_foto_media, '/');
	} elseif (isset($_FILES['kapak_foto']) && $_FILES['kapak_foto']['error'] == 0) {
		$allowed = ['jpg', 'jpeg', 'png', 'webp'];
		$filename = $_FILES['kapak_foto']['name'];
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		
		// Maksimum dosya boyutu kontrolü (5MB)
		$max_file_size = 5 * 1024 * 1024;
		if ($_FILES['kapak_foto']['size'] > $max_file_size) {
			$error_message = "Dosya boyutu çok büyük! Maksimum 5MB yükleyebilirsiniz.";
		} elseif (in_array($ext, $allowed)) {
			$new_filename = uniqid() . '.' . $ext;
			$upload_path = '../uploads/blog/' . $new_filename;
			
			if (!file_exists('../uploads/blog')) {
				mkdir('../uploads/blog', 0777, true);
			}
			
			// Otomatik ölçeklendirme kontrolü
			$auto_resize = isset($_POST['auto_resize']) ? true : false;
			
			if (move_uploaded_file($_FILES['kapak_foto']['tmp_name'], $upload_path)) {
				
				// Otomatik ölçeklendirme
				if ($auto_resize) {
					$resized = resizeCoverImage($upload_path, 1200, 630, $ext);
					if (!$resized) {
						// Ölçeklendirme başarısız olsa bile orijinal dosya kullanılır
						error_log("Kapak fotoğrafı ölçeklendirme başarısız: " . $upload_path);
					}
				}
				
				$kapak_foto = $new_filename;
			}
		} else {
			$error_message = "Geçersiz dosya formatı! Sadece JPG, PNG ve WebP dosyaları yükleyebilirsiniz.";
		}
	}
	
	// Slug işle
	$slug = trim($_POST['slug']);
	if ($slug === '') {
		$slug = slug_olustur($baslik);
	} else {
		$slug = slug_olustur($slug); // elle girileni de temizle
	}
	// Slug benzersiz mi kontrol et (güncellemede mevcut id hariç)
	$slug_check_sql = "SELECT id FROM blog_posts WHERE slug = ?";
	$slug_check_params = [$slug];
	$slug_check_types = "s";
	if (isset($_GET['id'])) {
		$slug_check_sql .= " AND id != ?";
		$slug_check_params[] = (int)$_GET['id'];
		$slug_check_types .= "i";
	}
	$slug_check = $conn->prepare($slug_check_sql);
	$slug_check->bind_param($slug_check_types, ...$slug_check_params);
	$slug_check->execute();
	$slug_check_result = $slug_check->get_result();
	if ($slug_check_result->num_rows > 0) {
		$error_message = "Aynı slug başka bir blog yazısında kullanılıyor. Lütfen farklı bir başlık veya slug girin.";
	}
	
	if (empty($error_message)) {
	if (isset($_GET['id'])) {
		// Güncelleme
		$id = (int)$_GET['id'];
		$sql = "UPDATE blog_posts SET 
				baslik = ?, 
				icerik = ?, 
				kategori_id = ?, 
				etiketler = ?, 
				durum = ?,
				slug = ?,
				seo_title = ?,
				meta_description = ?,
				meta_keywords = ?,
				og_title = ?,
				og_description = ?,
				og_image = ?,
				canonical_url = ?,
				focus_keyword = ?,
				seo_score = ?";
		$params = [$baslik, $icerik, $kategori_id, $etiketler, $durum, $slug, 
				   $seo_title, $meta_description, $meta_keywords, $og_title, 
				   $og_description, $og_image, $canonical_url, $focus_keyword, $seo_score];
		$types = "ssisisssssssssi";
		// author_id: blog_posts.author_id sütunu varsa ekle (idempotent).
		$col_check_author = $conn->query("SHOW COLUMNS FROM blog_posts LIKE 'author_id'");
		if ($col_check_author && $col_check_author->num_rows > 0) {
			$sql .= ", author_id = ?";
			$params[] = $author_id;
			$types .= "i";
		}
		if ($kapak_foto) {
			$sql .= ", kapak_foto = ?";
			$params[] = $kapak_foto;
			$types .= "s";
		}
		$sql .= " WHERE id = ?";
		$params[] = $id;
		$types .= "i";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param($types, ...$params);
		
		if ($stmt->execute()) {
			$success_message = "Blog yazısı başarıyla güncellendi.";
			// Güncel verileri yükle
			$result = $conn->query("SELECT * FROM blog_posts WHERE id = $id");
			$blog = $result->fetch_assoc();
		} else {
			$error_message = "Blog yazısı güncellenirken bir hata oluştu: " . $stmt->error;
		}
	} else {
		// Yeni ekleme — önce eksik sütunları ekle (canlı DB uyumu)
		$needed_cols = [
			'seo_title'        => "ALTER TABLE blog_posts ADD COLUMN seo_title VARCHAR(255) DEFAULT NULL",
			'meta_description' => "ALTER TABLE blog_posts ADD COLUMN meta_description TEXT DEFAULT NULL",
			'meta_keywords'    => "ALTER TABLE blog_posts ADD COLUMN meta_keywords VARCHAR(500) DEFAULT NULL",
			'og_title'         => "ALTER TABLE blog_posts ADD COLUMN og_title VARCHAR(255) DEFAULT NULL",
			'og_description'   => "ALTER TABLE blog_posts ADD COLUMN og_description TEXT DEFAULT NULL",
			'og_image'         => "ALTER TABLE blog_posts ADD COLUMN og_image VARCHAR(255) DEFAULT NULL",
			'canonical_url'    => "ALTER TABLE blog_posts ADD COLUMN canonical_url VARCHAR(500) DEFAULT NULL",
			'focus_keyword'    => "ALTER TABLE blog_posts ADD COLUMN focus_keyword VARCHAR(255) DEFAULT NULL",
			'seo_score'        => "ALTER TABLE blog_posts ADD COLUMN seo_score INT DEFAULT 0",
			'author_id'        => "ALTER TABLE blog_posts ADD COLUMN author_id INT DEFAULT NULL",
		];
		$cols_result = $conn->query("SHOW COLUMNS FROM blog_posts");
		$existing_cols = [];
		while ($col = $cols_result->fetch_assoc()) $existing_cols[] = $col['Field'];
		foreach ($needed_cols as $col => $sql) {
			if (!in_array($col, $existing_cols)) { $conn->query($sql); }
		}
		$has_author_id = in_array('author_id', $existing_cols, true) || true; // ALTER yukarıda eklendi

		$stmt = $conn->prepare("INSERT INTO blog_posts (baslik, icerik, kapak_foto, kategori_id, author_id, etiketler, durum, slug, seo_title, meta_description, meta_keywords, og_title, og_description, og_image, canonical_url, focus_keyword, seo_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		if (!$stmt) {
			$error_message = "Veritabanı hazırlama hatası: " . $conn->error;
		} else {
			$stmt->bind_param("sssiisisssssssssi", $baslik, $icerik, $kapak_foto, $kategori_id, $author_id, $etiketler, $durum, $slug, $seo_title, $meta_description, $meta_keywords, $og_title, $og_description, $og_image, $canonical_url, $focus_keyword, $seo_score);
			if ($stmt->execute()) {
				$id = $conn->insert_id;
				$success_message = "Blog yazısı başarıyla eklendi.";
				$result = $conn->query("SELECT * FROM blog_posts WHERE id = $id");
				$blog = $result->fetch_assoc();
				echo "<script>history.replaceState({}, '', 'blog_edit.php?id=" . $id . "');</script>";
			} else {
				$error_message = "Blog yazısı eklenirken bir hata oluştu: " . $stmt->error;
			}
		}
	}
	}
}
?>

<div class="container-fluid">
	<?php if ($success_message): ?>
		<div class="alert alert-success alert-dismissible fade show" role="alert">
			<?php echo $success_message; ?>
			<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
		</div>
	<?php endif; ?>
	
	<?php if ($error_message): ?>
		<div class="alert alert-danger alert-dismissible fade show" role="alert">
			<?php echo $error_message; ?>
			<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
		</div>
	<?php endif; ?>
	
	<div class="card shadow mb-4">
		<div class="card-header py-3 d-flex justify-content-between align-items-center">
			<h6 class="m-0 font-weight-bold text-primary"><?php echo isset($_GET['id']) ? 'Blog Yazısını Düzenle' : 'Yeni Blog Yazısı Ekle'; ?></h6>
			<a href="blog_posts.php" class="btn btn-secondary btn-sm">
				<i class="fas fa-arrow-left"></i> Listeye Dön
			</a>
		</div>
		<div class="card-body">
			<form method="POST" enctype="multipart/form-data" id="blogForm">
				<div class="form-group mb-3">
					<label for="baslik" class="form-label">Başlık</label>
					<input type="text" class="form-control" id="baslik" name="baslik" value="<?php echo htmlspecialchars($blog['baslik'] ?? ''); ?>" required>
				</div>
				
				<div class="form-group mb-3">
					<label for="slug" class="form-label">Slug (URL)</label>
					<input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($blog['slug'] ?? ''); ?>" placeholder="Otomatik oluşturulur veya elle düzenleyebilirsiniz">
				</div>
				
				<div class="form-group mb-3">
					<label for="icerik" class="form-label">İçerik</label>
					<textarea id="icerik" name="icerik" class="form-control"><?php echo htmlspecialchars($blog['icerik'] ?? ''); ?></textarea>
				</div>
				
				<div class="form-group mb-3">
					<label for="kapak_foto" class="form-label">Kapak Fotoğrafı</label>
					
					<!-- Tavsiye Edilen Ölçüler Bilgisi -->
					<div class="alert alert-info py-2 px-3 mb-2" style="font-size: 13px;">
						<i class="fas fa-info-circle me-1"></i>
						<strong>Tavsiye Edilen Ölçüler:</strong> 1200 x 630 piksel (16:9 oranı) - Sosyal medya paylaşımları için ideal.
						<br><small class="text-muted">Minimum: 800 x 420 piksel | Maksimum dosya boyutu: 5MB | Desteklenen formatlar: JPG, PNG, WebP</small>
					</div>
					
				<?php if (!empty($blog['kapak_foto'])):
					// media/ prefix varsa uploads/media/, yoksa uploads/blog/
					$kapak_display = strpos($blog['kapak_foto'], 'media/') === 0
						? '../uploads/' . $blog['kapak_foto']
						: '../uploads/blog/' . $blog['kapak_foto'];
				?>
					<div class="mb-2 current-cover-wrapper">
						<label class="form-label text-muted small">Mevcut Kapak Fotoğrafı:</label>
						<div class="current-cover-container">
							<img src="<?php echo htmlspecialchars($kapak_display); ?>" alt="Mevcut Kapak" class="current-cover-img">
						</div>
					</div>
				<?php endif; ?>
					
				<!-- Medya Kütüphanesi Seçici -->
				<div class="d-flex gap-2 align-items-center mb-2">
					<input type="file" class="form-control" id="kapak_foto" name="kapak_foto" accept="image/jpeg,image/png,image/webp">
					<button type="button" class="btn btn-outline-primary text-nowrap" id="btn-pick-kapak" title="Medya Kütüphanesinden Seç">
						<i class="bx bx-images me-1"></i> Medya Kütüphanesi
					</button>
				</div>
				<!-- Medya kütüphanesinden seçilen dosyanın yolu (PHP'de işlenir) -->
				<input type="hidden" id="kapak_foto_media" name="kapak_foto_media" value="">

				<!-- Resim Önizleme Alanı -->
				<div id="preview-container" class="mt-3" style="display: none;">
						<label class="form-label text-muted small">Yeni Resim Önizleme:</label>
						<div class="preview-wrapper">
							<img id="preview-image" src="" alt="Önizleme">
						</div>
						<div id="image-info" class="mt-2 small text-muted"></div>
						
						<!-- Ölçeklendirme Seçenekleri -->
						<div class="resize-options mt-2 p-3 bg-light rounded">
							<div class="form-check form-switch mb-2">
								<input class="form-check-input" type="checkbox" id="auto_resize" name="auto_resize" checked>
								<label class="form-check-label" for="auto_resize">
									<i class="fas fa-compress-arrows-alt me-1"></i> Otomatik ölçeklendir (1200x630 boyutuna)
								</label>
							</div>
							<small class="text-muted d-block">
								<i class="fas fa-lightbulb text-warning"></i> Bu seçenek aktifken, yüklediğiniz resim oranı korunarak ideal boyuta ölçeklendirilir.
							</small>
						</div>
					</div>
				</div>
				
				<div class="form-group mb-3">
					<label for="kategori_id" class="form-label">Kategori</label>
					<select class="form-control" id="kategori_id" name="kategori_id" required>
						<option value="">Kategori Seçin</option>
						<?php
						$categories = $conn->query("SELECT * FROM blog_categories ORDER BY ad ASC");
						while ($category = $categories->fetch_assoc()):
						?>
							<option value="<?php echo $category['id']; ?>" <?php echo (isset($blog['kategori_id']) && $blog['kategori_id'] == $category['id']) ? 'selected' : ''; ?>>
								<?php echo htmlspecialchars($category['ad']); ?>
							</option>
						<?php endwhile; ?>
					</select>
				</div>

				<div class="form-group mb-3">
					<label for="author_id" class="form-label">
						<i class="fas fa-user-edit me-1 text-info"></i> Yazar
						<span class="badge bg-info ms-2">E-E-A-T / Schema.org</span>
					</label>
					<?php $authors_tbl = $conn->query("SHOW TABLES LIKE 'authors'"); ?>
					<?php if ($authors_tbl && $authors_tbl->num_rows > 0): ?>
						<select class="form-control" id="author_id" name="author_id">
							<option value="">— Varsayılan yazar (otomatik) —</option>
							<?php
							$author_rows = $conn->query("SELECT id, name, title, is_default FROM authors WHERE status = 1 ORDER BY is_default DESC, name ASC");
							if ($author_rows):
								while ($author_row = $author_rows->fetch_assoc()):
									$selected = (isset($blog['author_id']) && (int)$blog['author_id'] === (int)$author_row['id']) ? 'selected' : '';
							?>
								<option value="<?php echo (int)$author_row['id']; ?>" <?php echo $selected; ?>>
									<?php echo htmlspecialchars($author_row['name']); ?><?php if (!empty($author_row['title'])) echo ' — ' . htmlspecialchars($author_row['title']); ?><?php if ((int)$author_row['is_default'] === 1) echo ' (varsayılan)'; ?>
								</option>
							<?php endwhile; endif; ?>
						</select>
						<small class="text-muted">
							Yazar seçimi <code>BlogPosting.author</code> yapılı verisini etkiler.
							Boş bırakırsanız varsayılan yazar atanır.
							<a href="authors.php" target="_blank">Yazarları yönet</a>.
						</small>
					<?php else: ?>
						<div class="alert alert-warning py-2 px-3 mb-0" style="font-size:13px;">
							<i class="fas fa-info-circle me-1"></i>
							<code>authors</code> tablosu bulunamadı. <code>php scripts/llm_seo_migrate.php</code> komutunu çalıştırın.
						</div>
					<?php endif; ?>
				</div>
				
				<div class="form-group mb-3">
					<label for="etiketler" class="form-label">Etiketler (virgülle ayırın)</label>
					<input type="text" class="form-control" id="etiketler" name="etiketler" value="<?php echo htmlspecialchars($blog['etiketler'] ?? ''); ?>">
				</div>
				
				<!-- SEO Meta Bilgileri -->
				<div class="card mb-4 seo-card">
					<div class="card-header seo-card-header" data-bs-toggle="collapse" data-bs-target="#seoCollapse" style="cursor: pointer;">
						<div class="d-flex justify-content-between align-items-center">
							<h6 class="mb-0">
								<i class="fas fa-search me-2"></i>
								SEO Ayarları
								<?php 
								$seo_score = $blog['seo_score'] ?? 0;
								$score_class = $seo_score >= 80 ? 'bg-success' : ($seo_score >= 50 ? 'bg-warning' : 'bg-danger');
								?>
								<span class="badge <?php echo $score_class; ?> ms-2" id="seoScoreBadge"><?php echo $seo_score; ?>%</span>
							</h6>
							<i class="fas fa-chevron-down"></i>
						</div>
					</div>
					<div class="collapse show" id="seoCollapse">
						<div class="card-body">
							<!-- Focus Keyword -->
							<div class="form-group mb-3">
								<label for="focus_keyword" class="form-label">
									<i class="fas fa-key text-warning me-1"></i> Odak Anahtar Kelime
								</label>
								<input type="text" class="form-control" id="focus_keyword" name="focus_keyword" 
									value="<?php echo htmlspecialchars($blog['focus_keyword'] ?? ''); ?>" 
									placeholder="Ana hedef anahtar kelimeniz...">
								<small class="text-muted">İçeriğinizin optimize edileceği ana anahtar kelime</small>
							</div>
							
							<!-- SEO Title -->
							<div class="form-group mb-3">
								<label for="seo_title" class="form-label">
									<i class="fas fa-heading text-primary me-1"></i> SEO Başlığı
									<span class="badge bg-secondary ms-2">Google için</span>
								</label>
								<input type="text" class="form-control" id="seo_title" name="seo_title" maxlength="70"
									value="<?php echo htmlspecialchars($blog['seo_title'] ?? ''); ?>" 
									placeholder="Arama sonuçlarında görünecek başlık...">
								<div class="d-flex justify-content-between mt-1">
									<small class="text-muted">Boş bırakırsanız yazı başlığı kullanılır</small>
									<small><span id="seoTitleCount">0</span>/60 karakter</small>
								</div>
								<div class="progress mt-1" style="height: 4px;">
									<div class="progress-bar" id="seoTitleProgress" role="progressbar" style="width: 0%"></div>
								</div>
							</div>
							
							<!-- Meta Description -->
							<div class="form-group mb-3">
								<label for="meta_description" class="form-label">
									<i class="fas fa-align-left text-info me-1"></i> Meta Açıklaması
									<span class="badge bg-primary ms-2">SEO</span>
								</label>
								<textarea class="form-control" id="meta_description" name="meta_description" 
									rows="3" maxlength="160"
									placeholder="Google arama sonuçlarında görünecek açıklama (150-160 karakter)..."><?php echo htmlspecialchars($blog['meta_description'] ?? ''); ?></textarea>
								<div class="d-flex justify-content-between mt-1">
									<small class="text-muted">Tıklama oranınızı artıracak çekici bir açıklama yazın</small>
									<small><span id="metaDescCount">0</span>/160 karakter</small>
								</div>
								<div class="progress mt-1" style="height: 4px;">
									<div class="progress-bar" id="metaDescProgress" role="progressbar" style="width: 0%"></div>
								</div>
							</div>
							
							<!-- Meta Keywords -->
							<div class="form-group mb-3">
								<label for="meta_keywords" class="form-label">
									<i class="fas fa-tags text-success me-1"></i> Meta Anahtar Kelimeler
								</label>
								<input type="text" class="form-control" id="meta_keywords" name="meta_keywords" 
									value="<?php echo htmlspecialchars($blog['meta_keywords'] ?? ''); ?>" 
									placeholder="kelime1, kelime2, kelime3...">
								<small class="text-muted">Virgülle ayırarak yazın (maksimum 8-10 kelime önerilir)</small>
							</div>
							
							<!-- Open Graph Ayarları -->
							<div class="border rounded p-3 mb-3 bg-light">
								<h6 class="mb-3"><i class="fab fa-facebook text-primary me-2"></i>Sosyal Medya Paylaşım Ayarları (Open Graph)</h6>
								
								<div class="form-group mb-3">
									<label for="og_title" class="form-label">OG Başlık</label>
									<input type="text" class="form-control" id="og_title" name="og_title" maxlength="95"
										value="<?php echo htmlspecialchars($blog['og_title'] ?? ''); ?>" 
										placeholder="Sosyal medyada görünecek başlık...">
									<small class="text-muted">Boş bırakırsanız SEO başlığı kullanılır</small>
								</div>
								
								<div class="form-group mb-3">
									<label for="og_description" class="form-label">OG Açıklama</label>
									<textarea class="form-control" id="og_description" name="og_description" 
										rows="2" maxlength="200"
										placeholder="Sosyal medyada görünecek açıklama..."><?php echo htmlspecialchars($blog['og_description'] ?? ''); ?></textarea>
									<small class="text-muted">Boş bırakırsanız meta açıklaması kullanılır</small>
								</div>
								
								<div class="form-group mb-0">
									<label for="og_image" class="form-label">OG Görsel URL</label>
									<input type="text" class="form-control" id="og_image" name="og_image" 
										value="<?php echo htmlspecialchars($blog['og_image'] ?? ''); ?>" 
										placeholder="https://...">
									<small class="text-muted">Boş bırakırsanız kapak fotoğrafı kullanılır (1200x630 önerilir)</small>
								</div>
							</div>
							
							<!-- Canonical URL -->
							<div class="form-group mb-3">
								<label for="canonical_url" class="form-label">
									<i class="fas fa-link text-secondary me-1"></i> Canonical URL
								</label>
								<input type="url" class="form-control" id="canonical_url" name="canonical_url" 
									value="<?php echo htmlspecialchars($blog['canonical_url'] ?? ''); ?>" 
									placeholder="https://...">
								<small class="text-muted">Yinelenen içerik için orijinal URL (genellikle boş bırakılır)</small>
							</div>
							
							<!-- SEO Önizleme -->
							<div class="seo-preview-box mt-4">
								<h6 class="mb-3"><i class="fab fa-google text-danger me-2"></i>Google Arama Önizlemesi</h6>
								<div class="google-preview">
									<div class="preview-title" id="previewTitle">
										<?php echo htmlspecialchars($blog['seo_title'] ?? $blog['baslik'] ?? 'Sayfa Başlığı'); ?>
									</div>
									<div class="preview-url">
										<?php echo SITE_URL; ?>/<span id="previewSlug"><?php echo htmlspecialchars($blog['slug'] ?? ''); ?></span>
									</div>
									<div class="preview-description" id="previewDescription">
										<?php echo htmlspecialchars($blog['meta_description'] ?? 'Meta açıklaması buraya gelecek...'); ?>
									</div>
								</div>
							</div>
							
							<!-- AI ile Otomatik Oluştur Butonu -->
							<div class="mt-4 pt-3 border-top">
								<button type="button" class="btn btn-outline-primary" id="generateSeoBtn">
									<i class="fas fa-magic me-2"></i>AI ile SEO İçeriği Oluştur
								</button>
								<button type="button" class="btn btn-outline-secondary ms-2" id="analyzeSeoBtn">
									<i class="fas fa-chart-bar me-2"></i>SEO Analizi Yap
								</button>
							</div>
						</div>
					</div>
				</div>
				
				<div class="form-group mb-3">
					<label for="durum" class="form-label fw-semibold">Yayın Durumu</label>
					<?php $__cur_durum = isset($blog['durum']) ? (int)$blog['durum'] : MYNAK_BLOG_STATUS_DRAFT; ?>
					<select id="durum" name="durum" class="form-select">
						<option value="0" <?php echo $__cur_durum === MYNAK_BLOG_STATUS_DRAFT ? 'selected' : ''; ?>>Taslak</option>
						<option value="1" <?php echo $__cur_durum === MYNAK_BLOG_STATUS_EDITOR_QUEUE ? 'selected' : ''; ?>>İnceleme bekliyor</option>
						<option value="2" <?php echo $__cur_durum === MYNAK_BLOG_STATUS_REVISION ? 'selected' : ''; ?>>Düzeltme gerekli</option>
						<option value="3" <?php echo $__cur_durum === MYNAK_BLOG_STATUS_PUBLISHED ? 'selected' : ''; ?>>Yayında</option>
					</select>
					<?php if (!empty($blog['id']) && in_array($__cur_durum, [1, 2, 3], true) && !empty($blog['is_ai_generated'])): ?>
					<button type="button" class="btn btn-primary btn-sm mt-2 w-100" id="btn-rewrite-send-edit" data-id="<?php echo (int)$blog['id']; ?>">
						<i class="bx bx-refresh"></i> Tekrar yaz ve editöre al
					</button>
					<div id="rewrite-send-edit-status" class="small text-muted mt-1"></div>
					<?php endif; ?>
					<small class="text-muted d-block mt-1">
						Sadece “Yayında” (3) yazılar ziyaretçilere, site haritalarına ve BlogPosting şemasına açıktır.
					</small>
				</div>
				
				<div class="d-flex gap-2">
					<button type="submit" class="btn btn-primary">
						<i class="fas fa-save"></i> Kaydet
					</button>
					<a href="blog_posts.php" class="btn btn-secondary">
						<i class="fas fa-times"></i> İptal
					</a>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- TinyMCE -->
<script src="https://cdn.tiny.cloud/1/4n94ins65nytwfoc5usihu3atd4bq7xoye0tou5lng48xawf/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script src="assets/js/tinymce-config.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
	tinymce.init(TinyMCEConfig.blogConfig());
	
	// SEO Karakter Sayaçları ve Önizleme
	const seoTitleInput = document.getElementById('seo_title');
	const metaDescInput = document.getElementById('meta_description');
	const baslikInput = document.getElementById('baslik');
	const slugInput = document.getElementById('slug');
	const focusKeywordInput = document.getElementById('focus_keyword');
	
	const seoTitleCount = document.getElementById('seoTitleCount');
	const metaDescCount = document.getElementById('metaDescCount');
	const seoTitleProgress = document.getElementById('seoTitleProgress');
	const metaDescProgress = document.getElementById('metaDescProgress');
	
	const previewTitle = document.getElementById('previewTitle');
	const previewSlug = document.getElementById('previewSlug');
	const previewDescription = document.getElementById('previewDescription');
	
	// SEO Title Counter & Preview
	function updateSeoTitle() {
		const length = seoTitleInput.value.length;
		const displayTitle = seoTitleInput.value || baslikInput.value || 'Sayfa Başlığı';
		
		seoTitleCount.textContent = length;
		previewTitle.textContent = displayTitle;
		
		// Progress bar
		const percent = Math.min((length / 60) * 100, 100);
		seoTitleProgress.style.width = percent + '%';
		
		if (length === 0) {
			seoTitleProgress.className = 'progress-bar bg-secondary';
		} else if (length >= 30 && length <= 60) {
			seoTitleProgress.className = 'progress-bar bg-success';
		} else if (length < 30) {
			seoTitleProgress.className = 'progress-bar bg-warning';
		} else {
			seoTitleProgress.className = 'progress-bar bg-danger';
		}
	}
	
	// Meta Description Counter & Preview
	function updateMetaDesc() {
		const length = metaDescInput.value.length;
		metaDescCount.textContent = length;
		previewDescription.textContent = metaDescInput.value || 'Meta açıklaması buraya gelecek...';
		
		// Progress bar
		const percent = Math.min((length / 160) * 100, 100);
		metaDescProgress.style.width = percent + '%';
		
		if (length === 0) {
			metaDescProgress.className = 'progress-bar bg-secondary';
		} else if (length >= 120 && length <= 160) {
			metaDescProgress.className = 'progress-bar bg-success';
		} else if (length >= 80 && length < 120) {
			metaDescProgress.className = 'progress-bar bg-warning';
		} else if (length > 160) {
			metaDescProgress.className = 'progress-bar bg-danger';
		} else {
			metaDescProgress.className = 'progress-bar bg-warning';
		}
	}
	
	// Slug Preview Update
	function updateSlugPreview() {
		previewSlug.textContent = slugInput.value || 'sayfa-url';
	}
	
	// Başlık değiştiğinde SEO önizlemesini güncelle (eğer SEO title boşsa)
	function updateTitlePreview() {
		if (!seoTitleInput.value) {
			previewTitle.textContent = baslikInput.value || 'Sayfa Başlığı';
		}
	}
	
	// Event Listeners
	if (seoTitleInput) {
		seoTitleInput.addEventListener('input', updateSeoTitle);
		updateSeoTitle();
	}
	if (metaDescInput) {
		metaDescInput.addEventListener('input', updateMetaDesc);
		updateMetaDesc();
	}
	if (slugInput) {
		slugInput.addEventListener('input', updateSlugPreview);
		updateSlugPreview();
	}
	if (baslikInput) {
		baslikInput.addEventListener('input', updateTitlePreview);
	}
	
	// AI ile SEO Oluştur butonu
	const generateSeoBtn = document.getElementById('generateSeoBtn');
	if (generateSeoBtn) {
		generateSeoBtn.addEventListener('click', function() {
			const title = baslikInput.value;
			const content = tinymce.get('icerik') ? tinymce.get('icerik').getContent({format: 'text'}) : '';
			const focusKeyword = focusKeywordInput ? focusKeywordInput.value : '';
			
			if (!title) {
				alert('Lütfen önce bir başlık girin.');
				return;
			}
			
			generateSeoBtn.disabled = true;
			generateSeoBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Oluşturuluyor...';
			
			fetch('ajax/generate_seo_content.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({
					title: title,
					content: content.substring(0, 2000),
					focus_keyword: focusKeyword,
					type: 'blog'
				})
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					if (data.seo_title) seoTitleInput.value = data.seo_title;
					if (data.meta_description) metaDescInput.value = data.meta_description;
					if (data.meta_keywords) document.getElementById('meta_keywords').value = data.meta_keywords;
					if (data.og_title) document.getElementById('og_title').value = data.og_title;
					if (data.og_description) document.getElementById('og_description').value = data.og_description;
					
					// Güncellemeleri tetikle
					updateSeoTitle();
					updateMetaDesc();
					
					alert('SEO içerikleri başarıyla oluşturuldu!');
				} else {
					alert('Hata: ' + (data.message || 'SEO içeriği oluşturulamadı.'));
				}
			})
			.catch(error => {
				console.error('Error:', error);
				alert('Bir hata oluştu. Lütfen tekrar deneyin.');
			})
			.finally(() => {
				generateSeoBtn.disabled = false;
				generateSeoBtn.innerHTML = '<i class="fas fa-magic me-2"></i>AI ile SEO İçeriği Oluştur';
			});
		});
	}
	
	// SEO Analizi butonu
	const analyzeSeoBtn = document.getElementById('analyzeSeoBtn');
	if (analyzeSeoBtn) {
		analyzeSeoBtn.addEventListener('click', function() {
			const title = baslikInput.value;
			const seoTitle = seoTitleInput.value;
			const metaDesc = metaDescInput.value;
			const focusKeyword = focusKeywordInput ? focusKeywordInput.value : '';
			const content = tinymce.get('icerik') ? tinymce.get('icerik').getContent({format: 'text'}) : '';
			
			// Basit SEO analizi
			let issues = [];
			let score = 0;
			
			// Başlık kontrolü
			if (!title) {
				issues.push('❌ Başlık eksik');
			} else {
				const titleLen = title.length;
				if (titleLen < 30) issues.push('⚠️ Başlık çok kısa (' + titleLen + ' karakter)');
				else if (titleLen > 60) issues.push('⚠️ Başlık çok uzun (' + titleLen + ' karakter)');
				else { issues.push('✅ Başlık uzunluğu ideal (' + titleLen + ' karakter)'); score += 15; }
			}
			
			// Meta description kontrolü
			if (!metaDesc) {
				issues.push('❌ Meta açıklaması eksik');
			} else {
				const descLen = metaDesc.length;
				if (descLen < 120) issues.push('⚠️ Meta açıklaması kısa (' + descLen + ' karakter)');
				else if (descLen > 160) issues.push('⚠️ Meta açıklaması uzun (' + descLen + ' karakter)');
				else { issues.push('✅ Meta açıklaması ideal (' + descLen + ' karakter)'); score += 20; }
			}
			
			// Focus keyword kontrolü
			if (!focusKeyword) {
				issues.push('⚠️ Odak anahtar kelime belirlenmemiş');
			} else {
				score += 5;
				if (title.toLowerCase().includes(focusKeyword.toLowerCase())) {
					issues.push('✅ Odak kelime başlıkta var');
					score += 10;
				} else {
					issues.push('⚠️ Odak kelime başlıkta yok');
				}
				if (content.toLowerCase().includes(focusKeyword.toLowerCase())) {
					issues.push('✅ Odak kelime içerikte var');
					score += 10;
				} else {
					issues.push('⚠️ Odak kelime içerikte yok');
				}
			}
			
			// İçerik uzunluğu
			const wordCount = content.split(/\s+/).filter(w => w.length > 0).length;
			if (wordCount < 300) {
				issues.push('⚠️ İçerik kısa (' + wordCount + ' kelime) - Min. 300 kelime önerilir');
			} else {
				issues.push('✅ İçerik uzunluğu yeterli (' + wordCount + ' kelime)');
				score += 20;
			}
			
			// Sonucu göster
			const resultHtml = `
				<div class="p-3">
					<h5>SEO Analiz Sonucu</h5>
					<div class="mb-3">
						<strong>Tahmini Skor:</strong> 
						<span class="badge ${score >= 60 ? 'bg-success' : (score >= 40 ? 'bg-warning' : 'bg-danger')}">${score}%</span>
					</div>
					<ul class="list-unstyled mb-0">
						${issues.map(i => '<li>' + i + '</li>').join('')}
					</ul>
				</div>
			`;
			
			// Modal veya alert ile göster
			if (typeof bootstrap !== 'undefined') {
				// Bootstrap modal varsa
				let modal = document.getElementById('seoAnalysisModal');
				if (!modal) {
					modal = document.createElement('div');
					modal.id = 'seoAnalysisModal';
					modal.className = 'modal fade';
					modal.innerHTML = `
						<div class="modal-dialog">
							<div class="modal-content">
								<div class="modal-header">
									<h5 class="modal-title"><i class="fas fa-chart-bar me-2"></i>SEO Analizi</h5>
									<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
								</div>
								<div class="modal-body" id="seoAnalysisContent"></div>
								<div class="modal-footer">
									<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
								</div>
							</div>
						</div>
					`;
					document.body.appendChild(modal);
				}
				document.getElementById('seoAnalysisContent').innerHTML = resultHtml;
				new bootstrap.Modal(modal).show();
			} else {
				alert(issues.join('\n'));
			}
		});
	}
	
	// Kapak fotoğrafı önizleme ve boyut kontrolü
	const kapakInput = document.getElementById('kapak_foto');
	const previewContainer = document.getElementById('preview-container');
	const previewImage = document.getElementById('preview-image');
	const imageInfo = document.getElementById('image-info');
	
	// Tavsiye edilen boyutlar
	const RECOMMENDED_WIDTH = 1200;
	const RECOMMENDED_HEIGHT = 630;
	const MIN_WIDTH = 800;
	const MIN_HEIGHT = 420;
	const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
	
	kapakInput.addEventListener('change', function(e) {
		const file = e.target.files[0];
		
		if (file) {
			// Dosya boyutu kontrolü
			if (file.size > MAX_FILE_SIZE) {
				alert('Dosya boyutu çok büyük! Maksimum 5MB yükleyebilirsiniz.');
				kapakInput.value = '';
				previewContainer.style.display = 'none';
				return;
			}
			
			// Dosya tipini kontrol et
			const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
			if (!validTypes.includes(file.type)) {
				alert('Geçersiz dosya formatı! Sadece JPG, PNG ve WebP dosyaları yükleyebilirsiniz.');
				kapakInput.value = '';
				previewContainer.style.display = 'none';
				return;
			}
			
			const reader = new FileReader();
			reader.onload = function(event) {
				const img = new Image();
				img.onload = function() {
					// Önizleme göster
					previewImage.src = event.target.result;
					previewContainer.style.display = 'block';
					
					// Boyut bilgilerini göster
					const width = img.width;
					const height = img.height;
					const ratio = (width / height).toFixed(2);
					const fileSize = (file.size / 1024).toFixed(1);
					const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
					
					let sizeClass = 'size-info';
					let sizeMessage = '';
					let resizeAdvice = '';
					
					// Boyut değerlendirmesi
					if (width >= RECOMMENDED_WIDTH && height >= RECOMMENDED_HEIGHT) {
						sizeClass = 'size-ok';
						sizeMessage = '<i class="fas fa-check-circle"></i> Mükemmel! Resim boyutu ideal.';
					} else if (width >= MIN_WIDTH && height >= MIN_HEIGHT) {
						sizeClass = 'size-info';
						sizeMessage = '<i class="fas fa-info-circle"></i> Kabul edilebilir boyut, ancak daha büyük resim tavsiye edilir.';
					} else {
						sizeClass = 'size-warning';
						sizeMessage = '<i class="fas fa-exclamation-triangle"></i> Resim boyutu çok küçük! Minimum ' + MIN_WIDTH + 'x' + MIN_HEIGHT + ' piksel olmalı.';
					}
					
					// Oran kontrolü (16:9 = 1.78)
					const idealRatio = 1.9; // 1200/630 = 1.9
					const ratioDiff = Math.abs(ratio - idealRatio);
					let ratioMessage = '';
					
					if (ratioDiff < 0.1) {
						ratioMessage = '<span class="size-ok"><i class="fas fa-check"></i> Oran ideal (16:9)</span>';
					} else if (height > width) {
						ratioMessage = '<span class="size-warning"><i class="fas fa-arrows-alt-v"></i> Dikey resim - Yatay resim önerilir</span>';
					} else if (ratio > 2.5) {
						ratioMessage = '<span class="size-info"><i class="fas fa-arrows-alt-h"></i> Çok geniş oran</span>';
					} else {
						ratioMessage = '<span class="size-info"><i class="fas fa-crop"></i> Farklı oran (' + ratio + ':1)</span>';
					}
					
					imageInfo.innerHTML = `
						<div class="row">
							<div class="col-md-6">
								<strong>Resim Bilgileri:</strong><br>
								📐 Boyut: <strong>${width} x ${height}</strong> piksel<br>
								📊 Oran: ${ratioMessage}<br>
								📁 Dosya: ${fileSizeMB} MB (${fileSize} KB)
							</div>
							<div class="col-md-6">
								<strong>Değerlendirme:</strong><br>
								<span class="${sizeClass}">${sizeMessage}</span>
							</div>
						</div>
					`;
				};
				img.src = event.target.result;
			};
			reader.readAsDataURL(file);
		} else {
			previewContainer.style.display = 'none';
		}
	});
});
</script>

<style>
.tox-tinymce {
	border: 1px solid #d2d6de;
	border-radius: 0.25rem;
}
.form-label {
	font-weight: 500;
}

/* SEO Card Styles */
.seo-card {
	border: 1px solid #e3f2fd;
	background: #f8fbff;
}
.seo-card-header {
	background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
	border-bottom: 1px solid #90caf9;
	transition: background 0.3s ease;
}
.seo-card-header:hover {
	background: linear-gradient(135deg, #bbdefb 0%, #e1bee7 100%);
}
.seo-card-header .fa-chevron-down {
	transition: transform 0.3s ease;
}
.seo-card-header.collapsed .fa-chevron-down {
	transform: rotate(-90deg);
}

/* Google Preview Box */
.seo-preview-box {
	background: #fff;
	border: 1px solid #dfe1e5;
	border-radius: 8px;
	padding: 20px;
}
.google-preview {
	font-family: Arial, sans-serif;
}
.google-preview .preview-title {
	color: #1a0dab;
	font-size: 20px;
	line-height: 1.3;
	margin-bottom: 3px;
	cursor: pointer;
	text-decoration: none;
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	overflow: hidden;
}
.google-preview .preview-title:hover {
	text-decoration: underline;
}
.google-preview .preview-url {
	color: #006621;
	font-size: 14px;
	line-height: 1.3;
	margin-bottom: 3px;
}
.google-preview .preview-description {
	color: #545454;
	font-size: 14px;
	line-height: 1.58;
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	overflow: hidden;
}

/* Progress Bar Colors */
.progress-bar.bg-success { background-color: #198754 !important; }
.progress-bar.bg-warning { background-color: #ffc107 !important; }
.progress-bar.bg-danger { background-color: #dc3545 !important; }

/* Kapak Fotoğrafı Stilleri */
.current-cover-container {
	max-width: 400px;
	background: #f8f9fa;
	border: 2px dashed #dee2e6;
	border-radius: 8px;
	padding: 10px;
	text-align: center;
}
.current-cover-img {
	max-width: 100%;
	max-height: 250px;
	object-fit: contain;
	border-radius: 6px;
}

/* Önizleme Stilleri */
.preview-wrapper {
	max-width: 400px;
	background: linear-gradient(45deg, #f0f0f0 25%, transparent 25%),
				linear-gradient(-45deg, #f0f0f0 25%, transparent 25%),
				linear-gradient(45deg, transparent 75%, #f0f0f0 75%),
				linear-gradient(-45deg, transparent 75%, #f0f0f0 75%);
	background-size: 20px 20px;
	background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
	border: 2px solid #0d6efd;
	border-radius: 8px;
	padding: 10px;
	text-align: center;
}
#preview-image {
	max-width: 100%;
	max-height: 300px;
	object-fit: contain;
	border-radius: 6px;
}

/* Ölçü Uyarıları */
.size-warning {
	color: #dc3545;
	font-weight: 500;
}
.size-ok {
	color: #198754;
	font-weight: 500;
}
.size-info {
	color: #0d6efd;
}

/* Resize Options */
.resize-options {
	border: 1px solid #dee2e6;
}
</style>

<script>
// Medya Kütüphanesi — Kapak Fotoğrafı Seçici
document.getElementById('btn-pick-kapak').addEventListener('click', function() {
    MediaPicker.open(function(item) {
        // Sadece dosya adını kaydet (media/ prefix olmadan, PHP ekleyecek)
        var filename = item.filename;
        document.getElementById('kapak_foto_media').value = filename;

        // Önizleme göster
        var previewContainer = document.getElementById('preview-container');
        var previewImg = document.getElementById('preview-image');
        previewImg.src = item.url;
        previewContainer.style.display = 'block';

        // Bilgi metni
        var infoEl = document.getElementById('image-info');
        if (infoEl) {
            infoEl.innerHTML = '<span class="text-success"><i class="bx bx-check-circle me-1"></i>Medya kütüphanesinden seçildi: <strong>' + item.original_name + '</strong></span>';
        }

        // Dosya inputunu temizle (ikisi aynı anda kullanılmasın)
        document.getElementById('kapak_foto').value = '';
    });
});

// Dosya inputu kullanılırsa media seçimini temizle
document.getElementById('kapak_foto').addEventListener('change', function() {
    if (this.files.length > 0) {
        document.getElementById('kapak_foto_media').value = '';
    }
});

document.getElementById('btn-rewrite-send-edit')?.addEventListener('click', function() {
    var btn = this;
    var st = document.getElementById('rewrite-send-edit-status');
    if (!confirm('AI metni yeniden yazacak (3–8 dk). Sayfayı kapatmayın. Devam?')) return;
    st.textContent = 'Yazılıyor… 3–8 dk sürebilir';
    btn.disabled = true;
    var fd = new FormData();
    fd.append('post_id', btn.getAttribute('data-id'));
    var ctrl = new AbortController();
    var kill = setTimeout(function() { ctrl.abort(); }, 600000);
    fetch('ajax/blog_ce_rewrite_send.php', { method: 'POST', body: fd, signal: ctrl.signal })
        .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then(function(d) {
            st.textContent = d.message || (d.success ? 'Tamam' : 'Hata');
            if (d.success && d.review_url) {
                setTimeout(function() { location.href = d.review_url; }, 1500);
            }
        })
        .catch(function(e) {
            st.textContent = (e && e.name === 'AbortError')
                ? 'Zaman aşımı — hosting Proxy Timeout artırın veya tekrar deneyin.'
                : 'Bağlantı hatası';
        })
        .finally(function() { clearTimeout(kill); btn.disabled = false; });
});
</script>

<?php require_once 'includes/footer.php'; ?> 
