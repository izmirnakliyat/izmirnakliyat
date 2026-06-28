/**
 * TinyMCE Merkezi Konfigürasyon
 * 
 * Tüm TinyMCE editörleri için ortak yapılandırma ayarları.
 * Bu dosya, her sayfada ayrı ayrı yapılandırma yapmak yerine
 * merkezi bir yerden tüm editörleri yönetmek için kullanılır.
 */

/**
 * watch, youtu.be, embed, shorts veya düz 11 karakterlik ID.
 * @param {string} raw
 * @returns {string}
 */
function mynakExtractYoutubeId(raw) {
    const s = String(raw || '').trim();
    if (!s) {
        return '';
    }
    if (/^[a-zA-Z0-9_-]{11}$/.test(s)) {
        return s;
    }
    const patterns = [
        /(?:youtube\.com\/(?:[^/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/i,
        /youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/i,
        /youtube-nocookie\.com\/embed\/([a-zA-Z0-9_-]{11})/i,
    ];
    for (let i = 0; i < patterns.length; i++) {
        const m = s.match(patterns[i]);
        if (m && m[1]) {
            return m[1];
        }
    }
    return '';
}

const TinyMCEConfig = {
    
    /**
     * Varsayılan editör yapılandırması
     * @param {string} selector - HTML selector for the textarea
     * @param {object} options - Additional options to override defaults
     * @returns {object} - TinyMCE configuration
     */
    getConfig: function(selector, options = {}) {
        const defaultConfig = {
            selector: selector,
            height: 500,
            language: 'tr',
            plugins: [
                'advlist', 'autolink', 'link', 'image', 'lists', 'charmap', 'preview', 'anchor', 'pagebreak',
                'searchreplace', 'wordcount', 'visualblocks', 'visualchars', 'code', 'fullscreen', 'insertdatetime',
                'media', 'table', 'emoticons', 'template', 'help', 'youtube'
            ],
            toolbar: [
                { name: 'formatting', items: ['bold', 'italic', 'underline', 'strikethrough'] },
                { name: 'alignment', items: ['alignleft', 'aligncenter', 'alignright', 'alignjustify'] },
                { name: 'indentation', items: ['outdent', 'indent'] },
                { name: 'lists', items: ['numlist', 'bullist'] },
                { name: 'insert', items: ['link', 'image', 'media', 'youtube', 'table'] },
                { name: 'styles', items: ['styles', 'blocks', 'fontfamily', 'fontsize'] },
                { name: 'colors', items: ['forecolor', 'backcolor'] },
                { name: 'tools', items: ['fullscreen', 'code', 'undo', 'redo'] }
            ],
            menubar: 'file edit view insert format tools table help',
            image_dimensions: true,
            image_class_list: [
                { title: 'Tam Boyut', value: 'img-fluid' },
                { title: 'Sola Hizalı', value: 'float-start me-3' },
                { title: 'Sağa Hizalı', value: 'float-end ms-3' },
                { title: 'Ortalanmış', value: 'mx-auto d-block' }
            ],
            style_formats: [
                { title: 'Başlıklar', items: [
                    { title: 'Başlık 1', format: 'h1' },
                    { title: 'Başlık 2', format: 'h2' },
                    { title: 'Başlık 3', format: 'h3' },
                    { title: 'Başlık 4', format: 'h4' },
                    { title: 'Başlık 5', format: 'h5' },
                    { title: 'Başlık 6', format: 'h6' }
                ]},
                { title: 'Bloklar', items: [
                    { title: 'Paragraf', format: 'p' },
                    { title: 'Alıntı', format: 'blockquote' },
                    { title: 'Kod Bloğu', format: 'pre' }
                ]},
                { title: 'Özel Stiller', items: [
                    { title: 'Vurgu Kutusu', block: 'div', classes: 'alert alert-primary' },
                    { title: 'Bilgi Kutusu', block: 'div', classes: 'alert alert-info' },
                    { title: 'Uyarı Kutusu', block: 'div', classes: 'alert alert-warning' },
                    { title: 'Hata Kutusu', block: 'div', classes: 'alert alert-danger' },
                    { title: 'Başarı Kutusu', block: 'div', classes: 'alert alert-success' }
                ]}
            ],
            image_title: true,
            image_caption: true,
            automatic_uploads: true,
            relative_urls: false,
            remove_script_host: false,
            convert_urls: true,
            promotion: false,
            statusbar: false,
            images_upload_handler: function(blobInfo, progress) {
                // TinyMCE 6: handler bir Promise döndürmeli (eski success/failure callback değil)
                return new Promise(function(resolve, reject) {
                    var xhr = new XMLHttpRequest();
                    xhr.withCredentials = false;
                    xhr.open('POST', 'upload_tinymce.php');

                    xhr.upload.onprogress = function(e) {
                        if (e.lengthComputable && typeof progress === 'function') {
                            progress(e.loaded / e.total * 100);
                        }
                    };

                    xhr.onload = function() {
                        if (xhr.status !== 200) {
                            reject('HTTP Error: ' + xhr.status);
                            return;
                        }

                        try {
                            var json = JSON.parse(xhr.responseText);
                            if (!json || typeof json.location !== 'string') {
                                reject('Geçersiz sunucu yanıtı');
                                return;
                            }
                            resolve(json.location);
                        } catch (e) {
                            reject('JSON parse hatası');
                        }
                    };

                    xhr.onerror = function() {
                        reject('Resim yüklenemedi (ağ hatası)');
                    };

                    var formData = new FormData();
                    formData.append('file', blobInfo.blob(), blobInfo.filename());
                    xhr.send(formData);
                });
            },
            file_picker_types: 'image',
            file_picker_callback: function(cb, value, meta) {
                var input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', 'image/*');
                
                input.onchange = function() {
                    var file = this.files[0];
                    var reader = new FileReader();
                    
                    reader.onload = function() {
                        var id = 'blobid' + (new Date()).getTime();
                        var blobCache = tinymce.activeEditor.editorUpload.blobCache;
                        var base64 = reader.result.split(',')[1];
                        var blobInfo = blobCache.create(id, file, base64);
                        blobCache.add(blobInfo);
                        
                        cb(blobInfo.blobUri(), { title: file.name });
                    };
                    
                    reader.readAsDataURL(file);
                };
                
                input.click();
            },
            content_style: `
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; 
                    font-size: 16px; 
                }
                
                /* Hata bildirimlerini gizle */
                .tox-notification--error {
                    display: none !important;
                }
                .tox-notification--warning {
                    display: none !important;
                }
                
                /* YouTube video container */
                .youtube-video-container {
                    position: relative;
                    padding-bottom: 56.25%;
                    height: 0;
                    margin: 20px 0;
                    border-radius: 8px;
                    overflow: hidden;
                }
                .youtube-video-container iframe {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                }
            `,
            
            // YouTube video ekleme için setup fonksiyonu
            setup: function(editor) {
                // YouTube butonu ekle
                editor.ui.registry.addButton('youtube', {
                    text: 'YouTube',
                    icon: 'embed',
                    tooltip: 'YouTube Video Ekle',
                    onAction: function() {
                        editor.windowManager.open({
                            title: 'YouTube Video Ekle',
                            body: {
                                type: 'panel',
                                items: [
                                    {
                                        type: 'input',
                                        name: 'youtube_url',
                                        label: 'YouTube Video URL\'si',
                                        placeholder: 'watch, youtu.be, embed, shorts veya 11 karakterlik VIDEO_ID'
                                    },
                                    {
                                        type: 'input',
                                        name: 'width',
                                        label: 'Genişlik (px)',
                                        placeholder: '560'
                                    },
                                    {
                                        type: 'input',
                                        name: 'height',
                                        label: 'Yükseklik (px)',
                                        placeholder: '315'
                                    }
                                ]
                            },
                            buttons: [
                                {
                                    type: 'cancel',
                                    text: 'İptal'
                                },
                                {
                                    type: 'submit',
                                    text: 'Video Ekle',
                                    primary: true
                                }
                            ],
                            onSubmit: function(api) {
                                const data = api.getData();
                                const youtube_url = data.youtube_url;
                                let width = data.width || '100%';
                                let height = data.height || '315';
                                
                                if (youtube_url) {
                                    const video_id = mynakExtractYoutubeId(youtube_url);
                                    
                                    if (video_id) {
                                        
                                        // Responsive iframe oluştur
                                        const iframe_html = `
                                            <div class="youtube-video-container" style="position: relative; padding-bottom: 56.25%; height: 0; margin: 20px 0; border-radius: 8px; overflow: hidden;">
                                                <iframe 
                                                    src="https://www.youtube.com/embed/${video_id}" 
                                                    title="YouTube video player" 
                                                    frameborder="0" 
                                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                                                    allowfullscreen
                                                    style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
                                                </iframe>
                                            </div>
                                        `;
                                        
                                        editor.insertContent(iframe_html);
                                        api.close();
                                    } else {
                                        editor.notificationManager.open({
                                            text: 'Geçersiz YouTube URL! Lütfen geçerli bir YouTube video linki girin.',
                                            type: 'error'
                                        });
                                    }
                                } else {
                                    editor.notificationManager.open({
                                        text: 'Lütfen bir YouTube URL girin!',
                                        type: 'warning'
                                    });
                                }
                            }
                        });
                    }
                });
                
                // Media plugin'ini de geliştirelim
                editor.on('init', function() {
                    // YouTube URL'lerini otomatik olarak embed yap
                    editor.on('paste', function(e) {
                        setTimeout(function() {
                            const content = editor.getContent();
                            const youtube_regex = /https?:\/\/(?:www\.)?(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})(?:[^\s"']*)?/g;
                            
                            const new_content = content.replace(youtube_regex, function(match, video_id) {
                                return `
                                    <div class="youtube-video-container" style="position: relative; padding-bottom: 56.25%; height: 0; margin: 20px 0; border-radius: 8px; overflow: hidden;">
                                        <iframe 
                                            src="https://www.youtube.com/embed/${video_id}" 
                                            title="YouTube video player" 
                                            frameborder="0" 
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                                            allowfullscreen
                                            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
                                        </iframe>
                                    </div>
                                `;
                            });
                            
                            if (new_content !== content) {
                                editor.setContent(new_content);
                            }
                        }, 100);
                    });
                });
            }
        };

        return {...defaultConfig, ...options};
    },

    /**
     * Sayfa içerik editörü için yapılandırma
     * @returns {object} - TinyMCE configuration
     */
    pageConfig: function() {
        return this.getConfig('#content');
    },

    /**
     * Blog içerik editörü için yapılandırma
     * @returns {object} - TinyMCE configuration
     */
    blogConfig: function() {
        return this.getConfig('#icerik');
    },

    /**
     * Özel başlık ve açıklama alanları için mini editör yapılandırması
     * @param {string} selector - HTML selector for the textarea
     * @returns {object} - TinyMCE configuration
     */
    miniConfig: function(selector) {
        return this.getConfig(selector, {
            height: 200,
            menubar: false,
            toolbar: [
                'bold italic underline | alignleft aligncenter alignright | bullist numlist | link | undo redo'
            ],
            plugins: ['link', 'lists']
        });
    }
}; 