<?php
$page_title = 'Footer Menü Yönetimi';
require_once 'includes/header.php';
require_once '../config/db.php';
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Site Yönetimi / </span> Footer Menü Yönetimi
    </h4>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Footer Menü Kategorileri</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class='bx bx-plus'></i> Yeni Kategori Ekle
                    </button>
                </div>
                <div class="card-body">
                    <?php
                    // Footer kategori yönetimi içeriğini include et
                    include 'includes/footer_category_management.php';
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Footer Menü Öğeleri</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMenuItemModal">
                        <i class='bx bx-plus'></i> Yeni Menü Öğesi Ekle
                    </button>
                </div>
                <div class="card-body">
                    <?php
                    // Footer menü öğeleri yönetimi içeriğini include et
                    include 'includes/footer_menu_management.php';
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 