<?php
/*
 * Plugin Name:       Sashami Map Admin
 * Description:       Manage the houses by artist Sashami on the map
 * Version:           1.0.0
 * Author:            c0chonnet
 */
 
 if (!defined('ABSPATH')) {
  die('Denied');
}

define('SASHAMI_MAP_ADMIN_PATH', plugin_dir_path(__FILE__));


/* --- ADMIN PAGES --- */

function sashami_admin_menu_item() {
	add_menu_page('Sashami Map Admin','Sashami Map Admin', 'manage_options', 'sashami_admin_menu',  'sashami_admin_menu_page', 'dashicons-admin-multisite', 3);	
	add_submenu_page('sashami_admin_menu', 'Добавить дом', 'Добавить дом', 'manage_options', 'sashami_add_house', 'sashami_add_house_page', 1 );
}

add_action('admin_menu','sashami_admin_menu_item');

function sashami_admin_menu_page() {
	?>
	<div class='wrap'>
    <h2>Управление домами</h2>
	<br>
	<button class="button button-primary"><span class="dashicons dashicons-admin-home"></span> Новый дом</button>
	<button class="button button-primary"><span class="dashicons dashicons-upload"></span> Массовая загрузка</button>
	<br>
	</div>
	<?php
	
    global $wpdb;
	$result = $wpdb->get_results('SELECT * FROM sashami_houses WHERE DELETED IS NULL 
	AND point_type=0 ORDER BY id');
	echo '<table style="font-size:10px;margin:9px;padding:11px;line-height:1.2;
	background-color:rgba(255,255,255,0.4);text-align: left;border-collapse: collapse;">
  <thead>	
  <tr>
	<th style="padding:20px;"></th>
    <th>ID</th>
    <th>Last Edit</th>
    <th>Address</th>
	<th>Lon</th>
	<th>Lat</th>
	<th>Neighborhood</th>
	<th>Year</th>
	<th>Materials</th>
	<th>W</th>
	<th>H</th>
	<th>Sold</th>
	<th>Owner</th>
	<th>Text</th>
	<th>Info</th>
	<th style="padding:20px;">Built</th>
	<th>Icon</th>
	<th>Full</th>
  </tr><thead>';
	foreach($result as $row) {
		$current_house = $row->id;
		echo 
		   '<tr style="border-bottom: 1px solid black;">
		   <td><span class="dashicons dashicons-edit"></span></td>
			<td style="padding:10px;font-size:12px;">' . $current_house .'</td>
			<td>' . $row->edited .' by '. $row->admin .'</td>
			<td>' . $row->address . '</td>
			<td>' . $row->lon . '</td>
			<td>' . $row->lat . '</td>
			<td>' . $row->neighborhood . '</td>
			<td>' . $row->year . '</td>
			<td>' . $row->materials . '</td>
			<td>' . $row->width . '</td>
			<td>' . $row->height . '</td>
			<td>' . $row->sold . '</td>
			<td>' . $row->owner . '</td>
			<td>' . $row->text . '</td>
			<td>' . $row->house_info . '</td>
			<td>' . $row->built . '</td>';
			
			if	(file_exists(SASHAMI_MAP_ADMIN_PATH . '/houses/icon/' . $current_house . '.png')){		
				echo '<td> <img width="50px" src="/wp-content/plugins/sashami/houses/icon/' . $current_house . '.png"></td>';}
			else {echo '<td><a>Добавить</a></td>';}
			
			if	(file_exists(SASHAMI_MAP_ADMIN_PATH . '/houses/full/' . $current_house . '.jpg')){
				echo '<td> <img width="50px" src="/wp-content/plugins/sashami/houses/full/' . $current_house . '.jpg"</td></tr>';}
			else {echo '<td><a>Добавить</a></td></tr>';}
}	echo '</table>'; }

function sashami_add_house_page() {
	?>
	<div class='wrap'>
    <h2>Добавить дом</h2>
	</div>
	<?php
}


/* --- MAP PAGE --- */

add_action('template_redirect', 'sashami_map_index');

function sashami_map_index() {
	/*if (is_home() || is_front_page()) {*/
	  if (is_page('map')) {
        $sashami_index = SASHAMI_MAP_ADMIN_PATH . '/templates/index.html';
        if (file_exists($sashami_index )) {
            $html_content = file_get_contents($sashami_index );
            echo $html_content;
			exit;
}
		} else {
					echo '';
        }
    }
	

?>