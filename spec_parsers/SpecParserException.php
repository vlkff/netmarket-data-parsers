<?php
/**
 * Class SpecParserException
 * @package common\spec_parsers
 *
 * A special exception class to handle logic errors happens on items spec parsing.
 * Errors can be related to wrong parsing behavior, formats of methods return values, etc.
 * @see  NumericSpecParser::validateParsed as using example.
 */

namespace common\spec_parsers;

class SpecParserException extends \yii\base\Exception {
    // object of one of classes extends NumericSpecParser
    protected $parser;
    // regexp that match the parsed value
    protected $pattern;

    function __construct($msg, $parser = null, $pattern = '') {
        $this->parser = $parser;
        $this->pattern = $pattern;

        if (!empty($parser)) {
            $msg .= ", Classname: ". get_class($parser) . ', ';
        }

        if (!empty($pattern)) {
            $msg .= ", Pattern:" . $pattern  . ', ';
        }

        // @todo pass $this as 3rd arg?
        parent::__construct($msg, 0, null);
    }
}
