<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

// Register Symfony's process-wide handler before PHPUnit snapshots test ownership.
// FrameworkBundle reuses it when a Kernel boots on Symfony 7.4 and 8.0.
Symfony\Component\ErrorHandler\ErrorHandler::register();
