// MY Nakliyat - Dil Çeviri Sistemi
document.addEventListener('DOMContentLoaded', function() {
    console.log('Translator.js yüklendi');
    
    // Çeviri fonksiyonları
    const translator = {
        // İlk çalıştırma ve temizlik için bayrak
        initialized: false,
        
        // Çeviri düzeltmeleri
        translationFixes: {
            'communication': 'Contact',
            'Communication': 'Contact',
            'COMMUNICATION': 'CONTACT',
            'iletişim': 'Contact',
            'İletişim': 'Contact',
            'İLETİŞİM': 'CONTACT',
            'contact': 'Contact',
            'CONTACT': 'CONTACT'
        },
        
        init: function() {
            console.log('Translator başlatılıyor...');
            
            // Google Translate element ve widget'larını gizle
            this.hideGoogleTranslateElements();
            
            // Dil dropdown menüsünü oluştur (önce bu çalışsın)
            this.createLanguageDropdown();
            
            // Dil butonlarını işlevsel hale getir
            this.bindLanguageButtons();
            
            // API yüklendi mi kontrol et ve durumu güncelle
            this.checkGoogleTranslateAPI();
            
            // Çeviri düzeltmelerini başlat
            this.startTranslationFixes();
            
            // DOM değişimlerini izle (setInterval yerine — main-thread dostu)
            this.observeGoogleTranslateDom();
            
            // İlk yükleme tamamlandı
            this.initialized = true;
        },

        observeGoogleTranslateDom: function() {
            if (this._gtDomObserver) {
                return;
            }
            this._gtDomObserver = new MutationObserver(() => {
                this.hideGoogleTranslateElements();
                if (localStorage.getItem('preferredLanguage') === 'en') {
                    this.scheduleTranslationFixes();
                }
            });
            this._gtDomObserver.observe(document.body, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['style', 'class']
            });
        },

        scheduleTranslationFixes: function() {
            if (this._fixDebounceTimer) {
                clearTimeout(this._fixDebounceTimer);
            }
            this._fixDebounceTimer = setTimeout(() => {
                this.applyTranslationFixes();
            }, 400);
        },
        
        // Google Translate widget ve banner'larını gizle
        hideGoogleTranslateElements: function() {
            // Google translate banner'ını gizle
            const banner = document.querySelector('.goog-te-banner-frame');
            if (banner) {
                banner.style.display = 'none';
                banner.style.visibility = 'hidden';
            }
            
            // Google Translate widget'ını gizle
            const widget = document.querySelector('.goog-te-gadget');
            if (widget) {
                widget.style.fontSize = '0';
                widget.style.display = 'none';
            }
            
            // Çeviri bandını gizle
            const translateBar = document.querySelector('.skiptranslate');
            if (translateBar) {
                translateBar.style.display = 'none';
                translateBar.style.visibility = 'hidden';
            }
            
            // Top:0 stilini ayarla
            document.body.style.top = '0px !important';
            
            // Google tarafından eklenen "position: relative" sorununu gider
            const htmlElement = document.querySelector('html');
            if (htmlElement && htmlElement.style.position === 'relative') {
                htmlElement.style.position = 'static';
            }
        },
        
        // Google API yüklendi mi kontrol et
        checkGoogleTranslateAPI: function() {
            let attempts = 0;
            const maxAttempts = 20;
            const poll = () => {
                if (typeof google !== 'undefined' && google.translate) {
                    console.log('Google Translate API hazır');
                    this.hideGoogleTranslateElements();
                    const activeLang = localStorage.getItem('preferredLanguage') || 'tr';
                    this.updateDropdownFlag(activeLang);
                    return;
                }
                attempts += 1;
                if (attempts < maxAttempts) {
                    setTimeout(poll, 500);
                }
            };
            poll();
        },
        
        // Dil butonlarını işlevsel hale getir
        bindLanguageButtons: function() {
            console.log('Dil butonları bağlanıyor...');
            
            // Tüm dil butonlarını seç
            const langButtons = document.querySelectorAll('.lang-switcher a, .mobile-lang a, .lang-dropdown-content a');
            console.log('Bulunan dil butonları:', langButtons.length);
            
            // Önce mevcut event listener'ları temizleyelim
            langButtons.forEach(button => {
                const newButton = button.cloneNode(true);
                if (button.parentNode) {
                    button.parentNode.replaceChild(newButton, button);
                }
            });
            
            // Yeniden tüm butonları seçelim
            const refreshedButtons = document.querySelectorAll('.lang-switcher a, .mobile-lang a, .lang-dropdown-content a');
            
            // Yeni event listener'ları ekleyelim
            refreshedButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const langCode = button.getAttribute('data-lang');
                    if (langCode) {
                        console.log('Dil değiştiriliyor:', langCode);
                        
                        // Aktif dil kontrolü - aynıysa işlem yapma
                        const currentLang = localStorage.getItem('preferredLanguage');
                        if (currentLang === langCode) {
                            console.log('Zaten seçili dil:', langCode);
            return;
        }
        
                        // Aktif stil ekle
                        refreshedButtons.forEach(btn => btn.classList.remove('active'));
                        button.classList.add('active');
                        
                        // Dil dropdown'ını kapat
                        const dropdownContent = document.querySelector('.lang-dropdown-content');
                        if (dropdownContent) {
                            dropdownContent.classList.remove('show');
                        }
                        
                        // Dili değiştir
                        this.translatePage(langCode);
                    }
                });
            });
            
            // Dropdown tıklama olayı
            document.addEventListener('click', (e) => {
                const btnElement = document.querySelector('.lang-dropdown-btn');
                if (btnElement && btnElement.contains(e.target)) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const content = document.querySelector('.lang-dropdown-content');
                    if (content) {
                        content.classList.toggle('show');
                    }
                } else if (!document.querySelector('.lang-dropdown-content')?.contains(e.target)) {
                    // Dropdown dışına tıklandıysa kapat
                    const content = document.querySelector('.lang-dropdown-content');
                    if (content && content.classList.contains('show')) {
                        content.classList.remove('show');
                    }
                }
            });
            
            // Aktif dili görsel olarak işaretle
            const activeLang = localStorage.getItem('preferredLanguage') || 'tr';
            const activeButtons = document.querySelectorAll(`.lang-switcher a[data-lang="${activeLang}"], .mobile-lang a[data-lang="${activeLang}"], .lang-dropdown-content a[data-lang="${activeLang}"]`);
            activeButtons.forEach(btn => btn.classList.add('active'));
            
            // Dropdown aktif dil bayrağını güncelle
            this.updateDropdownFlag(activeLang);
        },
        
        // Dil dropdown menüsünü oluştur
        createLanguageDropdown: function() {
            console.log("Dil dropdown menüsü oluşturuluyor");
            
            // Mevcut dil değiştiriciyi bul
            const langSwitcher = document.querySelector('.lang-switcher');
            if (!langSwitcher) return;
            
            // Mevcut dil butonlarını al
            const langButtons = langSwitcher.querySelectorAll('a');
            if (langButtons.length === 0) return;
            
            // Yeni dropdown oluştur
            const langDropdown = document.createElement('div');
            langDropdown.className = 'lang-dropdown';
            
            // Dropdown butonu oluştur
            const dropdownBtn = document.createElement('div');
            dropdownBtn.className = 'lang-dropdown-btn';
            
            // Aktif dil için bayrak ekle
            const activeLang = localStorage.getItem('preferredLanguage') || 'tr';
            let activeFlag = '';
            let activeAlt = '';
            
            langButtons.forEach(btn => {
                if (btn.getAttribute('data-lang') === activeLang) {
                    const img = btn.querySelector('img');
                    if (img) {
                        activeFlag = img.src;
                        activeAlt = img.alt;
                    }
                }
            });
            
            // Eğer aktif bayrak bulunamazsa, ilk bayrağı kullan
            if (!activeFlag && langButtons[0]) {
                const img = langButtons[0].querySelector('img');
                if (img) {
                    activeFlag = img.src;
                    activeAlt = img.alt;
                }
            }
            
            // Bayrak ve ok ekle
            dropdownBtn.innerHTML = `
                <img src="${activeFlag}" alt="${activeAlt}" id="active-flag">
                <i class="fas fa-chevron-down"></i>
            `;
            langDropdown.appendChild(dropdownBtn);
            
            // Dropdown içeriği oluştur
            const dropdownContent = document.createElement('div');
            dropdownContent.className = 'lang-dropdown-content';
            
            // Dil butonlarını dropdown içine kopyala
            langButtons.forEach(btn => {
                const langCode = btn.getAttribute('data-lang');
                const img = btn.querySelector('img');
                
                if (langCode && img) {
                    const newBtn = document.createElement('a');
                    newBtn.href = '#';
                    newBtn.setAttribute('data-lang', langCode);
                    newBtn.innerHTML = `
                        <img src="${img.src}" alt="${img.alt}">
                        <span style="display: none;">${img.alt}</span>
                    `;
                    
                    if (langCode === activeLang) {
                        newBtn.classList.add('active');
                    }
                    
                    dropdownContent.appendChild(newBtn);
                }
            });
            
            langDropdown.appendChild(dropdownContent);
            
            // Eski dil değiştiriciyi yeni ile değiştir
            langSwitcher.replaceWith(langDropdown);
        },
        
        // Dropdown bayrağını güncelle
        updateDropdownFlag: function(langCode) {
            console.log('Bayrak güncelleniyor:', langCode);
            const activeFlag = document.getElementById('active-flag');
            if (!activeFlag) {
                console.error('active-flag ID\'li eleman bulunamadı');
                return;
            }
            
            // Tüm dil butonlarında tarama yaparak doğru bayrağı bul
            const allButtons = document.querySelectorAll('a[data-lang]');
            let flagFound = false;
            
            // Önce direkt tam eşleşme ara
            allButtons.forEach(btn => {
                if (btn.getAttribute('data-lang') === langCode) {
                    const img = btn.querySelector('img');
                    if (img && img.src) {
                        activeFlag.src = img.src;
                        activeFlag.alt = img.alt || langCode;
                        console.log('Bayrak bulundu ve güncellendi:', langCode, img.src);
                        flagFound = true;
                    }
                }
            });
            
            // Eğer bulunamadıysa, sınıf bazlı ara
            if (!flagFound) {
                const langButton = document.querySelector(`.lang-dropdown-content a[data-lang="${langCode}"]`);
                if (langButton) {
                    const img = langButton.querySelector('img');
                    if (img && img.src) {
                        activeFlag.src = img.src;
                        activeFlag.alt = img.alt || langCode;
                        console.log('Dropdown içinde bayrak bulundu:', langCode, img.src);
                        flagFound = true;
                    }
                }
            }
            
            // Eğer o da bulunamadıysa, sabit bayrak adresleri dene
            if (!flagFound) {
                // Sabit bayrak adresleri - yaygın kullanılanlar (CDN linklerini kullanarak)
                const flagMap = {
                    'tr': 'https://cdn.countryflags.com/thumbs/turkey/flag-400.png',
                    'en': 'https://cdn.countryflags.com/thumbs/united-kingdom/flag-400.png',
                    'ar': 'https://cdn.countryflags.com/thumbs/saudi-arabia/flag-400.png',
                    'fr': 'https://cdn.countryflags.com/thumbs/france/flag-400.png',
                    'de': 'https://cdn.countryflags.com/thumbs/germany/flag-400.png',
                    'es': 'https://cdn.countryflags.com/thumbs/spain/flag-400.png',
                    'ru': 'https://cdn.countryflags.com/thumbs/russia/flag-400.png'
                };
                
                if (flagMap[langCode]) {
                    activeFlag.src = flagMap[langCode];
                    activeFlag.alt = langCode;
                    console.log('CDN adreslerinden bayrak kullanıldı:', langCode, flagMap[langCode]);
                    flagFound = true;
                }
            }
            
            // Mobil dilde de güncelle
            this.updateMobileLanguageButton(langCode);
        },
        
        // Mobil dil butonunu güncelle
        updateMobileLanguageButton: function(langCode) {
            const mobileLang = document.querySelector('.mobile-lang');
            if (!mobileLang) return;
            
            // Tüm dil butonlarını seç ve aktif olanı işaretle
            const mobileButtons = mobileLang.querySelectorAll('a[data-lang]');
            
            // Önce aktif sınıfı tüm butonlardan kaldır
            mobileButtons.forEach(btn => {
                btn.classList.remove('active');
            });
        
            // Ardından sadece seçili dil koduna sahip butonu aktif yap
            mobileButtons.forEach(btn => {
                const btnLangCode = btn.getAttribute('data-lang');
                if (btnLangCode === langCode) {
                    btn.classList.add('active');
                    console.log('Mobil menüde aktif dil işaretlendi:', langCode);
                }
            });
            
            // Eğer mobil menüdeki bayraklar yoksa veya hiç bayrak işaretlenmemişse, yeniden oluştur
            if (mobileButtons.length === 0 || !mobileLang.querySelector('.active')) {
                console.log('Mobil dil butonları yeniden oluşturuluyor');
                mobileLang.innerHTML = '';
                
                // Dil butonlarını al
                const langButtons = document.querySelectorAll('.lang-dropdown-content a');
                
                langButtons.forEach(btn => {
                    const btnLangCode = btn.getAttribute('data-lang');
                    const img = btn.querySelector('img');
                    
                    if (btnLangCode && img) {
                        const newBtn = document.createElement('a');
                        newBtn.href = '#';
                        newBtn.setAttribute('data-lang', btnLangCode);
                        
                        // Aktif dil için sınıf ekle
                        if (btnLangCode === langCode) {
                            newBtn.classList.add('active');
                        }
                        
                        newBtn.innerHTML = `<img src="${img.src}" alt="${img.alt || btnLangCode}">`;
                        mobileLang.appendChild(newBtn);
                    }
                });
                
                // Yeni butonlara event listener ekle
                this.bindLanguageButtons();
            }
        },
        
        // Sayfa çevirisini gerçekleştir - EN BASİT VE GÜVENİLİR YÖNTEM
        translatePage: function(langCode) {
            // Eğer geçerli bir dil kodu değilse işlem yapma
            if (!langCode) return;
            
            // Önceki dili kaydedelim
            const previousLang = localStorage.getItem('preferredLanguage') || 'tr';
            
            // Eğer aynı dil seçildiyse işlem yapma
            if (previousLang === langCode) {
                console.log('Aynı dil zaten seçili:', langCode);
                return;
            }
            
            console.log('Dil değiştiriliyor:', previousLang, '->', langCode);
            
            // Dil tercihini kaydet - sayfalar arası geçişte kullanılacak
            localStorage.setItem('preferredLanguage', langCode);
            
            // Dil bayrağını güncelle
            this.updateDropdownFlag(langCode);
            
            try {
                // Kaynak dil ve hedef dil ayarlanmış çerez
                const cookieDomain = window.location.hostname;
                
                // Türkçe diline geri dönüyorsa, çerezleri temizle ve sayfayı yenile
                if (langCode === 'tr') {
                    console.log('Türkçe diline dönülüyor, çerezler temizleniyor');
                    
                    // Çerezleri temizle
                    document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                    document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=' + cookieDomain;
                    document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=.' + cookieDomain;
                
                    // Sayfayı yenile
                    location.reload();
                    return;
                }
                
                // Google Translate çerezini ayarla - TÜM DOMAIN VERSİYONLARI İÇİN
                document.cookie = 'googtrans=/tr/' + langCode + '; path=/;';
                document.cookie = 'googtrans=/tr/' + langCode + '; path=/; domain=' + cookieDomain;
                document.cookie = 'googtrans=/tr/' + langCode + '; path=/; domain=.' + cookieDomain;
                
                // Google translate API'si ile çeviri yap
                if (typeof google !== 'undefined' && google.translate) {
                    // Kombo kutusu üzerinden dil değiştir
                    const select = document.querySelector('.goog-te-combo');
                    if (select) {
                        select.value = langCode;
                        select.dispatchEvent(new Event('change'));
                        console.log('API ile çeviri aktifleştirildi');
                        
                        // Çeviri sonrası Google elemanlarını gizle
                        setTimeout(() => {
                            this.hideGoogleTranslateElements();
                            // Çeviri düzeltmelerini uygula
                            this.applyTranslationFixes();
                        }, 300);
                    } else {
                        // API hazır değilse sayfayı yenile
                        console.log('Google Translate combosuna erişilemedi, sayfa yenileniyor');
                        location.reload();
                    }
                } else {
                    // API yüklü değilse sayfayı yenile, çerezler sayfa yüklendiğinde aktif olacak
                    console.log('Google Translate API mevcut değil, sayfa yenileniyor');
                    location.reload();
                }
            } catch (error) {
                console.error('Dil değiştirme hatası:', error);
                // Hata durumunda son çare olarak sayfayı yenile
                location.reload();
            }
        },
        
        // Çeviri düzeltmelerini başlat
        startTranslationFixes: function() {
            setTimeout(() => {
                this.applyTranslationFixes();
            }, 2000);
        },
        
        // Çeviri düzeltmelerini uygula
        applyTranslationFixes: function() {
            const currentLang = localStorage.getItem('preferredLanguage');
            
            // Sadece İngilizce çeviride düzeltme yap
            if (currentLang === 'en') {
                console.log('Çeviri düzeltmeleri uygulanıyor...');
                
                // Tüm metin içeren elementleri tara
                const textElements = document.querySelectorAll('h1, h2, h3, h4, h5, h6, p, span, a, button, label, div, li, td, th');
                
                textElements.forEach(element => {
                    // Sadece metin içeren elementler
                    if (element.childNodes.length === 1 && element.childNodes[0].nodeType === Node.TEXT_NODE) {
                        let text = element.textContent;
                        let originalText = text;
                        
                        // Düzeltmeleri uygula
                        Object.keys(this.translationFixes).forEach(wrongText => {
                            const correctText = this.translationFixes[wrongText];
                            const regex = new RegExp(wrongText, 'gi');
                            text = text.replace(regex, correctText);
                        });
                        
                        // Başlık elementlerinde ve menü öğelerinde "contact" kelimesini büyük harfle başlat
                        if (element.tagName && (element.tagName.match(/^H[1-6]$/) || element.closest('nav') || element.closest('.menu'))) {
                            text = text.replace(/\bcontact\b/gi, 'Contact');
                        }
                        
                        // Eğer değişiklik varsa güncelle
                        if (text !== originalText) {
                            element.textContent = text;
                            console.log('Çeviri düzeltildi:', originalText, '->', text);
                        }
                    } else {
                        // Karmaşık HTML yapısına sahip elementler için
                        this.fixTextInElement(element);
                    }
                });
            }
        },
        
        // Element içindeki metinleri düzelt
        fixTextInElement: function(element) {
            // Element içindeki tüm text node'ları bul
            const walker = document.createTreeWalker(
                element,
                NodeFilter.SHOW_TEXT,
                null,
                false
            );
            
            const textNodes = [];
            let node;
            while (node = walker.nextNode()) {
                textNodes.push(node);
            }
            
            // Her text node'u kontrol et ve düzelt
            textNodes.forEach(textNode => {
                let text = textNode.textContent;
                let originalText = text;
                
                // Düzeltmeleri uygula
                Object.keys(this.translationFixes).forEach(wrongText => {
                    const correctText = this.translationFixes[wrongText];
                    const regex = new RegExp(wrongText, 'gi');
                    text = text.replace(regex, correctText);
                });
                
                // Başlık elementlerinde ve menü öğelerinde "contact" kelimesini büyük harfle başlat
                const parentElement = textNode.parentElement;
                if (parentElement && (parentElement.tagName && parentElement.tagName.match(/^H[1-6]$/) || 
                    parentElement.closest('nav') || parentElement.closest('.menu') || 
                    parentElement.closest('.lang-switcher') || parentElement.closest('.mobile-lang'))) {
                    text = text.replace(/\bcontact\b/gi, 'Contact');
                }
                
                // Eğer değişiklik varsa güncelle
                if (text !== originalText) {
                    textNode.textContent = text;
                    console.log('Text node düzeltildi:', originalText, '->', text);
                }
            });
        }
    };
    
    // Çeviri sistemi başlat
    translator.init();
    
    // Global değişken olarak erişilebilir yapma
    window.translator = translator;
}); 