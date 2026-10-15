<?php
declare(strict_types=1);

namespace ADmad\I18n\Command;

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
    protected Table $model;

    /**
     * Get translation model.
     *
     * @return \Cake\ORM\Table
     */
    protected function loadModel(): Table
    {
        /** @var string $model */
        $model = $this->args->getOption('model') ?: static::DEFAULT_MODEL;

        return $this->model = $this->getTableLocator()->get($model);
    }
}
