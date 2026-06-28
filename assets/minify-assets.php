<?php
/**
 * Asset Minification System
 * Bu dosya CSS ve JavaScript dosyalarını minify eder ve birleştirir
 */

class AssetMinifier {
    
    private $css_files = [
        'bootstrap.min.css',
        'main.css',
        'common-style.css',
        'slider.css'
    ];
    
    private $js_files = [
        'vendor/jquary-3.6.0.min.js',
        'vendor/bootstrap.min.js',
        'vendor/swiper.min.js',
        'main.js'
    ];
    
    public function minifyCSS($content) {
        // CSS minification
        $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
        $content = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $content);
        $content = str_replace('{ ', '{', $content);
        $content = str_replace(' }', '}', $content);
        $content = str_replace('; ', ';', $content);
        $content = str_replace(', ', ',', $content);
        $content = str_replace(' {', '{', $content);
        $content = str_replace('} ', '}', $content);
        $content = str_replace(': ', ':', $content);
        $content = str_replace(' ;', ';', $content);
        return trim($content);
    }
    
    public function minifyJS($content) {
        // Basic JS minification (remove comments and extra whitespace)
        $content = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\'|\")\/\/.*))/', '', $content);
        $content = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $content);
        $content = preg_replace('/\s+/', ' ', $content);
        return trim($content);
    }
    
    public function combineCSS() {
        $combined = '';
        $combined .= "/* Combined CSS - Generated: " . date('Y-m-d H:i:s') . " */\n";
        
        foreach ($this->css_files as $file) {
            $filepath = __DIR__ . '/css/' . $file;
            if (file_exists($filepath)) {
                $content = file_get_contents($filepath);
                $combined .= "\n/* === " . $file . " === */\n";
                $combined .= $this->minifyCSS($content);
            }
        }
        
        // Critical CSS önce gelsin
        $critical_css = "
        /* Critical CSS */
        body{margin:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;line-height:1.6}
        .d-none{display:none!important}
        .d-block{display:block!important}
        .container{max-width:1200px;margin:0 auto;padding:0 15px}
        .slider-section{position:relative;height:100vh;overflow:hidden}
        .main-slider{height:100%}
        .swiper-wrapper{height:100%}
        .swiper-slide{height:100%;position:relative}
        .header{position:fixed;top:0;width:100%;z-index:999;background:#fff;box-shadow:0 2px 10px rgba(0,0,0,0.1)}
        .service-section{padding:80px 0}
        .section-heading{margin-bottom:40px}
        .lazy{opacity:0;transition:opacity 0.3s}
        .lazy.loaded{opacity:1}
        ";
        
        return $critical_css . $combined;
    }
    
    public function combineJS() {
        $combined = '';
        $combined .= "/* Combined JS - Generated: " . date('Y-m-d H:i:s') . " */\n";
        
        foreach ($this->js_files as $file) {
            $filepath = __DIR__ . '/js/' . $file;
            if (file_exists($filepath)) {
                $content = file_get_contents($filepath);
                $combined .= "\n/* === " . $file . " === */\n";
                $combined .= $this->minifyJS($content) . ";\n";
            }
        }
        
        return $combined;
    }
    
    public function generateFiles() {
        // CSS dosyası oluştur
        $css_content = $this->combineCSS();
        file_put_contents(__DIR__ . '/css/combined.min.css', $css_content);
        
        // JavaScript dosyası oluştur
        $js_content = $this->combineJS();
        file_put_contents(__DIR__ . '/js/combined.min.js', $js_content);
        
        echo "Asset minification completed!\n";
        echo "Generated: combined.min.css (" . round(strlen($css_content)/1024, 2) . "KB)\n";
        echo "Generated: combined.min.js (" . round(strlen($js_content)/1024, 2) . "KB)\n";
    }
}

// Eğer komut satırından çalıştırılıyorsa
if (php_sapi_name() === 'cli') {
    $minifier = new AssetMinifier();
    $minifier->generateFiles();
}

// Web'den tetiklemeyi devre dışı bırak (güvenlik nedeniyle)
// Sadece CLI'dan çalıştırın.
?> 