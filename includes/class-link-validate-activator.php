<?php

/**
 * Fired during plugin activation
 *
 * @link       http://eclipse-creative.com/
 * @since      1.0.0
 *
 * @package    Link_Validate
 * @subpackage Link_Validate/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Link_Validate
 * @subpackage Link_Validate/includes
 * @author     Craig Stanfield <c.stanfield@eclipse-creative.com>
 */
class Link_Validate_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        global $wpdb;

        $sql = "
CREATE TABLE `" . DB_NAME . "`.`" . $wpdb->prefix . "lv_links` ( 
    `id` INT(9) NOT NULL AUTO_INCREMENT , 
    `link` VARCHAR(255) NOT NULL , 
    `source` VARCHAR(255) NOT NULL,
    `http_code` INT(3) NOT NULL,
    `status` INT(1) NOT NULL DEFAULT '1',
    `counter` INT(9) NOT NULL DEFAULT '0',
    `active_since` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `depth` INT(1) NOT NULL,
    `fallback` VARCHAR(255) NOT NULL DEFAULT '#',
    `h1` INT(5) NOT NULL DEFAULT '0',
    `h2` INT(5) NOT NULL DEFAULT '0',
    `h3` INT(5) NOT NULL DEFAULT '0',
    `h4` INT(5) NOT NULL DEFAULT '0',
    `h5` INT(5) NOT NULL DEFAULT '0',
    `h6` INT(5) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) 
ENGINE = MyISAM 
CHARSET=utf8 
COLLATE utf8_swedish_ci;";

        dbDelta($sql);

        link_validation_cron_job();
	}
}
