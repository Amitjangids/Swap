<?php 

namespace App\Logger;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;


class BdaLog
{
    public function __invoke(array $config)
    {
        $month = date('Y-m');
        $date = date('Y-m-d');
        $folder = storage_path("logs/BDA/{$month}");

        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        $file = "{$folder}/BDA-{$date}.log";

        if (!file_exists($file)) {
            touch($file);
            @chown($file, 'www-data');
            @chgrp($file, 'www-data');
        }

        $handler = new StreamHandler($file, Logger::toMonologLevel($config['level'] ?? 'info'));

        // Custom format: no [] []
        $output = "[%datetime%] %channel%.%level_name%: %message%\n";
        $formatter = new LineFormatter($output, null, true, true);
        $handler->setFormatter($formatter);



        $logger = new Logger('BDA');
        $logger->pushHandler($handler);

        return $logger;
    }
}
