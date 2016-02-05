<?php
/**
 * @todo: write description
 */

namespace common\spec_parsers;

class PowerCat4495SpecParser extends NumericSpecParser {

    // These two attributes defines key of base_spec table and category id's the parser class is applicable to.
    static public $keys = array('Мощность');
    static public $categories = array(4495);

    protected $knownMeasures = array('кВт');

    protected $measures = array(
        'кВт' => array('кВт'),
    );

    protected function patterns() {
        return;
    }

    public function parse() {
        if ($this->parse_called) {
            return $this->getParsed();
        }


        if ($this->options['debug']) {
            echo "Trying to match value '$this->value' \n";
        }

        // parse 1 кВт / 1 кВт / 2 кВт, select the max value
        $exploded_values = explode('/', $this->value);
        if ($this->options['debug']) {
            echo "Exploded values are ".var_export($exploded_values, true)." \n";
        }

        $result = array();
        foreach ($exploded_values as $value) {
            $parser = new PowerSpecParser(trim($value));
            if ($this->options['debug']) {
                $parser->setOption('debug', true);
            }
            $parsed = $parser->parse();
            if ($parsed) {
                $result[ $parsed[0]['value'] ] = $parsed;
            }
        }

        if (!empty($result)) {
            $max_key = max(array_keys($result));
            $this->parsed = $result[$max_key];
            $this->parsed = $this->validateParsed($this->parsed);
            $this->matched_regex = '';
            $this->parse_called = true;
            if ($this->options['debug']) {
                echo "Parse result is ".var_export($this->parsed, true)." \n";
            }
            return $this->parsed;
        }

        if ($this->options['debug']) {
            echo "Parse result is empty \n";
        }
        $this->parsed = false;
        $this->parse_called = true;
        $this->matched_regex = '';
        return false;
    }

    protected function convertMeasureSingleValue($to_measure, $row) {
        return $row;
    }
}
