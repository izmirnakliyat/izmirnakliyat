<?php
$page_title = 'Dil Yönetimi';
require_once 'includes/header.php';
require_once '../config/db.php';
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Site Yönetimi / <a href="header_management.php">Header Yönetimi</a> /</span> Dil Yönetimi
    </h4>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Dil Yönetimi</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Dil yönetimi içeriğini include et
                    include 'includes/language_management.php';
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 