<?php

namespace Wexo\EasyTranslate\Service;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Wexo\EasyTranslate\WexoEasyTranslate;

class LogService
{
    private Logger $logger;

    /**
     * @param string $logsDir
     */
    public function __construct(string $logsDir)
    {
        $this->logger = new Logger(WexoEasyTranslate::LOG_CHANNEL);
        $this->logger->pushHandler(new StreamHandler($logsDir . '/wexoeasytranslate.log'));
    }

    /**
     * @param string $msg
     * @param array $context
     * @return void
     */
    public function logError(string $msg, array $context = [])
    {
        $this->logger->log(Logger::ERROR, $msg, $context);
    }
}
