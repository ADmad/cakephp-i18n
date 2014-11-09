CREATE TABLE `i18n_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain` varchar(100) NOT NULL,
  `locale` varchar(5) NOT NULL,
  `singular` varchar(255) NOT NULL,
  `plural` varchar(255) NOT NULL,
  `context` varchar(50) NOT NULL,
  `value_0` varchar(255) NOT NULL,
  `value_1` varchar(255) NOT NULL,
  `value_2` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `domain_locale` (`domain`,`locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
