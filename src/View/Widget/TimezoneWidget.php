<?php
declare(strict_types=1);

namespace ADmad\I18n\View\Widget;

use Cake\View\Form\ContextInterface;
use Cake\View\Widget\SelectBoxWidget;
use DateTimeZone;

/**
 * Input widget class for generating a selectbox of timezone.
 */
class TimezoneWidget extends SelectBoxWidget
{
    /**
     * {@inheritDoc}
     *
     * ### Options format
     *
     * `$data['options']` is expected to be associative array of regions for which
     * you want identifiers list. The key will be used as optgroup.
     * Eg. `['Asia' => DateTimeZone::ASIA, 'Europe' => DateTimeZone::EUROPE]`
     *
     * @param array $data Data to render with.
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string A generated select box.
     * @throws \RuntimeException when the name attribute is empty.
     */
    public function render(array $data, ContextInterface $context): string
    {
        $data['options'] = $this->_identifierList($data['options'] ?? []);

        return parent::render($data, $context);
    }

    /**
     * Converts list of regions to identifiers list.
     *
     * @param array $options List of regions
     * @return array
     */
    protected function _identifierList(array $options): array
    {
        if (empty($options)) {
            $options = [
                'Africa' => DateTimeZone::AFRICA,
                'America' => DateTimeZone::AMERICA,
                'Antarctica' => DateTimeZone::ANTARCTICA,
                'Arctic' => DateTimeZone::ARCTIC,
                'Asia' => DateTimeZone::ASIA,
                'Atlantic' => DateTimeZone::ATLANTIC,
                'Australia' => DateTimeZone::AUSTRALIA,
                'Europe' => DateTimeZone::EUROPE,
                'Indian' => DateTimeZone::INDIAN,
                'Pacific' => DateTimeZone::PACIFIC,
                'UTC' => DateTimeZone::UTC,
            ];
        }

        $identifiers = [];
        foreach ($options as $name => $region) {
            $list = (array)DateTimeZone::listIdentifiers($region);
            /** @psalm-suppress InvalidScalarArgument */
            $identifiers[$name] = array_combine($list, $list);
        }

        if (count($identifiers) === 1) {
            $identifiers = current($identifiers);
        }

        return $identifiers;
    }
}
