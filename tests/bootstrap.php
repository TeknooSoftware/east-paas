<?php
/**
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
defined('RUN_CLI_MODE')
    || define('RUN_CLI_MODE', true);

defined('PHPUNIT')
    || define('PHPUNIT', true);

set_time_limit(0);
date_default_timezone_set('UTC');

error_reporting(E_ALL | E_STRICT);

ini_set('memory_limit', '128M');

include 'fakeQuery.php';
include 'fakeUOW.php';
include __DIR__.'/../vendor/autoload.php';

if (!\class_exists(\MongoGridFSFile::class)) {
    /*
     * To avoid error on test where mongodb lib is not installed
     */
    class MongoGridFSFile
    {
        public function getSize()
        {
        }
    }
}
