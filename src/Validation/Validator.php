<?php
namespace ADmad\I18n\Validation;

use Cake\Validation\ValidationSet;

class Validator extends \Cake\Validation\Validator
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->_useI18n = true;
    }

    /**
     * I18n domain for validation messages.
     *
     * @var string
     */
    protected $_validationDomain = 'validation';

    /**
     * Get/set the I18n domain for validation messages.
     *
     * @param string|null $domain The validation domain to be used. If null
     *   returns currently set domain.
     *
     * @return string|null
     */
    public function validationDomain($domain = null)
    {
        if ($domain === null) {
            return $this->_validationDomain;
        }
        $this->_validationDomain = $domain;
    }

    /**
     * Iterates over each rule in the validation set and collects the errors resulting
     * from executing them.
     *
     * @param string $field The name of the field that is being processed
     * @param ValidationSet $rules the list of rules for a field
     * @param array $data the full data passed to the validator
     * @param bool $newRecord whether is it a new record or an existing one
     *
     * @return array
     */
    protected function _processRules($field, ValidationSet $rules, $data, $newRecord)
    {
        $errors = [];
        // Loading default provider in case there is none
        $this->getProvider('default');

        foreach ($rules as $name => $rule) {
            $result = $rule->process($data[$field], $this->_providers, compact('newRecord', 'data', 'field'));
            if ($result === true) {
                continue;
            }

            if (is_array($result) && $name === static::NESTED) {
                $errors = $result;
            } elseif (is_string($result)) {
                $errors[$name] = $result;
            } else {
                $args = $rule->get('pass');
                $errors[$name] = __d($this->_validationDomain, $name, $this->_translateArgs($args));
            }

            if ($rule->isLast()) {
                break;
            }
        }

        return $errors;
    }

    /**
     * Applies translations to validator arguments.
     *
     * @param array $args The args to translate
     *
     * @return array Translated args.
     */
    protected function _translateArgs($args)
    {
        foreach ((array)$args as $k => $arg) {
            if (is_string($arg)) {
                $args[$k] = __d($this->_validationDomain, $arg);
            }
        }

        return $args;
    }
}
