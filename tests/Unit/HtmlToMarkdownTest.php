<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * includes/markdown/html_to_markdown.php birim testleri.
 */
class HtmlToMarkdownTest extends TestCase
{
    protected function setUp(): void
    {
        require_once PROJECT_ROOT . '/includes/markdown/html_to_markdown.php';
    }

    public function testEmptyInputReturnsEmpty(): void
    {
        $this->assertSame('', mynak_html_to_markdown(''));
    }

    // --- Headings ---

    public function testH1Conversion(): void
    {
        $result = mynak_html_to_markdown('<h1>Baslik</h1>');
        $this->assertStringContainsString('# Baslik', $result);
    }

    public function testH2Conversion(): void
    {
        $result = mynak_html_to_markdown('<h2>Alt Baslik</h2>');
        $this->assertStringContainsString('## Alt Baslik', $result);
    }

    public function testH3Conversion(): void
    {
        $result = mynak_html_to_markdown('<h3>H3</h3>');
        $this->assertStringContainsString('### H3', $result);
    }

    public function testH4Conversion(): void
    {
        $result = mynak_html_to_markdown('<h4>H4</h4>');
        $this->assertStringContainsString('#### H4', $result);
    }

    // --- Paragraphs ---

    public function testParagraphConversion(): void
    {
        $result = mynak_html_to_markdown('<p>Bu bir paragraf.</p>');
        $this->assertStringContainsString('Bu bir paragraf.', $result);
    }

    public function testEmptyParagraphIsIgnored(): void
    {
        $result = mynak_html_to_markdown('<p></p>');
        $this->assertSame('', trim($result));
    }

    // --- Emphasis ---

    public function testBoldConversion(): void
    {
        $result = mynak_html_to_markdown('<strong>kalin</strong>');
        $this->assertStringContainsString('**kalin**', $result);
    }

    public function testItalicConversion(): void
    {
        $result = mynak_html_to_markdown('<em>italik</em>');
        $this->assertStringContainsString('*italik*', $result);
    }

    public function testBTagConversion(): void
    {
        $result = mynak_html_to_markdown('<b>kalin</b>');
        $this->assertStringContainsString('**kalin**', $result);
    }

    public function testITagConversion(): void
    {
        $result = mynak_html_to_markdown('<i>italik</i>');
        $this->assertStringContainsString('*italik*', $result);
    }

    // --- Links ---

    public function testLinkConversion(): void
    {
        $result = mynak_html_to_markdown('<a href="https://example.com">Link</a>');
        $this->assertStringContainsString('[Link](https://example.com)', $result);
    }

    public function testLinkWithoutHrefReturnsTextOnly(): void
    {
        $result = mynak_html_to_markdown('<a>metin</a>');
        $this->assertStringContainsString('metin', $result);
        $this->assertStringNotContainsString('[metin]', $result);
    }

    // --- Images ---

    public function testImageConversion(): void
    {
        $result = mynak_html_to_markdown('<img src="image.jpg" alt="Aciklama">');
        $this->assertStringContainsString('![Aciklama](image.jpg)', $result);
    }

    public function testImageWithoutSrcReturnsEmpty(): void
    {
        $result = mynak_html_to_markdown('<img alt="no src">');
        $this->assertStringNotContainsString('![no src]', $result);
    }

    // --- Lists ---

    public function testUnorderedListConversion(): void
    {
        $html = '<ul><li>A</li><li>B</li><li>C</li></ul>';
        $result = mynak_html_to_markdown($html);
        $this->assertStringContainsString('- A', $result);
        $this->assertStringContainsString('- B', $result);
        $this->assertStringContainsString('- C', $result);
    }

    public function testOrderedListConversion(): void
    {
        $html = '<ol><li>Birinci</li><li>Ikinci</li></ol>';
        $result = mynak_html_to_markdown($html);
        $this->assertStringContainsString('1. Birinci', $result);
        $this->assertStringContainsString('2. Ikinci', $result);
    }

    // --- Code ---

    public function testInlineCodeConversion(): void
    {
        $result = mynak_html_to_markdown('<code>foo</code>');
        $this->assertStringContainsString('`foo`', $result);
    }

    public function testPreCodeBlockConversion(): void
    {
        $result = mynak_html_to_markdown('<pre><code>echo "hello";</code></pre>');
        $this->assertStringContainsString('```', $result);
        $this->assertStringContainsString('echo "hello";', $result);
    }

    // --- Blockquote ---

    public function testBlockquoteConversion(): void
    {
        $result = mynak_html_to_markdown('<blockquote>Alinti metin</blockquote>');
        $this->assertStringContainsString('> Alinti metin', $result);
    }

    // --- HR / BR ---

    public function testHrConversion(): void
    {
        $result = mynak_html_to_markdown('<hr>');
        $this->assertStringContainsString('---', $result);
    }

    public function testBrConversion(): void
    {
        $result = mynak_html_to_markdown('Satir bir<br>Satir iki');
        // <br> either produces trailing spaces+newline or just a newline
        $this->assertMatchesRegularExpression('/Satir bir\s*\nSatir iki/', $result);
    }

    // --- Tables ---

    public function testTableConversion(): void
    {
        $html = '<table><tr><th>Hizmet</th><th>Fiyat</th></tr><tr><td>Nakliyat</td><td>1000 TL</td></tr></table>';
        $result = mynak_html_to_markdown($html);
        $this->assertStringContainsString('| Hizmet | Fiyat |', $result);
        $this->assertStringContainsString('| --- | --- |', $result);
        $this->assertStringContainsString('| Nakliyat | 1000 TL |', $result);
    }

    // --- Script/style removal ---

    public function testScriptTagsAreRemoved(): void
    {
        $result = mynak_html_to_markdown('<p>Metin</p><script>alert("xss")</script>');
        $this->assertStringNotContainsString('alert', $result);
        $this->assertStringContainsString('Metin', $result);
    }

    public function testStyleTagsAreRemoved(): void
    {
        $result = mynak_html_to_markdown('<style>.foo{color:red}</style><p>Metin</p>');
        $this->assertStringNotContainsString('color', $result);
        $this->assertStringContainsString('Metin', $result);
    }

    // --- Fallback ---

    public function testStripToPlainFallback(): void
    {
        $result = mynak_md_strip_to_plain('<p>Test &amp; metin</p>');
        $this->assertSame('Test & metin', $result);
    }

    // --- Complex HTML ---

    public function testComplexHtmlConversion(): void
    {
        $html = '<h1>Baslik</h1><p>Paragraf <strong>kalin</strong> ve <a href="/link">link</a>.</p><ul><li>Madde 1</li></ul>';
        $result = mynak_html_to_markdown($html);
        $this->assertStringContainsString('# Baslik', $result);
        $this->assertStringContainsString('**kalin**', $result);
        $this->assertStringContainsString('[link](/link)', $result);
        $this->assertStringContainsString('- Madde 1', $result);
    }
}
