<?php
namespace I18nMessages\Test\I18n;

use Cake\TestSuite\TestCase;
use I18nMessages\I18n\DbMessagesLoader;

class DbMessagesLoaderTest extends TestCase {

	public function setUp() {
		$loader = new DbMessagesLoader('default', 'en');
	}

}
