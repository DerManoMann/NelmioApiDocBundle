<?php

use Nelmio\ApiDocBundle\SpecPoC\Run;

require_once __DIR__ . '/vendor/autoload.php';

// php poc.php            — every controller
// php poc.php mapquery   — just the ones whose name matches
(new Run())->poc($argv[1] ?? null);
