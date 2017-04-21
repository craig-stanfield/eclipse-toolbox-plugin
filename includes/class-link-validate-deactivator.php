<?php

/**
 * Fired during plugin deactivation
 *
 * @link       http://eclipse-creative.com/
 * @since      1.0.0
 *
 * @package    Link_Validate
 * @subpackage Link_Validate/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Link_Validate
 * @subpackage Link_Validate/includes
 * @author     Craig Stanfield <c.stanfield@eclipse-creative.com>
 */
class Link_Validate_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
        global $wpdb;
        $sql = "DROP TABLE IF EXISTS " . $wpdb->prefix . "lv_links;";
        $result = $wpdb->query($sql);
        wp_clear_scheduled_hook('link_cron_init');
	}

}
