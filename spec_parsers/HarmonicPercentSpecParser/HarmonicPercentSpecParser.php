<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class HarmonicPercentSpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Коэффициент гармоник');
    static public $categories = array();

    protected $knownMeasures = array('%');

    protected $measures = array(
        '%' => array('%'),
    );

    protected function patterns() {

        $measures = $this->measures;
        $measures_list = implode('|', $this->getMeasureAliases($measures));

        $patterns = [];

        // строк: 24-94 кГц; кадров: 50-76 Гц
        $patterns['/(\d\.?\d*)\s?('. $measures_list .')/mi'] = function ($matches) use ($measures) {
            $max_key = array_search(max($matches[1]), $matches[1]);
            return array(
                'key' => 'Коэффициент гармоник (не больше)',
                'value' => $matches[1][$max_key],
                'measure' => \common\spec_parsers\NumericSpecParser::getMeasureByAlias($matches[2][$max_key], $measures),
            );
        };

        return $patterns;
    }

    /**
     * A copy of parent::parser with one distinction - use preg_match_all instead of preg_match.
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
            if (preg_match_all($pattern, $this->value, $matches)) {

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

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
