<?php

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Tests\Functional\Entity\Spec;

use Nelmio\ApiDocBundle\Attribute\Spec\Model;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\Article;
use OpenApi\Spec as OA;

class ArticleHolder
{
    #[OA\Property(schema: new Model(type: Article::class, description: 'The article'))]
    public $article;

    /**
     * @var string
     */
    #[OA\Property(schema: new OA\Schema(description: 'A label', maxLength: 20))]
    public $label;
}
