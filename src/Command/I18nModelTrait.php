<?php
declare(strict_types=1);

namespace ADmad\I18n\Command;

use Cake\Console\Arguments;
use Cake\ORM\Table;

/**
 * Trait to get model
 */
trait I18nModelTrait
{
    /**
     * Model instance for saving translation messages.
     *
     * @var \Cake\ORM\Table
     */
    protected $_model;

    /**
     * Get translation model.
     *
     * @param \Cake\Console\Arguments $args The Arguments instance
     * @return \Cake\ORM\Table
     */
    protected function _loadModel(Arguments $args): Table
    {
        /** @var string $model */
        $model = $args->getOption('model') ?: static::DEFAULT_MODEL;

        return $this->_model = $this->getTableLocator()->get($model);
    }
}
