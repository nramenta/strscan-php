<?php
/**
 * strscan-php - A PHP port of strscan-js.
 *
 * @author  Nofriandi Ramenta <nramenta@gmail.com>
 * @license http://en.wikipedia.org/wiki/MIT_License MIT
 */

namespace StrScan;

/**
 * Represents a string scanner.
 */
class StringScanner
{
    protected $source;
    protected $length;

    protected $head;
    protected $last;

    protected $captures;
    protected $match;

    /**
     * Creates a new StringScanner with a source string.
     *
     * @param string $source   The source string.
     */
    public function __construct($source = '')
    {
        if (!is_string($source)) {
            throw new \InvalidArgumentException(
                'string expected, got `' . gettype($source) . '` instead');
        }
        mb_internal_encoding('UTF-8');
        $this->setSource($source);
    }

    /**
     * Sets the source string.
     *
     * @param string $source The source string
     *
     * @return StringScanner The StringScanner object
     */
    public function setSource($source)
    {
        $this->source = $source;
        $this->length = mb_strlen($source);
        $this->head = 0;
        $this->last= 0;
        $this->captures = array();
        $this->match = null;
        $this->reset();

        return $this;
    }

    /* Scanning for matches */

    /**
     * Matches regexp at the current position.
     *
     * Returns the matched string and advances the scanner’s position,
     * or returns null if there is no match.
     *
     * @param string $regexp The regular expression to match.
     *
     * @return string|null
     */
    public function scan($regexp)
    {
        if (preg_match($regexp, $this->getRemainder(), $matches)) {
            if (strlen($matches[0]) &&
                mb_strpos($this->getRemainder(), $matches[0]) === 0) {
                return $this->setState($matches,
                    $this->head + mb_strlen($matches[0]), $this->head);
            }
        }
        return $this->setState();
    }

    /**
     * Matches regexp at or after the current position.
     *
     * Returns the portion of the source string after the scanner’s position
     * up to and including the end of the match and advances the scanner’s
     * position, or returns null if there is no match.
     *
     * @param string $regexp The regular expression to match.
     *
     * @return string|null
     */
    public function scanUntil($regexp)
    {
        if (preg_match($regexp, $this->getRemainder(), $matches)) {
            if (!strlen($matches[0])) {
                return $this->setState();
            }
            $index = mb_strpos($this->getRemainder(), $matches[0]);
            $this->setState($matches,
                $this->head + $index + mb_strlen($matches[0]), $this->head);
            return mb_substr($this->source, $this->last,
                $this->head - $this->last);
        } else {
            return $this->setState();
        }
    }

    /**
     * Scans one character, returns it, and advances the scanner’s position.
     *
     * This method calls scan().
     *
     * @see StringScanner::scan() for the return semantics.
     *
     * @return string|null
     */
    public function scanChar()
    {
        return $this->scan('/./su');
    }

    /**
     * Skips over the given regexp at the current position.
     *
     * Returns the length of the matched string and advances the scanner’s
     * position, or returns null if there is no match.
     *
     * @param string $regexp The regular expression to match.
     *
     * @return int|null
     */
    public function skip($regexp)
    {
        if ($this->scan($regexp)) {
            return mb_strlen($this->match);
        } else {
            return null;
        }
    }

    /**
     * Skips over the given regexp at or after the current position.
     *
     * Returns the length of the string up to and including the end of the
     * match and advances the scanner’s position, or returns null if there
     * is no match.
     *
     * @param string $regexp The regular expression to match.
     *
     * @return int|null
     */
    public function skipUntil($regexp)
    {
        if ($this->scanUntil($regexp)) {
            return $this->head - $this->last;
        } else {
            return null;
        }
    }

    /*
     * Looking ahead
     * The check, checkUntil and peek methods look for matching strings without
     * advancing the scanner’s position.
     */

    /**
     * Checks to see if regexp can be matched at the current position and
     * returns the matched string without advancing the scanner’s position,
     * or returns null if there is no match.
     *
     * @param string $regexp The regular expression to match
     *
     * @return string|null
     */
    public function check($regexp)
    {
        if (preg_match($regexp, $this->getRemainder(), $matches)) {
            if (strlen($matches[0]) &&
                mb_strpos($this->getRemainder(), $matches[0]) === 0) {
                return $this->setState($matches);
            }
        }
        return $this->setState();
    }

    /**
     * Checks to see if regexp can be matched at or after the current position.
     *
     * Returns the portion of the source string after the current position
     * up to and including the end of the match without advancing the scanner’s
     * position, or returns null if there is no match.
     *
     * @param string $regexp The regular expression to match
     *
     * @return string|null
     */
    public function checkUntil($regexp)
    {
        if (preg_match($regexp, $this->getRemainder(), $matches)) {
            if (!strlen($matches[0])) {
                return $this->setState();
            }
            $index = mb_strpos($this->getRemainder(), $matches[0]);
            $this->setState($matches);
            return mb_substr($this->source, $this->head,
                ($this->head + $index + mb_strlen($matches[0])) - $this->head);
        } else {
            return $this->setState();
        }
    }

    /**
     * Returns the next length characters after the current position.
     *
     * If called without a length, returns the next character.
     * The scanner’s position is not advanced.
     *
     * @param int $length The number of characters to peek.
     *
     * @return string|bool Returns a string or false on failure.
     */
    public function peek($length = 1)
    {
        return mb_substr($this->source, $this->head, $length);
    }

    /*
     * Accessing scanner data
     * The getSource, getRemainder, getPosition and hasTerminated methods
     * provide information about the scanner’s source string and position.
     */

    /**
     * Returns the scanner’s source string.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Returns the portion of the source string from the scanner’s position
     * onward.
     *
     * @return string|bool Returns a string or false on failure.
     */
    public function getRemainder()
    {
        return mb_substr($this->source, $this->head);
    }

    /**
     * Returns the scanner’s position. In the reset position, this value is
     * zero. In the terminated position, this value is the length of the source
     * string.
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->head;
    }

    /**
     * Checks to see if the scanner has reached the end of the string.
     *
     * @return bool
     */
    public function hasTerminated()
    {
        return $this->head == $this->length;
    }

    /*
     * Accessing match data
     * The getPreMatch, getMatch, getPostMatch and getCapture methods provide
     * information about the most recent match.
     */

    /**
     * Returns the portion of the source string leading up to, but not
     * including, the most recent match. Returns null if there is no recent
     * match.
     *
     * @return string|null
     */
    public function getPreMatch()
    {
        if ($this->match) {
            return mb_substr($this->source, 0,
                ($this->head - mb_strlen($this->match)));
        } else {
            return null;
        }
    }

    /**
     * Returns the most recently matched portion of the source string, or null
     * if there is no recent match.
     *
     * @return string|null
     */
    public function getMatch()
    {
        return $this->match;
    }

    /**
     * Returns the portion of the source string immediately following the most
     * recent match, or null if there is no recent match.
     *
     * @return string|null
     */
    public function getPostMatch()
    {
        if ($this->match) {
            return mb_substr($this->source, $this->head);
        } else {
            return null;
        }
    }

    /**
     * Returns the indexth capture from the most recent match, or null if there
     * is no recent match.
     *
     * @return string|null
     */
    public function getCapture($index)
    {
        if (isset($this->captures[$index])) {
            return $this->captures[$index];
        } else {
            return null;
        }
    }

    /*
     * Modifying the scanner’s state
     * The reset, terminate, concat and unscan methods let you change the state
     * of the scanner.
     */

    /**
     * Resets the scanner back to its original position and clears its match
     * data.
     *
     * @return StringScanner
     */
    public function reset()
    {
        $this->setState(array(), 0, 0);
        return $this;
    }

    /**
     * Advances the scanner position to the end of the string and clears its
     * match data.
     *
     * @return StringScanner
     */
    public function terminate()
    {
        $this->setState(array(), $this->length, $this->head);
        return $this;
    }

    /**
     * Appends string to the scanner’s source string. The scanner’s position is
     * not affected.
     *
     * @return StringScanner
     */
    public function concat($string)
    {
        $this->source .= $string;
        $this->length = mb_strlen($this->source);
        return $this;
    }

    /**
     * Sets the scanner’s position to its previous position and clears its
     * match data. Only one previous position is stored. Throws an exception if
     * there is no previous position.
     *
     * @return StringScanner
     */
    public function unscan()
    {
        if ($this->match) {
            $this->setState(array(), $this->last, 0);
            return $this;
        } else {
            throw new \RuntimeException('nothing to unscan');
        }
    }

    /**
     * Sets the state of the scanner (for internal use only).
     *
     * Returns either the second element from matches or null.
     *
     * @param array    $matches
     * @param int|null $head
     * @param int|null $last
     * 
     * @return string|null
     */
    protected function setState($matches = array(), $head = null, $last = null)
    {
        if (isset($head)) {
            $this->head = $head;
        }
        if (isset($last)) {
            $this->last = $last;
        }
        if (is_array($matches)) {
            $this->captures = array_slice($matches, 1);
            if (count($matches)) {
                $this->match = $matches[0];
            } else {
                $this->match = null;
            }
            return $this->match;
        } else {
            return null;
        }
    }
}

