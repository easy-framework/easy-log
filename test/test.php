<?php

require __DIR__ . '/../src/Log.php';

use Easy\Log;

try {
    $instance = Log::getInstance();
    $instance->logFileName = 'easy';
    $instance->logPath = __DIR__ . '/../runtime/';
    $instance->logFileSize = 1;
    
    Log::debug('Easy PHP TEST');
    Log::notice('Easy PHP TEST', 'Easy PHP TEST');
    Log::warning('Easy PHP TEST', 'Easy PHP TEST', 'Easy PHP TEST');
    Log::error('Easy PHP TEST', 'Easy PHP TEST', 'Easy PHP TEST', '...');

} catch (Exception $e) {
    die('Test Fail:'. $e->getMessage());
}

