CREATE TABLE `i18n_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain` varchar(50) NOT NULL,
  `locale` varchar(5) NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `domain_locale` (`domain`,`locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
