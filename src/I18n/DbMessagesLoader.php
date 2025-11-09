<?php
declare(strict_types=1);

namespace ADmad\I18n\I18n;

use Cake\Datasource\RepositoryInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\I18n\Package;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Table;

/**
 * DbMessages loader.
 *
 * Returns translation messages stored in database.
 */
class DbMessagesLoader
{
    use LocatorAwareTrait;

    /**
     * The domain name.
     *
     * @var string
     */
    protected string $_domain;

    /**
     * The locale to load messages for.
     *
     * @var string
     */
    protected string $_locale;

    /**
     * The model name to use for loading messages or model instance.
     *
     * @var \Cake\Datasource\RepositoryInterface|string
     */
    protected string|RepositoryInterface $_model;

    /**
     * Formatting used for messsages.
     *
     * @var string
     */
    protected string $_formatter;

    /**
     * Constructor.
     *
     * @param string $domain Domain name.
     * @param string $locale Locale string.
     * @param \Cake\Datasource\RepositoryInterface|string|null $model Model name or instance.
     *   Defaults to 'I18nMessages'.
     * @param string $formatter Formatter name. Defaults to 'default' (ICU formatter).
     */
    public function __construct(
        string $domain,
        string $locale,
        string|RepositoryInterface|null $model = null,
        string $formatter = 'default',
    ) {
        $this->_domain = $domain;
        $this->_locale = $locale;
        $this->_model = $model ?: 'I18nMessages';
        $this->_formatter = $formatter;
    }

    /**
     * Fetches the translation messages from db and returns package with those
     * messages.
     *
     * @throws \RuntimeException If model could not be loaded.
     * @return \Cake\I18n\Package
     */
    public function __invoke(): Package
    {
        $model = $this->_getModel();
        /** @var \Cake\ORM\Query\SelectQuery $query */
        $query = $model->find();

        if ($model instanceof Table) {
            // Get list of fields without primaryKey, domain, locale.
            $fields = $model->getSchema()->columns();
            $fields = array_flip(array_diff(
                $fields,
                $model->getSchema()->getPrimaryKey(),
            ));
            unset($fields['domain'], $fields['locale']);
            $query->select(array_flip($fields));
        }

        $results = $query
            ->where(['domain' => $this->_domain, 'locale' => $this->_locale])
            ->disableHydration()
            ->all();

        return new Package($this->_formatter, null, $this->_messages($results));
    }

    /**
     * Convert db resultset to messages array.
     *
     * @param \Cake\Datasource\ResultSetInterface $results ResultSet instance.
     * @return array
     */
    protected function _messages(ResultSetInterface $results): array
    {
        if (!$results->count()) {
            return [];
        }

        $messages = [];
        $pluralForms = 0;
        $item = $results->first();
        // There are max 6 plural forms possible but most people won't need
        // that so will only have the required number of value_{n} fields in db.
        for ($i = 5; $i > 0; $i--) {
            if (isset($item['value_' . $i])) {
                $pluralForms = $i;
                break;
            }
        }

        foreach ($results as $item) {
            $singular = $item['singular'];
            $context = $item['context'];
            if ($context) {
                $messages[$singular]['_context'][$context] = $item['value_0'];
            } else {
                $messages[$singular] = $item['value_0'];
            }

            if (empty($item['plural'])) {
                continue;
            }

            $key = $item['plural'];
            $plurals = [];
            for ($i = 0; $i <= $pluralForms; $i++) {
                $plurals[] = $item['value_' . $i];
            }

            if ($context) {
                $messages[$key]['_context'][$context] = $plurals;
            } else {
                $messages[$key] = $plurals;
            }
        }

        return $messages;
    }

    /**
     * Get model instance
     *
     * @return \Cake\Datasource\RepositoryInterface
     */
    protected function _getModel(): RepositoryInterface
    {
        if (is_string($this->_model)) {
            $this->_model = $this->getTableLocator()->get($this->_model);
        }

        return $this->_model;
    }
}
