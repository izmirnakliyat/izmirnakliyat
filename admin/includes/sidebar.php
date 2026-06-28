<nav class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <i class='bx bxs-dashboard'></i>
            <span><?php echo SITE_NAME; ?> Admin</span>
        </a>
    </div>
    <ul class="sidebar-nav nav flex-column">
        <?php if (hasPermission('dashboard_view')): ?>
        <li class="nav-item">
            <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? ' active' : ''; ?>" href="dashboard.php">
                <i class="bx bx-home"></i> Dashboard
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (hasPermission('slides_view')): ?>
        <li class="nav-item">
            <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'slides.php' ? ' active' : ''; ?>" href="slides.php">
                <i class="bx bx-images"></i> Slaytlar
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (hasPermission('services_view')): ?>
        <li class="nav-item">
            <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? ' active' : ''; ?>" href="services.php">
                <i class="bx bx-cube"></i> Hizmetler
            </a>
        </li>
        <?php endif; ?>
        <?php if (hasPermission('about_view')): ?>
        <li class="nav-item">
            <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'about_edit.php' ? ' active' : ''; ?>" href="about_edit.php">
                <i class="bx bx-info-circle"></i> Hakkımızda
            </a>
        </li>
        <?php endif; ?>
        <?php if (hasPermission('contact_view')): ?>
        <li class="nav-item">
            <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'contact_edit.php' ? ' active' : ''; ?>" href="contact_edit.php">
                <i class="bx bx-envelope"></i> İletişim
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (hasPermission('pages_view')): ?>
        <li class="nav-item">
            <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'pages.php' ? ' active' : ''; ?>" href="pages.php">
                <i class="bx bx-file"></i> Sayfalar
            </a>
        </li>
        <?php endif; ?>
        <!--
        <?php if (hasPermission('team_view')): ?>
        <li class="nav-item">
            <a class="nav-link<?php echo in_array(basename($_SERVER['PHP_SELF']), ['team_members.php', 'team_edit.php']) ? ' active' : ''; ?>" href="team_members.php">
                <i class="bx bx-user-circle"></i> Eğitmen Kadrosu
            </a>
        </li>
        <?php endif; ?>
        -->
        <?php if (hasPermission('gallery_view')): ?>
        <li class="nav-item">
            <a class="nav-link<?php echo in_array(basename($_SERVER['PHP_SELF']), ['gallery.php', 'gallery_edit.php']) ? ' active' : ''; ?>" href="gallery.php">
                <i class="bx bx-image-alt"></i> Fotoğraf Galerisi
            </a>
        </li>
        <?php endif; ?>

        <li class="nav-item">
            <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'media_library.php' ? ' active' : ''; ?>" href="media_library.php">
                <i class="bx bx-images" style="color:#17a2b8;"></i> <strong style="color:#17a2b8;">Medya Kütüphanesi</strong>
            </a>
        </li>
        
        <?php if (hasPermission('blog_view')): ?>
        <li class="nav-item">
            <a class="nav-link<?php echo in_array(basename($_SERVER['PHP_SELF']), ['blog_posts.php', 'blog_edit.php']) ? ' active' : ''; ?>" href="blog_posts.php">
                <i class="bx bx-news"></i> Blog Yazıları
            </a>
        </li>
        <?php
        // Editör Kuyruğu — bekleyen sayısı
        $__pending = 0;
        if (isset($conn)) {
            $__r = @$conn->query("SELECT COUNT(*) AS c FROM blog_posts WHERE durum = 1");
            if ($__r) { $__pending = (int) $__r->fetch_assoc()['c']; }
        }
        ?>
        <li class="nav-item">
            <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'blog_review.php' ? ' active' : ''; ?>" href="blog_review.php">
                <i class="bx bx-time-five" style="color: #f59f00;"></i>
                <strong style="color: #f59f00;">Editör Kuyruğu</strong>
                <?php if ($__pending > 0): ?>
                    <span class="badge bg-warning text-dark ms-1" style="font-size: 10px;"><?php echo $__pending; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'blog_bulk_refresh.php' ? ' active' : ''; ?>" href="blog_bulk_refresh.php">
                <i class="bx bx-refresh"></i> Toplu içerik yenileme
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'auto_blog_settings.php' ? ' active' : ''; ?>" href="auto_blog_settings.php">
                <i class="bx bx-bot" style="color:#6f42c1;"></i> Otomatik Blog (CE)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'content_roadmap.php' ? ' active' : ''; ?>" href="content_roadmap.php">
                <i class="bx bx-map" style="color:#20c997;"></i> İçerik Roadmap
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'ce_stabilization.php' ? ' active' : ''; ?>" href="ce_stabilization.php">
                <i class="bx bx-shield-quarter" style="color:#0d6efd;"></i> CE Stabilizasyon
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (hasPermission('blog_categories_view')): ?>
        <li class="nav-item">
            <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'blog_categories.php' ? ' active' : ''; ?>" href="blog_categories.php">
                <i class="bx bx-category"></i> Blog Kategorileri
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'authors.php' ? ' active' : ''; ?>" href="authors.php">
                <i class="bx bx-user-pin"></i> Yazarlar
                <span class="badge bg-info ms-1" style="font-size:9px;">E-E-A-T</span>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (hasPermission('admin_view')): ?>
        <li class="nav-item">
            <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'auto_blog.php' ? ' active' : ''; ?>" href="auto_blog.php">
                <i class="bx bx-bot"></i> Otomatik Blog Modülü
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (hasPermission('blog_view')): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'blog_seo.php' ? 'active' : ''; ?>" href="blog_seo.php">
                <i class='bx bx-search-alt-2' style="color: #ff6b35;"></i> <strong style="color: #ff6b35;">Blog SEO Optimizasyonu</strong>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'duplicate_titles.php' ? 'active' : ''; ?>" href="duplicate_titles.php">
                <i class='bx bx-duplicate' style="color: #e74c3c;"></i> <strong style="color: #e74c3c;">Kopya Başlık Düzeltici</strong>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'clean_code_fences.php' ? 'active' : ''; ?>" href="clean_code_fences.php">
                <i class='bx bx-code-block' style="color: #8e44ad;"></i> <strong style="color: #8e44ad;">Kod Bloğu Temizleyici</strong>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'humanize_all.php' ? 'active' : ''; ?>" href="humanize_all.php">
                <i class='bx bx-user-voice' style="color: #e67e22;"></i> <strong style="color: #e67e22;">AI İnsanlaştırma</strong>
            </a>
        </li>
        <?php endif; ?>


        
        

        <?php if (hasPermission('settings_view')): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'homepage_content.php' ? 'active' : ''; ?>" href="homepage_content.php">
                <i class='bx bx-edit-alt'></i> Ana Sayfa İçerikleri
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (hasPermission('settings_view')): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'homepage_sections.php' ? 'active' : ''; ?>" href="homepage_sections.php">
                <i class='bx bx-grid-alt'></i> Ana Sayfa Bölümleri
            </a>
        </li>
        <?php endif; ?>
        

        
        <?php if (hasPermission('settings_view')): ?>
        <li class="nav-item">
            <a class="nav-link<?php echo in_array(basename($_SERVER['PHP_SELF']), ['testimonials.php', 'testimonial_edit.php']) ? ' active' : ''; ?>" href="testimonials.php">
                <i class='bx bx-message-square-dots'></i> Müşteri Yorumları
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'google_reviews.php' ? ' active' : ''; ?>" href="google_reviews.php">
                <i class='bx bx-map' style="color: #4285F4;"></i> <span style="color: #4285F4;">Google Yorumlar</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'gbp_gonderi_rehberi.php' ? ' active' : ''; ?>" href="gbp_gonderi_rehberi.php">
                <i class='bx bx-spreadsheet' style="color: #0f9d58;"></i> <span>GBP gönderi rehberi</span>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (hasPermission('settings_view')): ?>
        <li class="nav-item">
            <a class="nav-link<?php echo in_array(basename($_SERVER['PHP_SELF']), ['sponsors.php', 'sponsor_edit.php']) ? ' active' : ''; ?>" href="sponsors.php">
                <i class='bx bx-briefcase'></i> Referans/Sponsor Resimleri
            </a>
        </li>
        <?php endif; ?>
        

        
        <li class="nav-item">
            <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'welcome_popup.php' ? ' active' : ''; ?>" href="welcome_popup.php">
                <i class="bx bx-window-open"></i> Hoş Geldin Popup
            </a>
        </li>
        
        <?php if (hasPermission('seo_view')): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'seo_detector.php' ? 'active' : ''; ?>" href="seo_detector.php">
                <i class='bx bx-search-alt-2' style="color: #e74c3c;"></i> <strong style="color: #e74c3c;">SEO Detector</strong>
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'seo_management.php' ? 'active' : ''; ?>" href="seo_management.php">
                <i class='bx bx-line-chart'></i> SEO Yönetimi
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'rich_snippets.php' ? 'active' : ''; ?>" href="rich_snippets.php">
                <i class='bx bx-code-alt'></i> Rich Snippets
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'auto_rich_snippets.php' ? 'active' : ''; ?>" href="auto_rich_snippets.php">
                <i class='bx bx-magic-wand' style="color:#198754;"></i> <strong style="color:#198754;">Otomatik Rich Snippet</strong>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'social_media_tags.php' ? 'active' : ''; ?>" href="social_media_tags.php">
                <i class='bx bxl-facebook-square'></i> Sosyal Medya Etiketleri
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'seo_audit.php' ? 'active' : ''; ?>" href="seo_audit.php">
                <i class='bx bx-search-alt'></i> SEO Audit
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'internal_linking.php' ? 'active' : ''; ?>" href="internal_linking.php">
                <i class='bx bx-link' style="color: #3498db;"></i> <span style="color: #3498db;">Internal Linking</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'internal_link_anchors.php' ? 'active' : ''; ?>" href="internal_link_anchors.php">
                <i class='bx bx-purchase-tag' style="color: #3498db;"></i> <span style="color: #3498db;">Anchor Havuzu</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'local_cluster_coverage.php' ? 'active' : ''; ?>" href="local_cluster_coverage.php">
                <i class='bx bx-map' style="color: #27ae60;"></i> <span style="color: #27ae60;">İlçe Kapsama</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'gsc_low_ctr_report.php' ? 'active' : ''; ?>" href="gsc_low_ctr_report.php">
                <i class='bx bx-bar-chart-alt-2' style="color: #8e44ad;"></i> <span style="color: #8e44ad;">GSC Düşük CTR</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'blog_health_report.php' ? 'active' : ''; ?>" href="blog_health_report.php">
                <i class='bx bx-clipboard' style="color: #16a085;"></i> <span style="color: #16a085;">Blog Sağlığı</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'broken_links.php' ? 'active' : ''; ?>" href="broken_links.php">
                <i class='bx bx-unlink' style="color: #e74c3c;"></i> <span style="color: #e74c3c;">Kırık Link Tarayıcı</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'indexnow.php' ? 'active' : ''; ?>" href="indexnow.php">
                <i class='bx bx-send' style="color: #e67e22;"></i> <span style="color: #e67e22;"><strong>IndexNow</strong></span>
            </a>
        </li>
        
        <?php endif; ?>
        
        <?php if (hasPermission('admin_view')): ?>
        <li class="nav-item">
            <a class="nav-link<?php echo in_array(basename($_SERVER['PHP_SELF']), ['modules_gallery_slider.php', 'modules_html_block.php', 'modules_blog_block.php', 'modules_contact_block.php']) ? ' active' : ''; ?> dropdown-toggle" href="#modulesSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['modules_gallery_slider.php', 'modules_html_block.php', 'modules_blog_block.php', 'modules_contact_block.php']) ? 'true' : 'false'; ?>">
                <i class="bx bx-extension"></i> Modüller
            </a>
            <ul class="collapse<?php echo in_array(basename($_SERVER['PHP_SELF']), ['modules_gallery_slider.php', 'modules_html_block.php', 'modules_blog_block.php', 'modules_contact_block.php']) ? ' show' : ''; ?>" id="modulesSubmenu" style="list-style: none; padding-left: 30px;">
                <li class="nav-item">
                    <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'modules_gallery_slider.php' ? ' active' : ''; ?>" href="modules_gallery_slider.php">
                        <i class="bx bx-images"></i> Galeri Slider Blok
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'modules_html_block.php' ? ' active' : ''; ?>" href="modules_html_block.php">
                        <i class="bx bx-code-block"></i> HTML Blok
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'modules_blog_block.php' ? ' active' : ''; ?>" href="modules_blog_block.php">
                        <i class="bx bx-news"></i> Blog Blok
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'modules_contact_block.php' ? ' active' : ''; ?>" href="modules_contact_block.php">
                        <i class="bx bx-envelope"></i> İletişim Blok
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'import_wordpress.php' ? ' active' : ''; ?>" href="import_wordpress.php">
                        <i class="bx bx-upload"></i> WordPress Blog Aktarımı
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>
        
        <?php if (hasModuleAccess('header') || hasModuleAccess('menu') || hasModuleAccess('popup') || hasModuleAccess('button') || hasModuleAccess('language')): ?>
        <li class="nav-item">
            <a class="nav-link<?php echo in_array(basename($_SERVER['PHP_SELF']), ['header_management.php', 'menu_management.php', 'button_management.php', 'language_management.php', 'popups.php']) ? ' active' : ''; ?> dropdown-toggle" href="#headerSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['header_management.php', 'menu_management.php', 'button_management.php', 'language_management.php', 'popups.php']) ? 'true' : 'false'; ?>">
                <i class="bx bx-layout"></i> Header Yönetimi
            </a>
            <ul class="collapse<?php echo in_array(basename($_SERVER['PHP_SELF']), ['header_management.php', 'menu_management.php', 'button_management.php', 'language_management.php', 'popups.php']) ? ' show' : ''; ?>" id="headerSubmenu" style="list-style: none; padding-left: 30px;">
                <?php if (hasPermission('menu_view')): ?>
                <li class="nav-item">
                    <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'menu_management.php' ? ' active' : ''; ?>" href="menu_management.php">
                        <i class="bx bx-menu"></i> Menü Yönetimi
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (hasPermission('popup_view')): ?>
                <li class="nav-item">
                    <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'popups.php' ? ' active' : ''; ?>" href="popups.php">
                        <i class="bx bx-window-alt"></i> Popup Yönetimi
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (hasPermission('button_view')): ?>
                <li class="nav-item">
                    <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'button_management.php' ? ' active' : ''; ?>" href="button_management.php">
                        <i class="bx bx-pointer"></i> Buton Yönetimi
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (hasPermission('language_view')): ?>
                <li class="nav-item">
                    <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'language_management.php' ? ' active' : ''; ?>" href="language_management.php">
                        <i class="bx bx-globe"></i> Dil Yönetimi
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </li>
        <?php endif; ?>
        
        <?php if (hasPermission('footer_view')): ?>
        <li class="nav-item">
            <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'footer_management.php' ? ' active' : ''; ?>" href="footer_management.php">
                <i class="bx bx-dock-bottom"></i> Footer Menü
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (hasPermission('mobile_menu_view')): ?>
        <li class="nav-item">
            <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'mobile_bottom_menu.php' ? ' active' : ''; ?>" href="mobile_bottom_menu.php">
                <i class="bx bx-mobile-alt"></i> Mobil Alt Menü
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (hasPermission('form_view')): ?>
        <li class="nav-item">
            <a class="nav-link<?php echo in_array(basename($_SERVER['PHP_SELF']), ['form_builder.php', 'form_builder_edit.php']) ? ' active' : ''; ?> dropdown-toggle" href="#formsSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['form_builder.php', 'form_builder_edit.php', 'form_submissions.php']) ? 'true' : 'false'; ?>">
                <i class="bx bx-form"></i> Form Yönetimi
            </a>
            <ul class="collapse<?php echo in_array(basename($_SERVER['PHP_SELF']), ['form_builder.php', 'form_builder_edit.php', 'form_submissions.php']) ? ' show' : ''; ?>" id="formsSubmenu" style="list-style: none; padding-left: 30px;">
                <li class="nav-item">
                    <a class="nav-link<?php echo in_array(basename($_SERVER['PHP_SELF']), ['form_builder.php', 'form_builder_edit.php']) ? ' active' : ''; ?>" href="form_builder.php">
                        <i class="bx bx-plus-circle"></i> Form Oluşturucu
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'form_submissions.php' ? ' active' : ''; ?>" href="form_submissions.php">
                        <i class="bx bx-envelope"></i> Form Başvuruları
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>
        
        <li class="nav-item">
            <a class="nav-link<?php echo in_array(basename($_SERVER['PHP_SELF']), ['case_studies.php', 'case_study_edit.php']) ? ' active' : ''; ?>" href="case_studies.php">
                <i class="bx bx-bookmark-heart"></i> Müşteri Hikayeleri
            </a>
        </li>

        <?php if (hasPermission('settings_view')): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                <i class='bx bxs-cog'></i> Ayarlar
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (hasModuleAccess('admin') || hasModuleAccess('role')): ?>
        <li class="nav-item">
            <a class="nav-link<?php echo in_array(basename($_SERVER['PHP_SELF']), ['admin_users.php', 'admin_edit.php', 'admin_roles.php', 'role_edit.php']) ? ' active' : ''; ?> dropdown-toggle" href="#adminSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['admin_users.php', 'admin_edit.php', 'admin_roles.php', 'role_edit.php']) ? 'true' : 'false'; ?>">
                <i class="bx bx-user"></i> Admin Yönetimi
            </a>
            <ul class="collapse<?php echo in_array(basename($_SERVER['PHP_SELF']), ['admin_users.php', 'admin_edit.php', 'admin_roles.php', 'role_edit.php']) ? ' show' : ''; ?>" id="adminSubmenu" style="list-style: none; padding-left: 30px;">
                <?php if (hasPermission('admin_view')): ?>
                <li class="nav-item">
                    <a class="nav-link<?php echo in_array(basename($_SERVER['PHP_SELF']), ['admin_users.php', 'admin_edit.php']) ? ' active' : ''; ?>" href="admin_users.php">
                        <i class="bx bx-user-plus"></i> Admin Kullanıcıları
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (hasPermission('role_view')): ?>
                <li class="nav-item">
                    <a class="nav-link<?php echo in_array(basename($_SERVER['PHP_SELF']), ['admin_roles.php', 'role_edit.php']) ? ' active' : ''; ?>" href="admin_roles.php">
                        <i class="bx bx-key"></i> Rol Yönetimi
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </li>
        <?php endif; ?>
        
        <li class="nav-item">
            <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) === 'db_entity_migration.php' ? ' active' : ''; ?>" href="db_entity_migration.php">
                <i class="bx bx-data"></i> DB Entity Temizliği
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'security_2fa.php' ? ' active' : ''; ?>" href="security_2fa.php">
                <i class='bx bx-shield-quarter'></i> Güvenlik (2FA)
            </a>
        </li>

        <li class="nav-item mt-auto">
            <a class="nav-link" href="logout.php">
                <i class='bx bxs-log-out'></i> Çıkış Yap
            </a>
        </li>
    </ul>
</nav> 