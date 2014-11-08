<?php
namespace I18nMessages\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\Table;

/**
 * Model for table which hold the translation messages.
 */
class I18nMessagesTable extends Table {

/**
 * Finder for translation messages.
 *
 * @param \Cake\ORM\Query $query Query instance.
 * @param array $options Options list
 * @return @return \Cake\ORM\Query
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
