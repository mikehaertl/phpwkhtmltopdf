<?php
use mikehaertl\wkhtmlto\TmpFile;

class TmpFileTest extends \PHPUnit_Framework_TestCase
{
    public function testCanCreateTmpFile()
    {
        $content = 'test content';
        $tmp = new TmpFile($content);
        $fileName = $tmp->getFileName();

        $this->assertFileExists($fileName);
        $readContent = file_get_contents($fileName);
        $this->assertEquals($content, $readContent);
        unset($tmp);
        $this->assertFileNotExists($fileName);
    }

    public function testCanCreateTmpFileWithSuffix()
    {
        $content = 'test content';
        $tmp = new TmpFile($content, '.html');
        $fileName = $tmp->getFileName();

        $this->assertEquals('.html', substr($fileName, -5));
    }

    public function testCanCreateTmpFileInDirectory()
    {
        $dir = __DIR__.'/tmp';
        @mkdir($dir);
        $content = 'test content';
        $tmp = new TmpFile($content, null, $dir);
        $fileName = $tmp->getFileName();
        $this->assertEquals($dir, dirname($fileName));

        unset($tmp);
        @rmdir($dir);
    }

    public function testCanCastToFileName()
    {
        $content = 'test content';
        $tmp = new TmpFile($content);
        $fileName = $tmp->getFileName();

        $this->assertEquals($fileName, (string)$tmp);
    }
}


