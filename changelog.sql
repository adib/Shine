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


ALTER TABLE `shine_versions`
ADD COLUMN `alternate_fname` VARCHAR(255) NOT NULL AFTER url;


-- 2012.04.05

ALTER TABLE `shine_feedback`
ADD COLUMN `notes` TEXT NOT NULL;


-- 2012.04.09

ALTER TABLE `shine_applications`
ADD COLUMN `use_ga` TINYINT(1) UNSIGNED NOT NULL,
ADD COLUMN `ga_key` VARCHAR(100) NOT NULL,
ADD COLUMN `ga_domain` VARCHAR(100) NOT NULL,
ADD COLUMN `ga_country` TINYINT(1) UNSIGNED NOT NULL;


-- 2012.05.10

ALTER TABLE `shine_orders`
MODIFY COLUMN `type` enum('PayPal','Manual','Student','MUPromo','FastSpring','MacUpdate','GetDealy') CHARACTER SET latin1 NOT NULL;

ALTER TABLE `shine_applications`
ADD COLUMN `getdealy_name` VARCHAR(128) NOT NULL,
ADD COLUMN `default_license_abbr` VARCHAR(100) NOT NULL,
ADD COLUMN `getdealy_price` FLOAT NOT NULL,
ADD COLUMN `use_postmark` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1;


-- 2012.05.10

ALTER TABLE `shine_orders`
MODIFY COLUMN `type` enum('PayPal','Manual','Student','MUPromo','FastSpring','MacUpdate','GetDealy','Facebook_like') CHARACTER SET latin1 NOT NULL;


-- 2012.06.27

ALTER TABLE `shine_orders`
MODIFY COLUMN `type` enum('PayPal','Manual','Student','MUPromo','FastSpring','MacUpdate','GetDealy','Facebook_like','MacBundler') CHARACTER SET latin1 NOT NULL;


-- 2012.08.02

ALTER TABLE `shine_orders`
MODIFY COLUMN `type` VARCHAR(255) NOT NULL;


-- 2012.10.09 Adding S3 and CloudFront support

ALTER TABLE `shine_applications` ADD s3domain VARCHAR(128) NOT NULL, ADD s3distribution VARCHAR(128) NOT NULL, ADD storage TINYINT UNSIGNED NOT NULL, ADD is_ssl TINYINT UNSIGNED NOT NULL;

-- 2013.01.25 ability to create several serials per license; charts;

ALTER TABLE `shine_downloads` ADD COLUMN app_id INT(10) UNSIGNED NOT NULL;
ALTER TABLE `shine_license_types` ADD COLUMN number_lines INT(10) UNSIGNED NOT NULL DEFAULT 1;

-- 2013.01.25 rename one field

ALTER TABLE `shine_license_types` CHANGE number_lines serials_quantity INT(10)

-- 2013.01.25 new entity - serial numbers

CREATE TABLE `shine_serial_numbers` (
	`id` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`order_id` INT(11) NOT NULL,
	`serial_number` VARCHAR(255) NOT NULL
)

-- 2013.01.28 app_id is stored in sparkle reports

ALTER TABLE `shine_sparkle_reports` ADD COLUMN app_id INT(10) UNSIGNED NOT NULL;

-- 2013.01.31 sent_to_qcrm flag added; its default value is 0, but this flag should be set for all elder activations

ALTER TABLE `shine_activations` ADD COLUMN sent_to_qcrm INT(1) UNSIGNED NOT NULL DEFAULT 0;
UPDATE shine_activations SET sent_to_qcrm=1

-- 2013.02.05 reset sent_to_qcrm flag for all old activations

UPDATE `shine_activations` SET sent_to_qcrm=0 WHERE dt < '2013-02-01'