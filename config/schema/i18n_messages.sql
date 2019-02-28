CREATE TABLE `translations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain` varchar(100) NOT NULL,
  `locale` varchar(5) NOT NULL,
  `singular` varchar(255) NOT NULL,
  `plural` varchar(255) DEFAULT NULL,
  `context` varchar(50) DEFAULT NULL,
  `value_0` varchar(255) DEFAULT NULL,
  `value_1` varchar(255) DEFAULT NULL,
  `value_2` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `domain_locale_singular` (`domain`,`locale`,`singular`),
  KEY `domain_locale` (`domain`,`locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
