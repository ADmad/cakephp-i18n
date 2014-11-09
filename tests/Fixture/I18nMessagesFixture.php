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
		'context' => ['type' => 'string', 'null' => false],
		'singular' => ['type' => 'string', 'null' => false],
		'value_0' => ['type' => 'string', 'null' => false],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
	];

/**
 * records property
 *
 * @var array
 */
	public $records = [
		['domain' => 'default', 'locale' => 'en', 'context' => '', 'singular' => 'test', 'value_0' => 'test translated'],
		['domain' => 'default', 'locale' => 'fr', 'context' => '', 'singular' => 'test', 'value_0' => 'fr test translated'],
		['domain' => 'my_domain', 'locale' => 'en', 'context' => '', 'singular' => 'test', 'value_0' => 'domain test translated'],
	];

}
