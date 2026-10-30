<?php

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Tests\Functional\Entity;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

/**
 * SPIKE: a nested model without groups of its own.
 */
class ArticleHolder
{
    #[OA\Property(description: 'The article', ref: new Model(type: Article::class))]
    public $article;

    /**
     * @var string
     */
    #[OA\Property(description: 'A label', maxLength: 20)]
    public $label;
}
