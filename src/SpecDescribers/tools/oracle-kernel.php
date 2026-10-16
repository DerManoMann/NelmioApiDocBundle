<?php
// Oracle for the full TestKernel (FunctionalTest's world), default area.
require getcwd().'/vendor/autoload.php';
use Nelmio\ApiDocBundle\Tests\Functional\TestKernel;
$kernel = new TestKernel();
$kernel->boot();
$container = $kernel->getContainer()->get('test.service_container');
foreach (['3.0.0', '3.1.0'] as $version) {
    $gen = $container->get('nelmio_api_doc.generator.default');
    $gen->reset();
    $gen->setOpenApiVersion($version);
    file_put_contents($argv[1]."/TestKernel-$version.json", $gen->generate()->toJson());
    echo "ok $version\n";
}
