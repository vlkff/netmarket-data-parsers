<?php
/**
 * @todo: write description
 * @todo: implement interface for child classes and control methods and properties implementations.
 * @todo: implement debug option
 */

namespace common\spec_parsers;

use yii\base\Exception;

abstract class NumericSpecParser {

    // We need these two to be a constants actually but in PHP constants can't be an arrays.
    static public $keys = array();
    static public $categories = array();

    // A key (spec name) of value being parsed.
    protected $current_key;

    /**
     * Property to store parse result.
     * We need the standard format so SpecParserController can operate with all parsers in same way.
     *
     * Should be in format
     *  array(
     *      array('key', 'value', 'measure'),
     *      array('key', 'value', 'measure'),
     *      ...
     *  )
     * False if no matched regexp
     * NULL if parser recognize the value but it should not be parsed to number.
     */
    protected $parsed;

    protected $parse_called = false;

    /**
     * List of measures the class can operate on.
     * It should be redeclared in child class.
     */
    protected $knownMeasures = array();

    /**
     * string value to parse
     */
    protected $value;

    protected $options = array(
        'debug' => false,
    );

    /**
     * if parsed with regex, write here matched regex for stats
     */
    protected $matched_regex = '';

    abstract protected function patterns();

    abstract protected function convertMeasureSingleValue($to_measure, $row);

    function __construct($value, $current_key = '') {
        $classname = get_class($this);
        if (empty($classname::$keys)) {
            throw new Exception('Class '.$classname.' should declare keys it is able to process in $keys property, while it is empty.');
        }

        $this->value = $value;
        if (!empty($current_key)) {
            $this->current_key = $current_key;
        }
    }

    public function setOption($option, $value) {
        if (!array_key_exists($option, $this->options)) {
            throw new Exception("Unknown option '$option' passed in ". __METHOD__);
        }
        $this->options[$option] = $value;
    }

    public function getParsed() {
        if (!($this->parse_called)) {
            throw new Exception('The parse() method should be called before ' . __METHOD__);
        }
        return $this->parsed;
    }

    public function getMatchedPattern() {
        if (!($this->parse_called)) {
            throw new Exception('The parse() method should be called before ' . __METHOD__);
        }
        return $this->matched_regex;
    }

    public function getKnownMeasures() {
        return $this->knownMeasures;
    }

    public function convertMeasure($measure) {
        if (!($this->parse_called)) {
            throw new Exception('The parse() method should be called before ' . __METHOD__);
        }

        // check is measure in domain
        if (!in_array($measure, $this->getKnownMeasures())) {
            throw new SpecParserException("$measure measure conversion can't be handled ", $this);
        }

        foreach ($this->parsed as $key => $row) {
            $converted = $this->convertMeasureSingleValue($measure, $row);
            $this->parsed[$key]['measure'] = $converted['measure'];
            $this->parsed[$key]['value'] = $converted['value'];
        }
    }



    /**
     * Parse and return result.
     * @return array parsed result or false.
     */
    public function parse() {
        if ($this->parse_called) {
            throw new Exception('->Parse() already been called');
        }

        $this->parse_called = true;
        $patterns = $this->patterns();
        if (empty($patterns)) {
            throw new Exception('No declared patterns');
        }

        if ($this->options['debug']) {
            echo "Trying to match value '$this->value' with patterns: \n";
            array_filter(array_keys($patterns), function($pattern){
                echo $pattern . "\n";
            });
        }

        foreach ($patterns as $pattern => $func) {
            $matches = array();
            if (preg_match($pattern, $this->value, $matches)) {

                if ($this->options['debug']) {
                    echo "Matched by pattern '$pattern', matches are: \n";
                    var_dump($matches);
                }

                $parsed = $func($matches);

                if (is_null($parsed)) {
                    $this->parsed = null;
                    $this->matched_regex = '';
                    if ($this->options['debug']) {
                        echo "Matched pattern '$pattern', return NULL \n";
                    }
                    return null;
                }

                $parsed = $this->formatParsed($parsed);

                if ($this->options['debug']) {
                    echo "Parse result is \n";
                    var_dump($parsed);
                }

                if (!empty($matches) && empty($parsed)) {
                    //var_dump($matches);
                    throw new SpecParserException("Regexp match the value but the parsing result is empty", $this, $pattern);
                }

                $this->parsed = $this->validateParsed($parsed, $pattern);
                $this->matched_regex = $pattern;

                // convert values to floats
                foreach ($this->parsed as &$row) {
                    if (is_string($row['value'])) {
                        $row['value'] = floatval($row['value']);
                    }
                }

                return $this->parsed;
            }
        }
        // Parsing wasn't success.
        if ($this->options['debug']) {
            echo "There are no matches\n";
        }

        $this->parsed = false;
        $this->matched_regex = '';
        return $this->parsed;
    }

    protected function formatParsed($parsed) {
        if (array_key_exists('key', $parsed) && array_key_exists('value', $parsed)) {
            $parsed = array($parsed);
        }

        foreach ($parsed as &$parsed_row) {
            if (!array_key_exists('measure', $parsed_row)) {
                $parsed_row['measure'] = '';
            }
            if (!array_key_exists('key', $parsed_row)) {
                $parsed_row['key'] = $this->current_key;
            }
        }

        return $parsed;
    }

    /**
     * Validate format of successfully parsed data.
     * It must be array(array('key' => key, 'value' => value, 'measure'=> measure), ... )
     * All measures must be declared in ->knownMeasures
     */
    protected function validateParsed($parsed, $pattern = 'unknown') {
        $required_keys = array('key', 'value', 'measure');
        $format_error_message = "Parse callback function for pattern $pattern should return an array in format " .
            "array(array('key' => key, 'value' => value, 'measure' => measure), ... ) ";

        if (!is_array($parsed)) {
            throw new SpecParserException($format_error_message, $this, $pattern);
        }
        foreach ($parsed as $parsed_row) {
            if (!is_array($parsed_row)) {
                throw new SpecParserException($format_error_message, $this, $pattern);
            }
            // Check is all keys in place
            foreach ($required_keys as $required_key) {
                if(!array_key_exists($required_key, $parsed_row)) {
                    throw new SpecParserException($format_error_message, $this, $pattern);
                }
            }

            if (!empty($this->knownMeasures) && !in_array($parsed_row['measure'], $this->knownMeasures)) {
                throw new SpecParserException("Undeclared measure '{$parsed_row['measure']}' returned. Declare it in ->knownMeasures", $this, $pattern);
            }

            if (!is_numeric($parsed_row['value'])) {
                throw new SpecParserException("Not a number returned for 'value'", $this, $pattern);
            }

            if (!is_string($parsed_row['key'])) {
                throw new SpecParserException("Not a string returned for 'key'", $this, $pattern);
            }

            if ((is_array($this->knownMeasures)) && count($this->knownMeasures) > 0) {
                if (empty($parsed_row['measure'])) {
                    throw new SpecParserException("Measure can not be empty if there are a known measures in a parser", $this, $pattern);
                }

                if (!is_string($parsed_row['measure'])) {
                    throw new SpecParserException("Measure should be a string", $this, $pattern);
                }
            }
        }

        if (!empty($err_message)) {
            throw new SpecParserException($err_message, $this, $pattern);
        }

        return $parsed;
    }

    /**
     * Helper for patterns creation.
     *
     * Generate a set of simple patterns like
     *      /^(\d+(\.|,){1}\d+)\s('.$measure_alias.'){1}$/mi
     *      /^(\d+)\s('.$measure_alias.'){1}$/mi
     *
     * @param string key name of spec to return in parse results array
     * @param measures array keyed array of recognized measures with they possible aliases e.g.
     *      array( array('Gb' => 'ГБ', 'гб', 'Gig', 'Gb'), 'Mb' => array('Мб', 'МБ', 'Mb', 'MB'), ... )
     */
    static protected function generateSimplePatterns($key, $measures) {
        $patterns = array();
        foreach ($measures as $measure => $measure_aliases) {
            if (!is_array($measure_aliases)) {
                throw new Exception('Wrong format of passed $measures argument.');
            }
            foreach ($measure_aliases as $measure_alias) {
                $patterns['/^(\-?\d+(\.|,){1}\d+)\s?('.$measure_alias.'){1}$/mi'] =
                    function($matches) use ($measure, $key) {
                        $parsed = array();
                        $parsed[] = array(
                            'key' => $key,
                            'value' => str_replace(',', '.', $matches[1]),
                            'measure' => $measure,
                        );
                        return $parsed;
                    };

                $patterns['/^(\-?\d+)\s?('.$measure_alias.'){1}$/mi'] =
                    function($matches) use ($measure, $key) {
                        $parsed = array();
                        $parsed[] = array(
                            'key' => $key,
                            'value' => $matches[1],
                            'measure' => $measure,
                        );
                        return $parsed;
                    };
            }
        }

        return $patterns;
    }

    /**
     * Helper for patterns creation.
     *
     * @measures array keyed array of measure aliases by measure.
     *
     * @return flat array of measure aliases from keyed array of measures.
     */
    static protected function getMeasureAliases ($measures) {
        $variants = array();
        foreach ($measures as $aliases) {
            foreach ($aliases as $alias) {
                $variants[] = $alias;
            }
        }
        return $variants;
    }

    /**
     * Helper for patterns creation.
     *
     * @return measure by measure alias.
     */
    static function getMeasureByAlias($needle_alias, $measures) {
        foreach ($measures as $measure => $aliases) {
            foreach ($aliases as $alias) {
                $alias = str_replace('\\', '', $alias);
                if ($needle_alias == $alias) {
                    return $measure;
                }
            }
        }
    }
}
