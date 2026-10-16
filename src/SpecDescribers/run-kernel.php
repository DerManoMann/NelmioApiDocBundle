<?php
// php src/SpecDescribers/run-kernel.php - FunctionalTest's TestKernel, default area, against POC_ORACLE
require __DIR__.'/../../vendor/autoload.php';

use Nelmio\ApiDocBundle\SpecDescribers\NelmioServices;
use Nelmio\ApiDocBundle\SpecDescribers\SpecGenerator;

$version = getenv('POC_VERSION') ?: '3.0.0';
$out = getenv('POC_OUT') ?: sys_get_temp_dir();
$result = (new SpecGenerator(NelmioServices::testKernel(), $version, logger: new class extends Psr\Log\AbstractLogger {
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        if ('debug' !== $level) {
            echo "builder log: $level $message\n";
        }
    }
}))->build();

$normalise = static function (mixed $json) use (&$normalise): mixed {
    if (!\is_array($json)) {
        return $json;
    }
    if (!array_is_list($json)) {
        ksort($json);
    }

    return array_map($normalise, $json);
};
$actual = $normalise(json_decode($result->toJson(), true));
$expected = $normalise(json_decode(file_get_contents(getenv('POC_ORACLE')."/TestKernel-$version.json"), true));
$flags = \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES;
file_put_contents("$out/TestKernel.expected.json", json_encode($expected, $flags)."\n");
file_put_contents("$out/TestKernel.actual.json", json_encode($actual, $flags)."\n");
foreach ($result->log() as $entry) {
    if ('debug' !== $entry['level']) {
        echo "log: {$entry['level']} {$entry['message']}\n";
    }
}
echo $actual == $expected ? "== TestKernel: MATCH\n" : "== TestKernel: DIFF\n";
