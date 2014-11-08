<?php
namespace I18nMessages\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class I18nMessagesFixture extends TestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = [
		'id' => ['type' => 'integer'],
		'domain' => ['type' => 'string', 'null' => false],
		'locale' => ['type' => 'string', 'null' => false],
		'key' => ['type' => 'string', 'null' => false],
		'value' => ['type' => 'string', 'null' => false],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
	];

/**
 * records property
 *
 * @var array
 */
	public $records = [
		['domain' => 'default', 'locale' => 'en', 'key' => 'test', 'value' => 'test translated'],
		['domain' => 'default', 'locale' => 'fr', 'key' => 'test', 'value' => 'fr test translated'],
		['domain' => 'my_domain', 'locale' => 'en', 'key' => 'test', 'value' => 'domain test translated'],
	];

}
