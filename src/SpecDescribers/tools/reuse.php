<?php
// Builder reuse (PR 50): the same configured Builder, built twice.
chdir(__DIR__.'/../../..');
require 'vendor/autoload.php';
use Nelmio\ApiDocBundle\SpecDescribers\{NelmioServices, SpecGenerator};
$c = $argv[1] ?? 'MapRequestPayloadController';
$builder = (new SpecGenerator(new NelmioServices($c, $argv[2] ?? null)))->builder();
$first = $builder->build()->toJson();
try {
    $second = $builder->build()->toJson();
    echo $first === $second ? "same output\n" : "DIFFERENT output: ".strlen($first)." vs ".strlen($second)." bytes\n";
    file_put_contents(sys_get_temp_dir().'/reuse-1.json', $first); file_put_contents(sys_get_temp_dir().'/reuse-2.json', $second);
} catch (Throwable $e) {
    echo "second build threw: ", get_class($e), ': ', substr($e->getMessage(), 0, 200), "\n";
}
