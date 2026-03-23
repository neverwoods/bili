<?php

namespace Bili\Tests;

use Bili\FileIO;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Bili\FileIO.
 *
 * Covers file extension extraction, filename base modification,
 * recursive directory deletion, temporary folder creation,
 * file line counting, and encoding detection (non-existent file case).
 *
 * Note: Methods that depend on external commands (wkhtmltopdf, ghostscript),
 * network (curl), sessions, or HTTP headers are not covered here.
 *
 * @see FileIO
 *
 * Run all tests in this file:
 *   vendor/bin/phpunit tests/FileIOTest.php
 *
 * Run a single test:
 *   vendor/bin/phpunit --filter testExtension tests/FileIOTest.php
 */
class FileIOTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/bili_test_' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            FileIO::unlinkDir($this->tempDir);
        }
        parent::tearDown();
    }

    /**
     * Tests that extension() returns the file extension (without the dot),
     * or null for files without an extension.
     *
     * @return void
     */
    public function testExtension(): void
    {
        $this->assertEquals("jpg", FileIO::extension("photo.jpg"));
        $this->assertEquals("gz", FileIO::extension("archive.tar.gz"));
        $this->assertEquals("php", FileIO::extension("/path/to/file.php"));
        $this->assertNull(FileIO::extension("noext"));
    }

    /**
     * Tests that add2Base() inserts an addition string between the
     * base filename and its extension.
     *
     * @return void
     */
    public function testAdd2Base(): void
    {
        $this->assertEquals("photo_thumb.jpg", FileIO::add2Base("photo.jpg", "_thumb"));
        $this->assertEquals("document_v2.pdf", FileIO::add2Base("document.pdf", "_v2"));
    }

    /**
     * Tests that add2Base() works correctly with full file paths,
     * returning only the modified filename (not the directory).
     *
     * @return void
     */
    public function testAdd2BaseWithPath(): void
    {
        $this->assertEquals("document_v2.pdf", FileIO::add2Base("/path/to/document.pdf", "_v2"));
    }

    /**
     * Tests that unlinkDir() recursively deletes a directory tree
     * including all files and subdirectories.
     *
     * @return void
     */
    public function testUnlinkDir(): void
    {
        $subDir = $this->tempDir . '/subdir';
        mkdir($subDir);
        file_put_contents($subDir . '/test.txt', 'content');
        file_put_contents($this->tempDir . '/root.txt', 'content');

        $this->assertTrue(FileIO::unlinkDir($this->tempDir));
        $this->assertDirectoryDoesNotExist($this->tempDir);
    }

    /**
     * Tests that unlinkDir() returns false for a non-existent path.
     *
     * @return void
     */
    public function testUnlinkDirNonExistent(): void
    {
        $this->assertFalse(FileIO::unlinkDir('/nonexistent/path'));
    }

    /**
     * Tests that createTempFolder() creates a uniquely named directory
     * inside the given base folder using Crypt::generateToken().
     *
     * @return void
     */
    public function testCreateTempFolder(): void
    {
        $folder = FileIO::createTempFolder($this->tempDir);
        $this->assertNotEmpty($folder);
        $this->assertDirectoryExists($folder);

        // Clean up
        rmdir($folder);
    }

    /**
     * Tests that createTempFolder() handles a base folder path
     * that already has a trailing directory separator.
     *
     * @return void
     */
    public function testCreateTempFolderWithTrailingSlash(): void
    {
        $folder = FileIO::createTempFolder($this->tempDir . '/');
        $this->assertNotEmpty($folder);
        $this->assertDirectoryExists($folder);

        rmdir($folder);
    }

    /**
     * Tests that getLineCount() returns the correct number of lines
     * in a file with multiple newline-terminated lines.
     *
     * @return void
     */
    public function testGetLineCount(): void
    {
        $file = $this->tempDir . '/lines.txt';
        file_put_contents($file, "line1\nline2\nline3\n");

        $this->assertEquals(4, FileIO::getLineCount($file));
    }

    /**
     * Tests that getLineCount() returns 1 for a file with a single line
     * and no trailing newline.
     *
     * @return void
     */
    public function testGetLineCountSingleLine(): void
    {
        $file = $this->tempDir . '/single.txt';
        file_put_contents($file, "single line");

        $this->assertEquals(1, FileIO::getLineCount($file));
    }

    /**
     * Tests that getLineCount() returns 0 for a non-existent file.
     *
     * @return void
     */
    public function testGetLineCountNonExistent(): void
    {
        $this->assertEquals(0, FileIO::getLineCount('/nonexistent/file.txt'));
    }

    /**
     * Tests that detectFileEncoding() returns null for a non-existent file.
     *
     * @return void
     */
    public function testDetectFileEncodingNonExistent(): void
    {
        $this->assertNull(FileIO::detectFileEncoding('/nonexistent/file.txt'));
    }
}
