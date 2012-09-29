# strscan-php
strscan-php is a simple string tokenizer for lexical scanning operations. It's a
PHP port of [strscan-js](https://github.com/sstephenson/strscan-js), which in
turn is a JavaScript port of the Ruby library with the same name. This library
assumes the mbstring extension is enabled and all strings to be UTF-8.

## Installation
The recommended way to install strscan-php is [through composer](http://getcomposer.org).

## Usage
```php
<?php
include '/path/to/StrScan/StringScanner.php';
use StrScan\StringScanner;
$s = new StringScanner("This is a test");
$s->scan("/\w+/");             # => "This"
$s->scan("/\w+/");             # => null
$s->scan("/\s+/");             # => " "
$s->scan("/\s+/");             # => null
$s->scan("/\w+/");             # => "is"
$s->hasTerminated();           # => false
$s->scan("/\s+/");             # => " "
$s->scan("/(\w+)\s+(\w+)/");   # => "a test"
$s->getMatch();                # => "a test"
$s->getCapture(0);             # => "a"
$s->getCapture(1);             # => "test"
$s->hasTerminated();           # => true
```

## License
strscan-php is released under the [MIT license](http://opensource.org/licenses/MIT).

## Acknowledgments
The original strscan-js was written by Sam Stephenson.

