<?php
namespace ADmad\I18n\View\Widget;

use Cake\View\Widget\SelectBoxWidget;
use DateTimeZone;

/**
 * Input widget class for generating a selectbox of timezone.
 */
class TimezoneWidget extends SelectBoxWidget
{
    /**
     * Render the contents of the select element.
     *
     * `$data['options']` is expected to be associative array of regions for which
     * you want identifiers list. The key will be used as optgroup.
     * Eg. `['Asia' => DateTimeZone::ASIA, 'Europe' => DateTimeZone::EUROPE]`
     *
     * @param array $data The context for rendering a select.
     *
     * @return array
     */
    protected function _renderContent($data)
    {
        $data['options'] = $this->_identifierList($data['options']);

        return parent::_renderContent($data);
    }

    /**
     * Converts list of regions to identifiers list.
     *
     * @param array $options List of regions
     *
     * @return array
     */
    protected function _identifierList($options)
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
            $list = DateTimeZone::listIdentifiers($region);
            $identifiers[$name] = array_combine($list, $list);
        }

        if (count($identifiers) === 1) {
            $identifiers = current($identifiers);
        }

        return $identifiers;
    }
}
