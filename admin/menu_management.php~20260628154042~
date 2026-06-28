<?php
$page_title = 'Menü Yönetimi';
require_once 'includes/header.php';
require_once '../config/db.php';
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Site Yönetimi / <a href="header_management.php">Header Yönetimi</a> /</span> Menü Yönetimi
    </h4>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Menü Yönetimi</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMenuModal">
                        <i class='bx bx-plus'></i> Yeni Menü
                    </button>
                </div>
                <div class="card-body">
                    <?php
                    // Menü yönetimi içeriğini include et
                    include 'includes/menu_management.php';
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 