<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class VideoObjectSchemaTest extends TestCase
{
    protected function setUp(): void
    {
        require_once PROJECT_ROOT . '/includes/mynak_video_watch_page.php';
    }

    public function testWatchWebPageReferencesAStandaloneVideoObject(): void
    {
        $videoId = 'https://www.mynakliyat.com.tr/video/spulgA_gx7o#video-spulgA_gx7o';
        $node = mynak_schema_build_watch_webpage_node(
            'https://www.mynakliyat.com.tr/video/spulgA_gx7o',
            'Video',
            ['@type' => 'VideoObject', '@id' => $videoId]
        );

        $this->assertSame(['@id' => $videoId], $node['mainEntity']);
        $this->assertIsString($node['mainEntity']['@id']);
        $this->assertSame('https://www.mynakliyat.com.tr/video/spulgA_gx7o#webpage', $node['@id']);
    }

    public function testWatchWebPageRejectsVideoWithoutAnId(): void
    {
        $this->assertSame(
            [],
            mynak_schema_build_watch_webpage_node(
                'https://www.mynakliyat.com.tr/video/spulgA_gx7o',
                'Video',
                ['@type' => 'VideoObject']
            )
        );
    }
}
