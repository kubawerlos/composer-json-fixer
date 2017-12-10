<?php

namespace Tests;

use ComposerJsonFixer\Console\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @coversNothing
 */
class FunctionalTest extends TestCase
{
    /** @var ApplicationTester */
    private $tester;

    public static function setUpBeforeClass()
    {
        eval(\sprintf('
            namespace Symfony\Component\Process {
                function proc_open(...$args)
                {
                    return \Tests\ProcOpenMock::call(...$args);
                }
            }
        '));
    }

    protected function setUp()
    {
        $application = new Application();
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);

        $this->tester = new ApplicationTester($application);
    }

    protected function tearDown()
    {
        if (\file_exists(__DIR__ . '/composer.json')) {
            (new Filesystem())->remove(__DIR__ . '/composer.json');
        }
        if (\file_exists(__DIR__ . '/composer.lock')) {
            (new Filesystem())->remove(__DIR__ . '/composer.lock');
        }
        if (\is_dir(__DIR__ . '/vendor')) {
            (new Filesystem())->remove(__DIR__ . '/vendor');
        }
    }

    public function testDryRunNotChangingFile()
    {
        $original = __DIR__ . '/stubs/a-lot-to-fix.json';
        $tested   = __DIR__ . '/composer.json';

        \copy($original, $tested);

        $this->tester->run([
            '--dry-run' => true,
            'directory' => \dirname($tested),
        ]);

        $this->assertSame(1, $this->tester->getStatusCode());
        $this->assertFileEquals($original, $tested);
    }

    public function testInvalidFile()
    {
        $this->doTest(__DIR__ . '/stubs/invalid-json.json', [], 2);
        $this->assertContains('File "composer.json" does not contain valid JSON', $this->tester->getDisplay());
    }

    public function testSelfComposer()
    {
        $this->doTest(__DIR__ . '/../composer.json', [], 0);
    }

    public function testFixing()
    {
        $this->doTest(__DIR__ . '/stubs/a-lot-to-fix.json', [], 1);
        $this->assertFileEquals(__DIR__ . '/stubs/a-lot-to-fix-fixed.json', __DIR__ . '/composer.json');
    }

    public function testWithUpdateAndFakeRepository()
    {
        $this->doTest(__DIR__ . '/stubs/fake-require.json', ['--with-updates' => true], 2);
        $this->assertContains('Command "composer require" failed', $this->tester->getDisplay());
    }

    public function testWithUpdateWhenNothingToFixAndUpdate()
    {
        $this->doTest(__DIR__ . '/stubs/psr-fixed-updated.json', ['--with-updates' => true], 0);
    }

    public function testWithUpdateWhenNothingToFix()
    {
        $this->doTest(__DIR__ . '/stubs/psr-fixed-to-update.json', ['--with-updates' => true], 1);
    }

    public function testWithUpdateWhenNothingToUpdate()
    {
        $this->doTest(__DIR__ . '/stubs/psr-to-fix-updated.json', ['--with-updates' => true], 1);
    }

    /**
     * @param string $path
     * @param array  $options
     * @param int    $statusCode
     */
    private function doTest($path, $options, $statusCode)
    {
        \copy($path, __DIR__ . '/composer.json');

        $this->tester->run(\array_merge(
            $options,
            ['directory' => __DIR__]
        ));

        $this->assertSame($statusCode, $this->tester->getStatusCode());
    }
}
