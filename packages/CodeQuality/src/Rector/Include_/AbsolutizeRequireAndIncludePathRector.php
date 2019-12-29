<?php

declare(strict_types=1);

namespace Rector\CodeQuality\Rector\Include_;

use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Scalar\String_;
use Rector\Rector\AbstractRector;
use Rector\RectorDefinition\CodeSample;
use Rector\RectorDefinition\RectorDefinition;

/**
 * @see https://github.com/symplify/CodingStandard#includerequire-should-be-followed-by-absolute-path
 *
 * @see \Rector\CodeQuality\Tests\Rector\Include_\AbsolutizeRequireAndIncludePathRector\AbsolutizeRequireAndIncludePathRectorTest
 */
final class AbsolutizeRequireAndIncludePathRector extends AbstractRector
{
    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('include/require to absolute path', [
            new CodeSample(
                <<<'PHP'
class SomeClass
{
    public function run()
    {
        require 'autoload.php';

        require $variable;
    }
}
PHP
,
                <<<'PHP'
class SomeClass
{
    public function run()
    {
        require __DIR__ . '/autoload.php';

        require $variable;
    }
}
PHP

            ),
        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [Include_::class];
    }

    /**
     * @param Include_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $node->expr instanceof String_) {
            return null;
        }

        /** @var string $includeValue */
        $includeValue = $this->getValue($node->expr);

        // skip phar
        if (Strings::startsWith($includeValue, 'phar://')) {
            return null;
        }

        // add preslash to string
        if (! Strings::startsWith($includeValue, '/')) {
            // keep dots
            if (! Strings::startsWith($includeValue, '.')) {
                $node->expr->value = '/' . $includeValue;
            }
        }

        $node->expr = new Concat(new Node\Scalar\MagicConst\Dir(), $node->expr);

        return $node;
    }
}