<?php

// Entity probe, Symfony's side (PR 154): each entity described by symfony/json-schema's
// SchemaGenerator, dialect OpenAPI 3.1, written in nelmio's shape for types-compare.py.
// php src/SpecDescribers/tools/types-symfony.php <out-dir> [Entity ...]
// TYPES_DIRECTION=input|output: Symfony's Direction, which decides `required`; unset by default
//
// The component is unreleased (Symfony 8.2) and not in composer.json: it is loaded from a Symfony
// checkout, JSON_SCHEMA_SRC or ../symfony+8.2 beside the repository. Its dependencies, type-info and
// property-info, are already installed.

use Symfony\Component\JsonSchema\Configuration;
use Symfony\Component\JsonSchema\Dialect;
use Symfony\Component\JsonSchema\Direction;
use Symfony\Component\JsonSchema\SchemaGenerator;
use Symfony\Component\TypeInfo\Type;

require __DIR__.'/../../../vendor/autoload.php';

$src = getenv('JSON_SCHEMA_SRC') ?: __DIR__.'/../../../../symfony+8.2/src/Symfony/Component/JsonSchema';
if (!is_dir($src)) {
    fwrite(STDERR, "symfony/json-schema not found at $src; set JSON_SCHEMA_SRC\n");
    exit(1);
}
spl_autoload_register(static function (string $class) use ($src): void {
    $prefix = 'Symfony\\Component\\JsonSchema\\';
    if (str_starts_with($class, $prefix) && is_file($file = $src.'/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php')) {
        require $file;
    }
});

$out = $argv[1] ?? sys_get_temp_dir();
$ns = 'Nelmio\\ApiDocBundle\\Tests\\Functional\\Entity\\';
$entities = array_slice($argv, 2) ?: array_values(array_filter(
    array_map(static fn (string $f): string => basename($f, '.php'), glob(__DIR__.'/../../../tests/Functional/Entity/*.php')),
    static fn (string $n): bool => !preg_match('/^(JMS|Bazinga|Generic|VirtualProperty)/', $n),
));

$direction = match (getenv('TYPES_DIRECTION') ?: '') {
    'input' => Direction::Input,
    'output' => Direction::Output,
    default => null,
};
$generator = SchemaGenerator::create();
$symfony = [];
foreach ($entities as $e) {
    $fqcn = $ns.$e;
    if (!class_exists($fqcn) && !enum_exists($fqcn)) { continue; }
    try {
        $type = enum_exists($fqcn) ? Type::enum($fqcn) : Type::object($fqcn);
        $schema = $generator->generate($type, new Configuration(Dialect::openApi31(), direction: $direction));
        $root = $schema->getRoot();
        // an inline root, as for a discriminator map's parent, is filed under the entity's name
        $symfony[$e] = isset($root['$ref'])
            ? ['ref' => $root['$ref'], 'schemas' => $schema->getDefinitions()]
            : ['ref' => '#/components/schemas/'.$e, 'schemas' => [$e => $root, ...$schema->getDefinitions()]];
    } catch (\Throwable $t) {
        $symfony[$e] = ['error' => $t::class.': '.$t->getMessage()];
    }
}

file_put_contents("$out/types.symfony.json", json_encode($symfony, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo count($symfony), " entities, ", count(array_filter($symfony, static fn ($s) => isset($s['error']))), " errors\n";
