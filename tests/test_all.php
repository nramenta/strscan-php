<?php

include __DIR__ . '/../src/StrScan/StringScanner.php';

use StrScan\StringScanner;

assert_options(ASSERT_ACTIVE,     true);
assert_options(ASSERT_BAIL,       false);
assert_options(ASSERT_WARNING,    true);
assert_options(ASSERT_QUIET_EVAL, false);

$s = new StringScanner('Fri Dec 12 1975 14:39');
$s->scan('/Fri /');
$s->concat(' +1000 GMT');
assert('$s->getSource() == "Fri Dec 12 1975 14:39 +1000 GMT"');
assert('$s->scan("/Dec/") == "Dec"');

$s->reset();
assert('$s->getPosition() == 0');
assert('!$s->getPreMatch()');
assert('!$s->getMatch()');
assert('!$s->getPostMatch()');
assert('$s->getRemainder() == $s->getSource()');

$s = new StringScanner('Fri Dec 12 1975 14:39');
assert('$s->scan("/(\w+) (\w+) (\d+) /") == "Fri Dec 12 "');
assert('$s->getMatch() == "Fri Dec 12 "');
assert('$s->getCapture(0) == "Fri"');
assert('$s->getCapture(1) == "Dec"');
assert('$s->getCapture(2) == "12"');
assert('$s->getPostMatch() == "1975 14:39"');
assert('$s->getPreMatch() == ""');

$s = new StringScanner("test string");
assert('!$s->hasTerminated()');
$s->scan("/test/");
assert('!$s->hasTerminated()');
$s->terminate();
assert('$s->hasTerminated()');

assert('$s->getPosition() == 11');
$s->concat("123");
assert('!$s->hasTerminated()');
assert('$s->getRemainder() == "123"');
assert('$s->scan("/123/")');
assert('$s->getPosition() == 14');

$s = new StringScanner("ab");
assert('$s->scanChar() == "a"');
assert('$s->scanChar() == "b"');
assert('!$s->scanChar()');

$s = new StringScanner("☃\n1");
assert('$s->scanChar() == "☃"');
assert('$s->scanChar() == "\n"');
assert('$s->scanChar() == "1"');
assert('!$s->scanChar()');

$s = new StringScanner("test string");
assert('$s->peek(7) == "test st"');
assert('$s->peek(7) == "test st"');

$s = new StringScanner("test string");
assert('$s->scan("/\w+/") == "test"');
assert('!$s->scan("/\w+/")');
assert('$s->scan("/\s+/") == " "');
assert('$s->scan("/\w+/") == "string"');
assert('!$s->scan("/\w+/")');

$s = new StringScanner("test string");
assert('$s->scan("/\w+/") == "test"');
assert('$s->scan("/\s+/") == " "');
assert('$s->getPreMatch() == "test"');
assert('$s->getPostMatch() == "string"');

$s = new StringScanner("Fri Dec 12 1975 14:39");
assert('$s->scanUntil("/1/") == "Fri Dec 1"');
assert('$s->getPreMatch() == "Fri Dec "');
assert('!$s->scanUntil("/XYZ/")');

$s = new StringScanner("abaabaaab");
assert('$s->scanUntil("/b/") == "ab"');
assert('$s->scanUntil("/b/") == "aab"');
assert('$s->scanUntil("/b/") == "aaab"');

$s = new StringScanner("test string");
assert('$s->skip("/\w+/") == 4');
assert('!$s->skip("/\w+/")');
assert('$s->skip("/\s+/") == 1');
assert('$s->skip("/\w+/") == 6');
assert('!$s->skip("/./")');

$s = new StringScanner("Fri Dec 12 1975 14:39");
assert('$s->skipUntil("/12/") == 10');
assert('$s->peek() == " "');
assert('$s->peek(3) == " 19"');

$s = new StringScanner("test string");
assert('$s->scan("/\w+/") == "test"');
$s->unscan();
assert('$s->scan("/../") == "te"');
assert('!$s->scan("/\d/")');

$thrown = true;
try {
    $s->unscan();
    $thrown = false;
} catch (Exception $e) {
}
assert('$thrown');

$s = new StringScanner("Fri Dec 12 1975 14:39");
assert('$s->check("/Fri/") == "Fri"');
assert('$s->getPosition() == 0');
assert('$s->getMatch() == "Fri"');
assert('!$s->check("/12/")');
assert('!$s->getMatch()');

$s = new StringScanner("Fri Dec 12 1975 14:39");
assert('$s->checkUntil("/12/") == "Fri Dec 12"');
assert('$s->getPosition() == 0');
assert('$s->getMatch() == "12"');

$s = new StringScanner("The end.");
assert('$s->scan("/\s*/") === NULL');
assert('$s->scan("/\w+/") === "The"');
assert('$s->scan("/\s+/") === " "');
assert('$s->scan("/\w*/") === "end"');
assert('$s->scan("/./s") === "."');

