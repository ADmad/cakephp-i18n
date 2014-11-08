<?php
namespace I18nMessages\Test\I18n;

use Cake\TestSuite\TestCase;
use I18nMessages\I18n\DbMessagesLoader;

/**
 * Tests for DbMessagesLoader
 */
class DbMessagesLoaderTest extends TestCase {

/**
 * fixtures
 *
 * @var array
 */
	public $fixtures = ['plugin.i18n_messages.i18n_messages'];

/**
 * testInvoke method
 *
 * @param string $domain
 * @param string $locale
 * @param string $model
 * @param array $expected
 * @return void
 * @dataProvider paramsProvider
 */
	public function testInvoke($domain, $locale, $model, $expected) {
		$loader = new DbMessagesLoader($domain, $locale, $model);
		$package = $loader();

		$this->assertEquals($expected, $package->getMessages());
	}

/**
 * Data provider for testInvoke
 *
 * @return array
 */
	public function paramsProvider() {
		return [
			[
				'default',
				'en',
				null,
				['test' => 'test translated']
			],
			[
				'default',
				'fr',
				null,
				['test' => 'fr test translated']
			],
			[
				'default',
				'en',
				null,
				['test' => 'test translated']
			],
			[
				'my_domain',
				'en',
				null,
				['test' => 'domain test translated']
			],
		];
	}

}
