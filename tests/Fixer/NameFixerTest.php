<?php

namespace Tests\Fixer;

/**
 * @covers \ComposerJsonFixer\Fixer\NameFixer
 */
final class NameFixerTest extends AbstractFixerTestCase
{
    public function provideFixerCases()
    {
        return [
            [
                [
                    'name' => 'foo/bar',
                ],
                [
                    'name' => 'Foo/Bar',
                ],
            ],
            [
                [
                    'description' => 'Foo',
                ],
            ],
        ];
    }
}