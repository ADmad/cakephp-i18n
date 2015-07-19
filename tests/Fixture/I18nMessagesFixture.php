<?php
namespace ADmad\I18n\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class I18nMessagesFixture extends TestFixture
{
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
        'plural' => ['type' => 'string', 'null' => false],
        'value_0' => ['type' => 'string', 'null' => false],
        'value_1' => ['type' => 'string', 'null' => false],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        [
            'domain' => 'default', 'locale' => 'en', 'context' => '', 'singular' => 'test',
            'plural' => '', 'value_0' => 'test translated', 'value_1' => ''
        ],
        [
            'domain' => 'default', 'locale' => 'fr', 'context' => '', 'singular' => 'test',
            'plural' => '', 'value_0' => 'fr test translated', 'value_1' => ''
        ],
        [
            'domain' => 'my_domain', 'locale' => 'en', 'context' => '', 'singular' => 'test',
            'plural' => '', 'value_0' => 'domain test translated', 'value_1' => ''
        ],
        [
            'domain' => 'default', 'locale' => 'en', 'context' => '', 'singular' => 'singular',
            'plural' => 'plural', 'value_0' => '{0} value', 'value_1' => '{0} values'
        ],
        [
            'domain' => 'w_context', 'locale' => 'en', 'context' => 'c1', 'singular' => 'test',
            'plural' => '', 'value_0' => 'test translated', 'value_1' => ''
        ],
        [
            'domain' => 'w_context', 'locale' => 'en', 'context' => 'c1', 'singular' => 'singular',
            'plural' => 'plural', 'value_0' => '{0} value', 'value_1' => '{0} values'
        ],
        [
            'domain' => 'w_context', 'locale' => 'en', 'context' => 'c2', 'singular' => 'singular',
            'plural' => 'plural', 'value_0' => '{0} value c2', 'value_1' => '{0} values c2'
        ],
    ];
}
