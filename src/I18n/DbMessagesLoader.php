<?php
namespace ADmad\I18n\I18n;

use Aura\Intl\Package;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

/**
 * DbMessages loader.
 *
 * Returns translation messages stored in database.
 */
class DbMessagesLoader
{
    /**
     * The domain name.
     *
     * @var string
     */
    protected $_domain;

    /**
     * The locale to load messages for.
     *
     * @var string
     */
    protected $_locale;

    /**
     * The model name to use for loading messages or model instance.
     *
     * @var string|\Cake\Datasource\RepositoryInterface
     */
    protected $_model;

    /**
     * Formatting used for messsages.
     *
     * @var string
     */
    protected $_formatter;

    /**
     * Constructor.
     *
     * @param string $domain Domain name.
     * @param string $locale Locale string.
     * @param string|\Cake\Datasource\RepositoryInterface $model Model name or instance.
     *   Defaults to 'I18nMessages'.
     * @param string $formatter Formatter name. Defaults to 'default' (ICU formatter).
     */
    public function __construct(
        $domain,
        $locale,
        $model = null,
        $formatter = 'default'
    ) {
        if (!$model) {
            $model = 'I18nMessages';
        }
        $this->_domain = $domain;
        $this->_locale = $locale;
        $this->_model = $model;
        $this->_formatter = $formatter;
    }

    /**
     * Fetches the translation messages from db and returns package with those
     * messages.
     *
     * @throws \RuntimeException If model could not be loaded.
     *
     * @return \Aura\Intl\Package
     */
    public function __invoke()
    {
        $model = $this->_model;
        if (is_string($model)) {
            $model = TableRegistry::get($this->_model);
            if (!$model) {
                throw new \RuntimeException(
                    sprintf('Unable to load model "%s".', $this->_model)
                );
            }
            $this->_model = $model;
        }

        $query = $model->find();

        if ($model instanceof Table) {
            // Get list of fields without primaryKey, domain, locale.
            $fields = $model->getSchema()->columns();
            $fields = array_flip(array_diff(
                $fields,
                $model->getSchema()->primaryKey()
            ));
            unset($fields['domain'], $fields['locale']);
            $query->select(array_flip($fields));
        }

        $results = $query
            ->where(['domain' => $this->_domain, 'locale' => $this->_locale])
            ->enableHydration(false)
            ->all();

        return new Package($this->_formatter, null, $this->_messages($results));
    }

    /**
     * Convert db resultset to messages array.
     *
     * @param \Cake\Datasource\ResultSetInterface $results ResultSet instance.
     *
     * @return array
     */
    protected function _messages(ResultSetInterface $results)
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
            $translation = $item['value_0'];
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
}
