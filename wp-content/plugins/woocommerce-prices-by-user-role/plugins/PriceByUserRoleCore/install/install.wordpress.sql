SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `festi_user_role_product_prices` (
  `id_post` int(11) unsigned NOT NULL,
  `user_role` varchar(40) NOT NULL,
  `price` varchar(10) NOT NULL,
  `sale_price` varchar(10) NOT NULL,
  PRIMARY KEY (`id_post`,`user_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SET FOREIGN_KEY_CHECKS=1;