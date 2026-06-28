// Otomatik Blog Modülü JS — Content Engine

document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('setting-modal');
    var closeBtn = document.getElementById('close-modal-btn');
    var form = document.getElementById('auto-blog-form');
    if (!form || !modal) {
        return;
    }

    document.getElementById('add-setting-btn').onclick = function() {
        form.reset();
        form.action.value = 'add';
        form.id.value = '';
        modal.style.display = 'flex';
    };
    closeBtn.onclick = function() { modal.style.display = 'none'; };
    window.onclick = function(e) { if (e.target === modal) modal.style.display = 'none'; };

    document.querySelectorAll('.edit-setting-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            fetch('ajax/auto_blog_save.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=get&id=' + encodeURIComponent(id)
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success || !data.setting) {
                    alert('Ayar bulunamadı!');
                    return;
                }
                var s = data.setting;
                form.reset();
                form.action.value = 'edit';
                form.id.value = s.id;
                form.category_id.value = s.category_id;
                form.keywords.value = s.keywords;
                if (form.manual_command) form.manual_command.value = s.manual_command || '';
                form.min_words.value = s.min_words;
                form.max_words.value = s.max_words;
                form.post_count_per_period.value = s.post_count_per_period;
                form.period_type.value = s.period_type;
                form.post_time.value = s.post_time;
                form.active.checked = s.active == 1;
                modal.style.display = 'flex';
            });
        });
    });

    document.querySelectorAll('.delete-setting-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Silmek istediğinize emin misiniz?')) return;
            var fd = new FormData();
            fd.append('action', 'delete');
            fd.append('id', this.getAttribute('data-id'));
            fetch('ajax/auto_blog_save.php', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) location.reload();
                    else alert(data.message || 'Silme hatası!');
                });
        });
    });

    document.querySelectorAll('.generate-now-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            if (!confirm('Content Engine ile yazı üretilsin mi? (QC gate uygulanır)')) return;
            btn.disabled = true;
            var fd = new FormData();
            fd.append('id', id);
            fetch('ajax/auto_blog_generate.php', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        var url = data.review_url || ('blog_review.php?focus=' + (data.blog_id || ''));
                        var qc = data.qc_pass ? 'QC PASS → editör kuyruğu' : 'QC FAIL → revize';
                        alert((data.message || 'Üretildi') + '\n' + qc);
                        if (url) location.href = url;
                    } else {
                        alert(data.message || 'Hata');
                    }
                })
                .finally(function() { btn.disabled = false; });
        });
    });
});
