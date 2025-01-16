<?php
declare(strict_types=1);

namespace ADmad\I18n\Validation;

use Cake\Validation\ValidationSet;
use Cake\Validation\Validator as CakeValidator;
use function Cake\I18n\__d;

class Validator extends CakeValidator
{
    /**
     * I18n domain for validation messages.
     *
     * @var string
     */
    protected string $_validationDomain = 'validation';

    /**
     * Get/set the I18n domain for validation messages.
     *
     * @param string|null $domain The validation domain to be used. If null
     *   returns currently set domain.
     * @return string|null
     */
    public function validationDomain(?string $domain = null): ?string
    {
        if ($domain === null) {
            return $this->_validationDomain;
        }

        return $this->_validationDomain = $domain;
    }

    /**
     * Iterates over each rule in the validation set and collects the errors resulting
     * from executing them.
     *
     * @param string $field The name of the field that is being processed
     * @param \Cake\Validation\ValidationSet $rules the list of rules for a field
     * @param array $data the full data passed to the validator
     * @param bool $newRecord whether is it a new record or an existing one
     * @return array<string, mixed>
     */
    protected function _processRules(string $field, ValidationSet $rules, array $data, bool $newRecord): array
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
     * @return array Translated args.
     */
    protected function _translateArgs(array $args): array
    {
        foreach ($args as $k => $arg) {
            if (is_array($arg)) {
                $arg = implode(', ', $arg);
            }

            $args[$k] = __d($this->_validationDomain, (string)$arg);
        }

        return $args;
    }
}
