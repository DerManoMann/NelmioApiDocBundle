<?php
// Phase 1 spike: real nelmio describer output, then classic -> spec conversion.
require __DIR__.'/../../vendor/autoload.php';

use Nelmio\ApiDocBundle\Tests\Functional\ConfigurableContainerFactory;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

$controller = $argv[1] ?? 'MapQueryParameterController';
$f = new ConfigurableContainerFactory();
$f->create([], static function (RoutingConfigurator &$routes) use ($controller) {
    $routes->withPath('/')->import(__DIR__."/../../tests/Functional/Controller/$controller.php", 'attribute');
}, [], []);
$c = $f->getContainer();
$gen = $c->get('nelmio_api_doc.generator.default');
$p = fn (object $o, string $n) => (new ReflectionProperty($o, $n))->getValue($o);
$modelDescribers = $p($gen, 'modelDescribers');
$routeDescriber = null;
foreach ($p($gen, 'describers') as $d) {
    if ($d instanceof Nelmio\ApiDocBundle\Describer\RouteDescriber) $routeDescriber = $d;
}
$scratch = new OpenApi\Annotations\OpenApi(['_context' => Nelmio\ApiDocBundle\OpenApiPhp\Util::createContext(['version' => '3.0.0'])]);
$registry = new Nelmio\ApiDocBundle\Model\ModelRegistry($modelDescribers, $scratch, []);
$routeDescriber->setModelRegistry($registry);
$routeDescriber->describe($scratch);
$registry->registerSchemas();
echo "--- classic describer output\n", $scratch->toJson(), "\n";

// collect every annotation in the tree, and how many carry a reflector
$all = [];
$walk = function ($a) use (&$walk, &$all) {
    if ($a instanceof OpenApi\Annotations\AbstractAnnotation) {
        $all[] = $a;
        foreach (get_object_vars($a) as $k => $v) { if ($k[0] !== '_') $walk($v); }
    } elseif (is_array($a)) { foreach ($a as $v) $walk($v); }
};
$walk($scratch);
$withRef = array_filter($all, fn ($a) => $a->_context?->reflector instanceof Reflector);
printf("--- %d annotations, %d with a reflector\n", count($all), count($withRef));

$analysis = new OpenApi\Analysis([], $scratch->_context);
foreach ($all as $a) $analysis->addAnnotation($a, $a->_context);
$spec = (new OpenApi\HybridBridge())->fromAnalysis($analysis);
printf("--- HybridBridge: %d operations, %d schemas\n", count($spec->operations), count($spec->schemas));

// option 2: stamp the route's ReflectionMethod onto each operation (and its path item)
$router = $c->get('router');
$byPath = [];
foreach ($router->getRouteCollection() as $route) {
    [$cls, $m] = explode('::', $route->getDefault('_controller')) + [1 => '__invoke'];
    foreach ($route->getMethods() ?: ['GET'] as $verb) $byPath[$route->getPath()][strtolower($verb)] = new ReflectionMethod($cls, $m);
}
$stamped = 0;
foreach ($scratch->paths as $pathItem) {
    foreach (['get','post','put','patch','delete','head','options','trace'] as $verb) {
        $op = $pathItem->{$verb};
        if ($op instanceof OpenApi\Annotations\Operation && isset($byPath[$pathItem->path][$verb])) {
            $op->_context->reflector = $byPath[$pathItem->path][$verb];
            $op->path = $pathItem->path;
            $stamped++;
        }
    }
}
$analysis = new OpenApi\Analysis([], $scratch->_context);
foreach ($all as $a) $analysis->addAnnotation($a, $a->_context);
$spec = (new OpenApi\HybridBridge())->fromAnalysis($analysis);
printf("--- stamped %d; HybridBridge: %d operations, params on first: %d\n", $stamped, count($spec->operations), count($spec->operations[0]->parameters ?? []));
$nested = array_filter($all, fn ($a) => $a instanceof OpenApi\Annotations\Operation && $a->_context->is('nested'));
printf("--- operations whose context says nested: %d\n", count($nested));

// option 2b: stamp the path item as well (reflector of its first operation)
$pi = 0; $piNested = 0;
foreach ($scratch->paths as $pathItem) {
    foreach (['get','post','put','patch','delete'] as $verb) {
        if ($pathItem->{$verb} instanceof OpenApi\Annotations\Operation) { $pathItem->_context->reflector = $pathItem->{$verb}->_context->reflector; break; }
    }
    $pi++; $piNested += $pathItem->_context->is('nested') ? 1 : 0;
}
$spec = (new OpenApi\HybridBridge())->fromAnalysis($analysis);
printf("--- path items %d (nested %d); HybridBridge: %d operations\n", $pi, $piNested, count($spec->operations));
// what does 'nested' hold?
$ctx = $scratch->paths[0]->_context; printf("--- nested = %s\n", is_object($ctx->nested) ? get_class($ctx->nested) : var_export($ctx->nested, true));

// option 3: DocumentBridge (protected converters on the tree), then compile and diff with classic
$scratch->info ??= null;
$spec = (new Nelmio\ApiDocBundle\SpecDescribers\DocumentBridge())->fromDocument($scratch);
printf("--- DocumentBridge: %d operations, %d schemas\n", count($spec->operations), count($spec->schemas));
$compiled = (new OpenApi\Compiler\OpenApi30Compiler())->compile($spec);
$norm = function ($j) { $a = json_decode(json_encode($j), true); $s = function (&$x) use (&$s) { if (is_array($x)) { if (!array_is_list($x)) ksort($x); foreach ($x as &$v) $s($v); } }; $s($a); return $a; };
$classic = $norm(json_decode($scratch->toJson()));
$converted = $norm(is_string($compiled) ? json_decode($compiled) : $compiled);
file_put_contents(sys_get_temp_dir().'/classic.json', json_encode($classic, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
file_put_contents(sys_get_temp_dir().'/converted.json', json_encode($converted, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
foreach (["paths","components"] as $k) echo "--- $k ", ($classic[$k] ?? null) == ($converted[$k] ?? null) ? "identical" : "differ", "\n";
