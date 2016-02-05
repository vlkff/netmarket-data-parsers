<?php
/**
 * @todo: write description
 *
 * @todo: implement rows batch processing to increase performance:
 *  split write methods to commit() and write() and push data to db when there would be a some mount to commit.
 *
 * @todo: make thrown errors more informative
 * @todo: fix code style.
 */

namespace common\spec_parsers;

use yii\base\Exception;

class SpecParserProcessor {

    // Processor allowed options with default values. Can be set in constructor.
    private $options = array(
        'convert_measure' => false,
        'debug' => false,
        // Log caught parsing exception instead of crash. Set this to FALSE for debugging.
        'crash_on_parsing_exception' => false,
    );

    // Keyed array, base_spec table row to process.
    private $row;

    // Parser object (one of the classes extends NumericSpecParser) that was able to parse the value.
    //
    // false if no declared parsers can parse the record.
    // NULL if parser recognize the value but it should not be parsed to number.
    private $parser;

    // Flag that row processing results already been written to db.
    private $parsed_written_to_db = false;

    private $table = array(
        'nums' => 'base_spec_numeric',       // table to write parsed numeric values
        'filters' => 'base_spec_filters', // we need that table to load measures for key and category
        'processed' => 'spec_parser_processor_processed',      // table to write processed rows
        'errlog' => 'spec_parser_processor_errors'             // errors log caught by try{} catch{}
    );

    private static $parsers_map;

    function __construct($options = array()) {
        foreach ($options as $key => $val) {
            if (array_key_exists($key, $this->options)) {
                $this->options[$key] = $val;
            }
        }
    }

    public function getRow() {
        if (is_null($this->row)) {
            throw new Exception('->setRow() method should be called before ' . __METHOD__);
        }
        return $this->row;
    }

    /**
     * Return parsed value or false.
     * False indicates that there no found parsers or no one could match the value with implemented regexp's.
     */
    public function getParsed() {
        // Check is ->parsed() was run.
        if (!($this->parse_called)) {
            throw new Exception('The ->parse() method should be called before ' . __METHOD__);
        }

        // Return false which indicates what parsing wasn't success.
        // No found parsers or no one could match the value with implemented regex.
        if ($this->parser === false) {
            return false;
        }

        return $this->parser->getParsed();
    }

    /**
     * @return array of spec keys known by implemented parsers.
     */
    public static function getKnownNumericParsersKeys() {
        self::buildParsersMap();
        // Filter out taxonomy parsers.
        $filtered = array_filter(self::$parsers_map['by_key'], function ($value) {
            return !in_array('common\spec_parsers\TaxonomySpecParser', $value);
        });
        return array_keys($filtered);
    }

    public static function getKnownListParsersKeysCats() {
        // Select only vocabularies with terms
        $sql = "SELECT v.key, v.category_id ".
            "FROM spec_vocabulary v ".
            "WHERE EXISTS (SELECT id FROM term WHERE vocabulary_id = v.id)";

        $return = [];
        foreach (\Yii::$app->db->createCommand($sql)->queryAll() as $row) {
            $return[ $row['key'] ][] = $row['category_id'];
        }
        return $return;
    }

    public static function getParsersMap() {
        self::buildParsersMap();
        return self::$parsers_map;
    }

    public function getMatchedPattern() {
        // Check is ->parsed() was run.
        if (!($this->parse_called)) {
            throw new Exception('The ->parse() method should be called before ' . __METHOD__);
        }

        // No found parsers or no one could match the value with implemented regex.
        if ($this->parser === false) {
            return '';
        }

        $pattern = $this->parser->getMatchedPattern();
        // Ensure we always return a string to avoid db query errors.
        $pattern = (empty($pattern)) ? '' : $pattern;
        return $pattern;
    }

    public function getMatchedClassname() {
        // Check is ->parsed() was run.
        if (!($this->parse_called)) {
            throw new Exception('The ->parse() method should be called before ' . __METHOD__);
        }

        // Return false which indicates what parsing wasn't success.
        // No found parsers or no one could match the value with implemented regex.
        if ($this->parser === false) {
            return false;
        }

        return get_class($this->parser);
    }

    /**
     * Set row to process.
     * $row = array('spec_id' => val, 'item_id' => val, 'key' => val, 'value' => val, 'category_id' => val);
     */
    public function setRow($row) {
        // Drop all other values that should be dropped.
        $this->setDefaults();
        // Check is we have all required values,
        $required_keys = array('key', 'value');
        if ($this->options['convert_measure']) {
            $required_keys[] = 'category_id';
        }
        foreach ($required_keys as $required_key) {
            if (!array_key_exists($required_key, $row)) {
                throw new Exception("Required key '$required_key' not exists in passed row: ". var_export($row, true));
            }
        }

        $this->row = $row;
    }

    /**
     * Process the row, write results to db and return parse result.
     * @return array parse result or false for row which value can't be parsed.
     *
     * Processing sequence:
     * - load all parsers classes declaring they can parse the row by key and category,
     * - apply them until first of them will able to return parsed result
     *      (first apply parsers declaring category and key, second declaring key only)
     * - write parsed values (if they are) to base_spec_numeric table and parsing status for the record (success/failure) to spec_parser_processor_processed
     */
    public function processRow() {
        if (empty($this->row)) {
            throw new Exception("You should set row to process with ->setRow() before running ". __METHOD__);
        }

        try {
            $this->parse();
            if ($this->options['convert_measure']) {
                $this->convertMeasure();
            }
        } catch (SpecParserException $e) {
            $this->parser = false;
            $this->processException($e);
        }

        $this->writeDB();
        return $this->getParsed();
    }

    /**
     * Process all passed rows per run. Has much better performance as using batch insert to db.
     */
    public function processRows(&$rows, $inserts_per_query = 250, &$results = []) {
        $results['count_processed'] = 0;
        $results['count_parsed'] = 0;
        $results['count_unparsed'] = 0;

        function commitInserts($inserts) {

            $spec_parser_processor_processed_inserts = [];
            $base_item_term_inserts = [];
            $base_spec_numeric_inserts = [];

            foreach ($inserts as $row) {

                if (!empty($row['base_spec_numeric'])) {
                    foreach ($row['base_spec_numeric'] as $insert) {
                        $base_spec_numeric_inserts[] = array(
                            $insert['base_spec_id'],
                            $insert['item_id'],
                            $insert['key'],
                            $insert['value'],
                            $insert['measure'],
                        );
                    }
                }

                if (!empty($row['base_item_term'])) {
                    foreach ($row['base_item_term'] as $insert) {
                        $base_item_term_inserts[] = $insert;
                    }
                }

                if (!empty($row['spec_parser_processor_processed'])) {
                    $spec_parser_processor_processed_inserts[] = array(
                        $row['spec_parser_processor_processed']['base_spec_id'],
                        (!empty($row['spec_parser_processor_processed']['parsed'])) ? 't' : 'f',
                    );
                }
            }

            $db = \Yii::$app->db;
            $transaction = $db->beginTransaction();
            try {
                if (!empty($spec_parser_processor_processed_inserts)) {
                    //var_dump($spec_parser_processor_processed_inserts);
                    $db->createCommand()->batchInsert('spec_parser_processor_processed',
                        ['base_spec_id', 'parsed'],
                        $spec_parser_processor_processed_inserts)
                        ->execute();
                }

                if (!empty($base_spec_numeric_inserts)) {
                    $db->createCommand()->batchInsert('base_spec_numeric',
                        ['base_spec_id', 'item_id', 'key', 'value', 'measure'],
                        $base_spec_numeric_inserts)
                        ->execute();
                }

                if (!empty($base_item_term_inserts)) {
                    $db->createCommand()->batchInsert('base_item_term',
                        ['term_id', 'base_item_id'],
                        $base_item_term_inserts
                    )
                    ->execute();
                }

                $transaction->commit();
                $processed = count($inserts);
            } catch (Exception $e) {
                $transaction->rollBack();
                //throw $e;
                $processed = 0;
            }
            return $processed;
        }

        $inserts = [];
        foreach ($rows as $key => $row) {

            try {
                $this->setRow($row);
                $parsed = $this->parse();
                if ($this->options['convert_measure']) {
                    $this->convertMeasure();
                }

                if ($parsed || is_null($parsed)) {
                    $results['count_parsed']++;
                } else {
                    $results['count_unparsed']++;
                }

            } catch (SpecParserException $e) {
                $this->parser = false;
                $this->processParserException($e);
            } catch (Exception $e) {
                continue;
            }

            $inserts = array_merge($inserts, $this->writeDB($return_inserts = TRUE));
            if (count($inserts) > $inserts_per_query) {
                $results['count_processed'] += commitInserts($inserts);
                $inserts = [];
            }

            unset($rows[$key]);
        }

        $results['count_processed'] += commitInserts($inserts);
    }

    /**
     * Load parsers declaring they can parse the record and try to apply them it until first success.
     * Only rows have assigned parsers should be passed or it will throw an exception.
     *
     * @todo: we need to distinguish not parsed values (no parser) from parsed with no success.
     *
     * @return array parse results or false.
     */
    public function parse() {
        // Check is already been parsed.
        if ($this->parse_called) {
            return $this->getParsed();
        }

        if ($this->options['debug']) {
            echo "Trying to process row: \n";
            var_dump($this->row);
        }

        $this->parse_called = true;

        // Check is row to parse assigned.
        if (empty($this->row)) {
            throw new Exception("You should set row to process with ->setRow() before running ". __METHOD__);
        }

        // Set parser to false that means that there is no declared parsers for the row or parsing wasn't success.
        // No found parsers or no one could match the value with implemented regex.
        $this->parser = false;

        // load parsers by key and category
        $classnames = $this->getParserClassnames();
        if (empty($classnames)) {
            throw new Exception("Parsers not found to process the row ". var_export($this->row, true));
        }

        if ($this->options['debug']) {
            echo "Found parser classes: \n";
            array_filter($classnames, function($classname){
                echo $classname . "\n";
            });
        }

        // Apply terms parser
        // Don't parse terms yet
        if (in_array('common\spec_parsers\TaxonomySpecParser', $classnames)) {

            if (empty($this->row['category_id'])) {
                throw new Exception('TaxonomySpecParser works only with vocabularies declare both key and category for now');
            }

            try {
                // If category or key is unknown (no vocabulary for given key & category) it will drop en exception.
                $voc_parser = new TaxonomySpecParser($this->row['key'], $this->row['category_id']);
                $parsed_terms = $voc_parser->parse($this->row['value']);
                $this->parser = $voc_parser;
            } catch (Exception $e){
                throw new Exception('Attempt to process with TaxonomySpecParser the row while there is no appropriate vocabulary: '. var_export($this->row, true));
                $parsed_terms = false;
            }

            if ($this->options['debug']) {
                echo "Parse result is: \n";
                var_dump($parsed_terms);
                echo "\n";
            }

            return $parsed_terms;
        }

        // Apply to find first applicable.
        $value = $this->row['value'];
        foreach ($classnames as $classname) {
            $parser = new $classname($value, $this->row['key']);
            $parsed = $parser->parse();
            if (is_array($parsed) || is_null($parsed)) {
                if ($this->options['debug']) {
                    echo "Parser $classname match the value, parse result is: \n";
                    var_dump($parsed);
                }

                if (empty($parser)) {
                    throw new Exception("Parser is empty while it should not.");
                }

                $this->parser = $parser;
                return $parsed;
            }
        }

        return false;
    }

    /**
     * Drop all variables to defaults to prepare the class to process next row.
     */
    private function setDefaults() {
        $this->row = NULL;
        $this->parser = NULL;
        $this->parse_called = false;
        $this->parsed_written_to_db = false;
    }

    /**
     * Load parsers by cat and key.
     * First try to return a parsers declare both key and category.
     * Second return parser declares ONLY a key.
     */
    private function getParserClassnames() {
        if (is_null(self::$parsers_map)) {
            self::buildParsersMap();
        }

        $key = $this->row['key'];
        $category = isset($this->row['category_id']) ? $this->row['category_id'] : NULL;

        if (!empty($category) && !empty(self::$parsers_map['by_category'][$category])) {
            $intersect = array_intersect(self::$parsers_map['by_category'][$category], self::$parsers_map['by_key'][$key]);
            if (!empty($intersect)) {
                return $intersect;
            }
        }

        if (!empty(self::$parsers_map['by_key'][$key])) {
            return array_diff(self::$parsers_map['by_key'][$key], self::$parsers_map['declare_category']);
        }

        return array();
    }

    public static function buildParsersMap() {
        if (is_array(self::$parsers_map)) {
            return;
        }

        $require_dir_files_recursive_func_name = function($dir) use (&$require_dir_files_recursive_func_name) {
            $files = scandir($dir);
            unset($files[0]); // remove .
            unset($files[1]); // remove ..
            foreach ($files as $filename) {
                if (is_file($dir. '/'. $filename)) {
                    require_once $dir . '/'. $filename;
                }
                if (is_dir($dir . '/'. $filename)) {
                    $require_dir_files_recursive_func_name(__DIR__ . '/'. $filename);
                }
            }
        };

        $require_dir_files_recursive_func_name(__DIR__);
        $classnames = array_filter(get_declared_classes(), function($class){
            return is_subclass_of($class, 'common\spec_parsers\NumericSpecParser');
        });

        self::$parsers_map = array();
        foreach ($classnames as $classname) {
            $class_vars = get_class_vars($classname);
            if (!empty($class_vars['categories'])) {
                foreach ($class_vars['categories'] as $category) {
                    self::$parsers_map['by_category'][$category][] = $classname;
                    self::$parsers_map['declare_category'][] = $classname;
                }
            }
            if (!empty($class_vars['keys'])) {
                foreach ($class_vars['keys'] as $key) {
                    self::$parsers_map['by_key'][$key][] = $classname;
                }
            }
        }

        // Add TaxonomySpecParser class to the map based on known vocabularies.

        $vocs_map = TaxonomySpecParser::getVocsMap();
        self::$parsers_map['declare_category'][] = 'common\spec_parsers\TaxonomySpecParser';
        foreach (array_keys($vocs_map['by_category']) as $category) {
            self::$parsers_map['by_category'][$category][] = 'common\spec_parsers\TaxonomySpecParser';
        }

        foreach (array_keys($vocs_map['by_key']) as $key) {
            self::$parsers_map['by_key'][$key][] = 'common\spec_parsers\TaxonomySpecParser';
        }

    }

    /**
     * Load measure from DB by category_id
     * we need cache here.
     */
    public function getMeasure() {
        // @todo: implement it
        $measure = 'кг';
        return $measure;
    }

    public function convertMeasure() {
        // Check is row to parse is set.
        if (empty($this->row)) {
            throw new Exception("You should set row to process with ->setRow() before running ". __METHOD__);
        }

        if (empty($this->parser)) {
            throw new Exception("There is no appropriate parser so we cant convert the measure in ". __METHOD__);
        }

        // Check is parser can convert measures.
        $parser_known_measures = $this->parser->getKnownMeasures();
        if (empty($parser_known_measures)) {
            // we can't throw exception here as processor can be run for rows of multiply keys and some of them may have no
            // measures at all, so we should just ignore them.
            return;
        }

        $measure_to = $this->getMeasure();
        if (!empty($measure_to)) {
            try {
                $this->parser->convertMeasure($measure_to);
            } catch (SpecParserException $e) {
                $this->processException($e);
            }
        }
    }

    /**
     * Write parsed data to db.
     */
    public function writeDB($return_inserts = false) {

        $inserts = [];

        $parsed_value = $this->getParsed();

        if (!($this->parse_called)) {
            throw new Exception('The parse() method should be called before ' . __METHOD__);
        }

        if ($this->parsed_written_to_db) {
            throw new Exception(__METHOD__ . ' method already been called and can\'t be called again for current row. Call ->setRow() to be able to process next row.');
        }

        $required_keys = array('spec_id', 'item_id');
        foreach ($required_keys as $required_key) {
            if (!array_key_exists($required_key, $this->row)) {
                throw new Exception("'$required_key' key required by ". __METHOD__ ." not exists in passed row: ". var_export($this->row, true));
            }
        }

        // @todo use transaction here
        //  https://yiiframework.com.ua/ru/doc/guide/2/db-dao/
        $parsed_status = false;
        if (is_array($parsed_value) && $this->isParserNumeric()) {
            $parsed_status = true;
            // Insert to nums table
            // @todo rework it with batch insert.
            foreach ($parsed_value as $parsed_row) {
                $insert = array(
                    'base_spec_id' => $this->row['spec_id'],
                    'item_id' => $this->row['item_id'],
                    'key' => $parsed_row['key'],
                    'value' => $parsed_row['value'],
                    'measure' => $parsed_row['measure']
                );
                if ($return_inserts) {
                    $inserts[ $this->row['spec_id'] ][$this->table['nums']][] = $insert;
                } else {
                    \Yii::$app->db->createCommand()->insert($this->table['nums'], $insert)->execute();
                }
            }
        }

        if (is_array($parsed_value) && !empty($parsed_value) && $this->isParserTaxonomy()) {
            $parsed_status = true;
            $insert = array();
            foreach ($parsed_value as $term) {
                $insert[] = array(
                    $term['id'],
                    $this->row['item_id'],
                );
            }

            if ($return_inserts) {
                $inserts[ $this->row['spec_id'] ]['base_item_term'] = $insert;
            } else {
                try {
                    \Yii::$app->db->createCommand()->batchInsert('base_item_term', ['term_id', 'base_item_id'], $insert)->execute();
                } catch (Exception $e) {
                    if (strstr($e->getMessage(), 'base_item_term_term_item_unique') !== false) {
                        echo "db key duplication error on attempt to associate a term with item while relation already exists: item id '{$this->row['item_id']}', key '{$this->row['key']}', value '{$this->row['value']}', spec id '{$this->row['spec_id']}'".PHP_EOL;
                        echo "Term: id '{$term['id']}', name '{$term['name']}', synonyms '".implode('|',$term['synonyms'])."'".PHP_EOL;
                        echo "Proceed processing.".PHP_EOL;
                    } else {
                        throw $e;
                    }
                }
            }

        }

        // NULL means that value is known and parsed but can't be converted to number.
        if (is_null($parsed_value)) {
            $parsed_status = true;
        }

        // Insert to table processed rows.
        $insert = array(
            'base_spec_id' => $this->row['spec_id'],
            'parsed' => $parsed_status,
        );

        if ($return_inserts) {
            $inserts[ $this->row['spec_id'] ][ $this->table['processed'] ] = $insert;
        } else {
            \Yii::$app->db->createCommand()->insert($this->table['processed'], $insert)->execute();
        }

        $this->parsed_written_to_db = true;

        if ($return_inserts) {
            return $inserts;
        }
    }

    /**
     * Single processor for all caught exceptions.
     */
    private function processParserException($e) {
        if ($this->options['crash_on_parsing_exception'] === false && get_class($e) == 'common\spec_parsers\SpecParserException') {
            $insert = array(
                'error' => $e->getMessage(),
                'base_spec_id' => $this->row['spec_id'],
            );
            $command = \Yii::$app->db->createCommand()->insert($this->table['errlog'], $insert)->execute();
        }
        else {
            throw $e;
        }
    }

    private function isParserNumeric() {
        return is_subclass_of($this->parser, 'common\spec_parsers\NumericSpecParser');
    }

    private function isParserTaxonomy() {
        return get_class($this->parser) == 'common\spec_parsers\TaxonomySpecParser';
    }
}
