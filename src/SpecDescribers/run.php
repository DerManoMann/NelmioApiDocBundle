<?php
// php src/SpecDescribers/run.php <Controller> [fixture-suffix]
// Builds the controller's document through the PoC and diffs it against nelmio's own fixture.
require __DIR__.'/../../vendor/autoload.php';

use Nelmio\ApiDocBundle\SpecDescribers\NelmioServices;
use Nelmio\ApiDocBundle\SpecDescribers\SpecGenerator;

$controller = $argv[1] ?? 'MapQueryParameterController';
// POC_ORACLE=<dir> compares against nelmio's classic generator run on swagger-php master
// (<Controller>[.<suffix>]-<version>.json) instead of the committed 3.0 fixture.
$version = getenv('POC_VERSION') ?: '3.0.0';
$oracle = getenv('POC_ORACLE') ?: null;
$fixture = null !== $oracle
    ? "$oracle/$controller".(isset($argv[2]) ? '.'.$argv[2] : '')."-$version.json"
    : __DIR__.'/../../tests/Functional/Fixtures/'.$controller.(isset($argv[2]) ? '.'.$argv[2] : '').'.json';
$out = getenv('POC_OUT') ?: sys_get_temp_dir();

$result = (new SpecGenerator(new NelmioServices($controller, $argv[2] ?? null), $version, logger: new class extends Psr\Log\AbstractLogger {
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
// nelmio's fixtures predate swagger-php #2251 (6.14.0): classic master emits a nullable ref as
// oneOf [ref, NULL_SCHEMA_30] too (verified with nelmio's own ControllerTest on Controller2209).
// Fold that back to the fixtures' shape and count it, so the diff shows only real differences.
$drift = 0;
$undrift = static function (mixed $json) use (&$undrift, &$drift): mixed {
    if (!\is_array($json)) {
        return $json;
    }
    $null = ['enum' => [null], 'nullable' => true, 'type' => 'object'];
    if (isset($json['oneOf']) && \count($json['oneOf']) >= 2 && \in_array($null, $json['oneOf'], false)) {
        $json['oneOf'] = array_values(array_filter($json['oneOf'], static fn ($s) => $s != $null));
        $json['nullable'] = true;
        ++$drift;
    }

    return array_map($undrift, $json);
};
$actual = $normalise(null === $oracle ? $undrift(json_decode($result->toJson(), true)) : json_decode($result->toJson(), true));
$expected = $normalise(json_decode(file_get_contents($fixture), true));
unset($actual['info'], $expected['info']);

$flags = \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES;
file_put_contents("$out/$controller.expected.json", json_encode($expected, $flags)."\n");
file_put_contents("$out/$controller.actual.json", json_encode($actual, $flags)."\n");

foreach ($result->log() as $entry) {
    if ('debug' !== $entry['level']) {
        echo "log: {$entry['level']} {$entry['message']}\n";
    }
}
$note = $drift ? " ($drift x #2251 drift folded)" : '';
echo $actual == $expected ? "== $controller: MATCH$note\n" : "== $controller: DIFF$note ($out/$controller.{expected,actual}.json)\n";
