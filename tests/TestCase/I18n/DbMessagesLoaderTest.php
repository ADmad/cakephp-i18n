<?php
declare(strict_types=1);

namespace ADmad\I18n\Test\I18n;

use ADmad\I18n\I18n\DbMessagesLoader;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for DbMessagesLoader.
 */
class DbMessagesLoaderTest extends TestCase
{
    protected array $fixtures = ['plugin.ADmad/I18n.I18nMessages'];

    /**
     * testInvoke method.
     *
     * @param string $domain
     * @param string $locale
     * @param string $model
     * @param array $expected
     * @return void
     */
    #[DataProvider('paramsProvider')]
    public function testInvoke($domain, $locale, $model, $expected)
    {
        $loader = new DbMessagesLoader($domain, $locale, $model);
        $package = $loader();

        $this->assertEquals($expected, $package->getMessages());
    }

    /**
     * Data provider for testInvoke.
     *
     * @return array
     */
    public static function paramsProvider()
    {
        return [
            [
                'default',
                'en',
                null,
                [
                    'test' => 'test translated',
                    'singular' => '{0} value',
                    'plural' => ['{0} value', '{0} values'],
                ],
            ],
            [
                'default',
                'fr',
                null,
                ['test' => 'fr test translated'],
            ],
            [
                'my_domain',
                'en',
                null,
                ['test' => 'domain test translated'],
            ],
            [
                'w_context',
                'en',
                null,
                [
                    'test' => ['_context' => ['c1' => 'test translated']],
                    'singular' => [
                        '_context' => [
                            'c1' => '{0} value',
                            'c2' => '{0} value c2',
                        ],
                    ],
                    'plural' => [
                        '_context' => [
                            'c1' => ['{0} value', '{0} values'],
                            'c2' => ['{0} value c2', '{0} values c2'],
                        ],
                    ],
                ],
            ],
            [
                'foo',
                'bar',
                null,
                [],
            ],
        ];
    }
}
