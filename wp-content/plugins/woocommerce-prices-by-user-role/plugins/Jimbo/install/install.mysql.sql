SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

DROP TABLE IF EXISTS `festi_listeners`;
DROP TABLE IF EXISTS `festi_menu_permissions`;
DROP TABLE IF EXISTS `festi_menus`;
DROP TABLE IF EXISTS `festi_sections_user_types_permission`;
DROP TABLE IF EXISTS `festi_sections_user_permission`;
DROP TABLE IF EXISTS `festi_section_actions`;
DROP TABLE IF EXISTS `festi_sections`;
DROP TABLE IF EXISTS `festi_url_rules2areas`;
DROP TABLE IF EXISTS `festi_url_areas`;
DROP TABLE IF EXISTS `festi_url_rules`;
DROP TABLE IF EXISTS `festi_plugins`;
DROP TABLE IF EXISTS `festi_texts`;
DROP TABLE IF EXISTS `festi_settings`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `users_types`;

CREATE TABLE IF NOT EXISTS `festi_plugins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `status` enum('active','hidden') NOT NULL DEFAULT 'active',
  `ident` varchar(32) NOT NULL,
  `version` int(5)  DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ident` (`ident`),
  UNIQUE KEY `ident_2` (`ident`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `festi_url_areas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ident` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ident` (`ident`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `festi_sections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `caption` varchar(64) NOT NULL,
  `ident` varchar(32) NOT NULL,
  `mask` enum('2','4','6') NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_type` int(11) unsigned NOT NULL,
  `name` varchar(128) DEFAULT NULL,
  `lastname` varchar(64) DEFAULT NULL,
  `login` varchar(128) DEFAULT NULL,
  `pass` varchar(32) NOT NULL,
  `email` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `id_type` (`id_type`),
  KEY `login` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `festi_listeners` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `plugin` varchar(32) NOT NULL,
  `method` varchar(64) NOT NULL,
  `callback_plugin` varchar(32) NOT NULL,
  `callback_method` varchar(64) NOT NULL,
  `url_area` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin` (`plugin`),
  KEY `callback_plugin` (`callback_plugin`),
  KEY `id_url_area` (`url_area`),
  CONSTRAINT `festi_listeners_ibfk_1` FOREIGN KEY (`plugin`) REFERENCES `festi_plugins` (`ident`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `festi_listeners_ibfk_2` FOREIGN KEY (`callback_plugin`) REFERENCES `festi_plugins` (`ident`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `festi_listeners_ibfk_3` FOREIGN KEY (`url_area`) REFERENCES `festi_url_areas` (`ident`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `festi_menu_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_role` int(11) NOT NULL,
  `id_menu` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `festi_menus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `caption` varchar(64) NOT NULL,
  `url` varchar(64) DEFAULT NULL,
  `id_parent` int(11) DEFAULT NULL,
  `order_n` int(11) NOT NULL,
  `description` varchar(128) DEFAULT NULL,
  `id_section` int(10) unsigned DEFAULT NULL,
  `area` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_section` (`id_section`),
  KEY `area` (`area`),
  CONSTRAINT `festi_menus_ibfk_1` FOREIGN KEY (`id_section`) REFERENCES `festi_sections` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `festi_menus_ibfk_2` FOREIGN KEY (`area`) REFERENCES `festi_url_areas` (`ident`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

CREATE TABLE IF NOT EXISTS `festi_section_actions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_section` int(10) unsigned NOT NULL,
  `plugin` varchar(32) NOT NULL,
  `method` varchar(64) NOT NULL,
  `mask` enum('2','4','6') NOT NULL DEFAULT '2',
  `comment` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plugin` (`plugin`,`method`,`id_section`),
  KEY `id_action` (`id_section`),
  KEY `id` (`id`),
  KEY `mask` (`mask`),
  KEY `method` (`method`),
  CONSTRAINT `festi_section_actions_ibfk_1` FOREIGN KEY (`id_section`) REFERENCES `festi_sections` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `festi_section_actions_ibfk_2` FOREIGN KEY (`plugin`) REFERENCES `festi_plugins` (`ident`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `festi_sections_user_permission` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_section` int(10) unsigned NOT NULL,
  `id_user` int(10) unsigned NOT NULL,
  `value` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_action_2` (`id_section`,`id_user`),
  KEY `id_action` (`id_section`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `festi_sections_user_permission_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `festi_sections_user_permission_ibfk_3` FOREIGN KEY (`id_section`) REFERENCES `festi_sections` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `festi_sections_user_types_permission` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_section` int(10) unsigned NOT NULL,
  `id_user_type` int(10) unsigned NOT NULL,
  `value` int(11) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`),
  KEY `id_section` (`id_section`),
  CONSTRAINT `festi_sections_user_types_permission_ibfk_1` FOREIGN KEY (`id_section`) REFERENCES `festi_sections` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `festi_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `caption` varchar(255) NOT NULL,
  `name` varchar(32) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `festi_texts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ident` varchar(32) NOT NULL,
  `text` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `festi_url_rules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `plugin` varchar(32) NOT NULL,
  `pattern` varchar(255) NOT NULL,
  `method` varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin` (`plugin`),
  CONSTRAINT `festi_url_rules_ibfk_1` FOREIGN KEY (`plugin`) REFERENCES `festi_plugins` (`ident`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `festi_url_rules2areas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_url_rule` int(10) unsigned NOT NULL,
  `area` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_url_rule` (`id_url_rule`,`area`),
  KEY `area` (`area`),
  CONSTRAINT `festi_url_rules2areas_ibfk_1` FOREIGN KEY (`area`) REFERENCES `festi_url_areas` (`ident`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `users_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `caption` varchar(128) NOT NULL,
  `ident` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ident` (`ident`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


SET FOREIGN_KEY_CHECKS=1;