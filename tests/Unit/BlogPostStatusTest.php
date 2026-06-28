<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * includes/blog_post_status.php birim testleri.
 */
class BlogPostStatusTest extends TestCase
{
    protected function setUp(): void
    {
        require_once PROJECT_ROOT . '/includes/blog_post_status.php';
    }

    public function testConstants(): void
    {
        $this->assertSame(0, MYNAK_BLOG_STATUS_DRAFT);
        $this->assertSame(1, MYNAK_BLOG_STATUS_EDITOR_QUEUE);
        $this->assertSame(2, MYNAK_BLOG_STATUS_REVISION);
        $this->assertSame(3, MYNAK_BLOG_STATUS_PUBLISHED);
    }

    public function testIsPublishedReturnsTrueForPublished(): void
    {
        $this->assertTrue(mynak_blog_is_published(MYNAK_BLOG_STATUS_PUBLISHED));
    }

    public function testIsPublishedReturnsFalseForDraft(): void
    {
        $this->assertFalse(mynak_blog_is_published(MYNAK_BLOG_STATUS_DRAFT));
    }

    public function testIsPublishedReturnsFalseForEditorQueue(): void
    {
        $this->assertFalse(mynak_blog_is_published(MYNAK_BLOG_STATUS_EDITOR_QUEUE));
    }

    public function testIsPublishedReturnsFalseForRevision(): void
    {
        $this->assertFalse(mynak_blog_is_published(MYNAK_BLOG_STATUS_REVISION));
    }

    public function testStatusLabelDraft(): void
    {
        $this->assertSame('Taslak', mynak_blog_status_label_tr(MYNAK_BLOG_STATUS_DRAFT));
    }

    public function testStatusLabelEditorQueue(): void
    {
        $this->assertSame('Editör kuyruğu', mynak_blog_status_label_tr(MYNAK_BLOG_STATUS_EDITOR_QUEUE));
    }

    public function testStatusLabelRevision(): void
    {
        $this->assertSame('Revize', mynak_blog_status_label_tr(MYNAK_BLOG_STATUS_REVISION));
    }

    public function testStatusLabelPublished(): void
    {
        $this->assertSame('Yayında', mynak_blog_status_label_tr(MYNAK_BLOG_STATUS_PUBLISHED));
    }

    public function testStatusLabelUnknown(): void
    {
        $this->assertSame('Bilinmeyen durum', mynak_blog_status_label_tr(99));
    }
}
