ALTER TABLE `shine_orders`
ADD COLUMN `serial_number` varchar(255) NOT NULL,
ADD COLUMN `deactivated` tinyint(1) UNSIGNED NOT NULL,
ADD COLUMN `upgrade_coupon` varchar(255) NOT NULL,
ADD COLUMN `notes` text NOT NULL;


ALTER TABLE `shine_versions`
ADD COLUMN `status` tinyint UNSIGNED NOT NULL DEFAULT 1;