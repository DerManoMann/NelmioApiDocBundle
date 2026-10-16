<?php
// Oracle: nelmio's own classic generator on swagger-php master, per ControllerTest case and version.
// php oracle.php <version> ; run from a clean nelmio checkout
require getcwd().'/vendor/autoload.php';
use Nelmio\ApiDocBundle\Tests\Functional\ConfigurableContainerFactory;
use Nelmio\ApiDocBundle\Tests\Functional\ControllerTest;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
$version = $argv[1];
$out = $argv[2];
foreach (ControllerTest::provideTestCases() as $case) {
    [$controller, $suffix] = [$case[0] ?? null, $case[1] ?? null];
    if (null === $controller) continue;
    $f = new ConfigurableContainerFactory();
    $extra = $case[5] ?? null;
    $f->create($case[2] ?? [], static function (RoutingConfigurator &$routes) use ($controller, $extra) {
        if ($extra) $extra($routes);
        $routes->withPath('/')->import(getcwd()."/tests/Functional/Controller/$controller.php", 'attribute');
    }, $case[3] ?? [], $case[4] ?? []);
    $gen = $f->getContainer()->get('nelmio_api_doc.generator.default');
    $gen->setOpenApiVersion($version);
    try {
        $json = $gen->generate()->toJson();
        file_put_contents("$out/$controller".($suffix ? ".$suffix" : '')."-$version.json", $json);
        echo "ok   $controller $suffix\n";
    } catch (Throwable $e) {
        echo "FAIL $controller $suffix: ", substr($e->getMessage(), 0, 120), "\n";
    }
}
