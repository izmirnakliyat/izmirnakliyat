<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * blog-detay.php başlık (heading) hiyerarşisi regresyon testi (W1).
 *
 * Şablon kaynağındaki <h1>-<h6> etiketlerini DOM sırasına göre çıkarır ve doğrular:
 *  - Tam olarak tek <h1> (makale başlığı).
 *  - Aşağı yönde seviye atlaması yok (her iniş en fazla +1).
 *  - <h1>'den ÖNCE herhangi bir başlık gelmez (banner artık <p>).
 *
 * Not: DB'den gelen gövde içeriği pipeline'da <h1>→<h2> demote edildiğinden,
 * şablon iskeleti geçerliyse çalışma zamanı çıktısı da geçerli kalır.
 */
class BlogDetailHeadingHierarchyTest extends TestCase
{
    /**
     * @return list<int> DOM sırasına göre başlık seviyeleri (1..6)
     */
    private function templateHeadingLevels(): array
    {
        $src = (string) file_get_contents(PROJECT_ROOT . '/blog-detay.php');

        // PHP blokları, <style> ve HTML yorumları başlık taramasından çıkarılır.
        $src = preg_replace('/<\?php.*?\?>/s', '', $src) ?? $src;
        $src = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $src) ?? $src;
        $src = preg_replace('/<!--.*?-->/s', '', $src) ?? $src;

        preg_match_all('/<h([1-6])\b/i', $src, $m);

        return array_map('intval', $m[1]);
    }

    public function testExactlyOneH1(): void
    {
        $levels = $this->templateHeadingLevels();
        $h1Count = count(array_filter($levels, static fn (int $l): bool => $l === 1));
        $this->assertSame(1, $h1Count, 'blog-detay.php şablonunda tam olarak tek <h1> olmalı.');
    }

    public function testFirstHeadingIsH1(): void
    {
        $levels = $this->templateHeadingLevels();
        $this->assertNotEmpty($levels);
        $this->assertSame(1, $levels[0], 'İlk başlık <h1> olmalı (banner <p> oldu, H1 öncesi başlık yok).');
    }

    public function testNoSkippedHeadingLevelsDownward(): void
    {
        $levels = $this->templateHeadingLevels();
        $prev = 0;
        foreach ($levels as $i => $level) {
            if ($prev !== 0 && $level > $prev + 1) {
                $this->fail("Seviye atlaması: index {$i} konumunda H{$prev} → H{$level} (aşağı en fazla +1 olmalı).");
            }
            $prev = $level;
        }
        $this->addToAssertionCount(1);
    }

    public function testExpectedTemplateSkeleton(): void
    {
        // Statik şablon başlık iskeleti (DB gövdesi hariç).
        $this->assertSame([1, 2, 3, 3, 2, 3, 2, 2], $this->templateHeadingLevels());
    }
}
