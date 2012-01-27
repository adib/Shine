ALTER TABLE `shine_orders`
ADD COLUMN `serial_number` varchar(255) NOT NULL,
ADD COLUMN `deactivated` tinyint(1) UNSIGNED NOT NULL,
ADD COLUMN `upgrade_coupon` varchar(255) NOT NULL,
ADD COLUMN `notes` text NOT NULL;


ALTER TABLE `shine_versions`
ADD COLUMN `status` tinyint UNSIGNED NOT NULL DEFAULT 1;


ALTER TABLE `shine_applications`
ADD COLUMN `bundle_id` varchar(255) NOT NULL;


CREATE TABLE `shine_inapp` (
  `trx_id` varchar(255) NOT NULL,
  `app_id` int(11) NOT NULL,
  `inapp_id` varchar(255) NOT NULL,
  `trx_date` datetime NOT NULL,
  `bundle_version` varchar(255) NOT NULL,
  `price` decimal(18,2) NOT NULL,
  `currency` char(3) NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `country` varchar(50) NOT NULL,
  PRIMARY KEY (`trx_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
