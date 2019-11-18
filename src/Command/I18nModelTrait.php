<?php
declare(strict_types=1);

namespace ADmad\I18n\Command;

use Cake\Console\Arguments;
use Cake\Datasource\RepositoryInterface;

/**
 * Trait to get model
 */
trait I18nModelTrait
{
    /**
     * Model instance for saving translation messages.
     *
     * @var \Cake\Datasource\RepositoryInterface
     */
    protected $_model;

    /**
     * Get translation model.
     *
     * @param \Cake\Console\Arguments $args The Arguments instance
     * @return \Cake\Datasource\RepositoryInterface
     */
    protected function _loadModel(Arguments $args): RepositoryInterface
    {
        $model = $args->getOption('model') ?: static::DEFAULT_MODEL;

        return $this->_model = $this->loadModel($model, $args->getOption('model-type') ?: null);
    }
}
