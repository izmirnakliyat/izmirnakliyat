tinymce.init({
    selector: '#icerik, #content',
    height: 500,
    plugins: 'preview importcss searchreplace autolink autosave save directionality code visualblocks visualchars fullscreen image link media codesample table charmap pagebreak nonbreaking anchor insertdatetime advlist lists wordcount help charmap quickbars emoticons',
    menubar: 'file edit view insert format tools table help',
    toolbar: 'undo redo | bold italic underline strikethrough | fontfamily fontsize blocks | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | insertfile image media link anchor codesample | ltr rtl',
    toolbar_sticky: true,
    autosave_ask_before_unload: true,
    autosave_interval: '30s',
    autosave_prefix: '{path}{query}-{id}-',
    autosave_restore_when_empty: false,
    autosave_retention: '2m',
    image_advtab: true,
    importcss_append: true,
    file_picker_callback: function (callback, value, meta) {
        /* Dosya yükleme işlemleri */
    },
    templates: [
        { title: 'New Table', description: 'creates a new table', content: '<div class="mceTmpl"><table width="98%%"  border="0" cellspacing="0" cellpadding="0"><tr><th scope="col"> </th><th scope="col"> </th></tr><tr><td> </td><td> </td></tr></table></div>' },
        { title: 'Starting my story', description: 'A cure for writers block', content: 'Once upon a time...' },
        { title: 'New list with dates', description: 'New List with dates', content: '<div class="mceTmpl"><span class="cdate">cdate</span><br><span class="mdate">mdate</span><h2>My List</h2><ul><li></li><li></li></ul></div>' }
    ],
    template_cdate_format: '[Date Created (CDATE): %m/%d/%Y : %H:%M:%S]',
    template_mdate_format: '[Date Modified (MDATE): %m/%d/%Y : %H:%M:%S]',
    height: 600,
    image_caption: true,
    quickbars_selection_toolbar: 'bold italic | quicklink h2 h3 blockquote quickimage quicktable',
    noneditable_class: 'mceNonEditable',
    toolbar_mode: 'sliding',
    contextmenu: 'link image table',
    content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }',
    setup: function(editor) {
        // Blok ekleme butonları
        editor.ui.registry.addButton('blokEkle', {
            text: 'Blok Ekle',
            onAction: function() {
                editor.windowManager.open({
                    title: 'Blok Ekle',
                    body: {
                        type: 'panel',
                        items: [{
                            type: 'selectbox',
                            name: 'blok_tipi',
                            label: 'Blok Tipi',
                            items: [
                                { value: 'galeri_slider', text: 'Galeri Slider' },
                                { value: 'html', text: 'HTML Blok' },
                                { value: 'blog', text: 'Blog Blok' },
                                { value: 'iletisim', text: 'İletişim Formu' }
                            ]
                        }]
                    },
                    buttons: [
                        {
                            type: 'submit',
                            text: 'Ekle'
                        }
                    ],
                    onSubmit: function(api) {
                        var blok_tipi = api.getData().blok_tipi;
                        var blok_id = prompt('Blok ID\'sini girin:');
                        if (blok_id) {
                            editor.insertContent('[blok:' + blok_tipi + ' id=' + blok_id + ']');
                            api.close();
                        }
                    }
                });
            }
        });

        // Toolbar'a blok ekleme butonunu ekle
        editor.ui.registry.addMenuItem('blokEkle', {
            text: 'Blok Ekle',
            onAction: function() {
                editor.execCommand('mceBlokEkle');
            }
        });

        // Menüye blok ekleme seçeneğini ekle
        editor.ui.registry.addNestedMenuItem('bloklar', {
            text: 'Bloklar',
            getSubmenuItems: function() {
                return [
                    {
                        type: 'menuitem',
                        text: 'Galeri Slider Ekle',
                        onAction: function() {
                            var blok_id = prompt('Galeri Slider Blok ID\'sini girin:');
                            if (blok_id) {
                                editor.insertContent('[blok:galeri_slider id=' + blok_id + ']');
                            }
                        }
                    },
                    {
                        type: 'menuitem',
                        text: 'HTML Blok Ekle',
                        onAction: function() {
                            var blok_id = prompt('HTML Blok ID\'sini girin:');
                            if (blok_id) {
                                editor.insertContent('[blok:html id=' + blok_id + ']');
                            }
                        }
                    },
                    {
                        type: 'menuitem',
                        text: 'Blog Blok Ekle',
                        onAction: function() {
                            var blok_id = prompt('Blog Blok ID\'sini girin:');
                            if (blok_id) {
                                editor.insertContent('[blok:blog id=' + blok_id + ']');
                            }
                        }
                    },
                    {
                        type: 'menuitem',
                        text: 'İletişim Formu Ekle',
                        onAction: function() {
                            var blok_id = prompt('İletişim Formu Blok ID\'sini girin:');
                            if (blok_id) {
                                editor.insertContent('[blok:iletisim id=' + blok_id + ']');
                            }
                        }
                    }
                ];
            }
        });
    }
}); 