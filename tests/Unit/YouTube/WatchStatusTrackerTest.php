<?php

declare(strict_types=1);

namespace ListDAV\Tests\Unit\YouTube;

use ListDAV\Core\Database;
use ListDAV\Module\YouTube\Services\WatchStatusTracker;
use PHPUnit\Framework\TestCase;

class WatchStatusTrackerTest extends TestCase
{
    private Database $database;
    private WatchStatusTracker $tracker;
    private string $dbPath;

    protected function setUp(): void
    {
        $this->dbPath = sys_get_temp_dir() . '/listdav_test_' . uniqid() . '.sqlite';
        $this->database = new Database($this->dbPath);
        $this->tracker = new WatchStatusTracker($this->database);
    }

    protected function tearDown(): void
    {
        unset($this->database);
        if (file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
    }

    public function testSetAndGetStatus(): void
    {
        $this->tracker->setStatus('video123', WatchStatusTracker::STATUS_WATCHED);

        $status = $this->tracker->getStatus('video123');

        $this->assertNotNull($status);
        $this->assertEquals('watched', $status['user_status']);
    }

    public function testGetStatusReturnsNullForNew(): void
    {
        $status = $this->tracker->getStatus('newvideo');
        $this->assertNull($status);
    }

    public function testDisplayPrefixNew(): void
    {
        $prefix = $this->tracker->getDisplayPrefix('newvideo');
        $this->assertEquals('[ ]', $prefix);
    }

    public function testDisplayPrefixConfirmed(): void
    {
        $this->tracker->setStatus('video', WatchStatusTracker::STATUS_WATCHED);

        $prefix = $this->tracker->getDisplayPrefix('video');
        $this->assertEquals('[✓]', $prefix);
    }

    public function testDisplayPrefixSkipped(): void
    {
        $this->tracker->setStatus('video', WatchStatusTracker::STATUS_SKIPPED);

        $prefix = $this->tracker->getDisplayPrefix('video');
        $this->assertEquals('[—]', $prefix);
    }

    public function testDisplayPrefixYouTubeWatched(): void
    {
        $this->tracker->setYouTubeWatched('video', true);

        $prefix = $this->tracker->getDisplayPrefix('video');
        $this->assertEquals('[?]', $prefix);
    }

    public function testParseStatusFromFilename(): void
    {
        $this->assertEquals(
            'watched',
            $this->tracker->parseStatusFromFilename('[✓] Video.url')
        );

        $this->assertEquals(
            'skipped',
            $this->tracker->parseStatusFromFilename('[—] Video.url')
        );

        $this->assertEquals(
            'unmarked',
            $this->tracker->parseStatusFromFilename('[ ] Video.url')
        );

        $this->assertEquals(
            'unmarked',
            $this->tracker->parseStatusFromFilename('[?] Video.url')
        );
    }

    public function testStripPrefixFromFilename(): void
    {
        $this->assertEquals(
            'Video.url',
            $this->tracker->stripPrefixFromFilename('[✓] Video.url')
        );

        $this->assertEquals(
            'Video.url',
            $this->tracker->stripPrefixFromFilename('[ ] Video.url')
        );

        $this->assertEquals(
            'No Prefix.url',
            $this->tracker->stripPrefixFromFilename('No Prefix.url')
        );
    }

    public function testSetYouTubeWatched(): void
    {
        $this->tracker->setYouTubeWatched('video', true);

        $status = $this->tracker->getStatus('video');
        $this->assertEquals(1, $status['youtube_watched']);

        $this->tracker->setYouTubeWatched('video', false);

        $status = $this->tracker->getStatus('video');
        $this->assertEquals(0, $status['youtube_watched']);
    }

    public function testOverrideYouTubeStatusWithUserStatus(): void
    {
        // YouTube marks as watched
        $this->tracker->setYouTubeWatched('video', true);
        $this->assertEquals('[?]', $this->tracker->getDisplayPrefix('video'));

        // User confirms
        $this->tracker->setStatus('video', WatchStatusTracker::STATUS_WATCHED);
        $this->assertEquals('[✓]', $this->tracker->getDisplayPrefix('video'));
    }
}
