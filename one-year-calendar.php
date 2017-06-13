<?php
/*
Plugin Name: One Year Calendar
Plugin URI: http://lancis.in
Description: One year calendar. That's about it
Version: 1.0
Author: Francis Lalnunmawia
Author URI: http://lancis.in
Author Email: francis@lancis.in
License: MIT
*/

class OneYearCalendar {

    private $plugin_path;
    private $plugin_url;
    private $is_public;

    public function __construct() 
    {
        // Set up default vars
        $this->plugin_path = plugin_dir_path( __FILE__ );
        $this->plugin_url = plugin_dir_url( __FILE__ );
        $this->dates = unserialize(get_option('one_year_calendar_settings'));
        // Set up activation hooks
        // register_activation_hook( __FILE__, array(&$this, 'activate') );
        // register_deactivation_hook( __FILE__, array(&$this, 'deactivate') );
        // Set up l10n
        load_plugin_textdomain( 'plugin-name-locale', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
        
        // Add your own hooks/filters
        add_action( 'init', array(&$this, 'init') );
        add_action( 'admin_enqueue_scripts', array(&$this, 'enqueue_admin_assets') );
        add_action( 'admin_menu', array(&$this,'calendar_menu') );
        add_action( 'admin_post_lancis_calendar_update_dates', array(&$this, 'update_dates') );

        add_shortcode( 'one-year-calendar', array(&$this, 'public_calendar') );
        add_shortcode( 'one-year-calendar-event-list', array(&$this, 'event_list') );
        // echo $this->plugin_url; exit;
    }

    public function init()
    {
        register_setting( 'one-year-calendar-settings', 'one_year_calendar_settings' );
    }

    public function public_calendar()
    {
        $this->is_public = true;
        $this->enqueue_public_assets();
        $this->generate_calendar();
    }

    public function event_list($attributes)
    {
        $a = shortcode_atts([
                'format' => 'Y-m-d'
            ], $attributes);
        echo '<ul id="one-year-calendar-event-list">';
        foreach ($this->dates as $key => $value) {
            $date = date($a['format'], strtotime(date('Y') . '-' . $key));
            echo '<li>' . $date . ' - ' . $value . '</li>';
        }
        echo '</ul>';
    }

    public function calendar_menu() {
        add_menu_page( "Calendar", "Calendar", "manage_options", "lancis-calendar", array(&$this, "generate_calendar"));
    }

    public function generate_calendar()
    {
        $date = strtotime(date("Y-m-d"));
        $year = isset($_GET['year']) ? $_GET['year'] : date('Y');
        $class = 'one-year-calendar' . ($this->is_public ? ' ' : ' wrap')
        ?>
        <div class="<?php echo $class; ?>">
            <h1>One Year Calendar</h1>
            <p>You can use [one-year-calendar] to display the calendar.</p>
            <p>To display list of dates, use [one-year-calendar-event-list] with optional "format" attribute. eg [one-year-calendar-event-list format="dS M, Y"] </p>
            <div>
                <?php
                for ($i=1; $i <= 12; $i++) { 
                    $this->print_month($i, $year);
                }
                ?>
            </div>
        </div>
        <?
    }

    public function print_form()
    {
        ?>
        <div id="date_form" style="display:none;">
            <form action="/wp-admin/admin-post.php?" method="post">
            <?php wp_nonce_field( 'one-year-calendar-update-date_' ); ?>
            <input type="hidden" name="action" value="lancis_calendar_update_dates">
            <input type="hidden" name="date" value="" id="lancis_calendar_item_date">
            <table class="form-table">
                <tbody>
                    <tr>
                        <td>
                            <input type="text" name="value" value="test" id="lancis_calendar_item_value" style="width: 100%"/>
                        </td>
                        <td>
                            <input type="submit" value="Save Changes" class="button-primary" name="Submit">
                        </td>
                    </tr>
                </tbody>
            </table>
            </form>
        </div>
        <?php
    }

    public function enqueue_public_assets()
    {
        wp_enqueue_style( 'calendar', $this->plugin_url . 'one-year-calendar.public.css', false, date('Y_m_d_h_i_s', filemtime($this->plugin_path . 'bootstrap.min.css')) );
        wp_enqueue_script( 'tooltipsy', $this->plugin_url . 'tooltipsy.min.js', ['jquery']);
        wp_enqueue_script( 'calendar', $this->plugin_url . 'one-year-calendar.public.js', ['tooltipsy'], date('Y_m_d_h_i_s', filemtime($this->plugin_url . 'one-year-calendar.public.js')) );
    }

    public function enqueue_admin_assets($hook)
    {
        if($hook != 'toplevel_page_lancis-calendar') {
            return;
        }
        wp_enqueue_script( 'calendar', $this->plugin_url . 'one-year-calendar.js', ['jquery'], date('Y_m_d_h_i_s', filemtime($this->plugin_path . 'one-year-calendar.js')) );
        wp_enqueue_style( 'calendar', $this->plugin_url . 'bootstrap.min.css', false, date('Y_m_d_h_i_s', filemtime($this->plugin_path . 'bootstrap.min.css')) );
    }

    public function print_month($month, $year)
    {
        $firstDay = mktime(0,0,0,$month, 1, $year);
        $title = strftime('%B', $firstDay);
        $dayOfWeek = date('D', $firstDay);
        $daysInMonth = cal_days_in_month(0, $month, $year);
        /* Get the name of the week days */
        $timestamp = strtotime('next Sunday');
        $weekDays = array();
        for ($i = 0; $i < 7; $i++) {
            $weekDays[] = strftime('%a', $timestamp);
            $timestamp = strtotime('+1 day', $timestamp);
        }
        $blank = date('w', strtotime("{$year}-{$month}-01"));
        ?>
        <table class='table table-bordered' style="table-layout: fixed;">
            <tr>
                <th colspan="7" class="text-center"> <?php echo $title ?> <?php echo $year ?> </th>
            </tr>
            <tr class="days">
                <?php foreach($weekDays as $key => $weekDay) : ?>
                    <td class="text-center"><?php echo $weekDay ?></td>
                <?php endforeach ?>
            </tr>
            <?php $this->print_week($i, $blank, $daysInMonth, $month) ?>
        </table>
    <?php 
    }

    public function print_week($i, $blank, $daysInMonth, $month)
    {
        echo '<tr>';
        for($i = 0; $i < $blank; $i++){
            echo '<td></td>';
        }
        if ($this->is_public) {
            $this->print_day($i, $blank, $daysInMonth, $month, $year); // frontend
        } else {
            $this->print_day_admin($i, $blank, $daysInMonth, $month); // backend
        }
        for($i = 0; ($i + $blank + $daysInMonth) % 7 != 0; $i++){
            echo '<td></td>';
        }
        echo '</tr>';
    }

    public function print_day($i, $blank, $daysInMonth, $month)
    {
        $today = date('d');
        $thisMonth = date('m');
        for($i = 1; $i <= $daysInMonth; $i++){
            $classes = '';
            $info ='';
            if(isset($this->dates[$month . '-' . $i])) {
                $classes = 'has-detail info';
                $info = $this->dates[$month . '-' . $i];
            }
            if ($today == $i && $month == $thisMonth) {
                $classes.=' success';
            }

            echo '<td class="' . $classes . '" title="'.$info.'">';
                echo $i;
            echo '</td>';

            if(($i + $blank) % 7 == 0){
                echo '</tr><tr>';
            }
        }
    }

    public function print_day_admin($i, $blank, $daysInMonth, $month)
    {
        add_thickbox();
        for($i = 1; $i <= $daysInMonth; $i++){
            $detail = $this->dates[$month . '-'. $i];

            echo '<td class="'.($detail?'danger':'').'">';
            echo '<a data-details="'. ($detail?:'') .'" data-date="' . $month . '-'. $i .'" href="#TB_inline?width=100&height=71&inlineId=date_form" class="thickbox lancis_calendar_date '.($detail ? 'has-detail': '').'">';
            echo $i;
            echo '</a>';
            echo '</td>';

            if(($i + $blank) % 7 == 0){
                echo '</tr><tr>';
            }
        }
    }

    public function update_dates()
    {
        check_admin_referer( 'one-year-calendar-update-date_' );
        $dates = $this->dates;
        $dates[$_POST['date']] = $_POST['value'];
        update_option('one_year_calendar_settings', serialize($dates));
        wp_redirect(admin_url('admin.php?page=lancis-calendar'));
        die();
    }

}
new OneYearCalendar();

?>