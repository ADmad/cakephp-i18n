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
    protected string $domain;

    /**
     * The locale to load messages for.
     *
     * @var string
     */
    protected string $locale;

    /**
     * The model name to use for loading messages or model instance.
     *
     * @var \Cake\Datasource\RepositoryInterface|string
     */
    protected string|RepositoryInterface $model;

    /**
     * Formatting used for messsages.
     *
     * @var string
     */
    protected string $formatter;

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
        $this->domain = $domain;
        $this->locale = $locale;
        $this->model = $model ?: 'I18nMessages';
        $this->formatter = $formatter;
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
        $model = $this->getModel();

        if ($model instanceof Table) {
            /** @var \Cake\ORM\Query\UnhydratedSelectQuery $query */
            $query = $model->unhydratedFind();

            // Get list of fields without primaryKey, domain, locale.
            $fields = $model->getSchema()->columns();
            $fields = array_flip(array_diff(
                $fields,
                $model->getSchema()->getPrimaryKey(),
            ));
            unset($fields['domain'], $fields['locale']);
            $query->select(array_flip($fields));
        } else {
            /** @var \Cake\Datasource\QueryInterface $query */
            $query = $model->find();
        }

        $results = $query
            ->where(['domain' => $this->domain, 'locale' => $this->locale])
            ->all();

        return new Package($this->formatter, null, $this->messages($results));
    }

    /**
     * Convert db resultset to messages array.
     *
     * @param \Cake\Datasource\ResultSetInterface $results ResultSet instance.
     * @return array
     */
    protected function messages(ResultSetInterface $results): array
    {
        if (!$results->count()) {
            return [];
        }

        $messages = [];
        $pluralForms = 0;
        $item = $results->first();
        // Count plural forms from the selected columns, not the values: a
        // singular-only first row has NULL values and would report 0 forms.
        if (is_array($item)) {
            for ($i = 5; $i > 0; $i--) {
                if (array_key_exists('value_' . $i, $item)) {
                    $pluralForms = $i;
                    break;
                }
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
    protected function getModel(): RepositoryInterface
    {
        if (is_string($this->model)) {
            $this->model = $this->getTableLocator()->get($this->model);
        }

        return $this->model;
    }
}
