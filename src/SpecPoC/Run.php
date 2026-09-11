<?php declare(strict_types=1);

namespace Nelmio\ApiDocBundle\SpecPoC;

use Nelmio\ApiDocBundle\SpecPoC\Augmenter\MapRequestPayloadAugmenter;
use Nelmio\ApiDocBundle\SpecPoC\Augmenter\ModelAugmenter;
use Nelmio\ApiDocBundle\SpecPoC\Translators\ClassRouteTranslator;
use Nelmio\ApiDocBundle\SpecPoC\Translators\MapQueryParameterTranslator;
use Nelmio\ApiDocBundle\SpecPoC\Translators\MapRequestPayloadTranslator;
use Nelmio\ApiDocBundle\SpecPoC\Translators\MethodRouteTranslator;
use Nelmio\ApiDocBundle\SpecPoC\Translators\SchemaAutoTranslator;
use Nelmio\ApiDocBundle\Tests\Functional\Controller\GenericTypesController;
use Nelmio\ApiDocBundle\Tests\Functional\Controller\InvoiceDocumentController;
use Nelmio\ApiDocBundle\Tests\Functional\Controller\InvokableController;
use Nelmio\ApiDocBundle\Tests\Functional\Controller\MapQueryParameterController;
use Nelmio\ApiDocBundle\Tests\Functional\Controller\MapRequestPayloadController;
use OpenApi\Assembler;
use OpenApi\Augmenter;
use OpenApi\Builder;
use OpenApi\Utils\AttributeFactory;
use OpenApi\Utils\Pipeline;
use OpenApi\Utils\TypedList;
use ReflectionClass;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * PoC of how NelmioApiDocBundle could leverage the new swagger-php spec pipeline...
 *
 * The implemented way of handling MapRequestPayload does work, but is probably not the best way to do it.
 * Alternatives would be to lean more on the captured reflectors:
 * - Iterate over all operations (we do have all due to the Route translators) and inspect their reflectors to find MapRequestPayload attributes
 * - Simplify MapRequestPayloadTranslator to just wrap MapRequestPayload in a plain attachable. Unmerged Attachables have their own slot in Specification
 *   and therefore can be handled by an Augmenter later on. Again, reflectors would help associate with the correct Operation.
 */
class Run
{
    public function poc(?string $only = null): void
    {
        $controllers = [
            MapQueryParameterController::class,
            GenericTypesController::class,
            InvoiceDocumentController::class,
            InvokableController::class,
            MapRequestPayloadController::class,
        ];

        foreach ($controllers as $className) {
            $shortName = (new ReflectionClass($className))->getShortName();
            if (null !== $only && !str_contains(strtolower($shortName), strtolower($only))) {
                continue;
            }

            echo "\n=== {$shortName} ==============================================\n\n";
            $this->build($className);
        }
    }

    public function build(string $className): void
    {
        // auto annotate all classes and public properties
        $modelAttributeFactory = (new AttributeFactory())
            ->withTranslators(fn(TypedList $translators) => $translators
                ->add(new SchemaAutoTranslator())
            );

        $modelAssembler = new Assembler(attributeFactory: $modelAttributeFactory);

        $result = (new Builder())
            ->setMode(Builder\Mode::SPEC)
            ->setLogger(new ConsoleLogger(new ConsoleOutput()))
            ->addSource([
                new ReflectionClass(Doc::class),
                new ReflectionClass($className),
            ])
            ->withAugmenters(fn(Pipeline $augmenters) => $augmenters
                ->insert(new ModelAugmenter($modelAssembler), Augmenter\Inheritance::class)
                ->insert(new MapRequestPayloadAugmenter(), Augmenter\Names::class)
            )
            ->withAttributeFactory(fn(AttributeFactory $attributeFactory) => $attributeFactory
                ->withTranslators(fn(TypedList $translators) => $translators
                    ->add(new ClassRouteTranslator())
                    ->add(new MethodRouteTranslator())
                    ->add(new MapQueryParameterTranslator())
                    ->add(new MapRequestPayloadTranslator())
                )
            )
            ->build();

        echo $result->toYaml();
    }
}
