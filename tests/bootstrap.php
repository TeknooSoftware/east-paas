<?php

declare(strict_types=1);

/**
 * @author      Richard Déloge <richard@teknoo.software>
 */

use Symfony\Component\HttpKernel\Kernel;

defined('RUN_CLI_MODE')
    || define('RUN_CLI_MODE', true);

defined('PHPUNIT')
    || define('PHPUNIT', true);

set_time_limit(0);
date_default_timezone_set('UTC');

error_reporting(E_ALL);

ini_set('memory_limit', '320M');

include __DIR__ . '/fakeQuery.php';
include __DIR__ . '/fakeUOW.php';
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
