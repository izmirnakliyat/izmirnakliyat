<?php
$page_title = 'Sosyal Medya Etiketleri';
require_once 'includes/header.php';

// Form işleme
if ($_POST && isset($_POST['save_social_tags'])) {
    $settings_to_update = [
        'og_title' => $_POST['og_title'] ?? '',
        'og_description' => $_POST['og_description'] ?? '',
        'og_image' => $_POST['og_image'] ?? '',
        'og_url' => $_POST['og_url'] ?? '',
        'og_type' => $_POST['og_type'] ?? 'website',
        'og_site_name' => $_POST['og_site_name'] ?? '',
        'twitter_card' => $_POST['twitter_card'] ?? 'summary_large_image',
        'twitter_site' => $_POST['twitter_site'] ?? '',
        'twitter_creator' => $_POST['twitter_creator'] ?? '',
        'twitter_title' => $_POST['twitter_title'] ?? '',
        'twitter_description' => $_POST['twitter_description'] ?? '',
        'twitter_image' => $_POST['twitter_image'] ?? '',
        'facebook_app_id' => $_POST['facebook_app_id'] ?? '',
        'instagram_profile' => $_POST['instagram_profile'] ?? '',
        'linkedin_company' => $_POST['linkedin_company'] ?? ''
    ];
    
    $success = true;
    foreach ($settings_to_update as $name => $value) {
        $value = $conn->real_escape_string($value);
        
        $check_sql = "SELECT * FROM settings WHERE name = '$name'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            $update_sql = "UPDATE settings SET value = '$value' WHERE name = '$name'";
            if (!$conn->query($update_sql)) {
                $success = false;
                break;
            }
        } else {
            $insert_sql = "INSERT INTO settings (name, value) VALUES ('$name', '$value')";
            if (!$conn->query($insert_sql)) {
                $success = false;
                break;
            }
        }
    }
    
    if ($success) {
        $success_message = "Sosyal medya etiketleri başarıyla kaydedildi!";
    } else {
        $error_message = "Kaydetme sırasında bir hata oluştu!";
    }
}

// Mevcut ayarları getir
$social_settings = [];
$result = $conn->query("SELECT * FROM settings WHERE name LIKE 'og_%' OR name LIKE 'twitter_%' OR name LIKE 'facebook_%' OR name LIKE 'instagram_%' OR name LIKE 'linkedin_%'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $social_settings[$row['name']] = $row['value'];
    }
}

function generateSocialMediaTags($settings) {
    $tags = [];
    
    // Open Graph Tags
    if (!empty($settings['og_title'])) {
        $tags[] = '<meta property="og:title" content="' . htmlspecialchars($settings['og_title']) . '">';
    }
    if (!empty($settings['og_description'])) {
        $tags[] = '<meta property="og:description" content="' . htmlspecialchars($settings['og_description']) . '">';
    }
    if (!empty($settings['og_image'])) {
        $tags[] = '<meta property="og:image" content="' . htmlspecialchars($settings['og_image']) . '">';
    }
    if (!empty($settings['og_url'])) {
        $tags[] = '<meta property="og:url" content="' . htmlspecialchars($settings['og_url']) . '">';
    }
    if (!empty($settings['og_type'])) {
        $tags[] = '<meta property="og:type" content="' . htmlspecialchars($settings['og_type']) . '">';
    }
    if (!empty($settings['og_site_name'])) {
        $tags[] = '<meta property="og:site_name" content="' . htmlspecialchars($settings['og_site_name']) . '">';
    }
    
    // Twitter Cards
    if (!empty($settings['twitter_card'])) {
        $tags[] = '<meta name="twitter:card" content="' . htmlspecialchars($settings['twitter_card']) . '">';
    }
    if (!empty($settings['twitter_site'])) {
        $tags[] = '<meta name="twitter:site" content="' . htmlspecialchars($settings['twitter_site']) . '">';
    }
    if (!empty($settings['twitter_creator'])) {
        $tags[] = '<meta name="twitter:creator" content="' . htmlspecialchars($settings['twitter_creator']) . '">';
    }
    if (!empty($settings['twitter_title'])) {
        $tags[] = '<meta name="twitter:title" content="' . htmlspecialchars($settings['twitter_title']) . '">';
    }
    if (!empty($settings['twitter_description'])) {
        $tags[] = '<meta name="twitter:description" content="' . htmlspecialchars($settings['twitter_description']) . '">';
    }
    if (!empty($settings['twitter_image'])) {
        $tags[] = '<meta name="twitter:image" content="' . htmlspecialchars($settings['twitter_image']) . '">';
    }
    
    // Facebook
    if (!empty($settings['facebook_app_id'])) {
        $tags[] = '<meta property="fb:app_id" content="' . htmlspecialchars($settings['facebook_app_id']) . '">';
    }
    
    return implode("\n", $tags);
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1><i class="bx bxl-facebook-square"></i> Sosyal Medya Etiketleri</h1>
                <p class="text-muted">Open Graph, Twitter Cards ve diğer sosyal medya meta etiketleri</p>
            </div>
            
            <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="bx bx-check-circle"></i> <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="bx bx-error-circle"></i> <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-8">
                    <form method="post">
                        <!-- Open Graph Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="bx bxl-facebook"></i> Open Graph (Facebook) Ayarları</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">OG Title</label>
                                        <input type="text" name="og_title" class="form-control" 
                                               value="<?php echo htmlspecialchars($social_settings['og_title'] ?? ''); ?>" 
                                               placeholder="Sitenizin başlığı">
                                        <div class="form-text">Sosyal medyada görünecek başlık</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">OG Site Name</label>
                                        <input type="text" name="og_site_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($social_settings['og_site_name'] ?? ''); ?>" 
                                               placeholder="Site adı">
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">OG Description</label>
                                        <textarea name="og_description" class="form-control" rows="3" 
                                                  placeholder="Sitenizin açıklaması"><?php echo htmlspecialchars($social_settings['og_description'] ?? ''); ?></textarea>
                                        <div class="form-text">Maksimum 300 karakter</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">OG Image URL</label>
                                        <input type="url" name="og_image" class="form-control" 
                                               value="<?php echo htmlspecialchars($social_settings['og_image'] ?? ''); ?>" 
                                               placeholder="https://domain.com/image.jpg">
                                        <div class="form-text">Önerilen boyut: 1200x630px</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">OG URL</label>
                                        <input type="url" name="og_url" class="form-control" 
                                               value="<?php echo htmlspecialchars($social_settings['og_url'] ?? ''); ?>" 
                                               placeholder="https://domain.com">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">OG Type</label>
                                        <select name="og_type" class="form-select">
                                            <option value="website" <?php echo ($social_settings['og_type'] ?? '') == 'website' ? 'selected' : ''; ?>>Website</option>
                                            <option value="article" <?php echo ($social_settings['og_type'] ?? '') == 'article' ? 'selected' : ''; ?>>Article</option>
                                            <option value="business.business" <?php echo ($social_settings['og_type'] ?? '') == 'business.business' ? 'selected' : ''; ?>>Business</option>
                                            <option value="profile" <?php echo ($social_settings['og_type'] ?? '') == 'profile' ? 'selected' : ''; ?>>Profile</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Facebook App ID</label>
                                        <input type="text" name="facebook_app_id" class="form-control" 
                                               value="<?php echo htmlspecialchars($social_settings['facebook_app_id'] ?? ''); ?>" 
                                               placeholder="Facebook App ID">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Twitter Cards Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="bx bxl-twitter"></i> Twitter Cards Ayarları</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Twitter Card Type</label>
                                        <select name="twitter_card" class="form-select">
                                            <option value="summary" <?php echo ($social_settings['twitter_card'] ?? '') == 'summary' ? 'selected' : ''; ?>>Summary</option>
                                            <option value="summary_large_image" <?php echo ($social_settings['twitter_card'] ?? '') == 'summary_large_image' ? 'selected' : ''; ?>>Summary Large Image</option>
                                            <option value="app" <?php echo ($social_settings['twitter_card'] ?? '') == 'app' ? 'selected' : ''; ?>>App</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Twitter Site (@username)</label>
                                        <input type="text" name="twitter_site" class="form-control" 
                                               value="<?php echo htmlspecialchars($social_settings['twitter_site'] ?? ''); ?>" 
                                               placeholder="@username">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Twitter Creator (@username)</label>
                                        <input type="text" name="twitter_creator" class="form-control" 
                                               value="<?php echo htmlspecialchars($social_settings['twitter_creator'] ?? ''); ?>" 
                                               placeholder="@creator">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Twitter Title</label>
                                        <input type="text" name="twitter_title" class="form-control" 
                                               value="<?php echo htmlspecialchars($social_settings['twitter_title'] ?? ''); ?>" 
                                               placeholder="Twitter başlığı">
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Twitter Description</label>
                                        <textarea name="twitter_description" class="form-control" rows="3" 
                                                  placeholder="Twitter açıklaması"><?php echo htmlspecialchars($social_settings['twitter_description'] ?? ''); ?></textarea>
                                        <div class="form-text">Maksimum 200 karakter</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Twitter Image URL</label>
                                        <input type="url" name="twitter_image" class="form-control" 
                                               value="<?php echo htmlspecialchars($social_settings['twitter_image'] ?? ''); ?>" 
                                               placeholder="https://domain.com/image.jpg">
                                        <div class="form-text">Önerilen boyut: 1200x600px</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Other Social Media -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="bx bxl-instagram"></i> Diğer Sosyal Medya Platformları</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Instagram Profil URL</label>
                                        <input type="url" name="instagram_profile" class="form-control" 
                                               value="<?php echo htmlspecialchars($social_settings['instagram_profile'] ?? ''); ?>" 
                                               placeholder="https://instagram.com/username">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">LinkedIn Company URL</label>
                                        <input type="url" name="linkedin_company" class="form-control" 
                                               value="<?php echo htmlspecialchars($social_settings['linkedin_company'] ?? ''); ?>" 
                                               placeholder="https://linkedin.com/company/name">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <button type="submit" name="save_social_tags" class="btn btn-primary">
                                <i class="bx bx-save"></i> Ayarları Kaydet
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="col-lg-4">
                    <!-- Preview -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6><i class="bx bx-show"></i> Facebook Önizleme</h6>
                        </div>
                        <div class="card-body">
                            <div class="social-preview facebook-preview">
                                <?php if (!empty($social_settings['og_image'])): ?>
                                <img src="<?php echo htmlspecialchars($social_settings['og_image']); ?>" class="preview-image mb-2" alt="Preview">
                                <?php endif; ?>
                                <h6 class="preview-title"><?php echo htmlspecialchars($social_settings['og_title'] ?? 'Site Başlığı'); ?></h6>
                                <p class="preview-description small text-muted"><?php echo htmlspecialchars($social_settings['og_description'] ?? 'Site açıklaması'); ?></p>
                                <small class="text-muted"><?php echo parse_url($social_settings['og_url'] ?? '', PHP_URL_HOST); ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6><i class="bx bx-show"></i> Twitter Önizleme</h6>
                        </div>
                        <div class="card-body">
                            <div class="social-preview twitter-preview">
                                <?php if (!empty($social_settings['twitter_image'])): ?>
                                <img src="<?php echo htmlspecialchars($social_settings['twitter_image']); ?>" class="preview-image mb-2" alt="Preview">
                                <?php endif; ?>
                                <h6 class="preview-title"><?php echo htmlspecialchars($social_settings['twitter_title'] ?? 'Site Başlığı'); ?></h6>
                                <p class="preview-description small text-muted"><?php echo htmlspecialchars($social_settings['twitter_description'] ?? 'Site açıklaması'); ?></p>
                                <small class="text-muted"><?php echo parse_url($social_settings['og_url'] ?? '', PHP_URL_HOST); ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Generated Code -->
                    <div class="card">
                        <div class="card-header">
                            <h6><i class="bx bx-code"></i> Oluşturulan Etiketler</h6>
                        </div>
                        <div class="card-body">
                            <textarea class="form-control font-monospace" rows="12" readonly><?php echo generateSocialMediaTags($social_settings); ?></textarea>
                            <div class="form-text mt-2">Bu etiketleri sitenizin &lt;head&gt; bölümüne ekleyin.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.social-preview {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    background-color: #f8f9fa;
}

.preview-image {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 4px;
}

.preview-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 8px;
    line-height: 1.3;
}

.preview-description {
    margin-bottom: 8px;
    line-height: 1.4;
}

.facebook-preview {
    border-left: 4px solid #1877f2;
}

.twitter-preview {
    border-left: 4px solid #1da1f2;
}
</style>

<?php require_once 'includes/footer.php'; ?> 