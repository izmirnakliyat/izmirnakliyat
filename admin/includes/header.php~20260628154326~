<?php
require_once __DIR__ . '/require_admin_web.php';

require_once __DIR__ . '/permissions.php';

// Çıktı tamponu yoksa başlat
if (ob_get_level() == 0) {
    ob_start();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> Admin Panel</title>
    
    <!-- 🎨 Modern Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- 📚 Core Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- 🎨 Modern Admin Theme -->
    <link href="assets/css/modern-admin.css" rel="stylesheet">
    
    <!-- 📱 JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
  </head>
  <body data-theme="light">
      <!-- Sidebar -->
      <?php include 'sidebar.php'; ?>
  
      <!-- Main Content -->
      <div class="main-content">
          <!-- Modern Topbar -->
          <div class="topbar">
              <!-- Left Section -->
              <div class="topbar-left">
                  <!-- Mobile Menu Toggle -->
                  <button class="sidebar-toggle d-md-none me-3" id="sidebarToggle" aria-label="Toggle Menu">
                      <i class="bx bx-menu"></i>
                  </button>
                  
                  <!-- Page Title -->
                  <h1 class="page-title"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
              </div>
              
              <!-- Right Section -->
              <div class="topbar-right">
                  <!-- Site Link -->
                  <a href="../" target="_blank" class="btn btn-primary">
                      <i class="bx bx-world"></i>
                      <span class="d-none d-sm-inline">Siteye Git</span>
                  </a>
                  
                  <!-- Theme Toggle -->
                  <button class="theme-toggle" id="themeToggle" title="Tema Değiştir">
                      <i class="bx bx-moon" id="themeIcon"></i>
                  </button>
                  
                  <!-- User Info -->
                  <div class="user-info" style="display: flex; align-items: center; margin-left: 1rem; padding: 0.5rem 1rem; background: var(--bg-tertiary); border-radius: 50px; color: var(--text-primary);">
                      <i class="bx bx-user-circle" style="font-size: 1.5rem; margin-right: 0.5rem; color: var(--accent-blue);"></i>
                      <span style="font-weight: 500;"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
                  </div>
            </div>
        </div>

        <!-- Modern Content Wrapper -->
        <div class="content-wrapper fade-in-up">
            <!-- Content starts here -->

    <!-- 🎨 Modern Admin JavaScript -->
    <script>
        // 🌙 Theme Toggle Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('themeToggle');
            const themeIcon = document.getElementById('themeIcon');
            const body = document.body;
            
            // Load saved theme
            const savedTheme = localStorage.getItem('admin-theme') || 'light';
            body.setAttribute('data-theme', savedTheme);
            updateThemeIcon(savedTheme);
            
            // Theme toggle event
            themeToggle.addEventListener('click', function() {
                const currentTheme = body.getAttribute('data-theme');
                const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                
                body.setAttribute('data-theme', newTheme);
                localStorage.setItem('admin-theme', newTheme);
                updateThemeIcon(newTheme);
                
                // Smooth transition
                body.style.transition = 'all 0.3s ease';
                setTimeout(() => {
                    body.style.transition = '';
                }, 300);
            });
            
            function updateThemeIcon(theme) {
                if (theme === 'dark') {
                    themeIcon.className = 'bx bx-sun';
                } else {
                    themeIcon.className = 'bx bx-moon';
                }
            }
            
            // 📱 Mobile Sidebar Toggle
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.querySelector('.sidebar');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    sidebar.classList.toggle('active');
                });
            }
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                        sidebar.classList.remove('active');
                    }
                }
            });
            
            // ✨ Animation on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in-up');
                    }
                });
            }, observerOptions);
            
            // Observe cards and elements
            const cards = document.querySelectorAll('.card, .stat-card');
            cards.forEach(card => {
                observer.observe(card);
            });
        });
        
        // 🔄 Legacy jQuery compatibility
        $(function() {
          if (window.innerWidth <= 768) {
            $('.sidebar').removeClass('show');
          }
        });
      });
    </script>
 