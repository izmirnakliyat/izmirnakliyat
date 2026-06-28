</div>
<!-- End of Content Wrapper -->

<!-- Modern Footer -->
<footer
    style="background: var(--bg-primary); border-top: 1px solid rgba(148, 163, 184, 0.1); padding: 1.5rem 2rem; margin-top: auto;">
    <div
        style="display: flex; justify-content: space-between; align-items: center; color: var(--text-secondary); font-size: 0.9rem;">
        <span>© <?php echo date('Y'); ?> MetropolWeb - Modern Admin Panel</span>
        <div style="display: flex; gap: 1rem;">
            <span>v2.0</span>
            <span>•</span>
            <span style="color: var(--accent-blue);">Premium Design</span>
        </div>
    </div>
</footer>

</div>
<!-- End of Main Content -->

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- TinyMCE Türkçe Dil Dosyası - Eğer TinyMCE varsa -->
<script>
    if (document.querySelector('#icerik') || document.querySelector('#content')) {
        // TinyMCE Türkçe Dil Dosyasını Yükle
        document.write('<script src="https://cdn.tiny.cloud/1/4n94ins65nytwfoc5usihu3atd4bq7xoye0tou5lng48xawf/tinymce/6/langs/tr.js"><\/script>');
    }
</script>

<!-- TinyMCE bildirimlerini gizle -->
<style>
    /* TinyMCE bildirimlerini gizleyen stiller */
    .tox-notifications-container {
        display: none !important;
    }

    .tox-notification {
        display: none !important;
    }

    .tox-statusbar__branding {
        display: none !important;
    }

    .mce-notification,
    .mce-notification-error {
        display: none !important;
    }

    /* TinyMCE yükleme bildirimlerini gizle */
    .tox-dialog__busy-spinner {
        display: none !important;
    }

    div[role='alert'] {
        display: none !important;
    }
</style>

<!-- =============================================
     MEDYA KÜTÜPHANESİ - Global Seçici Modal
     Kullanım: MediaPicker.open(callback)
     callback(item) → item.url, item.filename, item.original_name
     ============================================= -->
<div class="modal fade" id="globalMediaPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title mb-0">
                    <i class="bx bx-images me-2"></i>Medya Kütüphanesi
                </h5>
                <div class="d-flex gap-2 ms-auto me-2">
                    <!-- Modal içi yükleme butonu -->
                    <label class="btn btn-light btn-sm mb-0" style="cursor:pointer;">
                        <i class="bx bx-upload me-1"></i> Yeni Yükle
                        <input type="file" id="mp-file-input" multiple
                               accept="image/jpeg,image/png,image/webp,image/gif,image/avif"
                               style="display:none;">
                    </label>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <!-- Arama -->
                <div class="mb-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" id="mp-search" class="form-control" placeholder="Dosya adı ara...">
                        <button class="btn btn-outline-secondary" id="mp-search-btn">Ara</button>
                    </div>
                </div>

                <!-- Yükleme progress -->
                <div id="mp-upload-progress" class="mb-2" style="display:none;"></div>

                <!-- Grid -->
                <div id="mp-grid" class="row g-2">
                    <div class="col-12 text-center py-4" id="mp-loading">
                        <div class="spinner-border text-primary"></div>
                    </div>
                </div>

                <!-- Sayfalama -->
                <div id="mp-pagination" class="mt-3 d-flex justify-content-center gap-2" style="display:none !important;"></div>
            </div>
            <div class="modal-footer py-2 justify-content-between">
                <span class="text-muted small" id="mp-status">—</span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary btn-sm" id="mp-select-btn" disabled>
                        <i class="bx bx-check me-1"></i> Seç
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.mp-item { cursor: pointer; transition: all .15s; }
.mp-item:hover .mp-card { box-shadow: 0 0 0 3px #0d6efd40; transform: translateY(-2px); }
.mp-item.selected .mp-card {
    box-shadow: 0 0 0 3px #0d6efd !important;
    border-color: #0d6efd !important;
    background: #f0f4ff;
}
.mp-item.selected .mp-card::after {
    content: '✓';
    position: absolute;
    top: 4px; right: 4px;
    background: #0d6efd;
    color: #fff;
    width: 22px; height: 22px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: bold;
    z-index: 1;
}
.mp-card { position: relative; transition: all .15s; border: 2px solid transparent; }
.mp-thumb { height: 110px; overflow: hidden; background: #f8f9fa; border-radius: .25rem; }
.mp-thumb img { width:100%; height:100%; object-fit:cover; }
</style>

<script>
var MediaPicker = (function() {
    var _callback  = null;
    var _selected  = null;
    var _page      = 1;
    var _query     = '';
    var SITE_URL_MP = <?= json_encode(defined('SITE_URL') ? SITE_URL : '') ?>;

    function open(callback) {
        _callback = callback;
        _selected = null;
        _page     = 1;
        _query    = '';
        $('#mp-search').val('');
        $('#mp-select-btn').prop('disabled', true);
        loadMedia();
        var modal = new bootstrap.Modal(document.getElementById('globalMediaPickerModal'));
        modal.show();
    }

    function loadMedia(page, q) {
        page = page || _page;
        q    = (q !== undefined) ? q : _query;
        _page  = page;
        _query = q;

        $('#mp-grid').html('<div class="col-12 text-center py-4"><div class="spinner-border text-primary"></div></div>');
        $('#mp-pagination').hide();

        $.get('ajax/media_list.php', { page: page, q: q, per_page: 36 }, function(data) {
            renderGrid(data.items || []);
            renderPagination(data.total_pages, data.page);
            $('#mp-status').text((data.total || 0) + ' dosya');
        }, 'json').fail(function() {
            $('#mp-grid').html('<div class="col-12 text-center text-danger py-3">Yükleme hatası.</div>');
        });
    }

    function renderGrid(items) {
        if (!items.length) {
            $('#mp-grid').html('<div class="col-12 text-center py-4 text-muted"><i class="bx bx-images" style="font-size:3rem;"></i><p class="mt-2">Medya bulunamadı.</p><p class="small">Yükle butonundan yeni resim ekleyin.</p></div>');
            return;
        }
        var html = '';
        items.forEach(function(item) {
            var name = (item.original_name || item.filename || '').substring(0, 20);
            html += '<div class="col-6 col-sm-4 col-md-3 col-lg-2 mp-item" data-id="' + item.id + '" data-url="' + item.url + '" data-filename="' + item.filename + '" data-orig="' + (item.original_name || '') + '">' +
                '<div class="card mp-card h-100">' +
                '<div class="mp-thumb"><img src="' + item.url + '" alt="' + (item.alt_text || '') + '" loading="lazy" onerror="this.parentElement.innerHTML=\'<div class=\\\'d-flex align-items-center justify-content-center h-100\\\'><i class=\\\'bx bx-image-alt fs-2 text-muted\\\'></i></div>\'"></div>' +
                '<div class="card-body p-1"><p class="mb-0 text-truncate" style="font-size:10px;" title="' + (item.original_name || '') + '">' + name + '</p>' +
                '<p class="mb-0 text-muted" style="font-size:9px;">' + (item.file_size_str || '') + '</p></div></div></div>';
        });
        $('#mp-grid').html(html);

        // Önceki seçimi restore et
        if (_selected) {
            $('.mp-item[data-id="' + _selected.id + '"]').addClass('selected');
        }

        // Click handler
        $('#mp-grid').off('click.mp').on('click.mp', '.mp-item', function() {
            $('.mp-item').removeClass('selected');
            $(this).addClass('selected');
            _selected = {
                id:            $(this).data('id'),
                url:           $(this).data('url'),
                filename:      $(this).data('filename'),
                original_name: $(this).data('orig'),
            };
            $('#mp-select-btn').prop('disabled', false);
        });
    }

    function renderPagination(total_pages, current) {
        if (total_pages <= 1) { $('#mp-pagination').hide(); return; }
        var html = '';
        var start = Math.max(1, current - 3);
        var end   = Math.min(total_pages, current + 3);
        if (current > 1)      html += '<button class="btn btn-sm btn-outline-secondary mp-page" data-page="' + (current-1) + '"><i class="bx bx-chevron-left"></i></button>';
        for (var i = start; i <= end; i++) {
            html += '<button class="btn btn-sm ' + (i === current ? 'btn-primary' : 'btn-outline-secondary') + ' mp-page" data-page="' + i + '">' + i + '</button>';
        }
        if (current < total_pages) html += '<button class="btn btn-sm btn-outline-secondary mp-page" data-page="' + (current+1) + '"><i class="bx bx-chevron-right"></i></button>';
        $('#mp-pagination').html(html).show();
    }

    // Sayfalama tıklamaları
    $(document).on('click', '.mp-page', function() { loadMedia(parseInt($(this).data('page'))); });

    // Arama
    $('#mp-search-btn').on('click', function() { loadMedia(1, $('#mp-search').val().trim()); });
    $('#mp-search').on('keypress', function(e) { if (e.which === 13) loadMedia(1, this.value.trim()); });

    // Seç butonu
    $('#mp-select-btn').on('click', function() {
        if (_selected && _callback) {
            _callback(_selected);
            bootstrap.Modal.getInstance(document.getElementById('globalMediaPickerModal')).hide();
        }
    });

    // Modal içi yükleme
    $('#mp-file-input').on('change', function() {
        uploadToMedia(this.files, function() { loadMedia(1); });
        this.value = '';
    });

    function uploadToMedia(files, onDone) {
        if (!files || !files.length) return;
        $('#mp-upload-progress').html('').show();
        var done = 0;
        var total = files.length;

        Array.from(files).forEach(function(file) {
            var uid  = 'mp_' + Date.now() + Math.random().toString(36).substr(2,4);
            var phtml = '<div id="' + uid + '" class="d-flex align-items-center gap-2 mb-1 small">' +
                '<span class="text-truncate flex-grow-1">' + file.name + '</span>' +
                '<div class="progress flex-grow-1" style="height:6px;min-width:80px;"><div class="progress-bar bg-primary progress-bar-animated" id="pb_' + uid + '" style="width:5%"></div></div>' +
                '<span id="st_' + uid + '" class="text-muted">...</span></div>';
            $('#mp-upload-progress').append(phtml);

            var fd = new FormData();
            fd.append('file', file);
            $.ajax({
                url: 'ajax/media_upload.php',
                type: 'POST', data: fd, processData: false, contentType: false,
                xhr: function() {
                    var x = new XMLHttpRequest();
                    x.upload.onprogress = function(e) {
                        if (e.lengthComputable) $('#pb_' + uid).css('width', Math.round(e.loaded/e.total*100) + '%');
                    };
                    return x;
                },
                success: function(r) {
                    if (r.success) {
                        $('#pb_' + uid).removeClass('bg-primary').addClass('bg-success').css('width','100%');
                        $('#st_' + uid).text('✓').addClass('text-success');
                    } else {
                        $('#pb_' + uid).addClass('bg-danger');
                        $('#st_' + uid).text('Hata').addClass('text-danger');
                    }
                },
                complete: function() {
                    done++;
                    if (done >= total && typeof onDone === 'function') {
                        setTimeout(function() {
                            $('#mp-upload-progress').slideUp(300);
                            onDone();
                        }, 800);
                    }
                }
            });
        });
    }

    return { open: open };
})();
</script>

<!-- Bootstrap core JavaScript-->
<!-- <script src="assets/vendor/jquery/jquery.min.js"></script> -->
<!-- Core JS -->
<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script> -->
<!-- <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> -->

<!-- Custom JS -->
<script>
    // Force cache clearing for ajax requests
    $.ajaxSetup({
        cache: false
    });
</script>

<!-- Core plugin JavaScript-->
<script src="assets/vendor/jquery-easing/jquery.easing.min.js"></script>

<!-- Custom scripts for all pages-->
<script src="assets/js/sb-admin-2.min.js"></script>
</body>

</html>```