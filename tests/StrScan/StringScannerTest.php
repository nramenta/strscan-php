<?php

require_once __DIR__ . '/../../src/StrScan/StringScanner.php';

use StrScan\StringScanner;

class StringScannerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->scanner = new StringScanner;
    }

    public function testScanAfterConcat()
    {
        $this->scanner->setSource('Fri Dec 12 1975 14:39');
        $this->scanner->scan('/Fri /');
        $this->scanner->concat(' +1000 GMT');
        $this->assertEquals($this->scanner->getSource(), "Fri Dec 12 1975 14:39 +1000 GMT");
        $this->assertEquals($this->scanner->scan("/Dec/"), "Dec");
    }

    public function testReset()
    {
        $this->scanner->reset();
        $this->assertEquals($this->scanner->getPosition(), 0);
        $this->assertNull($this->scanner->getPreMatch());
        $this->assertNull($this->scanner->getMatch());
        $this->assertNull($this->scanner->getPostMatch());
        $this->assertEquals($this->scanner->getRemainder(), $this->scanner->getSource());
    }

    public function testCapturePostPreMatch()
    {
        $this->scanner->setSource('Fri Dec 12 1975 14:39');
        $this->assertEquals($this->scanner->scan("/(\w+) (\w+) (\d+) /"), "Fri Dec 12 ");
        $this->assertEquals($this->scanner->getMatch(), "Fri Dec 12 ");
        $this->assertEquals($this->scanner->getCapture(0), "Fri");
        $this->assertEquals($this->scanner->getCapture(1), "Dec");
        $this->assertEquals($this->scanner->getCapture(2), "12");
        $this->assertEquals($this->scanner->getPostMatch(), "1975 14:39");
        $this->assertEquals($this->scanner->getPreMatch(), "");
    }

    public function testHasTerminated()
    {
        $this->scanner->setSource("test string");
        $this->assertFalse($this->scanner->hasTerminated());
        $this->scanner->scan("/test/");
        $this->assertFalse($this->scanner->hasTerminated());
        $this->scanner->terminate();
        $this->assertTrue($this->scanner->hasTerminated());
        return $this->scanner;
    }

    /**
     * @depends testHasTerminated
     */
    public function testConcatNotTerminated($scanner)
    {
        $this->assertEquals($scanner->getPosition(), 11);
        $scanner->concat("123");
        $this->assertFalse($scanner->hasTerminated());
        $this->assertEquals($scanner->getRemainder(), "123");
        $this->assertNotNull($scanner->scan("/123/"));
        $this->assertEquals($scanner->getPosition(), 14);
    }

    public function testScanChar()
    {
        $this->scanner->setSource("ab");
        $this->assertEquals($this->scanner->scanChar(), "a");
        $this->assertEquals($this->scanner->scanChar(), "b");
        $this->assertNull($this->scanner->scanChar());
    }

    public function testScanCharUnicode()
    {
        $this->scanner->setSource("☃\n1");
        $this->assertEquals($this->scanner->scanChar(), "☃");
        $this->assertEquals($this->scanner->scanChar(), "\n");
        $this->assertEquals($this->scanner->scanChar(), "1");
        $this->assertNull($this->scanner->scanChar());
    }

    public function testPeek()
    {
        $this->scanner->setSource("test string");
        $this->assertEquals($this->scanner->peek(7), "test st");
        $this->assertEquals($this->scanner->peek(7), "test st");
    }

    public function testScan()
    {
        $this->scanner->setSource("test string");
        $this->assertEquals($this->scanner->scan("/\w+/"), "test");
        $this->assertNull($this->scanner->scan("/\w+/"));
        $this->assertEquals($this->scanner->scan("/\s+/"), " ");
        $this->assertEquals($this->scanner->scan("/\w+/"), "string");
        $this->assertNull($this->scanner->scan("/\w+/"));
    }

    public function testPrePostMatch()
    {
        $this->scanner->setSource("test string");
        $this->assertEquals($this->scanner->scan("/\w+/"), "test");
        $this->assertEquals($this->scanner->scan("/\s+/"), " ");
        $this->assertEquals($this->scanner->getPreMatch(), "test");
        $this->assertEquals($this->scanner->getPostMatch(), "string");
    }

    public function testScanUntil()
    {
        $this->scanner->setSource("Fri Dec 12 1975 14:39");
        $this->assertEquals($this->scanner->scanUntil("/1/"), "Fri Dec 1");
        $this->assertEquals($this->scanner->getPreMatch(), "Fri Dec ");
        $this->assertNull($this->scanner->scanUntil("/XYZ/"));
    }

    public function testScanUntil2()
    {
        $this->scanner->setSource("abaabaaab");
        $this->assertEquals($this->scanner->scanUntil("/b/"), "ab");
        $this->assertEquals($this->scanner->scanUntil("/b/"), "aab");
        $this->assertEquals($this->scanner->scanUntil("/b/"), "aaab");
    }

    public function testSkip()
    {
        $this->scanner->setSource("test string");
        $this->assertEquals($this->scanner->skip("/\w+/"), 4);
        $this->assertNull($this->scanner->skip("/\w+/"));
        $this->assertEquals($this->scanner->skip("/\s+/"), 1);
        $this->assertEquals($this->scanner->skip("/\w+/"), 6);
        $this->assertNull($this->scanner->skip("/./"));
    }

    public function testSkipUntil()
    {
        $this->scanner->setSource("Fri Dec 12 1975 14:39");
        $this->assertEquals($this->scanner->skipUntil("/12/"), 10);
        $this->assertEquals($this->scanner->peek(), " ");
        $this->assertEquals($this->scanner->peek(3), " 19");
    }

    public function testUnscan()
    {
        $this->scanner->setSource("test string");
        $this->assertEquals($this->scanner->scan("/\w+/"), "test");
        $this->scanner->unscan();
        $this->assertEquals($this->scanner->scan("/../"), "te");
        $this->assertNull($this->scanner->scan("/\d/"));
    }

    /**
     * @depends testUnscan
     * @expectedException Exception
     */
    public function testUnscanThrowsException()
    {
        $this->scanner->unscan();
    }

    public function testCheck()
    {
        $this->scanner->setSource("Fri Dec 12 1975 14:39");
        $this->assertEquals($this->scanner->check("/Fri/"), "Fri");
        $this->assertEquals($this->scanner->getPosition(), 0);
        $this->assertEquals($this->scanner->getMatch(), "Fri");
        $this->assertNull($this->scanner->check("/12/"));
        $this->assertNull($this->scanner->getMatch());
    }

    public function testCheckUntil()
    {
        $this->scanner->setSource("Fri Dec 12 1975 14:39");
        $this->assertEquals($this->scanner->checkUntil("/12/"), "Fri Dec 12");
        $this->assertEquals($this->scanner->getPosition(), 0);
        $this->assertEquals($this->scanner->getMatch(), "12");
    }

    public function testEnd()
    {
        $this->scanner->setSource("The end.");
        $this->assertTrue($this->scanner->scan("/\s*/") === NULL);
        $this->assertTrue($this->scanner->scan("/\w+/") === "The");
        $this->assertTrue($this->scanner->scan("/\s+/") === " ");
        $this->assertTrue($this->scanner->scan("/\w*/") === "end");
        $this->assertTrue($this->scanner->scan("/./s") === ".");
    }
}

