document.addEventListener('DOMContentLoaded', function() {
    console.log("main.js loaded - Mobil menu fix");
    
    // Mobil menü butonunu aktif edelim - ACIL FIX
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const mobileMenu = document.querySelector('.mobile-menu');
    const bodyOverlay = document.querySelector('.body-overlay');
    
    if (mobileMenuBtn && mobileMenu) {
        console.log("Mobil menü butonu bulundu, olay ekleniyor");
        
        // Butona tıklama olayı ekle
        mobileMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log("Menü butonuna tıklandı");
            
            // Menüyü aç
            mobileMenu.style.left = '0';
            mobileMenu.classList.add('active');
            
            // Overlay'i aç
            if (bodyOverlay) {
            bodyOverlay.classList.add('active');
            }
            
            // Sayfanın kaydırılmasını engelle
            document.body.style.overflow = 'hidden';
        });
    }
    
    // Video popup işlevselliği
    const playBtn = document.querySelector('.play-btn button');
    if (playBtn) {
        console.log("Video play butonu bulundu");
        playBtn.addEventListener('click', function() {
            console.log("Play butonuna tıklandı");
            const videoUrl = this.getAttribute('data-video-url');
            if (videoUrl) {
                console.log("Video URL:", videoUrl);
                
                // Video popup oluştur
                const popup = document.createElement('div');
                popup.className = 'video-popup';
                popup.innerHTML = `
                    <div class="video-popup-overlay"></div>
                    <div class="video-popup-content">
                        <button type="button" class="video-popup-close">&times;</button>
                        <div class="video-container">
                            <iframe width="100%" height="100%" src="${videoUrl}" frameborder="0" allowfullscreen></iframe>
                        </div>
                    </div>
                `;
                
                // Popup'ı sayfaya ekle
                document.body.appendChild(popup);
                
                // Popup'ı göster
                setTimeout(() => {
                    popup.classList.add('active');
                }, 10);
                
                // Kapatma düğmesi ve overlay için event listener'lar ekle
                const closeBtn = popup.querySelector('.video-popup-close');
                const overlay = popup.querySelector('.video-popup-overlay');
                
                closeBtn.addEventListener('click', closeVideoPopup);
                overlay.addEventListener('click', closeVideoPopup);
                
                // ESC tuşu ile kapatma
                document.addEventListener('keydown', function escHandler(e) {
                    if (e.key === 'Escape') {
                        closeVideoPopup();
                        document.removeEventListener('keydown', escHandler);
                    }
                });
                
                // Popup'ı kapatma fonksiyonu
                function closeVideoPopup() {
                    popup.classList.remove('active');
                    setTimeout(() => {
                        document.body.removeChild(popup);
                    }, 300);
                }
            } else {
                console.warn("Video URL bulunamadı!");
            }
        });
    } else {
        console.warn("Play butonu bulunamadı!");
    }
    
    // Test etmek için siteyi yüklediğimizde registration popup var mı kontrol edelim
    const registrationPopup = document.getElementById('registrationPopup');
    if (registrationPopup) {
        console.log("Registration popup bulundu!");
            } else {
        console.warn("Registration popup bulunamadı!");
            }
    
    // Mobil Menü İşlevselliği - Tamamen Yenilendi
    initMobileMenu();
    
    // Popup İşlemleri için yeni metot ekliyoruz - Tüm popup butonlarını bul ve işle
    setupAllPopups();
    
    // Hero Banner Slider — Swiper ile assets/js/main.js üzerinden yönetiliyor
    
    // Setup Blog and Team slider buttons
    setupBlogSlider();
    setupTeamSlider();
    
    // Initialize Blog and Team sliders
    initBlogSlider();
    initTeamSlider();

    // Init Registration Popup
    initRegistrationPopup();
    
    // Dil değişikliklerini izle ve bayrağı güncelle
    monitorLanguageChanges();
});
            
// Dil değişikliklerini izle ve arayüzü güncelle
function monitorLanguageChanges() {
    // Sayfa tamamen yüklendiğinde dil bayrağını ve çevirileri kontrol et
    window.addEventListener('load', function() {
        const activeLang = localStorage.getItem('preferredLanguage') || 'tr';
        console.log('Sayfa yüklendi, aktif dil:', activeLang);
                        
        // Google Translate'in yüklenmesini bekle ve bayrağı güncelle
                        setTimeout(() => {
            if (window.translator) {
                window.translator.updateDropdownFlag(activeLang);
            }
        }, 800);
                        
        // Google translate API'si için olay dinleyici ekle - Select değişikliğini izle
        document.addEventListener('change', function(e) {
            if (e.target && e.target.className === 'goog-te-combo') {
                const selectedLang = e.target.value;
                if (selectedLang && selectedLang !== '') {
                    console.log('Google Translate combosu değişti:', selectedLang);
                    
                    // Seçilen dili kaydet
                    localStorage.setItem('preferredLanguage', selectedLang);
                    
                    // Bayrakları güncelle
                    if (window.translator) {
                        setTimeout(() => {
                            window.translator.updateDropdownFlag(selectedLang);
                        }, 100);
                    }
                    }
                }
            });
        });
        
    // LocalStorage izleme işlevi
    const oldSetItem = localStorage.setItem;
    localStorage.setItem = function(key, value) {
        // Aynı değeri tekrar kaydetmeyi önle
        if (key === 'preferredLanguage' && localStorage.getItem(key) === value) {
            console.log('Aynı dil tekrar seçildi, işlem yapılmayacak:', value);
            return;
        }
        
        // Orijinal işlevi çağır
        oldSetItem.apply(this, arguments);
                
        // preferredLanguage değiştiğinde
        if (key === 'preferredLanguage') {
            if (window.translator) {
                console.log('Dil tercihi değiştirildi:', value);
                
                // Dil bayrağını güncelle
                setTimeout(() => {
                    window.translator.updateDropdownFlag(value);
                        }, 300);
                        }
                    }
    };
}

// Mobil menü işlevselliğini başlatan yeni fonksiyon
function initMobileMenu() {
    console.log("Mobil menü başlatılıyor");
    
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const mobileMenu = document.querySelector('.mobile-menu');
    const closeMenu = document.querySelector('.close-menu');
    const bodyOverlay = document.querySelector('.body-overlay');
    const submenuToggles = document.querySelectorAll('.submenu-toggle');
    
    console.log("Menü elemanları:", {
        "Menü butonu var mı": !!mobileMenuBtn,
        "Menü var mı": !!mobileMenu,
        "Kapat butonu var mı": !!closeMenu,
        "Overlay var mı": !!bodyOverlay,
        "Alt menü toggle sayısı": submenuToggles.length
    });
    
    // Menü butonuna tıklama olayını ekle (header.php inline script handles .mobile-menu-icon)
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (mobileMenu) {
                mobileMenu.style.left = '0';
                mobileMenu.classList.add('active');
                
                if (bodyOverlay) {
                    bodyOverlay.classList.add('active');
                }
                
                document.body.style.overflow = 'hidden';
            }
        });
    }
    
    // Kapat butonuna tıklama olayını ekle
    if (closeMenu) {
        closeMenu.addEventListener('click', function() {
            closeMobileMenu();
        });
    }
    
    // Overlay'a tıklama olayını ekle
    if (bodyOverlay) {
        bodyOverlay.addEventListener('click', function() {
            closeMobileMenu();
            closeAllPopups();
        });
    }
    
    // Alt menü açma/kapama işlevselliğini ekle
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const parentLi = this.parentElement;
            parentLi.classList.toggle('active');
            const submenu = parentLi.querySelector('.mobile-submenu');
            
            // Close all other submenus first
            const allSubmenus = document.querySelectorAll('.mobile-submenu');
            const allParentLi = document.querySelectorAll('.has-submenu');
            
            allSubmenus.forEach(menu => {
                if (menu !== submenu) {
                    menu.classList.remove('show');
        }
            });
            
            allParentLi.forEach(li => {
                if (li !== parentLi) {
                    li.classList.remove('active');
                }
            });
            
            // Toggle the clicked submenu
            if (parentLi.classList.contains('active')) {
                submenu.classList.add('show');
                    } else {
                submenu.classList.remove('show');
            }
        });
    });
    
    // ESC tuşu ile menü kapatma
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeMobileMenu();
            closeAllPopups();
        }
});

    // Mobil menü durumunu sıfırlama fonksiyonu
    function resetMobileMenuState() {
        if (mobileMenu) {
            mobileMenu.classList.remove('active');
            mobileMenu.style.left = '';
        }
        
        if (bodyOverlay) {
            bodyOverlay.classList.remove('active');
        }
        
        document.body.style.overflow = '';
    }

    // Mobil menüyü kapatma fonksiyonu
    function closeMobileMenu() {
        if (mobileMenu) {
            mobileMenu.classList.remove('active');
            mobileMenu.style.left = '';
        }

        if (bodyOverlay) {
            bodyOverlay.classList.remove('active');
        }

        document.body.style.overflow = '';
    }
}

// Blog Slider Functionality
function initBlogSlider() {
    console.log('Initializing blog slider');
    const blogSlider = document.querySelector('.blog-slider');
    if (!blogSlider) {
        console.warn('Blog slider not found');
        return;
    }

    const slides = document.querySelectorAll('.blog-slide');
    if (!slides.length) {
        console.warn('Blog slides not found');
        return;
    }
    
    const dotsContainer = document.querySelector('.blog-dots');
    const isMobile = window.innerWidth < 992;
    
    // Calculate how many dots we actually need
    // For mobile: one dot per slide
    // For desktop: we have 3 visible slides at once, so fewer dots
    const numDots = isMobile ? slides.length : Math.max(1, slides.length - 2);
    
    // Initialize dots
    if (dotsContainer) {
        dotsContainer.innerHTML = ''; // Clear existing dots
        
        // Only create the needed number of dots
        for (let i = 0; i < numDots; i++) {
            const dot = document.createElement('div');
            dot.classList.add('blog-dot');
            if (i === 0) dot.classList.add('active');
            
            dot.addEventListener('click', function() {
                if (isMobile) {
                    // Mobile - single slide view
                    slides.forEach((slide, slideIndex) => {
                        if (slideIndex === i) {
                            slide.classList.add('active');
                            slide.style.display = 'block';
                        } else {
                            slide.classList.remove('active');
                            slide.style.display = 'none';
                        }
                    });
                } else {
                    // Desktop - show 3 slides at a time
                    slides.forEach((slide, slideIndex) => {
                        // Start from this dot index
                        if (slideIndex >= i && slideIndex < i + 3) {
                            slide.style.display = 'block';
                        } else {
                            slide.style.display = 'none';
                        }
                    });
                }
                
                // Update active dot
                const dots = dotsContainer.querySelectorAll('.blog-dot');
                dots.forEach((d, index) => {
                    d.classList.toggle('active', index === i);
                });
            });
            
            dotsContainer.appendChild(dot);
        }
    }
    
    // Set initial state
    if (isMobile) {
        // Mobile - show only first slide as active
        slides.forEach((slide, index) => {
            if (index === 0) {
                slide.classList.add('active');
                slide.style.display = 'block';
            } else {
                slide.classList.remove('active');
                slide.style.display = 'none';
            }
        });
    } else {
        // Desktop - show first 3 slides
        slides.forEach((slide, index) => {
            if (index < 3) {
                slide.style.display = 'block';
            } else {
                slide.style.display = 'none';
            }
        });
    }
    
    // Add swipe functionality for mobile
    let touchStartX = 0;
    let touchEndX = 0;
    
    blogSlider.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    });
    
    blogSlider.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    });
    
    function handleSwipe() {
        const threshold = 50; // minimum distance for swipe
        if (touchEndX < touchStartX - threshold) {
            // Swipe left - next slide
            handleBlogSlide('next');
        } else if (touchEndX > touchStartX + threshold) {
            // Swipe right - previous slide
            handleBlogSlide('prev');
        }
    }
}

// Handle blog slide change
function handleBlogSlide(direction) {
    console.log('Handle blog slide', direction);
    const slides = document.querySelectorAll('.blog-slide');
    if (!slides.length) return;
    
    const isMobile = window.innerWidth < 992;
    
    if (isMobile) {
        // Mobile view - single slide show
        let activeIndex = -1;
        
        // Find the currently active slide
        slides.forEach((slide, index) => {
            if (slide.classList.contains('active')) {
                activeIndex = index;
            }
        });
        
        if (activeIndex === -1) {
            activeIndex = 0; // Default to first slide if none active
        }
        
        // Calculate new active index
        let newIndex;
        if (direction === 'next') {
            newIndex = (activeIndex + 1) % slides.length;
        } else {
            newIndex = (activeIndex - 1 + slides.length) % slides.length;
        }
        
        console.log('Blog mobile slide moving from', activeIndex, 'to', newIndex);
        
        // Update active classes
        slides.forEach((slide, index) => {
            if (index === newIndex) {
                slide.classList.add('active');
                slide.style.display = 'block';
            } else {
                slide.classList.remove('active');
                slide.style.display = 'none';
            }
        });
        
        // Update dots
        updateBlogDots(newIndex);
    } else {
        // Desktop view - multiple slides
        let firstVisibleIndex = -1;
        
        // Find first visible slide
        slides.forEach((slide, index) => {
            if (slide.style.display !== 'none' && firstVisibleIndex === -1) {
                firstVisibleIndex = index;
            }
        });
        
        if (firstVisibleIndex === -1) {
            firstVisibleIndex = 0;
        }
        
        // Calculate new first visible slide
        let newFirstIndex;
        if (direction === 'next') {
            newFirstIndex = (firstVisibleIndex + 1) % slides.length;
            if (newFirstIndex + 3 > slides.length) {
                newFirstIndex = 0; // Loop back to beginning
            }
        } else {
            newFirstIndex = (firstVisibleIndex - 1 + slides.length) % slides.length;
        }
        
        console.log('Blog desktop slide moving from', firstVisibleIndex, 'to', newFirstIndex);
        
        // Update slide visibility
        slides.forEach((slide, index) => {
            if (index >= newFirstIndex && index < newFirstIndex + 3) {
                slide.style.display = 'block';
            } else {
                slide.style.display = 'none';
            }
        });
        
        // Calculate which dot should be active
        const dotIndex = Math.min(newFirstIndex, slides.length - 3);
        updateBlogDots(dotIndex);
    }
}

// Update blog dots
function updateBlogDots(activeIndex) {
    const dots = document.querySelectorAll('.blog-dot');
    if (!dots.length) return;
    
    dots.forEach((dot, index) => {
        if (index === activeIndex) {
            dot.classList.add('active');
        } else {
            dot.classList.remove('active');
        }
    });
}

// Team Slider Functionality
function initTeamSlider() {
    console.log('Initializing team slider');
    const teamSlider = document.querySelector('.team-slider');
    if (!teamSlider) {
        console.warn('Team slider not found');
        return;
    }

    const slides = document.querySelectorAll('.team-slide');
    if (!slides.length) {
        console.warn('Team slides not found');
        return;
    }
    
    const dotsContainer = document.querySelector('.team-dots');
    const isMobile = window.innerWidth < 992;
    
    // Calculate how many dots we actually need
    // For mobile: one dot per slide
    // For desktop: we have 4 visible slides at once, so fewer dots
    const numDots = isMobile ? slides.length : Math.max(1, slides.length - 3);
    
    // Initialize dots
    if (dotsContainer) {
        dotsContainer.innerHTML = ''; // Clear existing dots
        
        // Only create the needed number of dots
        for (let i = 0; i < numDots; i++) {
            const dot = document.createElement('div');
            dot.classList.add('team-dot');
            if (i === 0) dot.classList.add('active');
            
            dot.addEventListener('click', function() {
                if (isMobile) {
                    // Mobile - single slide view
                    slides.forEach((slide, slideIndex) => {
                        if (slideIndex === i) {
                            slide.classList.add('active');
                            slide.style.display = 'block';
                        } else {
                            slide.classList.remove('active');
                            slide.style.display = 'none';
                        }
                    });
                } else {
                    // Desktop - show 4 slides at a time
                    slides.forEach((slide, slideIndex) => {
                        // Start from this dot index
                        if (slideIndex >= i && slideIndex < i + 4) {
                            slide.style.display = 'block';
                        } else {
                            slide.style.display = 'none';
                        }
                    });
                }
                
                // Update active dot
                const dots = dotsContainer.querySelectorAll('.team-dot');
                dots.forEach((d, index) => {
                    d.classList.toggle('active', index === i);
                });
            });
            
            dotsContainer.appendChild(dot);
        }
    }
    
    // Set initial state
    if (isMobile) {
        // Mobile - show only first slide as active
        slides.forEach((slide, index) => {
            if (index === 0) {
                slide.classList.add('active');
                slide.style.display = 'block';
            } else {
                slide.classList.remove('active');
                slide.style.display = 'none';
            }
        });
    } else {
        // Desktop - show first 4 slides
        slides.forEach((slide, index) => {
            if (index < 4) {
                slide.style.display = 'block';
            } else {
                slide.style.display = 'none';
            }
        });
    }
}

// Handle team slide change
function handleTeamSlide(direction) {
    console.log('Handle team slide', direction);
    const slides = document.querySelectorAll('.team-slide');
    if (!slides.length) return;
    
    const isMobile = window.innerWidth < 992;
    
    if (isMobile) {
        // Mobile view - single slide show
        let activeIndex = -1;
        
        // Find the currently active slide
        slides.forEach((slide, index) => {
            if (slide.classList.contains('active')) {
                activeIndex = index;
            }
        });
        
        if (activeIndex === -1) {
            activeIndex = 0; // Default to first slide if none active
        }
        
        // Calculate new active index
        let newIndex;
        if (direction === 'next') {
            newIndex = (activeIndex + 1) % slides.length;
        } else {
            newIndex = (activeIndex - 1 + slides.length) % slides.length;
        }
        
        console.log('Team mobile slide moving from', activeIndex, 'to', newIndex);
        
        // Update active classes
        slides.forEach((slide, index) => {
            if (index === newIndex) {
                slide.classList.add('active');
                slide.style.display = 'block';
            } else {
                slide.classList.remove('active');
                slide.style.display = 'none';
            }
        });
        
        // Update dots
        updateTeamDots(newIndex);
    } else {
        // Desktop view - multiple slides
        let firstVisibleIndex = -1;
        
        // Find first visible slide
        slides.forEach((slide, index) => {
            if (slide.style.display !== 'none' && firstVisibleIndex === -1) {
                firstVisibleIndex = index;
            }
        });
        
        if (firstVisibleIndex === -1) {
            firstVisibleIndex = 0;
        }
        
        // Calculate new first visible slide
        let newFirstIndex;
        if (direction === 'next') {
            newFirstIndex = (firstVisibleIndex + 1) % slides.length;
            if (newFirstIndex + 4 > slides.length) {
                newFirstIndex = 0; // Loop back to beginning
            }
        } else {
            newFirstIndex = (firstVisibleIndex - 1 + slides.length) % slides.length;
        }
        
        console.log('Team desktop slide moving from', firstVisibleIndex, 'to', newFirstIndex);
        
        // Update slide visibility
        slides.forEach((slide, index) => {
            if (index >= newFirstIndex && index < newFirstIndex + 4) {
                slide.style.display = 'block';
            } else {
                slide.style.display = 'none';
            }
        });
        
        // Calculate which dot should be active
        const dotIndex = Math.min(newFirstIndex, slides.length - 4);
        updateTeamDots(dotIndex);
    }
}

// Update team dots
function updateTeamDots(activeIndex) {
    const dots = document.querySelectorAll('.team-dot');
    if (!dots.length) return;
    
    dots.forEach((dot, index) => {
        if (index === activeIndex) {
            dot.classList.add('active');
        } else {
            dot.classList.remove('active');
        }
    });
}

// Registration Popup functionality
function initRegistrationPopup() {
    console.log('Initializing registration popup...');
    
    // Get DOM elements
    const registrationPopup = document.getElementById('registrationPopup');
    if (!registrationPopup) {
        console.log('Registration popup not on this page, skipping.');
        return;
    }
    const registrationForm = document.getElementById('registrationForm');
    const successMessage = registrationPopup.querySelector('.form-success-message');
    
    // Validate required elements
    if (!registrationPopup) {
        console.warn('Registration popup element not found! (#registrationPopup)');
        return;
    }
    
    if (!registrationForm) {
        console.warn('Registration form element not found! (#registrationForm)');
        return;
    }
    
    if (!successMessage) {
        console.warn('Success message element not found! (.form-success-message)');
        return;
    }
    
    // Setup open buttons
    const openButtons = document.querySelectorAll('.open-registration-popup');
    if (openButtons.length > 0) {
        console.log('Found', openButtons.length, 'registration popup buttons');
        
        openButtons.forEach(button => {
            // Clone and replace to remove any existing event listeners
            const newButton = button.cloneNode(true);
            if (button.parentNode) {
                button.parentNode.replaceChild(newButton, button);
            }
            
            // Add event listener
            newButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Show popup
                registrationPopup.style.display = 'block';
                setTimeout(() => {
                    registrationPopup.classList.add('active');
                }, 10);
                
                // Disable background scrolling
                document.body.style.overflow = 'hidden';
            });
        });
    } else {
        console.warn('No .open-registration-popup buttons found');
    }
    
    // Setup close button
    const closeButton = registrationPopup.querySelector('.close-popup');
    const popupOverlay = registrationPopup.querySelector('.popup-overlay');
    
    if (!closeButton) {
        console.warn('Close button not found! (.close-popup)');
    } else {
        closeButton.addEventListener('click', closePopup);
    }
    
    if (!popupOverlay) {
        console.warn('Popup overlay not found! (.popup-overlay)');
    } else {
        popupOverlay.addEventListener('click', closePopup);
    }
    
    // Handle form submission with AJAX
    registrationForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        console.log('Processing registration form submission');
        
        // Collect form data
        const formData = new FormData(this);
        
        // Send form data to server
        fetch('ajax/process_form.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Form submission response:', data);
            
            if (data.success) {
                // Hide form and show success message
                registrationForm.style.display = 'none';
                successMessage.style.display = 'block';
            } else {
                alert('Form gönderimi başarısız: ' + data.message);
            }
        })
        .catch(error => {
            console.warn('Error:', error);
            alert('Form gönderilirken bir hata oluştu: ' + error.message);
        });
    });
    
    // Function to close popup
    function closePopup() {
        registrationPopup.classList.remove('active');
        setTimeout(() => {
            registrationPopup.style.display = 'none';
        }, 300);
        
        // Enable background scrolling
        document.body.style.overflow = '';
        
        // Reset form after popup is closed
        setTimeout(() => {
            registrationForm.reset();
            registrationForm.style.display = 'block';
            successMessage.style.display = 'none';
        }, 300);
    }
    
    console.log('Registration popup initialization complete!');
}

// Setup blog slider navigation
function setupBlogSlider() {
    console.log('Setting up blog slider navigation');
    const prevButton = document.querySelector('.blog-prev-slide');
    const nextButton = document.querySelector('.blog-next-slide');
    
    if (prevButton) {
        prevButton.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Blog prev button clicked');
            handleBlogSlide('prev');
        });
    } else {
        console.warn('Blog prev button not found');
    }
    
    if (nextButton) {
        nextButton.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Blog next button clicked');
            handleBlogSlide('next');
        });
    } else {
        console.warn('Blog next button not found');
    }
}

// Setup team slider navigation
function setupTeamSlider() {
    console.log('Setting up team slider navigation');
    const prevButton = document.querySelector('.team-prev-slide');
    const nextButton = document.querySelector('.team-next-slide');
    
    if (prevButton) {
        prevButton.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Team prev button clicked');
            handleTeamSlide('prev');
        });
    } else {
        console.warn('Team prev button not found');
    }
    
    if (nextButton) {
        nextButton.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Team next button clicked');
            handleTeamSlide('next');
        });
    } else {
        console.warn('Team next button not found');
    }
}

// Popup İşlemleri için yeni metot ekliyoruz - Tüm popup butonlarını bul ve işle
function setupAllPopups() {
    // Tüm popup butonlarını bul
    console.log('Setup all popups - Starting...');
    const popupButtons = document.querySelectorAll('.open-popup');
    
    if (popupButtons.length > 0) {
        console.log('Found', popupButtons.length, 'popup buttons');
        
        // Her buton için event listener ekle
        popupButtons.forEach(button => {
            const newButton = button.cloneNode(true);
            if (button.parentNode) {
                button.parentNode.replaceChild(newButton, button);
            }
            
            newButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const popupId = this.getAttribute('data-popup-id');
                if (popupId) {
                    console.log('Opening popup with ID:', popupId);
                    
                    // Popup elementini bul ve aç
                    const popup = document.getElementById('popup' + popupId);
                    if (popup) {
                        popup.style.display = 'block';
                        setTimeout(() => {
                            popup.classList.add('active');
                        }, 10);
                        
                        // Arka planı dondur
                        document.body.style.overflow = 'hidden';
                    } else {
                        console.warn('Popup with ID popup' + popupId + ' not found!');
                    }
                } else {
                    console.warn('Button does not have data-popup-id attribute!');
                }
            });
        });
    } else {
        console.warn('No .open-popup buttons found');
    }
    
    // Tüm popup formları işle
    setupPopupForms();
    
    // Tüm popup'ların kapatma düğmelerini ayarla
    const closeButtons = document.querySelectorAll('.popup .close-popup');
    closeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // En yakın popup elementini bul ve kapat
            const popup = this.closest('.popup');
            if (popup) {
                popup.classList.remove('active');
                setTimeout(() => {
                    popup.style.display = 'none';
                }, 300);
                
                // Arka plan kaydırmayı etkinleştir
                document.body.style.overflow = '';
            }
        });
    });
    
    // Tüm overlay'lere tıklama olayı ekle
    const overlays = document.querySelectorAll('.popup .popup-overlay');
    overlays.forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // En yakın popup elementini bul ve kapat
            const popup = this.closest('.popup');
            if (popup) {
                popup.classList.remove('active');
                setTimeout(() => {
                    popup.style.display = 'none';
                }, 300);
                
                // Arka plan kaydırmayı etkinleştir
                document.body.style.overflow = '';
            }
        });
    });
}

// Tüm popup formlarını işle
function setupPopupForms() {
    // Tüm popup formlarını bul
    const popupForms = document.querySelectorAll('.popup-contact-form');
    
    if (popupForms.length > 0) {
        console.log('Found', popupForms.length, 'popup forms');
        
        // Her form için event listener ekle
        popupForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const popupId = this.getAttribute('data-popup-id');
                if (!popupId) {
                    console.warn('Form does not have data-popup-id attribute!');
                    return;
                }
                
                console.log('Processing form submission for popup ID:', popupId);
                
                // Form verilerini topla
                const formData = new FormData(this);
                
                // Form gönderimini yap
                fetch('ajax/process_form.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Form submission response:', data);
                    
                    // Başarı mesajını göster
                    if (data.success) {
                        // Formu gizle ve başarı mesajını göster
                        const formElement = this;
                        const successMessage = formElement.nextElementSibling;
                        
                        if (successMessage && successMessage.classList.contains('form-success-message')) {
                            formElement.style.display = 'none';
                            successMessage.style.display = 'block';
                        } else {
                            // Success message elementi bulunamadı
                            const parentElement = formElement.parentElement;
                            formElement.style.display = 'none';
                            
                            // Başarı mesajı oluştur
                            const successElement = document.createElement('div');
                            successElement.className = 'form-success-message';
                            successElement.innerHTML = `
                                <div class="success-icon"><i class="fas fa-check-circle"></i></div>
                                <h4>Başvurunuz Alındı!</h4>
                                <div class="alert alert-success">${data.message || 'Mesajınız başarıyla gönderildi. En kısa sürede sizinle iletişime geçeceğiz.'}</div>
                            `;
                            
                            parentElement.appendChild(successElement);
                        }
                    } else {
                        alert('Form gönderimi başarısız: ' + data.message);
                    }
                })
                .catch(error => {
                    console.warn('Error:', error);
                    alert('Form gönderilirken bir hata oluştu: ' + error.message);
                });
            });
        });
    } else {
        console.warn('No .popup-contact-form forms found');
    }
}

// Tüm açık popupları kapat
function closeAllPopups() {
    const popups = document.querySelectorAll('.popup.active, .registration-popup.active');
    popups.forEach(popup => {
        popup.classList.remove('active');
        setTimeout(() => {
            popup.style.display = 'none';
        }, 300);
    });
    
    // Arka plan kaydırmayı etkinleştir
    document.body.style.overflow = '';
} 