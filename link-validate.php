<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://eclipse-creative.com/
 * @since             0.0.0
 * @package           Link_Validate
 *
 * @wordpress-plugin
 * Plugin Name:       Eclipse SEO Toolbox
 * Plugin URI:        https://www.eclipse-creative.com/wordpress-plugins/EclipseToolbox/
 * Author URI:        https://www.eclipse-creative.com
 * Description:       Scrape the site and get all links. Test the links are active and hide them if not. find h tags
 * Version:           1.1.0
 * Email:             c.stanfield@eclipse-creative.com
 * Author:            Eclipse Creative Consultants Ltd.
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       link-validation
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
if( ! class_exists( 'Eclipse_Toolbox_Updater' ) ){
    include_once( plugin_dir_path( __FILE__ ) . 'updater.php' );
}
$updater = new Eclipse_Toolbox_Updater( __FILE__ );
$updater->set_username( 'craig-stanfield' );
$updater->set_repository( 'eclipse-toolbox-plugin' );
/*
	$updater->authorize( 'abcdefghijk1234567890' ); // Your auth code goes here for private repos
*/
$updater->initialize();

define("CLI", false);
define("NL", CLI ? "\n" : "<br>");
define("IGNORE_EMPTY_CONTENT_TYPE", false);

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-link-validate-activator.php
 */
function activate_link_validate()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-link-validate-activator.php';
    Link_Validate_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-link-validate-deactivator.php
 */
function deactivate_link_validate()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-link-validate-deactivator.php';
    Link_Validate_Deactivator::deactivate();
}
register_activation_hook(__FILE__, 'activate_link_validate');
register_deactivation_hook(__FILE__, 'deactivate_link_validate');

function lv_action_javascript()
{
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function () {

            jQuery('.retest').on('click', function() {
                var lv_element = jQuery(this);
                show_spinner(lv_element);
                testLink(lv_element);
            });

            jQuery('.remove').on('click', function() {
                var lv_element = jQuery(this);
                show_spinner(lv_element);
                removeLink(lv_element);
            });

            function show_spinner(lv_element) {
                var spinner = jQuery(".spinner");
                spinner.detach().appendTo(lv_element);
                spinner.css("visibility", "visible");
            }

            function hide_spinner() {
                var spinner = jQuery(".spinner");
                spinner.detach().appendTo(".heads");
                spinner.css("visibility", "hidden");
            }

            function removeLink(lv_element) {
                var id = lv_element.attr('id');
                id = id.slice(3);
                var data = {
                    'action': 'lv_remove',
                    'id':     id
                };

                jQuery.post(ajaxurl, data, function(response) {

                    if (response == 0) {
                        lv_element.addClass('red');
                        alert('link could not be removed. Support will be emailed.');
                        data = {
                            'action':  'lv_email',
                            'id': id
                        };
                        jQuery.post(ajaxurl, data, function (response) {
                            alert('Email function ' + response);
                        });
                    }

                    if (response == 1) {
                        lv_element.addClass('green');
                        alert('link has been removed.');
                        jQuery(lv_element).closest('tr').remove();
                    }

                    if (isNaN(response)){
                        alert('c');
                        lv_element.addClass('red');
                        alert('This link is still being used, manually repair the link (shown on page ' + response + ')in its location before removing broken link again!')
                    }

                    hide_spinner();
                });
            }

            function testLink(lv_element) {
                var url = lv_element.attr('id');
                var url_mod = url;
                if (url.includes('instagram.com')) {
                    url_mod = '/';
                }
                var spinner = jQuery(".spinner");
                var test = jQuery('#modusOperandi').find(":selected").text();
                jQuery.ajax({
                    url: url_mod, //+'dud#link',
                    type: 'GET',
                    method: 'GET',
                    error: function() {
                        lv_element.addClass('red');
                        hide_spinner();
                        alert('link is still broken, it has therefore not been updated.');
                    },
                    success: function() {
                        lv_element.addClass('green');
                        var data = {
                            'action': 'lv_action',
                            'url':    url,
                            'test':   test
                        };

                        jQuery.post(ajaxurl, data, function (response) {
                            if (jQuery('#menu_order').val() == '' || jQuery('#menu_order').val() == '0')
                                jQuery('#menu_order').val(response);
                            hide_spinner();
                            alert(response);
                            if (response != 'Link not updated, test mode.') location.reload();
                        });
                    }
                });
            }

        });

    </script> <?php
}
add_action('admin_footer', 'lv_action_javascript');

function lv_remove_link() {
    global $wpdb;
    $id = $_POST['id']+0;
    $source = $wpdb->get_var("SELECT source FROM ".$wpdb->prefix."lv_links WHERE id=$id");

    $test = $wpdb->rows_affected + 0;
    if ($test > 0) {
        // if the link is still used on the page then add to result
        $test += find_paged_links($source);
    }
    // Results as follows
    // 0 Link has not been removed. ERROR
    // 1 Link removed successfully
    // 2 Link removed but is being used so it has been added back.
    if ($test == 2) $test = $source;
    echo $test;
    wp_die();
}
add_action('wp_ajax_lv_remove', 'lv_remove_link');

function lv_email_error() {
    global $wpdb;
    $host = $_SERVER['SERVER_NAME'];
    $short = ltrim($host, "www.");
    $id = $_POST['id'];
    $link = $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . 'lv_links WHERE id=' . $id);
    $to      = 'c.stanfield@eclipse-creative.com';
    $subject = 'Error Removing Link';
    $body    = ' There has been an error on ' . get_bloginfo('name') . ' processing link ' . $link->link . ' which has an id of ' . $id . ' and found on page ' . $link->source . '.';
    $headers = array();
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'From: Server<no-reply@' . $short . '>';
    $headers[] = 'Cc: ' . get_bloginfo('admin_email');
    wp_mail($to, $subject, $body, $headers);
}
add_action('wp_ajax_lv_email', 'lv_email_error');

function lv_action_callback() {
    global $wpdb;
    $test = $_POST['test'];
    if ($test == 'Live Mode') {
        $url = $_POST['url'];

        // We now need to update the status of this link
        $data = array(
            'http_code' => 200,
            'status' => 1,
            'active_since' => date('Y-m-d H:i:s')
        );

        $where = array(
            'link' => $url
        );

        $wpdb->update($wpdb->prefix . 'lv_links', $data, $where);
        echo 'Link ' . $url . ' has been updated now it is working again. Please press Ok to reload.';
    } else {
        echo 'Link not updated, test mode.';
    }
    wp_die(); // this is required to terminate immediately and return a proper response
}
add_action('wp_ajax_lv_action', 'lv_action_callback');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-link-validate.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_link_validate()
{
    $plugin = new Link_Validate();
    $plugin->run();

}
run_link_validate();

add_action('admin_menu', 'link_validate_setup_menu');

function link_validate_setup_menu()
{
    add_menu_page('Eclipse Toolbox Settings', 'Eclipse Toolbox', 'manage_options', 'link-validate', 'link_validate_settings_init', 'dashicons-editor-unlink');

}

function link_validate_settings_init() {
    $path_to_link_validate_admin_display_php = plugin_dir_path(__FILE__) . 'admin/partials/link-validate-admin-display.php';
    include_once($path_to_link_validate_admin_display_php);

}

/**
 * This takes an array with source and links and returns object without duplicate items keeping first source
 * @param array $aLinks
 * @return array $result
 */
function lv_array_unique($aLinks) {
    $merge = array();
    $result = array();
    foreach ($aLinks as $key => $aLink) {
        $link     = $aLink['link'];
        if (!array_key_exists($link,$merge)) $merge[$link] = $key;
    }
    foreach ($merge as $link => $key) {
        $res      = $aLinks[$key];
        $result[] = $res;
    }

    return $result;
}

/**
 * This is ran when the plugin is created, it finds all links tests if active and places results in the database
 *
 * @param bool $full (just the home page or to a depth of 3)
 * @return int
 */
function set_all_links($full = true)
{
    $message = '';
    // first get home page links
    $path = get_home_url();
    $links = get_page_links($path, 0);
    if ($full) {
        // Now we will get second level urls
        foreach ($links as $aLink) {
            $link = $aLink['link'];
            if ($path != $link && $path . "/" != $link) {
                $tmp = get_page_links($link, 1);
                if ($tmp) $links = array_merge($links, $tmp);
            }
        }
        // Remove duplicates
        $links = lv_array_unique($links);
        if ($full > 1) {
            //And finally 3rd level urls
            foreach ($links as $aLink) {
                $link = $aLink['link'];
                if ($path != $link) {
                    $tmp = get_page_links($link, 2);
                    if ($tmp) $links = array_merge($links, $tmp);
                }
            }
            // Remove duplicates
            $links = lv_array_unique($links);
        }
    }

    // Drop existing entries and rebuild if they exist
    populate_links($links);

    return count($links);
}

function get_page_links($path, $depth) {
    $base = get_home_url();
    if (strpos($path, $base) !== false) {
        $aLinks = array();
        $urls = array();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $path);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        $html = curl_exec ($ch);
        curl_close ($ch);

        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        // grab all the links on the page
        $xpath = new DOMXPath($dom);
        $hrefs = $xpath->evaluate("/html/body//a");
        for ($i = 0; $i < $hrefs->length; $i++) {
            $href = $hrefs->item($i);
            $url = $href->getAttribute('href');
            $tick = explode('#', $url);
            if (count($tick) > 1) $url = $tick[0];
            if ($url != "" && $url != "#") {
                $urls[] = rel2abs($url, $path);
            }
        }
        // remove duplicate entries in array
        $links = array_unique($urls);

        foreach ($links as $link) {
            $tmp['source']   = $path;
            $tmp['link']     = $link;
            $tmp['depth']    = $depth;
            $tmp['fallback'] = '#';
            $aLinks[]        = $tmp;
        }
    }

    return $aLinks;

}

/**
 * @param $path
 * @return array
 */
function get_page_links_sanity($path, $depth) {
    // return nothing if external url
    $aLinks = array();
    $base = get_home_url();
    if (strpos($path, $base) !== false) {
        $urls = array();
        $html = @file_get_contents($path);
        if (strpos($http_response_header[0], "200")) {
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            // grab all the links on the page
            $xpath = new DOMXPath($dom);
            $hrefs = $xpath->evaluate("/html/body//a");
            for ($i = 0; $i < $hrefs->length; $i++) {
                $href = $hrefs->item($i);
                $url = $href->getAttribute('href');
                $tick = explode('#', $url);
                if (count($tick) > 1) $url = $tick[0];
                if ($url != "" && $url != "#") {
                    $urls[] = rel2abs($url, $path);
                }
            }

            // remove duplicate entries in array
            $links = array_unique($urls);

            foreach ($links as $link) {
                $tmp['source']   = $path;
                $tmp['link']     = $link;
                $tmp['depth']    = $depth;
                $tmp['fallback'] = '#';
                $aLinks[]        = $tmp;
            }
        } else {
            // 404 so ignore
        }

    }

    return $aLinks;

}

/**
 * Find new links when a post or page is created or updated
 */
add_action('save_post', 'find_new_links', 10, 3);

add_action('delete_post', 'post_deleted_seo', 10);

function post_deleted_seo($post_id) {
    $path = get_permalink($post_id);
    updateRedirects();
}

/**
 * This is called after we delete a link, the link may be needed so we reload any missing links in this page
 * Lastly we add a message for last_action
 * @param $source
 * @return integer
 */
function find_paged_links($source) {
    global $wpdb;
    $links = get_page_links($source, -1);
    if (count($links) < 1) return false;

    // We need to get the links in the correct format
    $foundLinks = array();
    foreach($links as $linkArray) {
        $foundLinks[] = $linkArray['link'];
    }
    $oldLinks = $wpdb->get_col('SELECT link FROM ' . $wpdb->prefix . 'lv_links');
    $newLinks = my_array_diff($foundLinks, $oldLinks);
    // Convert result into correct format for populate links
    $newArrayLinks = array();
    foreach ($newLinks as $link) {
        $tmp['source']   = $source;
        $tmp['link']     = $link;
        $tmp['depth']    = -1;
        $newArrayLinks[] = $tmp;
    }

    populate_links($newArrayLinks, 'append');

    return count($newLinks);

}

/**
 * This is ran when we need to find new links (ie after a post or page is edited or published)
 * We use the post_id to get content and then create and test the links on this page ignoring if already present
 *
 * @param integer $post_id This is the id passed with the save hook
 * @param object  $post    Standard wordpress post object passed with the hook
 * @param boolean $update  True if post has updates
 *
 * @return integer         Count of discovered links
 */
function find_new_links($post_id, $post, $update) {
    // We return if its a display_type
    if ($post->post_type == 'display_type') return false;
    global $wpdb;
    $path = get_permalink($post_id);
    // Next we need to check if this is a non published job and add a redirect if it has and one doesn't exist then return false
    // Conversely remove it if the page has become active

    updateRedirects();

    // Is this a draft
    if ($post->post_status == 'draft') return false;
    // test this is not a query
    if (count(explode('?',$path)) > 1) return false;
    $links = get_page_links($path, -1);
    if (count($links) < 1) return false;

    // We need to get the links in the correct format
    $foundLinks = array();
    foreach($links as $linkArray) {
        $foundLinks[] = $linkArray['link'];
    }
    $oldLinks = $wpdb->get_col('SELECT link FROM ' . $wpdb->prefix . 'lv_links');
    $newLinks = my_array_diff($foundLinks, $oldLinks);
    // Convert result into correct format for populate links
    $newArrayLinks = array();
    foreach ($newLinks as $link) {
        $tmp['source']   = $path;
        $tmp['link']     = $link;
        $tmp['depth']    = -1;
        $newArrayLinks[] = $tmp;
    }

    populate_links($newArrayLinks, 'append');

    return count($newLinks);

}

/**
 * If we have a job and it has been saved check for redirections
 */
function updateRedirects()
{
    global $wpdb;
    $states   = get_option('post_type');
    $status   = $_POST['post_status'];
    $postType = $_POST['post_type'];
    $postName = $_POST['post_name'];
    $set = array_key_exists($postType, $states);
    if ($set) {
        $data = array(
            'url' => '/' . $postType . '/' . $postName . '/',
            'regex' => 0,
            'position' => 999,
            'last_count' => 0,
            'last_access' => date('Y-m-d H:i:s'),
            'group_id' => 1,
            'status' => 'enabled',
            'action_type' => 'url',
            'action_code' => 301,
            'action_data' => '/' . $postType . '/',
            'match_type' => 'url',
            'title' => ''
        );
        $qry = 'SELECT * FROM ' . $wpdb->prefix . 'redirection_items WHERE url="' . $data['url'] . '";';
        $row = $wpdb->get_row($qry, ARRAY_A);

        if ($status == 'publish') {
            // Page is published, disable redirect if one exists
            if (count($row) > 0) {
                $row['status'] = 'disabled';
                $wpdb->update($wpdb->prefix . 'redirection_items', $row, array('url' => $row['url']));
            }
        } else {
            if (count($row) > 0) {
                // Page has previously been published so update and save
                $row['status'] = 'enabled';
                $wpdb->update($wpdb->prefix . 'redirection_items', $row, array('url' => $row['url']));
            } else {
                // Page is not published so add redirect
                $wpdb->insert($wpdb->prefix . 'redirection_items', $data);
            }
        }
    }
}

/**
 * Truncate the
 */
function truncate_wp_lv_links_table()
{
    global $wpdb;
    $sql = 'TRUNCATE TABLE ' . $wpdb->prefix . 'lv_links;';
    $wpdb->query($sql);
}

/**
 * This utility function converts link into relative if it is a link on the site
 *
 * @param $link
 * @return string
 */
function abs2rel($link)
{
    $base = get_home_url();
    $boom = explode($base, $link);
    switch (count($boom)) {
        case 0:
            // The link is wrong
            return false;
            break;
        case 1:
            // This link is external so return empty string
            return '';
            break;
        case 2:
            // This is a correct link, its abs is stored in $boom[1]
            return $boom[1];
            break;
        default:
            // Something odd has happened to hit this so we will complain
            return false;
    }
}

/**
 * This utility function standardizes links (relative to absolute etc)
 *
 * @param $rel
 * @param $base
 * @return string
 */
function rel2abs($rel, $base)
{
    // Maybe an embedded url ie http://seddon.dev/\"http:\/\/seddon.dev\/developments\/new-homes-for-sale-belmont-bolton\/\"
    // We need to get part in quotes and stripslashes
    $boom = explode('"', $rel);
    if (count($boom) > 1) {
        $rel = stripslashes($boom[1]);
    }
    /* return if already absolute URL */
    if (parse_url($rel, PHP_URL_SCHEME) != '')
        return ($rel);

    /* queries and anchors */
    if ($rel[0] == '#' || $rel[0] == '?')
        return ($base . $rel);

    /* parse base URL and convert to local variables:
       $scheme, $host, $path
    */
    $scheme = '';
    $host = '';
    $path = '';
    extract(parse_url($base));

    /* remove non-directory element from path */
    $path = preg_replace('#/[^/]*$#', '', $path);

    /* destroy path if relative url points to root */
    if ($rel[0] == '/')
        $path = '';

    /* dirty absolute URL */
    $abs = '';

    /* do we have a user in our URL? */
    if (isset($user)) {
        $abs .= $user;

        /* password too? */
        if (isset($pass))
            $abs .= ':' . $pass;

        $abs .= '@';
    }

    $abs .= $host;

    /* did somebody sneak in a port? */
    if (isset($port))
        $abs .= ':' . $port;

    $abs .= $path . '/' . $rel;

    /* replace '//' or '/./' or '/foo/../' with '/' */
    $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
    for ($n = 1; $n > 0; $abs = preg_replace($re, '/', $abs, -1, $n)) {
    }

    /* absolute URL is ready! */
    return ($scheme . '://' . $abs);
}

/**
 * Populate the database with new link data
 *
 * @param $rawLinks
 * @param string $mode Can be 'create' or 'append' where append adds the supplied links and create purges the table first
 */
function populate_links($rawLinks, $mode = 'create')
{
    // remove anything which isn't http or https (like mailto or tel etc)
    $links = array();
    foreach ($rawLinks as $aLink) {
        $dLink = $aLink['link'];
        $bits = explode(':', $dLink);
        $prot = $bits[0];
        $allowed = array('http', 'https');
        if (in_array($prot, $allowed) && strpos($dLink, '?') === false) $links[] = $aLink;
    }
    $thread_limit = get_option('lv_parallel', 10) + 0;
    $multiplier   = get_option('lv_multiplier', 6) + 0;

    $sql_limit = $thread_limit * $multiplier;
    $qry = array();
    $results = runRequests($links, $thread_limit);

    global $wpdb;
    $wpdb->show_errors();
    // We need to merge multiple rows into a single request to speed the process of saving the links
    $data = array();
    foreach ($links as $key => $aLink) {
        $link = $aLink['link'];
        $src = $aLink['source'];
        $status = $results[$key]['active'];
        $code = $results[$key]['http_code'];
        if (!$status) {
            $date = '0000-00-00 00:00:00';
        } else {
            $date = date('Y-m-d H:i:s');
        }
        $depth = $aLink['depth'];
        $fallback = $aLink['fallback'];
        $data[$key] = array(
            'link'         => $link,
            'source'       => $src,
            'http_code'         => $code + 0,
            'status'       => $status + 0,
            'counter'      => !$status + 0,
            'active_since' => $date,
            'depth'        => $depth,
            'fallback'     => $fallback
        );
    }

    if ($mode == 'create') {
        truncate_wp_lv_links_table();
    }
    for ($a = 0; $a < count($data); $a = $a + $sql_limit) {
        $qry[$a] = 'INSERT INTO ' . $wpdb->prefix . 'lv_links (link,source,http_code,status,counter,active_since,depth,fallback) VALUES';
        for ($b = 0; $b < $sql_limit; $b++) {
            $r = $a + $b;
            if ($data[$r]['link'] != '') {
                $qry[$a] .= '("' .
                    $data[$r]['link'] . '","' .
                    $data[$r]['source'] . '",' .
                    $data[$r]['http_code'] . ',' .
                    $data[$r]['status'] . ',' .
                    $data[$r]['counter'] . ',"' .
                    $data[$r]['active_since'] . '",' .
                    $data[$r]['depth'] . ',"' .
                    $data[$r]['fallback'] . '"),';
            }
        }

        $qry[$a] = substr($qry[$a],0, -1) . ';';

    }

    // we can now add these queries to the database
    foreach ($qry as $q) {
        $wpdb->query($q);
    }
}

function curl_get_headers($url, $silent = false)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => $url));
    curl_exec($curl);
    $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if (!$silent) echo '<p>Response code for ' . $url . ' is ' . $response_code . '</p>';
    return $response_code;
}

/**
 * Utility function to test if the supplied url is active or not
 *
 * @param string $url Link to be tested
 * @param bool $echo Echo process and results if true
 * @param string $mode Whether to use curl or get headers to test
 * @return bool        If the link is active or not
 */
function is_link_active($url, $echo = false, $mode = 'curl')
{
    // 'curl' or 'header' Curl is less strict and quicker
    if ($mode == 'header') {
        $headers = get_headers($url, 1);

        // Handle pages not found
        if (strpos($headers[0], "404") !== false) {
            if ($echo) echo "Not found: $url" . NL;
            return false;
        }

        // Handle redirected pages
        if (strpos($headers[0], "301") !== false) {
            $url = $headers["Location"];     // Continue with new URL
            if ($echo) echo "Redirected to: $url" . NL;
        } // Handle other codes than 200
        else if (strpos($headers[0], "200") == false) {
            $url = $headers["Location"];
            if ($echo) echo "Skip HTTP code $headers[0]: $url" . NL;
            return false;
        }


        // Get content type
        if (is_array($headers["Content-Type"])) {
            $content = explode(";", $headers["Content-Type"][0]);
        } else {
            $content = explode(";", $headers["Content-Type"]);
        }

        $content_type = trim(strtolower($content[0]));

        // Check content type for website
        if ($content_type != "text/html") {
            if ($content_type == "" && IGNORE_EMPTY_CONTENT_TYPE) {
                if ($echo) echo "Info: Ignoring empty Content-Type." . NL;
            } else {
                if ($content_type == "") {
                    if ($echo) echo "Info: Content-Type is not sent by the web server. Change " .
                        "'IGNORE_EMPTY_CONTENT_TYPE' to 'true' in the sitemap script " .
                        "to scan those pages too." . NL;
                } else {
                    if ($echo) echo "Info: $url is not a website: $content[0]" . NL;
                }
                return false;
            }
        }
        if ($echo) echo "Success: $url is an active link";
        return true;

    } else {
        $response_code = curl_get_headers($url, true);
        $rc = substr($response_code, 0, 1);
        switch ($rc) {
            case 4:
            case 5:
            case 1:
                return false;
                break;
            default:
                return true;
        }
    }
}

function my_array_diff($arr1, $arr2)
{
    $diff = array();
    foreach ($arr1 as $url1) {
        $found = false;
        foreach ($arr2 as $url2) {
            if ($url1 == $url2) {
                $found = true;
            }
        }
        if (!$found) {
            $diff[] = $url1;
        }
    }
    return $diff;
}

function getResult($info, $urls) {
    // determine result set to use
    $res['http_code'] = $info['http_code'];
    if ($res['http_code'] < 400 && $res['http_code'] > 0) {
        $res['active'] = true;
    } else {
        $res['active'] = false;
    }
    $key = array_search($info['url'], array_column($urls, 'link'));
    $urls[$key]['http_code'] = $res['http_code'];
    $urls[$key]['active'] = $res['active'];
    return $urls;
}

function rolling_curl($urls, $rolling_window = 5, $callback = 'getResult', $custom_options = null) {

    // make sure the rolling window isn't greater than the # of urls
    $rolling_window = (sizeof($urls) < $rolling_window) ? sizeof($urls) : $rolling_window;

    $master = curl_multi_init();
    $curl_arr = array();

    // add additional curl options here
    $std_options = array(
        CURLOPT_HTTPHEADER => array(
            'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:6.0.2) Gecko/20100101 Firefox/6.0.2',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-gb,en;q=0.5',
            'Accept-Encoding: gzip, deflate',
            'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'Proxy-Connection: Close',
            'Cookie: PREF=ID=2bb051bfbf00e95b:U=c0bb6046a0ce0334:',
            'Cache-Control: max-age=0',
            'Connection: Close'
        ),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER         => true,
        CURLOPT_NOBODY         => true,
        CURLOPT_AUTOREFERER    => true,
        CURLOPT_ENCODING       => "",
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_CONNECTTIMEOUT => 120,
        CURLOPT_TIMEOUT        => 120
    );
    $options = ($custom_options) ? ($std_options + $custom_options) : $std_options;

    // start the first batch of requests
    for ($i = 0; $i < $rolling_window; $i++) {
        $ch = curl_init();
        $options[CURLOPT_URL] = $urls[$i]['link'];
        curl_setopt_array($ch,$options);
        curl_multi_add_handle($master, $ch);
    }

    do {
        while(($execrun = curl_multi_exec($master, $running)) == CURLM_CALL_MULTI_PERFORM);
        if($execrun != CURLM_OK)
            break;
        // a request was just completed -- find out which one
        while($done = curl_multi_info_read($master)) {
            $info = curl_getinfo($done['handle']);
            // request successful.  process output using the callback function.
            $urls = $callback($info, $urls);

            // start a new request (it's important to do this before removing the old one)
            $ch = curl_init();
            $options[CURLOPT_URL] = $urls[$i++]['link'];  // increment i
            curl_setopt_array($ch,$options);
            curl_multi_add_handle($master, $ch);

            // remove the curl handle that just completed
            curl_multi_remove_handle($master, $done['handle']);

        }
    } while ($running);

    curl_multi_close($master);
    return $urls;
}

function runRequests($url_array, $thread_width = 4)
{
    $roll = false;
    if ($roll) {
        $results = rolling_curl($url_array, $thread_width);
    } else {
        $threads = 0;
        $master = curl_multi_init();
        $curl_opts = array(
            CURLOPT_HTTPHEADER => array(
                'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:6.0.2) Gecko/20100101 Firefox/6.0.2',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Encoding: gzip, deflate',
                'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7',
                'Cookie: PREF=ID=2bb051bfbf00e95b:U=c0bb6046a0ce0334:',
                'Cache-Control: max-age=0',
                'Connection: Close'
            ),
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_HEADER          => true,
            CURLOPT_NOBODY          => true,
            CURLOPT_MAXREDIRS       => 5,
            CURLOPT_CONNECTTIMEOUT  => 15,
            CURLOPT_TIMEOUT         => 15
        );
        $results = array();

        $count = 0;
        foreach ($url_array as $urlObj) {
            $url = $urlObj['link'];
            $ch = curl_init();
            $curl_opts[CURLOPT_URL] = $url;

            curl_setopt_array($ch, $curl_opts);
            curl_multi_add_handle($master, $ch); //push URL for single rec send into curl stack
            $results[$count] = array("url" => $url, "handle" => $ch);
            $threads++;
            $count++;
            if ($threads >= $thread_width) { //start running when stack is full to width
                while ($threads >= $thread_width) {
                    usleep(10);
                    while (($execrun = curl_multi_exec($master, $running)) === -1) {
                    }
                    curl_multi_select($master);

                    // a request was just completed - find out which one and remove it from stack
                    while ($done = curl_multi_info_read($master)) {
                        foreach ($results as &$res) {
                            if ($res['handle'] == $done['handle']) {
                                //$res['result'] = curl_multi_getcontent($done['handle']);
                                $res['http_code'] = curl_getinfo($done['handle'], CURLINFO_HTTP_CODE) + 0;
                                $res['active'] = false;
                                if ($res['http_code'] == 0) {
                                    $res['http_code'] = get_ext_status($done['handle']);
                                    $res['active'] = false;
                                }
                                if ($res['http_code'] < 400) {
                                    $res['active'] = true;
                                }
                            }
                        }

                        curl_multi_remove_handle($master, $done['handle']);
                        curl_close($done['handle']);

                        $threads--;
                    }
                }
            }
        }
        do { //finish sending remaining queue items when all have been added to curl
            usleep(10);
            while (($execrun = curl_multi_exec($master, $running)) === -1) {
            }
            curl_multi_select($master);
            while ($done = curl_multi_info_read($master)) {
                foreach ($results as &$res) {
                    if ($res['handle'] == $done['handle']) {
                        //$res['result'] = curl_multi_getcontent($done['handle']);
                        $res['http_code'] = curl_getinfo($done['handle'], CURLINFO_HTTP_CODE);
                        $res['active'] = false;
                        if ($res['http_code'] == 0) {
                            $res['http_code'] = get_ext_status($done['handle']);
                            $res['active'] = false;
                        }
                        if ($res['http_code'] < 400) {
                            $res['active'] = true;
                        }
                    }
                }
                curl_multi_remove_handle($master, $done['handle']);
                curl_close($done['handle']);
                $threads--;
            }
        } while ($running > 0);
        curl_multi_close($master);
        // we may have a url of '' or null so put it back in the results
        foreach ($url_array as $key => $url) {
            $results[$key]['url'] = $url[0];
        }
    }
    return $results;
}

function get_ext_status($url) {
    $code = 404;
    $res = is_link_active($url);
    if ($res) $code = 200;
    return $code;
}

/**
 * Output Buffering
 */
ob_start();

add_action('shutdown', 'ecl_shutdown', 10, 1);

function ecl_shutdown() {
    $final = '';

    // We'll need to get the number of ob levels we're in, so that we can iterate over each, collecting
    // that buffer's output into the final output.
    $levels = ob_get_level();

    for ($i = 0; $i < $levels; $i++)
    {
        $final .= ob_get_clean();
    }

    // Apply any filters to the final output
    $result = apply_filters('final_output', $final);
    if (is_admin()) {
        echo $final;
    } else {
        echo $result;
    }
}

add_filter('final_output', 'ecl_final_output');

function ecl_final_output($buffer) {
    // return buffer if an admin page
    if (is_admin()) return $buffer;
    // get the list of broken links
    if (isset($_GET['page']) && $_GET['page'] == 'link-validate') return $buffer;
    if (isset($_GET['page']) && $_GET['page'] == 'ngg_addgallery') return $buffer;
    global $wpdb;
    // this may run before the table is created so just return buffer unless table exists
    $re = $wpdb->get_var("SHOW TABLES LIKE '" . $wpdb->prefix . "lv_links'");
    if($re ==  $wpdb->prefix . 'lv_links') {
        $sql = 'SELECT link FROM ' . $wpdb->prefix . 'lv_links WHERE status=0 AND http_code>399';
        $absLinks = $wpdb->get_col($sql);
        // We need to add any relative url's to this array as we could have both absolute and relative links
        $relLinks = array();
        foreach ($absLinks as $abs) {
            $rel = abs2rel($abs);
            if ($rel) $relLinks[] = $rel;
        }

        $links = array_merge($absLinks, $relLinks);
        $hrefs = array();
        foreach ($links as $link) {
            $hrefs[] = 'href="' . $link . '"';
        }
        $_html = str_replace($hrefs, 'href="#"', $buffer);

        return $_html;
    } else {
        return $buffer;
    }

}

/**
 * CRON STUFF
 */

// Scheduled Action Hook
function replaceLinks() {
    global $wpdb;
    $allLinks = $wpdb->get_results('SELECT link, source FROM ' . $wpdb->prefix . 'lv_links', ARRAY_A);
    if (count($allLinks) > 0) {
        populate_links($allLinks);
    } else {
        $count = set_all_links(2); // 0 is homepage, 1 is all links from 0 all links from these pages, 2 is another level depth, use 0 for test 1 for speed 2 for full
    }
}

/**
 * This is not run or needed but is for testing
 */
function link_cron_init() {
    die;
}

/*
 * Add custom cron cycle of 4 weeks (13 times a year)
 */
function link_validation_cron_job_recurrence( $schedules ) {
    $schedules['monthly'] = array(
        'display' => __( 'Every 28 days', 'textdomain' ),
        'interval' => 2419200,
    );
    return $schedules;
}
add_filter( 'cron_schedules', 'link_validation_cron_job_recurrence' );

// Schedule Cron Job Event
function link_validation_cron_job() {
    if ( ! wp_next_scheduled( 'link_cron_init' ) ) {
        wp_schedule_event( time(), 'monthly', 'link_cron_init' );
    }
}
add_action( 'wp', 'link_validation_cron_job' );
add_action('link_cron_init', 'replaceLinks');
