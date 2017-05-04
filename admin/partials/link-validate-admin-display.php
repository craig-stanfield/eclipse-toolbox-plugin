<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://eclipse-creative.com/
 * @since      1.0.0
 *
 * @package    Link_Validate
 * @subpackage Link_Validate/admin/partials
 */
global $wpdb, $lvParallel, $lvMultiplier, $postTypes;
$total          = $wpdb->get_var("SELECT count(id) FROM " . $wpdb->prefix . "lv_links");

$h1s            = array();
$qry            = 'SELECT * FROM ' . $wpdb->prefix . 'lv_links WHERE h1!=1;';
$h1s            = $wpdb->get_results($qry);
$h1broken       = count($h1s);
$h1working      = $total - $h1broken;
if ($total > 0) {
    $h1workingPercent = $h1working / $total * 100;
} else {
    $h1workingPercent = 0;
}
$h1brokenPercent  = (100 - $h1workingPercent) + 0.0000000;

$links          = array();
$qry            = 'SELECT * FROM ' . $wpdb->prefix . 'lv_links WHERE http_code!=200 AND http_code!=301 AND http_code!=302 AND http_code!=303;';
$links          = $wpdb->get_results( $qry );
$broken         = count($links);
$working        = $total - $broken;
if ($total > 0) {
    $workingPercent = $working / $total * 100;
} else {
    $workingPercent = 0;
}
$brokenPercent  = (100 - $workingPercent) + 0.0000000;

// Global Options
if (isset($_POST["update_settings"])) {
    // Do the saving
    $lvParallel = esc_attr($_POST['lv_parallel']);
    update_option("lv_parallel", $lvParallel);
    $lvMultiplier = esc_attr($_POST['lv_multiplier']);
    update_option("lv_multiplier", $lvMultiplier);
    $postTypes = $_POST['post_type'];
    update_option('post_type', $postTypes);
    echo '
<div id="message" class="updated">Settings have been successfully updated.</div>
    ';
}
if(isset($_POST['lv_rebuild']) && $_POST['lv_secret'] == 'lopear') {
    $count = set_all_links(2);
    // Reload page
    header("Refresh:0");
}
$lvParallel = get_option('lv_parallel', 10);
$lvMultiplier = get_option('lv_multiplier', 6);
$postTypes = get_option('post_type');
?>
<script type="text/javascript">
    /* Link Validate for admin */
    jQuery(document).ready(function(){
        jQuery(".lv-tab").hide();
        jQuery("#div-lv-links").show();
        jQuery(".lv-tab-links").click(function(){
            var divid=jQuery(this).attr("id");
            jQuery(".lv-tab-links").removeClass("active");
            jQuery(".lv-tab").hide();
            jQuery("#"+divid).addClass("active");
            jQuery("#div-"+divid).fadeIn();
        });

        jQuery("#lv-settings-form-admin .button-primary").click(function(){
            var $el = jQuery("#lv_active");
            var $vlue = jQuery("#lv_rewrite_text").val();
            var lvActive ="";
            /*if((!$el[0].checked) && $vlue=="")
             {
             alert("Please enable plugin");
             return false;
             }*/

            if(($el[0].checked) && $vlue=="")
            {
                jQuery("#lv_rewrite_text").css("border","1px solid red");
                jQuery("#adminurl").append(" <strong style='color:red;'>Please enter admin url slug</strong>");
                return false;
            }

            if(($el[0].checked) && lvActive==""){
                //alert(lvActive);
                if (confirm("1. Have you updated your permalink settings?\n\n2. Have you checked writable permission on htaccess file?\n\nIf your answer is YES then Click OK to continue")){
                    return true;
                }else
                {
                    return false;
                }
            }
            var seoUrlVal=jQuery("#check_permalink").val();
            var htaccessWriteable ="0";
            var hostIP ="127.0.0.1";
            //	alert(hostIP);
            if(seoUrlVal=="no")
            {
                alert("Please update permalinks before activate the plugin. permalinks option should not be default!.");
                document.location.href="http://seddon.dev/wp-admin/options-permalink.php";
                return false;
            }
            /*else if(htaccessWriteable=="0" && hostIP!="127.0.0.1"){
             alert("Error : .htaccess file is not exist OR may be htaccess file is not writable, So please double check it before enable the plugin");
             return false;
             }*/
            else
            {
                return true;
            }
        });

        jQuery('#modusOperandi').change(function() {
            if (this.value == 1) {
                // Show the hidden delete option
                jQuery('.remove').show();
            } else {
                // Hide the hidden delete option
                jQuery('.remove').hide();
            }
        }).change();
    })
</script>
<div class="heads">
    <div class="spinner"></div>
</div>
<div class="wrap">
    <form id="link__validator__settings" action="" method="POST">
        <h1>Eclipse Creative Consultants Ltd. Admin Toolbox</h1>
        <p>&nbsp;</p>
        <div id="lv-tab-menu">
            <a id="lv-links" class="lv-tab-links active">Link Validation</a>
            <a id="lv-general" class="lv-tab-links">General Settings</a>
            <a id="lv-rebuild" class="lv-tab-links">Rebuild</a>
            <a id="lv-h1-tags" class="lv-tab-links">&lt;h1&gt; Tags</a>
            <!--<a id="lv-spare3" class="lv-tab-links">Spare3</a>-->
            <!--<a id="lv-spare4" class="lv-tab-links">Spare4</a>-->
            <!--<a id="lv-spare5" class="lv-tab-links">Spare5</a>-->
            <!--<a id="lv-spare6" class="lv-tab-links">Spare6</a>-->
        </div>
        <div class="lv-setting">
            <div id="div-lv-links" class="first lv-tab" style="display: block;">
                <h2>Broken Link Validation</h2>

                <p>Existing broken links (<?php echo $broken ?>)&nbsp;&nbsp;&nbsp;&nbsp;
                    <span>Existing Working links (<?php echo $working ?>)&nbsp;&nbsp;&nbsp;&nbsp;</span>
                    <span>Total existing links (<?php echo $total ?>)</span>
                </p>

                <div class="percentbar" style="width: 100%;">
                    <div style="width:<?php echo round($brokenPercent); ?>%;"></div>
                </div>
                Percentage: <?php echo $brokenPercent; ?>%<br>
                <p>&nbsp;</p>
                <?php
                if ($broken > 0) {
                    ?>
                    <select id="modusOperandi">
                        <option value="0" selected>Test Mode</option>
                        <option value="1">Live Mode</option>
                    </select>
                    &nbsp;&nbsp;&nbsp;&nbsp;Test mode allows you to check if a link is broken,
                    Live mode updates the link in the database if it has become active but leaves it unchanged if still broken,
                    The page is then reloaded which removes the now active link,
                    <p>&nbsp;</p>
                    <!-- This file should primarily consist of HTML with a little bit of PHP. -->
                    <table class="link-validate table">
                        <tr class="link-validate tr">
                            <td class="link-validate link">Link</td>
                            <td class="link-validate status">Status</td>
                            <td class="link-validate source">Page Found</td>
                            <td class="link-validate fallback">Fallback Url</td>
                            <!--<td class="link-validate">Active for (days)</td>-->
                            <td class="link-validate depth">Depth</td>
                            <td class="link-validate counter">Broken count</td>
                            <td class="link-validate repair">Repair Link?</td>
                        </tr>
                        <!-- Get the Existing links -->
                        <?php foreach ($links as $link) {
                            if ($link->status) {
                                $state = 'green';
                            } else {
                                $state = 'red';
                            }
                            $dc = $link->counter;
                            if ($dc > 255) $dc = 255;
                            ?>
                            <tr class="link-validate tr" style="background-color: rgba(<?php echo $dc ?>, 0, 0, 0.5);">
                                <td class="link-validate link" class="<?php echo $state ?>"> <?php echo $link->link ?> </td>
                                <td class="link-validate status"><?php echo $link->http_code ?></td>
                                <td class="link-validate source"> <?php echo $link->source ?> </td>
                                <td class="link-validate fallback"> <?php echo $link->fallback ?> </td>
                                <!--<td class="link-validate"><?php //echo $days ?></td>-->
                                <td class="link-validate depth" style="background-color: rgba(<?php echo $dc ?>, 0, 0, 1); color: rgba(<?php echo 255 - $dc?>,255,255,1);"><?php echo $link->depth ?></td>
                                <td class="link-validate counter" style="background-color: rgba(<?php echo $dc ?>, 0, 0, 1); color: rgba(<?php echo 255 - $dc?>,255,255,1);"><?php echo $link->counter ?></td>
                                <td class="link-validate repair"><a href="#" class="retest" id="<?php echo $link->link ?>">Fix?</a><a href="#" class="remove" style="display: none;" id="lv_<?php echo $link->id ?>">  Del?</a></td>
                            </tr>
                        <?php } ?>
                    </table>
                    <p>If a Link has a status of 0 it is a suspect link and is not removed from the page. If the link is valid we can retest or change the status</p>
                    <p>Clicking fix while in Live Mode will retest the link and if it is now resolved it will update it in the database, in this way it is possible to fix broken links</p>
                    <p>There is a bug with social media websites which are returning status code 0 (the site just rejects it) This will be resolved in the next version</p>
                <?php } else { ?>
                    <h3>YOU HAVE NO BROKEN LINKS</h3>
                <?php } ?>
                <p>&nbsp;</p>
            </div>
            <div id="div-lv-general" class="lv-tab" style="display: none;">
                <h2>Eclipse Toolbox Wordpress Settings</h2><?php wp_nonce_field('update-options') ?>
                <table class="form-table" width="100%" cellpadding="10">
                    <tr>
                        <th scope="row">Track Post Types</th>
                        <td scope="row" align="left">
                            <fieldset>
                                <?php
                                $post_types = get_post_types();
                                // We dont need all of these post types. remove defaults and ones from ngg cf7 and others leaving just custom
                                // ToDo get these in another way...
                                $exclude = array(
                                    'post',
                                    'page',
                                    'attachment',
                                    'revision',
                                    'nav_menu_item',
                                    'custom_css',
                                    'customize_changeset',
                                    'acf-field-group',
                                    'acf-field',
                                    'wpcf7_contact_form',
                                    'slideshow',
                                    'page_theme',
                                    'faq',
                                    'video',
                                    'dedo_download',
                                    'ngg_album',
                                    'ngg_gallery',
                                    'ngg_pictures',
                                    'lightbox_library',
                                    'photocrati-comments',
                                    'displayed_gallery',
                                    'display_type',
                                    'gal_display_source',
                                    'tablepress_table',
                                    'mc4wp-form',
                                    'flamingo_contact',
                                    'flamingo_inbound',
                                    'flamingo_outbound',
                                    'acf'
                                );
                                foreach ($post_types as $postType) {
                                    $set = array_key_exists($postType, $postTypes);
                                    $excluded = in_array($postType, $exclude);
                                    if (!$excluded) {
                                        echo '
                                <label>
                                    <input type="checkbox" name="post_type[' . $postType . ']" value="post_type[' . $postType . ']" ' . ($set ? 'checked' : '') . '>
                                    ' . $postType . '
                                </label>
                                <br>
                            ';
                                    }
                                }
                                //wp_dropdown_categories('show_count=a&heirarchical=1');
                                ?>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="lv_parallel">Links in Parallel</label>
                        </th>
                        <td>
                            <input id="lv_parallel" class="regular_text" name="lv_parallel" aria-describedby="parallel-description" value="<?php echo $lvParallel ?>" type="text">
                            <p id="parallel-description" class="description">Enter the number of links to test in parallel, suggest between 4 and 20, default is 10</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="lv_multiplier">MySql Multiplier</label>
                        </th>
                        <td>
                            <input id="lv_multiplier" class="regular_text" name="lv_multiplier" aria-describedby="multiplier-description" type="text" value="<?php echo $lvMultiplier ?>">
                            <p id="parallel-description" class="description">Enter the MySql multiplier factor, this determines how many records to add to the database in a single query, default is 6</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            &nbsp;
                        </th>
                        <td><span class="regular_text">Result: <?php echo $lvParallel ?> x <?php echo $lvMultiplier ?> = <?php echo $lvParallel * $lvMultiplier ?> rows entered in parallel to the database (default 60)</span></td>
                    </tr>
                </table>
            </div>
            <div id="div-lv-rebuild" class="lv-tab" style="display: none;">
                <h2>Rebuild Link Table</h2>
                <table class="form-table" width="100%" cellpadding="10">
                    <tr>
                        <th scope="row">Rebuild table</th>
                        <td scope="row" align="left">
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="lv_rebuild" value="" >
                                    Rebuild table
                                </label>
                                <br>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="lv_secret">Authorization password</label>
                        </th>
                        <td>
                            <input id="lv_secret" class="regular_text" name="lv_secret" aria-describedby="secret-description" type="password" value="">
                            <p id="secret-description" class="description">Enter the secret password to allow the tables to update. WARNING: Rebuild takes time and is resource intensive.</p>
                        </td>
                    </tr>
                </table>
            </div>
            <div id="div-lv-h1-tags" class="lv-tab" style="display: none;">
                <h2>&lt;h1&gt; Issues</h2>


                <p>Existing Incorrect h1 tags (<?php echo $h1broken ?>)&nbsp;&nbsp;&nbsp;&nbsp;
                    <span>Existing Correct h1 tags (<?php echo $h1working ?>)&nbsp;&nbsp;&nbsp;&nbsp;</span>
                    <span>Total Potential h1 Tags (<?php echo $total ?>)</span>
                </p>

                <div class="percentbar" style="width: 100%;">
                    <div style="width:<?php echo round($h1brokenPercent); ?>%;"></div>
                </div>
                Percentage: <?php echo $h1brokenPercent; ?>%<br>
                <p>&nbsp;</p>
                <?php
                if ($h1broken > 0) {
                    ?>
                    &nbsp;&nbsp;&nbsp;&nbsp;Test mode allows you to check if a link is broken,
                    Live mode updates the link in the database if it has become active but leaves it unchanged if still broken,
                    The page is then reloaded which removes the now active link,
                    <p>&nbsp;</p>
                    <!-- This file should primarily consist of HTML with a little bit of PHP. -->
                    <table class="link-validate table">
                        <tr class="link-validate tr">
                            <td class="link-validate uri">Uri</td>
                            <td class="link-validate h1">h1</td>
                            <td class="link-validate h2">h2</td>
                            <td class="link-validate h3">h3</td>
                            <td class="link-validate h4">h4</td>
                            <td class="link-validate h5">h5</td>
                            <td class="link-validate h6">h6</td>
                        </tr>
                        <!-- Get the Existing links -->
                        <?php foreach ($h1s as $link) {
                            if ($link->h1 == 1) {
                                $state = 'green';
                            } else {
                                $state = 'red';
                            }
                            $dc = $link->counter;
                            if ($dc > 255) $dc = 255;
                            ?>
                            <tr class="link-validate tr" style="background-color: rgba(<?php echo $dc ?>, 0, 0, 0.5);">
                                <td class="link-validate uri"><?php echo $link->link ?></td>
                                <td class="link-validate h1" class="<?php echo $state ?>"><?php echo $link->h1 ?></td>
                                <td class="link-validate h2"><?php echo $link->h2 ?></td>
                                <td class="link-validate h3"><?php echo $link->h3 ?></td>
                                <td class="link-validate h4"><?php echo $link->h4 ?></td>
                                <td class="link-validate h5"><?php echo $link->h5 ?></td>
                                <td class="link-validate h6"><?php echo $link->h6 ?></td>
                            </tr>
                        <?php } ?>
                    </table>
                    <p> </p>
                    <p> </p>
                    <p> </p>
                <?php } else { ?>
                    <h3>YOU HAVE NO BROKEN &lt;h1&gt; TAGS</h3>
                <?php } ?>
                <p>&nbsp;</p>
            </div>
            <!--<div id="div-lv-spare3" class="lv-tab" style="display: none;">
                <h2>Spare Tab 3</h2>
            </div>-->
            <!--<div id="div-lv-spare4" class="lv-tab" style="display: none;">
                <h2>Spare Tab 4</h2>
            </div>-->
            <!--<div id="div-lv-spare5" class="lv-tab" style="display: none;">
                <h2>Spare Tab 5</h2>
            </div>-->
            <!--<div id="div-lv-spare6" class="lv-tab" style="display: none;">
                <h2>Spare Tab 6</h2>
            </div>-->
        </div>
        <input type="hidden" name="update_settings" value="Y">
        <input type="submit" class="button-primary" name="cpt_submit" value="Save Details">
    </form>
</div>
