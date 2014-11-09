<?php
namespace I18nMessages\I18n;

use Aura\Intl\Package;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

/**
 * DbMessages loader.
 *
 * Returns translation messages stored in database.
 */
class DbMessagesLoader {

/**
 * The domain name
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
 * @var mixed
 */
	protected $_model;

/**
 * Formatting used for messsages.
 * @var string
 */
	protected $_formatter;

/**
 * Constructor
 *
 * @param string $domain Domain name.
 * @param string $locale Locale string.
 * @param mixed $model Model name or instance. Defaults to 'I18nMessages.I18nMessages'.
 * @param string $formatter Formatter name. Defaults to 'default' (ICU formatter).
 */
	public function __construct(
		$domain,
		$locale,
		$model = null,
		$formatter = 'default'
	) {
		if (!$model) {
			$model = 'I18nMessages.I18nMessages';
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
 * @return \Aura\Intl\Package
 * @throws \RuntimeException If model could not be loaded.
 */
	public function __invoke() {
		$model = $this->_model;
		if (is_string($model)) {
			$model = TableRegistry::get($this->_model);
			if (!$model) {
				throw new \RuntimeException(sprintf(
					'Unable to model "%s".', $this->_model
				));
			}
			$this->_model = $model;
		}

		$results = $model->find('all', [
				'conditions' => [
					'domain' => $this->_domain,
					'locale' => $this->_locale
				]
			])
			->hydrate(false)
			->all();

		return new Package($this->_formatter, null, $this->_messages($results));
	}

/**
 * Convert db results to message array.
 *
 * @param \Cake\ORM\ResultSet $results ResultSet
 * @return array
 */
	protected function _messages(ResultSet $results) {
		if (!$results->count()) {
			return [];
		}

		$messages = [];
		$pluralForms = 0;
		$item = $results->first();
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

			if (!empty($item['plural'])) {
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
		}

		return $messages;
	}

}
