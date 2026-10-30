<?php

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Tests\Functional;

use OpenApi\Annotations as OA;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * SPIKE: the classic probe writes the fixture, the spec twin has to produce it.
 */
final class SpikeModelTest extends WebTestCase
{
    use ComparesOpenApiFixturesTrait;

    private ConfigurableContainerFactory $configurableContainerFactory;

    protected function setUp(): void
    {
        $this->configurableContainerFactory = new ConfigurableContainerFactory();
    }

    /**
     * @param array<string, array<mixed>> $extraConfigs
     */
    #[DataProvider('provideCases')]
    public function testProbe(string $controllerFile, string $fixture, array $extraConfigs = []): void
    {
        $this->configurableContainerFactory->create([], static function (RoutingConfigurator &$routes) use ($controllerFile) {
            $routes->withPath('/')->import(__DIR__.'/Controller/'.$controllerFile, 'attribute');
        }, $extraConfigs, []);

        $api = $this->configurableContainerFactory->getContainer()->get('nelmio_api_doc.generator.default')->generate();
        $path = __DIR__.'/Fixtures/'.$fixture.'.json';
        if (!file_exists($path)) {
            file_put_contents($path, LegacyNullable::upgradeJson($api->toJson()));
        }

        self::assertEquals(
            self::normalizeOpenApiDecodedForComparison(json_decode(self::getFixture($path), false, 512, \JSON_THROW_ON_ERROR)),
            self::normalizeOpenApiDecodedForComparison(LegacyNullable::upgrade(json_decode($api->toJson(), false, 512, \JSON_THROW_ON_ERROR))),
        );
    }

    public static function provideCases(): \Generator
    {
        yield 'models, classic' => ['ModelProbeController.php', 'ModelProbeController'];
        yield 'models, spec' => ['Spec/ModelProbeController.php', 'ModelProbeController'];
        yield 'models, classic, 3.1' => ['ModelProbeController.php', 'ModelProbeController.openapi31', ['nelmio_api_doc' => ['documentation' => ['openapi' => '3.1.0']]]];
        yield 'models, spec, 3.1' => ['Spec/ModelProbeController.php', 'ModelProbeController.openapi31', ['nelmio_api_doc' => ['documentation' => ['openapi' => '3.1.0']]]];
    }
}
