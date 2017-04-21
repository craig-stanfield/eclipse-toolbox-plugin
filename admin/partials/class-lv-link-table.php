<?php

/**
 * Created by PhpStorm.
 * User: Craig Stanfield
 * Date: 24/02/2017
 * Time: 09:52
 */
class Link_Validate_Link_Table extends WP_List_table
{
    private $order;
    private $orderby;
    private $posts_per_page = 5;
    /**
     * Constructor, we override the parent to pass our own arguments
     * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
     */
    function __construct() {
        parent::__construct(array(
            'singular' => 'broken_link', //Singular label
            'plural' => 'broken_links', //plural label, also this well be one of the table css class
            'ajax' => false //We won't support Ajax for this table
        ));
        $this->set_order();
        $this->set_orderby();
        $this->prepare_items();
        $this->display();
    }

    /**
     * Add extra markup in the toolbars before or after the list
     * @param string $which , helps you decide if you add the markup after (bottom) or before (top) the list
     */
    function extra_tablenav_sanity($which)
    {
        global $wpdb;
        $_html ='';
        if ($which == "top") {
            //The code that goes before the table is here
            $links          = array();
            $qry            = 'SELECT * FROM ' . $wpdb->prefix . 'lv_links WHERE status=0;';
            $links          = $wpdb->get_results( $qry );
            $broken         = count($links);
            $total          = $wpdb->get_var("SELECT count(id) FROM " . $wpdb->prefix . "lv_links");
            $working        = $total - $broken;
            $workingPercent = $working / $total * 100;
            $brokenPercent  = 100 - $workingPercent;
            $scale = 6.0;

            $_html = '
            <div class="wrap">
                <h1>Link Validate Settings</h1>
                <p>Existing broken links (' . $broken . ')</p>
                <p>Existing Working links (' . $working . ')</p>
                <p>Total existing links (' . $total . ')</p>
            
                <div class="percentbar" style="width:' . round(100 * $scale) . 'px;">
                    <div style="width:' . round($brokenPercent * $scale) . 'px;"></div>
                </div>
                Percentage: ' . $brokenPercent . '%';

            if ($broken > 0) {
                $_html .= '
                <!-- This file should primarily consist of HTML with a little bit of PHP. -->
                <table class="link-validate">
                    <tr class="link-validate">
                        <td class="link-validate">Link</td>
                        <td class="link-validate">Status</td>
                        <td class="link-validate">Page Found</td>
                        <!--<td class="link-validate">Active for (days)</td>-->
                        <td class="link-validate">Broken count</td>
                    </tr>
                    <!-- Get the Existing links -->';
                    foreach ($links as $link) {
                        if ($link->status) {
                            $state = 'green';
                        } else {
                            $state = 'red';
                        }
                        $now = time();
                        $the_date = strtotime($link->active_since);
                        $date_diff = $now - $the_date;
                        $days = floor($date_diff / (60 * 60 * 24));
                        $dc = $link->counter;
                        if ($dc > 255) $dc = 255;
                        $_html .= '
                        <tr class="link-validate" style="background-color: rgba(<?php echo $dc ?>, 0, 0, 0.5);">
                            <td class="link-validate" class="' . $state . '"> ' . $link->link . '</td>
                            <td class="link-validate">' . $link->code . '</td>
                            <td class="link-validate">' . $link->source . '</td>
                            <!--<td class="link-validate">' . $days . '</td>-->
                            <td class="link-validate" style="background-color: rgba(' . $dc . ', 0, 0, 1); color: rgba(' . (255 - $dc) . ',255,255,1);">' . $link->counter . '</td>
                        </tr>';
                    }
                    $_html .= '
                    </table>
                <form id="link__validator__settings" action="options.php" method="post">
                    <!--<input type="text" name="lv_counter" value="" />-->
                    <!--<input type="submit" name="lv_submit" title="search links" value="Search Links" />-->
                </form>';
            } else {
                $_html .= '<h3>YOU HAVE NO BROKEN LINKS</h3>';
            }
        }
        if ($which == "bottom") {
            //The code that goes after the table is there
            $_html = "</div>";
        }

        echo $_html;
    }

    /**
     * Define the columns that are going to be used in the table
     * @return array $columns, the array of columns to use with the table
     */
    function get_columns()
    {
        return $columns = array(
            'cb'         => '<input type="checkbox" />', //Render a checkbox instead of text
            //'col_id'     => __('ID'),
            'col_link'   => __('Link'),
            'col_source' => __('Source'),
            'col_code'   => __('Status Code'),
            'col_status' => __('Active')
        );
    }

    /** ************************************************************************
     * Optional. If you need to include bulk actions in your list table, this is
     * the place to define them. Bulk actions are an associative array in the format
     * 'slug'=>'Visible Title'
     *
     * If this method returns an empty value, no bulk action will be rendered. If
     * you specify any bulk actions, the bulk actions box will be rendered with
     * the table automatically on display().
     *
     * Also note that list tables are not automatically wrapped in <form> elements,
     * so you will need to create those manually in order for bulk actions to function.
     *
     * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_bulk_actions() {
        $actions = array(
            'retest'    => 'Retest Link(s)'
        );
        return $actions;
    }

    /**
     * Decide which columns to activate the sorting functionality on
     * @return array $sortable, the array of columns that can be sorted by the user
     */
    public function get_sortable_columns()
    {
        return $sortable = array(
            //'col_id' => 'id',
            'col_link' => 'link',
            'col_source' => 'source',
            'col_code' => 'code',
            'col_status' => 'status'
        );
    }

    /**
     * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
     * For this example package, we will handle it in the class to keep things
     * clean and organized.
     *
     * @see $this->prepare_items()
     */
    function process_bulk_action() {

        //Detect when a bulk action is being triggered...
        if( 'retest'===$this->current_action() ) {
            wp_die('Items deleted (or they would be if we had items to delete)!');
        }

    }

    /** ************************************************************************
     * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
     * is given special treatment when columns are processed. It ALWAYS needs to
     * have it's own method.
     *
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/ $item['id']                //The value of the checkbox should be the record's id
        );
    }

    /**
     * Prepare the table with different parameters, pagination, columns and table elements
     */
    function prepare_items_sanity()
    {
        global $wpdb, $_wp_column_headers;
        $screen = get_current_screen();

        /* -- Preparing your query -- */
        $query = "SELECT * FROM " . $wpdb->prefix . "lv_links";

        /* -- Ordering parameters -- */
        //Parameters that are going to be used to order the result
        $orderby = !empty($_GET["orderby"]) ? mysql_real_escape_string($_GET["orderby"]) : 'ASC';
        $order = !empty($_GET["order"]) ? mysql_real_escape_string($_GET["order"]) : '';
        if (!empty($orderby) & !empty($order)) {
            $query .= ' ORDER BY ' . $orderby . ' ' . $order;
        }

        /* -- Pagination parameters -- */
        //Number of elements in your table?
        $totalitems = $wpdb->query($query);

        //How many to display per page?
        $perpage = 5;
        //Which page is this?
        $paged = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';
        //Page Number
        if (empty($paged) || !is_numeric($paged) || $paged <= 0) {
            $paged = 1;
        }
        //How many pages do we have in total?
        $totalpages = ceil($totalitems / $perpage);
        //adjust the query to take pagination into account
        if (!empty($paged) && !empty($perpage)) {
            $offset = ($paged - 1) * $perpage;
            $query .= ' LIMIT ' . (int)$offset . ',' . (int)$perpage;
        }
        /* -- Register the pagination -- */
        $this->set_pagination_args(array(
            "total_items" => $totalitems,
            "total_pages" => $totalpages,
            "per_page" => $perpage,
        ));
        //The pagination links are automatically built according to those parameters

        /* -- Register the Columns -- */
        $columns = $this->get_columns();
        $_wp_column_headers[$screen->id] = $columns;

        /* -- Fetch the items -- */
        $this->items = $wpdb->get_results($query);
    }

    /** ************************************************************************
     * Recommended. This is a custom column method and is responsible for what
     * is rendered in any column with a name/slug of 'title'. Every time the class
     * needs to render a column, it first looks for a method named
     * column_{$column_title} - if it exists, that method is run. If it doesn't
     * exist, column_default() is called instead.
     *
     * This example also illustrates how to implement rollover actions. Actions
     * should be an associative array formatted as 'slug'=>'link html' - and you
     * will need to generate the URLs yourself. You could even ensure the links
     *
     *
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    function column_link($item){

        //Build row actions
        $actions = array(
            'test'      => sprintf('<a href="?page=%s&action=%s&link=%s">Retest</a>',$_REQUEST['page'],'test',$item['id']),
            'delete'    => sprintf('<a href="?page=%s&action=%s&link=%s">Delete</a>',$_REQUEST['page'],'delete',$item['id']),
        );

        //Return the title contents
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            /*$1%s*/ $item['link'],
            /*$2%s*/ $item['id'],
            /*$3%s*/ $this->row_actions($actions)
        );
    }

    /**
     * Display the rows of records in the table
     * @return string, echo the markup of the rows
     */
    function display_rows() {

        //Get the records registered in the prepare_items method
        $records = $this->items;

        //Get the columns registered in the get_columns and get_sortable_columns methods
        list( $columns, $hidden ) = $this->get_column_info();

        //Loop for each record
        if(!empty($records)){
            foreach($records as $rec){
                //Open the line
                echo '<tr id="record_'.$rec->id.'">';
                foreach ( $columns as $column_name => $column_display_name ) {
                    //Style attributes for each col
                    $class = "class='$column_name column-$column_name'";
                    $style = "";
                    if ( in_array( $column_name, $hidden ) ) $style = ' style="display:none;"';
                    $attributes = $class . $style;

                    //edit link
                    $editlink  = '/wp-admin/link.php?action=edit&link_id='.(int)$rec->link_id;

                    //Display the cell
                    switch ( $column_name ) {
                        case "cb":  echo '<td '.$attributes.'><input id="cb-select-'.$rec->id.'" name="post[]" value="'.$rec->id.'" type="checkbox"></td>';   break;
                        //case "col_id":  echo '<td '.$attributes.'>'.stripslashes($rec->id).'</td>';   break;
                        case "col_link": echo '<td '.$attributes.'>'.stripslashes($rec->link).'</td>'; break;
                        case "col_source": echo '<td '.$attributes.'>'.stripslashes($rec->source).'</td>'; break;
                        case "col_code": echo '<td '.$attributes.'>'.$rec->code.'</td>'; break;
                        case "col_status": echo '<td '.$attributes.'>'.$rec->status.'</td>'; break;
                    }
                }

                //Close the line
                echo'</tr>';
            }
        }
    }

    /** ************************************************************************
     * REQUIRED! This is where you prepare your data for display. This method will
     * usually be used to query the database, sort and filter the data, and generally
     * get it ready to be displayed. At a minimum, we should set $this->items and
     * $this->set_pagination_args(), although the following properties and methods
     * are frequently interacted with here...
     *
     * @global WPDB $wpdb
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     **************************************************************************/
    function prepare_items() {
        global $wpdb; //This is used only if making any database queries

        /**
         * First, lets decide how many records per page to show
         */
        $per_page = $this->posts_per_page;


        /**
         * REQUIRED. Now we need to define our column headers. This includes a complete
         * array of columns to be displayed (slugs & titles), a list of columns
         * to keep hidden, and a list of columns that are sortable. Each of these
         * can be defined in another method (as we've done here) before being
         * used to build the value for our _column_headers property.
         */
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        /**
         * REQUIRED. Finally, we build an array to be used by the class for column
         * headers. The $this->_column_headers property takes an array which contains
         * 3 other arrays. One for all columns, one for hidden columns, and one
         * for sortable columns.
         */
        $this->_column_headers = array($columns, $hidden, $sortable);


        /**
         * Optional. You can handle your bulk actions however you see fit. In this
         * case, we'll handle them within our package just to keep things clean.
         */
        $this->process_bulk_action();


        $screen = get_current_screen();

        /* -- Preparing your query -- */
        $query = "SELECT * FROM " . $wpdb->prefix . "lv_links";

        /* -- Ordering parameters -- */
        //Parameters that are going to be used to order the result
        $orderby = !empty($_GET["orderby"]) ? mysql_real_escape_string($_GET["orderby"]) : 'ASC';
        $order = !empty($_GET["order"]) ? mysql_real_escape_string($_GET["order"]) : '';
        if (!empty($orderby) & !empty($order)) {
            $query .= ' ORDER BY ' . $orderby . ' ' . $order;
        }

        /* -- Pagination parameters -- */
        $data = $wpdb->get_results($query);

        /**
         * REQUIRED for pagination. Let's figure out what page the user is currently
         * looking at. We'll need this later, so you should always include it in
         * your own package classes.
         */
        $current_page = $this->get_pagenum();

        /**
         * REQUIRED for pagination. Let's check how many items are in our data array.
         * In real-world use, this would be the total number of items in your database,
         * without filtering. We'll need this later, so you should always include it
         * in your own package classes.
         */
        $total_items = count($data);


        /**
         * The WP_List_Table class does not handle pagination for us, so we need
         * to ensure that the data is trimmed to only the current page. We can use
         * array_slice() to
         */
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);



        /**
         * REQUIRED. Now we can add our *sorted* data to the items property, where
         * it can be used by the rest of the class.
         */
        $this->items = $data;


        /**
         * REQUIRED. We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );

        $last_post = $current_page * $per_page;
        $first_post = $last_post - $per_page + 1;
        $last_post > $total_items AND $last_post = $total_items;

        // Setup the range of keys/indizes that contain
        // the posts on the currently displayed page(d).
        // Flip keys with values as the range outputs the range in the values.
        $range = array_flip( range( $first_post - 1, $last_post - 1, 1 ) );

        // Filter out the posts we're not displaying on the current page.
        $posts_array = array_intersect_key( $data, $range );
        # <<<< Pagination

        // Prepare the data
        $permalink = __( 'Edit:' );
        foreach ( $posts_array as $key => $post )
        {
            $link     = get_edit_post_link( $post->ID );
            $no_title = __( 'No title set' );
            $title    = ! $post->post_title ? "<em>{$no_title}</em>" : $post->post_title;
            $data[ $key ]->post_title = "<a title='{$permalink} {$title}' href='{$link}'>{$title}</a>";
        }
        $this->items = $posts_array;
    }

    /**
     * Override of table nav to avoid breaking with bulk actions & according nonce field
     */
    public function display_tablenav_sanity( $which ) {
        ?>
        <div class="tablenav <?php echo esc_attr( $which ); ?>">
            <!--
            <div class="alignleft actions">
                <?php # $this->bulk_actions( $which ); ?>
            </div>
             -->
            <?php
            $this->extra_tablenav( $which );
            $this->pagination( $which );
            ?>
            <br class="clear" />
        </div>
        <?php
    }

}