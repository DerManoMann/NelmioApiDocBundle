<?php declare(strict_types=1);

namespace Nelmio\ApiDocBundle\SpecPoC;

use OpenApi\Spec as OA;

/**
 * Add a default Info so the compiler reports the document as valid.
 */
#[OA\Info(title: 'NelmioApiDocBundle spec PoC', version: '0.1')]
class Doc
{
}
