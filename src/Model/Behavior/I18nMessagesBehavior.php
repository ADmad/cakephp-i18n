<?php
namespace I18nMessages\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Query;

/**
 * This behavior provides custom finder for returning formatted i18n messages.
 */
class I18nMessagesBehavior extends Behavior {

/**
 * Finder for translation messages.
 *
 * @param \Cake\ORM\Query $query Query instance.
 * @param array $options Options list
 * @return \Cake\ORM\Query
 */
	public function findMessages(Query $query, array $options) {
		return $query->formatResults(function ($results) use ($options) {
			return $results->combine(
				'key',
				'value'
			);
		});
	}

}
