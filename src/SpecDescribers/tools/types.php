<?php

// Entity probe, phase 1: each entity described by nelmio's ModelRegistry and by swagger-php
// (PlainClassResolver + Augmenter\Types), types compared per property.
// php src/SpecDescribers/tools/types.php <out-dir> [Entity ...]

use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\Model\ModelRegistry;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use Nelmio\ApiDocBundle\SpecDescribers\NelmioServices;
use Nelmio\ApiDocBundle\SpecDescribers\PlainClassResolver;
use Nelmio\ApiDocBundle\Util\LegacyTypeConverter;
use OpenApi\Annotations as OA;
use OpenApi\Builder;
use OpenApi\Builder\Mode;
use OpenApi\Resolver;
use OpenApi\Spec as OAS;
use OpenApi\Specification;
use OpenApi\Utils\Pipeline;
use OpenApi\Utils\TypedList;

require __DIR__.'/../../../vendor/autoload.php';

// TYPES_REQUIRE=<dir>: load the classes there first, as migrated copies of entities, and describe
// only those with swagger-php (the nelmio half reads the originals, so it is skipped)
$migrated = getenv('TYPES_REQUIRE') ?: null;
if (null !== $migrated) {
    foreach (glob($migrated.'/*.php') as $file) {
        require_once $file;
    }
}

$out = $argv[1] ?? sys_get_temp_dir();
$ns = 'Nelmio\\ApiDocBundle\\Tests\\Functional\\Entity\\';
$entities = array_slice($argv, 2) ?: array_values(array_filter(
    array_map(static fn (string $f): string => basename($f, '.php'), glob(__DIR__.'/../../../tests/Functional/Entity/*.php')),
    static fn (string $n): bool => !preg_match('/^(JMS|Bazinga|Generic|VirtualProperty)/', $n),
));

$services = NelmioServices::testKernel();
$log = [];
$logger = new class($log) extends Psr\Log\AbstractLogger {
    public function __construct(private array &$log) {}
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        if ('debug' !== $level) { $this->log[] = "[$level] $message"; }
    }
};

$nelmio = [];
foreach (null === $migrated ? $entities : [] as $e) {
    $fqcn = $ns.$e;
    if (!class_exists($fqcn) && !enum_exists($fqcn)) { continue; }
    $scratch = new OA\OpenApi(['_context' => Util::createContext(['version' => '3.1.0'])]);
    $registry = new ModelRegistry($services->modelDescribers(), $scratch, $services->alternativeNames());
    try {
        $ref = $registry->register(new Model(LegacyTypeConverter::createType($fqcn)));
        $registry->registerSchemas();
        $all = json_decode(json_encode($scratch->components), true)['schemas'] ?? [];
        $nelmio[$e] = ['ref' => $ref, 'schemas' => $all];
    } catch (\Throwable $t) {
        $nelmio[$e] = ['error' => $t::class.': '.$t->getMessage()];
    }
}

$probe = array_values(array_filter($entities, static fn ($e) => class_exists($ns.$e) || enum_exists($ns.$e)));
$result = (new Builder())
    ->setMode(Mode::SPEC)
    ->setVersion('3.1.0')
    ->setLogger($logger)
    ->withResolver(static fn (Resolver $r) => $r->withResolvers(static fn (TypedList $l) => $l->add(new PlainClassResolver())))
    ->withAugmenters(static fn (Pipeline $p) => $p->configure(['cleanup.enabled=false']))
    ->withSpecification(static function (Specification $s) use ($probe, $ns): void {
        $s->add(new OAS\Info(title: 'types', version: '1'));
        foreach ($probe as $e) {
            $s->add(new OAS\Schema(component: '__probe_'.$e, ref: $ns.$e));
        }
    })
    ->build();
$swagger = $result->toArray()['components']['schemas'] ?? [];

$suffix = null === $migrated ? '' : '.migrated';
if (null === $migrated) {
    file_put_contents("$out/types.nelmio.json", json_encode($nelmio, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}
file_put_contents("$out/types.swagger$suffix.json", json_encode($swagger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
file_put_contents("$out/types$suffix.log", implode("\n", $log)."\n");
echo count($nelmio), " entities, ", count($swagger), " swagger-php schemas, ", count($log), " log lines\n";
