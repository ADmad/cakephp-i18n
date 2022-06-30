<?php
declare(strict_types=1);

return [
    [
        'table' => 'i18n_messages',
        'columns' => [
            'id' => ['type' => 'integer'],
            'domain' => ['type' => 'string', 'null' => false],
            'locale' => ['type' => 'string', 'null' => false],
            'context' => ['type' => 'string', 'null' => true, 'default' => null],
            'singular' => ['type' => 'string', 'null' => false],
            'plural' => ['type' => 'string', 'null' => true, 'default' => null],
            'refs' => ['type' => 'string', 'null' => true, 'default' => null],
            'value_0' => ['type' => 'string', 'null' => true, 'default' => null],
            'value_1' => ['type' => 'string', 'null' => true, 'default' => null],
        ],
        'constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
    ],
];
