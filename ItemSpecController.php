<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace console\controllers;

use Yii;
use yii\base\Exception;
use yii\console\Controller;

use common\spec_parsers\SpecParserProcessor;

/**
 * This command operates on product specifications:
 *   Parse specifications from free text to structured values.
 *   Help to form filters for specs.
 *
 * @author Vlad Bogatyrov <sorosand@gmail.com>
 */
class ItemSpecController extends Controller
{
    private $table = array(
        'specs' => 'base_spec',
        'nums' => 'base_spec_numeric',
        'processed' => 'spec_parser_processor_processed',
        'errlog' => 'spec_parser_processor_errors',
        'parselog' => 'spec_parser_processor_parsed_log',
    );

    /**
     * Show base_spec table processing stats.
     */
    public function actionIndex() {
        echo $this->getHelp() . "\n";
    }

    /**
     * Helper to test a value against a parser.
     * @param string value to test
     * @param string parser classname
     */
    public function actionTestParser($parser_classname, $key, $value) {
        // Call SpecParserProcessor::buildParsersMap() to load all parser classes, as they placement not follow PSR and
        // can't be autoloaded.
        $processor = new SpecParserProcessor();
        $processor->buildParsersMap();

        $full_classname = "\common\spec_parsers\\$parser_classname" ;
        $p = new $full_classname($value, $key);
        $p->setOption('debug', true);
        $p->parse();
    }

    /**
     * Create / Truncate / Delete tables related to base_spec table processing.
     *
     * @param string, table to operate on, acceptable values are:
     *     'nums' for base_spec_numbers,
     *     'processed' for spec_parser_processor_processed,
     *     'errlog' for spec_parser_processor_errors,
     *     'parselog' for spec_parser_processor_parsed_log
     *
     * @param string action with table, acceptable values are:
     *     'create', 'truncate', 'drop', 'name'
     *
     * @param string, pass 'debug' to show sql instead running it.
     */
    public function actionTableOps($table, $op , $debug = '')
    {

        if ($table == 'all' && $op == 'truncate') {
            $this->actionTableOps('nums', 'truncate');
            $this->actionTableOps('processed', 'truncate');
            $this->actionTableOps('errlog', 'truncate');
            $this->actionTableOps('parselog', 'truncate');
            return;
        }

        if (!in_array($op, array('create', 'truncate', 'drop', 'name'))) {
            echo "op avaliable values are: create, truncate, drop, name \n";
            return;
        }

        $tables = $this->table;
        unset($tables['specs']); // We don't want allow to drop base_spec table..

        if (!array_key_exists($table, $tables)) {
            echo "Unknown table argument passed. Table avaliable values are: " . implode(', ', array_keys($tables)) . " \n";
            return;
        }

        $sql_commands = array();

        if ($op == 'create') {
            switch($table) {
                // @todo insert REFERENCES base_spec for nums table
                // @todo insert REFERENCES base_items for nums table
                // @todo move tables description to SpecParserProcessor ?

                case 'nums':
                    $sql_commands[] = "CREATE SEQUENCE base_spec_numeric_id_seq; ";
                    $sql_commands[] = "CREATE TABLE $tables[$table] ( " .
                        "id bigint primary key DEFAULT nextval('base_spec_numeric_id_seq'),  " .
                        "item_id bigint NOT NULL REFERENCES base_items,  " .
                        "base_spec_id bigint NOT NULL REFERENCES base_spec,  " .
                        "key varchar(255) NOT NULL,  " .
                        "value real NOT NULL,  " .
                        "measure varchar(31) NOT NULL DEFAULT ''" .
                        ");";
                    break;
                case 'processed':
                    $sql_commands[] = "CREATE TABLE $tables[$table] ( " .
                        "base_spec_id bigint primary key REFERENCES base_spec,  " .   // parsed numeric value
                        "parsed boolean NOT NULL DEFAULT false" .
                        ");";
                    break;
                case 'errlog':
                    $sql_commands[] = "CREATE SEQUENCE spec_parser_processor_errors_id_seq; ";
                    $sql_commands[] = "CREATE TABLE $tables[$table] ( " .
                        "id bigint primary key DEFAULT nextval('spec_parser_processor_errors_id_seq'),  " .
                        "base_spec_id bigint NOT NULL, " .
                        "error text NOT NULL" .
                        ");";
                    break;
                case 'parselog':
                    $sql_commands[] = "CREATE TABLE $tables[$table] ( " .
                        "base_spec_id bigint primary key NOT NULL, " .
                        "value text NOT NULL," .
                        "parsed text NOT NULL," .
                        "pattern varchar(255) NOT NULL," .
                        "parser_classname varchar(255) NOT NULL" .
                        ");";
            }
        }

        switch ($op) {
            case 'truncate':
                // drop/truncate sequences as well
                $sql_commands[] = "TRUNCATE TABLE $tables[$table]";
                break;
            case 'drop':
                $sql_commands[] = "DROP TABLE $tables[$table]";
                switch ($table) {
                    case 'nums': $sql_commands[] = "DROP SEQUENCE base_spec_numeric_id_seq; "; break;
                    case 'processed': break;
                    case 'errlog': $sql_commands[] = "DROP SEQUENCE spec_parser_processor_errors_id_seq; "; break;
                }

                break;
            case 'name':
                echo "$tables[$table] \n";
                break;
        }

        if (!empty($sql_commands)) {
            foreach ($sql_commands as $command) {
                if ($debug == 'debug') {
                    echo "\n" .$command. "\n";
                } else {
                    $conn = \Yii::$app->db;
                    $command = $conn->createCommand($command)->execute();
                }
            }
        }
    }

    /**
     * Check is all required tables exists.
     */
    private function checkRequiredTables() {
        $conn = \Yii::$app->db;
        $tables = $this->table;
        if (empty($log_parsed)) {
            unset($tables['parselog']);
        }
        foreach ($tables as $alias => $table) {
            try {
                $conn->createCommand("SELECT * FROM $table LIMIT 1")->execute();
            } catch (Exception $e) {
                echo "Table $table should exists before running this command, use 'php yii item-spec/table-ops $alias create' command to create it.\n";
                return;
            }
        }
    }

    private function processRowsBatch ($rows, $per_insert = 250) {
        $specParserProcessor = new SpecParserProcessor(
            array(
                'convert_measure' => false,
                'crash_on_parsing_exception' => false,
                'debug' => false,
            )
        );

        $t1 = time();
        echo count($rows) . " rows loaded \n";

        $results = [];
        $specParserProcessor->processRows($rows, $per_insert, $results);

        $time = time() - $t1;
        echo "{$results['count_processed']} rows processed for $time seconds, {$results['count_parsed']} rows are parsed and {$results['count_unparsed']} are not. " . PHP_EOL;

    }

    private function processRows ($rows, $log_parsed = '') {
        if (in_array($log_parsed, array('log_parsed', 'log parsed', 'log-parsed', 'log'))) {
            $log_parsed = true;
        }

        $conn = \Yii::$app->db;
        $t1 = time();
        echo count($rows) . " rows loaded \n";

        $counter = 0;
        $matched = 0;
        $unmatched = 0;

        $specParserProcessor = new SpecParserProcessor(
            array(
                'convert_measure' => false,
                'crash_on_parsing_exception' => false,
                'debug' => false,
            )
        );

        foreach ($rows as $key => $row) {
            $specParserProcessor->setRow($row);
            try {
                $parsed = $specParserProcessor->processRow();
            } catch (Exception $e) {
                throw $e;
            }

            if ($parsed || is_null($parsed)) {
                $matched++;
                if ($log_parsed) {
                    $insert = array(
                        'base_spec_id' => $row['spec_id'],
                        'value' => $row['value'],
                        'parsed' => serialize($specParserProcessor->getParsed()),
                        'pattern' => $specParserProcessor->getMatchedPattern(),
                        'parser_classname' => $specParserProcessor->getMatchedClassname(),
                    );
                    $conn->createCommand()->insert($this->table['parselog'], $insert)->execute();
                }
            }
            elseif ($parsed === false) {
                $unmatched++;
                echo "Can't parse: spec id {$row['spec_id']} '".$row['value'] . "' for item id {$row['item_id']}, category id {$row['category_id']} " . PHP_EOL;
            }

            unset($rows[$key]); // save memory.
            $counter++;
        }

        $time = time() - $t1;
        echo "$counter rows processed for $time seconds, $matched rows are parsed and $unmatched are not. " . PHP_EOL;

        if ($counter == 0) {
            echo "All rows already processed \n";
        }
    }

    public function actionStatus () {
        $db = \Yii::$app->db;
        $processed = $db->createCommand('SELECT COUNT(*) FROM spec_parser_processor_processed')
            ->queryScalar();

        $parsed = $db->createCommand('SELECT COUNT(*) FROM spec_parser_processor_processed WHERE parsed IS true')
            ->queryScalar();

        $not_parsed = $db->createCommand('SELECT COUNT(*) FROM spec_parser_processor_processed WHERE parsed IS false')
            ->queryScalar();

        $total = $db->createCommand('SELECT COUNT(*) FROM base_spec')
            ->queryScalar();

        $parced_percange = round($parsed / $processed * 100, 2);

        echo number_format($processed) . " specs processed of ".number_format($total)." total (includinc specs can not be parsed)" . PHP_EOL;
        echo number_format($parsed) . " specs are parsed and " . number_format($not_parsed) . " are not of ". number_format($processed) ." processed, so $parced_percange % are parsed". PHP_EOL;
    }

    /**
     * Takes N rows from base_spec table able to be parsed both by numeric and taxonomy parsers and process it.
     *
     * Details for case of 'all' 'all' args
     * - select N specs rows with keys known to numeric parsers or we have vocs for and not marked as processed.
     * - for each spec row:
     *   - try to apply to spec row taxonomy parser,
     *   - then numeric parser declaring both key and cat,
     *   - then first numeric parser declaring only key which can parse the value.
     *   - write parsing result to db and mark spec row (both parsed and not) as processed.
     *
     * @param string key value from base_spec table to process, can have special 'all' value to process all keys we have parsers for.
     * @param integer category_id if of category to process, can have special 'all' value to process all categories.
     * @param integer number of rows to process per run
     * @param integer number of processed specs to insert per query. If any of inserts throw en error all insearts in batch will be rolled up so they all will be not processed.
     */
    public function actionProcess($key, $category_id, $limit = 10, $per_insert = 250) {
        $this->checkRequiredTables();

        // ToDo: filter incoming args
        if ($key == 'all' && $category_id == 'all') {
            $numeric_keys = specParserProcessor::getKnownNumericParsersKeys();
            $numeric_keys = array_map(function($item) {
                return '\''.$item.'\'';
            }, $numeric_keys);
            $numeric_keys_in = (empty($numeric_keys)) ? "''" : implode(',', $numeric_keys);

            $list_keys_cats = specParserProcessor::getKnownListParsersKeysCats();
            $or_clauses = array();
            foreach ($list_keys_cats as $key => $cats) {
                foreach ($cats as $cat) {
                    if (!empty($cat)) {
                        $or_clauses[] = "(subquery.key = '".$key."' AND subquery.category_id = ".$cat.")";
                    } else {
                        $or_clauses[] = "(subquery.key = '".$key."' )";
                    }
                }
            }

            $sql = "SELECT subquery.* ".
                "FROM ".
                "(SELECT base_spec.item as item_id, base_spec.value as value, base_spec.key as key, base_spec.id as spec_id, base_items.category_id ".
                "FROM base_spec LEFT join base_items ON base_items.id = base_spec.item) as subquery ".
                "LEFT JOIN spec_parser_processor_processed processed ON subquery.spec_id = processed.base_spec_id ".
                "WHERE processed.base_spec_id IS NULL AND ((".implode(' OR ', $or_clauses).") ".
                "OR key IN (" . $numeric_keys_in . ")) ".
                "LIMIT :limit";

        }
        elseif (is_numeric($category_id) && is_string($key)) {
            $sql = "SELECT specs.id AS spec_id, specs.value AS value, specs.item AS item_id, specs.key AS key, items.category_id AS category_id " .
                "FROM {{{$this->table['specs']}}} specs " .
                "LEFT JOIN {{{$this->table['processed']}}}  processed ".
                "ON specs.id = processed.base_spec_id ".
                "LEFT JOIN {{base_items}}  items ".
                "ON specs.item = items.id ".
                "WHERE processed.base_spec_id IS NULL AND specs.key = '".$key."' AND items.category_id = '".$category_id."' LIMIT :limit";
        }
        else {
            echo "Enter specific values both for Key and category_id or set both to 'all' \n";
            return;
        }

        $conn = \Yii::$app->db;
        $command = $conn->createCommand($sql);
        $command->bindValue(':limit', $limit);
        $rows = $command->queryAll();
        if ($per_insert == 1) {
            $this->processRows($rows);
        } else {
            $this->processRowsBatch($rows, $per_insert);
        }

    }

    /**
     * Process only numeric values
     * While processing numeric values we can ignore categories as we always have a parser for key including all categories.
     *
     * Details for case of 'all' 'all' args
     * - select N specs rows with keys known to numeric parsers and not marked as processed.
     * - for each spec row:
     *   - try to apply to spec row taxonomy parser, then numeric parser declaring both key and cat,
     *       then first numeric parser declaring only key which can parse the value.
     *   - write parsing result to db and mark spec row (both parsed and not) as processed.
     *
     * @param string key value from base_spec table to process, can have special 'all' value to process all keys we have parsers for.
     * @param integer category_id if of category to process, can have special 'all' value or be empty to process all categories.
     * @param integer number of rows to process per run
     * @param integer number of processed specs to insert per query. If any of inserts throw en error all insearts in batch will be rolled up so they all will be not processed.
     */
    public function actionProcessNumeric($key, $category_id = '', $limit = 10, $per_insert = 250) {
        $this->checkRequiredTables();

        if ($key == 'all') {
            $keys = specParserProcessor::getKnownNumericParsersKeys();
        } else {
            $keys = array($key);
        }
        $keys = array_map(function($item) {
            return '\''.$item.'\'';
        }, $keys);

        if ($category_id == 'all') {
            $category_id = '';
        }

        $keys_in = implode(',', $keys);

        $where = "specs.key IN (".$keys_in.") AND processed.base_spec_id IS NULL";
        if (!empty($category_id) && is_numeric($category_id)) {
            $where .= " AND items.category_id = :category_id";
        }

        $sql = "SELECT specs.id AS spec_id, specs.value AS value, specs.item AS item_id, specs.key AS key, items.category_id AS category_id " .
            "FROM {{{$this->table['specs']}}} specs " .
            "LEFT JOIN {{{$this->table['processed']}}}  processed ".
            "ON specs.id = processed.base_spec_id ".
            "LEFT JOIN {{base_items}}  items ".
            "ON specs.item = items.id ".
            "WHERE " . $where . " LIMIT :limit";
        $conn = Yii::$app->db;
        $command = $conn->createCommand($sql);
        $command->bindValue(':limit', $limit);
        if (!empty($category_id) && is_numeric($category_id)) {
            $command->bindValue(':category_id', $category_id);
        }
        $rows = $command->queryAll();

        if ($per_insert == 1) {
            $this->processRows($rows);
        } else {
            $this->processRowsBatch($rows, $per_insert);
        }
    }

    /**
     * Process only list values.
     *
     * Details for case of 'all' 'all' args
     * - select N specs rows for which we have vocs (for a case 'all' and 'all' args) and not marked as processed.
     * - for each spec row:
     *   - try to apply to spec row taxonomy parser (which we expect to exist), then numeric parser declaring both key and cat,
     *       then first numeric parser declaring only key which can parse the value.
     *   - write parsing result to db and mark spec row (both parsed and not) as processed.
     *
     * @param string key value from base_spec table to process, can have special 'all' value to process all keys we have vocabularies for.
     * @param integer category_id if of category to process, can have special 'all' value to process all categories we have vocabularies for.
     * @param integer number of rows to process per run
     * @param integer number of processed specs to insert per query. If any of inserts throw en error all insearts in batch will be rolled up so they all will be not processed.
     *
     */
    public function actionProcessLists($key, $category_id, $limit = 10, $per_insert = 50) {
        $this->checkRequiredTables();
        if ($key == 'all' && $category_id == 'all') {
            $list_keys_cats = specParserProcessor::getKnownListParsersKeysCats();
        }
        elseif (is_string($key) && is_numeric($category_id)) {
            $list_keys_cats = [$key => [$category_id]];
        }
        else {
            echo "Enter specific values both for Key and category_id or set both to 'all' \n";
            return;
        }


        $or_clauses = array();
        foreach ($list_keys_cats as $key => $cats) {
            foreach ($cats as $cat) {
                if (!empty($cat)) {
                    $or_clauses[] = "(subquery.key = '".$key."' AND subquery.category_id = ".$cat.")";
                } else {
                    $or_clauses[] = "(subquery.key = '".$key."' )";
                }
            }
        }
        if (empty($or_clauses)) {
            echo "No vocabularies with a terms found, there is nothing to process \n";
            return;
        }

        $sql = "SELECT subquery.* ".
            "FROM ".
            "(SELECT base_spec.item as item_id, base_spec.value as value, base_spec.key as key, base_spec.id as spec_id, base_items.category_id ".
            "FROM base_spec LEFT join base_items ON base_items.id = base_spec.item) as subquery ".
            "LEFT JOIN spec_parser_processor_processed processed ON subquery.spec_id = processed.base_spec_id ".
            "WHERE processed.base_spec_id IS NULL AND (".implode(' OR ', $or_clauses).") ".
            "LIMIT :limit";
        
        $conn = Yii::$app->db;
        $command = $conn->createCommand($sql);
        $command->bindValue(':limit', $limit);
        $rows = $command->queryAll();
        if ($per_insert == 1) {
            $this->processRows($rows);
        } else {
            $this->processRowsBatch($rows, $per_insert);
        }
    }

    public function getFiltersWithNoParsers () {
        // Select cats and keys showed as a filters ordered by cats popularity
        $sql = "SELECT c.id as category_id, f.name as spec_key ".
            "FROM base_cats c RIGHT JOIN base_filters f ON c.id = f.category ".
            "WHERE c.id IS NOT NULL ".
            "AND f.important=true ".
            "AND c.id NOT IN (select distinct pid from base_cats WHERE pid IS NOT NULL) ".
            //"AND c.name NOT IN (select name from base_cats group by name having count(id) > 1) ".
            "order by popularity desc";
        $cats_and_keys = \Yii::$app->db->createCommand($sql)->queryAll();

        // Filter out cats and keys we have the vocabularies for.
        $list_parsers_keys_cats = specParserProcessor::getKnownListParsersKeysCats();
        $cats_and_keys = array_filter($cats_and_keys, function($row) use ($list_parsers_keys_cats) {
            if (!isset($list_parsers_keys_cats[ $row['spec_key'] ])) {
                return true;
            }
            return !(in_array($row['category_id'], $list_parsers_keys_cats[ $row['spec_key'] ]));
        });

        // Filter out cats and keys we have numeric parsers for
        $numeric_parsers_keys = specParserProcessor::getKnownNumericParsersKeys();
        $cats_and_keys = array_filter($cats_and_keys, function($row) use ($numeric_parsers_keys) {
            return !in_array($row['spec_key'], $numeric_parsers_keys);
        });

        return $cats_and_keys;
    }

    /**
     * Show pairs of keys and categories which have no numeric or list processors sorted by category popularity.
     *
     * @param integer $limit nubmer of rows to show.
     */
    public function actionShowFiltersWithNoParsers($limit, $ignore_file = '') {

        $ignore = array();
        if (!empty($ignore_file)) {
            $str = file_get_contents($ignore_file);
            $rows = explode(PHP_EOL, $str);
            foreach ($rows as $row) {
                $exploded = explode(',', $row);
                $ignore[trim($exploded[0])][] = trim($exploded[1]);
            }
        }

        $rows = $this->getFiltersWithNoParsers($limit);
        $rows = array_filter($rows, function($row) use ($ignore) {

            if (!array_key_exists($row['category_id'], $ignore)) {
                return TRUE;
            }

            if (false === array_search($row['spec_key'], $ignore[ $row['category_id'] ])) {
                return TRUE;
            }

            return FALSE;
        });

        $counter = 0;
        $show = '';
        foreach ($rows as $row) {
            $show .= $row['category_id'] .', '. $row['spec_key'] . PHP_EOL;
            $counter++;
            if ($limit == $counter) {
                break;
            }
        }
        echo $show;
        echo "$counter records shown" . PHP_EOL;
        echo "Warning!! Right now it is show ONLY category-key pairs for categories which have no duplicates " . PHP_EOL;
    }
}
