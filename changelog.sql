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


-- 2012.03.12

ALTER TABLE `shine_applications`
ADD COLUMN `cf_key` TEXT NOT NULL AFTER ap_pkey,
ADD COLUMN `cf_pkey` TEXT NOT NULL AFTER cf_key;


-- 2012.03.16

ALTER TABLE `shine_applications`
ADD COLUMN `activation_online` TINYINT(1) NOT NULL DEFAULT 0 AFTER sparkle_pkey,
ADD COLUMN `activation_online_class` VARCHAR(128) NOT NULL DEFAULT 'default' AFTER activation_online,
ADD COLUMN `fs_license_key` VARCHAR(45) NOT NULL DEFAULT '' AFTER return_url,
ADD COLUMN `rsa_key` TEXT NOT NULL AFTER cf_pkey,
ADD COLUMN `rsa_pkey` TEXT NOT NULL AFTER rsa_key;

ALTER TABLE `shine_activations`
ADD COLUMN `hwid` VARCHAR(255) NOT NULL DEFAULT '' AFTER serial_number;

ALTER TABLE `shine_orders`
ADD COLUMN `expiration_date` DATE NOT NULL,
ADD COLUMN `license_type_id` INTEGER UNSIGNED NOT NULL DEFAULT 0;

CREATE TABLE `shine_license_types` (
  `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `app_id` INTEGER(11) NOT NULL,
  `abbreviation` VARCHAR(100) NOT NULL,
  `quantity` INTEGER UNSIGNED NOT NULL DEFAULT 1,
  `expiration_days` INTEGER UNSIGNED NOT NULL DEFAULT 0,
  `max_update_version` VARCHAR(255) NOT NULL DEFAULT '',
  
  PRIMARY KEY (`id`),
  UNIQUE KEY app_id_abbr (`app_id`, `abbreviation`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- 2012.04.02 - MacUpdate

ALTER TABLE `shine_applications`
ADD COLUMN `mu_license_key` varchar(45) NOT NULL AFTER fs_security_key;

ALTER TABLE `shine_orders`
MODIFY COLUMN `type` enum('PayPal','Manual','Student','MUPromo','FastSpring','MacUpdate') CHARACTER SET latin1 NOT NULL;


-- 2012.04.04

ALTER TABLE `shine_applications`
ADD COLUMN `abbreviation` VARCHAR(15) NOT NULL AFTER id,
ADD COLUMN `direct_download` TINYINT(1) UNSIGNED NOT NULL;