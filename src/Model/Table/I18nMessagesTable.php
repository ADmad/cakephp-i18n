<?php
namespace I18nMessages\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\Table;

/**
 * Model for table which hold the translation messages.
 */
class I18nMessagesTable extends Table {

/**
 * Add I18nMessages behavior which provides the finder for messages.
 *
 * @param array $config Config list
 * @return void
 */
	public function initialize(array $config) {
		$this->addBehavior('I18nMessages.I18nMessages');
	}

}
