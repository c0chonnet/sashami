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
require_once(ABSPATH.'wp-admin/includes/file.php');

/* --- ADMIN PAGES --- */

function sashami_admin_menu_item() {
	add_menu_page('Sashami Map Admin','Sashami Map Admin', 'manage_options', 'sashami_admin_menu',  'sashami_admin_menu_page', 'dashicons-admin-multisite', 3);	
	add_submenu_page('sashami_admin_menu', 'Добавить дом', 'Добавить дом', 'manage_options', 'sashami_add_house', 'sashami_add_house_page', 1 );
}

add_action('admin_menu','sashami_admin_menu_item');

/* --- MANAGE ALL HOUSES --- */

function sashami_admin_menu_page() {
	
		?>
	<div class='wrap'>
    <h2>Управление домами</h2>
	<br>
	<a href="?page=sashami_add_house&id=0"><button class="button"><span class="dashicons dashicons-admin-home"></span> Новый дом</button></a>
	<button class="button"><span class="dashicons dashicons-upload"></span> Массовая загрузка</button>
	<a href="?page=sashami_admin_menu&action=update_map"><button class="button button-primary"><span class="dashicons dashicons-update"></span> Обновить карту</button></a>
	<?php
	
	/* --- ACTION GEOJSON MAKING --- */

if (isset($_GET['action']) && $_GET['action'] == 'update_map') {
	global $wpdb;
	$result = $wpdb->get_results('SELECT * FROM sashami_houses WHERE DELETED IS NULL 
	AND point_type=0 ORDER BY id');
	$features = [];
	foreach ($result as $row) {
        $features[] = [
            "type" => "Feature",
            "geometry" => [
                "type" => "Point",
                "coordinates" => [(float)$row->lon, (float)$row->lat]
            ],
            "properties" => [
                "id" => $row->id,
                "address" => $row->address,
				"year" => $row->year,
				"neighborhood" => $row->neighborhood,
				"materials" => $row->materials,
				"width" => $row->width,
				"height" => $row->height,
				"text" => $row->text,
				"sold" => $row->sold,
				"owner" => $row->owner,
				"house_info" => $row->house_info,
				"built" => $row->built
            ]
        ];
    }
	
	$geojson = [
    "type" => "FeatureCollection",
    "features" => $features];
	$geojson_string = json_encode($geojson, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	file_put_contents(SASHAMI_MAP_ADMIN_PATH . '/includes/map.geojson', $geojson_string);
	echo '<p>Карта успешно обновлена</p>
	<a href="?page=sashami_admin_menu"><button class="button"><span class="dashicons dashicons-undo"></span> Назад к списку домов</button></a>';
	exit;
	
}
	
/* --- INFO MESSAGE --- */
    
	global $wpdb;
	$result = $wpdb->get_results('SELECT COUNT(*) count_houses FROM sashami_houses WHERE DELETED IS NULL 
	AND point_type=0');
	
    echo '<p>В базе данных '. $result[0]->count_houses . ' дома/домов, на <a href="/map" target="_blank">карте</a> отображается '. 0 .' </p>
	<p>Для просмотра изменений в медиа обнови страницу карты/админку используя 
	<a target="_blank" href="https://bacreative.com.au/blogs/news/how-to-hard-refresh-browser-and-clear-cache-on-a-mac">hard refresh.</a></p>';


/* --- UPLOAD PNG --- */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['upload_png'])) {
	
	if	(file_exists(ABSPATH . '/wp-content/uploads/houses/icon/' . $_REQUEST['icon_id'] . '.png')){
		wp_delete_file(ABSPATH . '/wp-content/uploads/houses/icon/' . $_REQUEST['icon_id'] . '.png');
	}
	
	function custom_upload_dir_png($upload) {
	$upload['subdir'] = '/houses/icon' . $upload['subdir'];
	$upload['path']   = $upload['basedir'] . $upload['subdir'];
	$upload['url']    = $upload['baseurl'] . $upload['subdir'];
	return $upload;
	
	}
	
	function custom_upload_name_png($file){
    $file['name'] = $_REQUEST['icon_id'] . '.png';
    return $file;
    } 
	
	$uploadedfile = $_FILES['upload_png'];
	wp_handle_upload( $_FILES['upload_png']['name'] );
	$upload_overrides = array( 'test_form' => false );
    add_filter('upload_dir', 'custom_upload_dir_png');
	add_filter('wp_handle_upload_prefilter', 'custom_upload_name_png');
    wp_handle_upload( $uploadedfile, $upload_overrides );
    remove_filter('upload_dir', 'custom_upload_dir_png');
	remove_filter('wp_handle_upload_prefilter', 'custom_upload_name_png');
} 

/* --- UPLOAD FULL --- */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['upload_full'])) {
	
		
	if	(file_exists(ABSPATH . '/wp-content/uploads/houses/full/' . $_REQUEST['full_id'] . '.jpg')){
		wp_delete_file(ABSPATH . '/wp-content/uploads/houses/full/' . $_REQUEST['full_id'] . '.jpg');
	}

	function custom_upload_dir_full($upload) {
	$upload['subdir'] = '/houses/full' . $upload['subdir'];
	$upload['path']   = $upload['basedir'] . $upload['subdir'];
	$upload['url']    = $upload['baseurl'] . $upload['subdir'];
	return $upload;
	
	}
	
	function custom_upload_name_full($file){
    $file['name'] = $_REQUEST['full_id'] . '.jpg';
    return $file;
    } 

	$uploadedfile = $_FILES['upload_full'];
	wp_handle_upload( $_FILES['upload_full']['name']);
	$upload_overrides = array( 'test_form' => false );
    add_filter('upload_dir', 'custom_upload_dir_full');
	add_filter('wp_handle_upload_prefilter', 'custom_upload_name_full');
    wp_handle_upload( $uploadedfile, $upload_overrides );
    remove_filter('upload_dir', 'custom_upload_dir_full');
	remove_filter('wp_handle_upload_prefilter', 'custom_upload_name_full');

} 

/* --- TABLE --- */

	
	global $wpdb;
	$result = $wpdb->get_results('SELECT * FROM sashami_houses WHERE DELETED IS NULL 
	AND point_type=0 ORDER BY id DESC');
	
	echo '<table style="font-size:10px;margin:9px;padding:11px;line-height:1.2;
	background-color:rgba(255,255,255,0.4);text-align: left;border-collapse:separate;
	border-spacing: 5px 10px;">
  <thead>	
  <tr>
    <th>ID</th>
    <th>Last Edit</th>
    <th>Address</th>
	<th>Lon</th>
	<th>Lat</th>
	<th>Neighborhood</th>
	<th>Year</th>
	<th>Text</th>
	<th>Icon</th>
	<th>Full</th>
  </tr><thead>';
  
	foreach($result as $row) {
		$current_house = $row->id;
		echo 
		   '<tr>
			<td style="padding:10px;font-size:12px;">' . $current_house .'</td>
			<td>' . $row->edited .' by '. $row->admin .'</td>
			<td>' . $row->address . '</td>
			<td>' . $row->lon . '</td>
			<td>' . $row->lat . '</td>
			<td>' . $row->neighborhood . '</td>
			<td>' . $row->year . '</td>
			<td>' . $row->text . '</td><td> ';
			
			if	(file_exists(ABSPATH . '/wp-content/uploads/houses/icon/' . $current_house . '.png')){		
				echo '<img width="40px" src="/wp-content/uploads/houses/icon/' . $current_house . '.png">';}
				
			 echo '<form  method="post" enctype="multipart/form-data" id="pngupload'. $current_house .'">
				<input type="file" name="upload_png" id="upload_png" accept="image/png" onchange="document.getElementById(\'pngupload'. $current_house .'\').submit();">
				<input type="hidden" id="icon_id" name="icon_id" value='. $current_house .'>
				</form></td><td>';
				
			if	(file_exists(ABSPATH .'/wp-content/uploads/houses/full/' . $current_house . '.jpg')){
				echo '
			<img width="40px" src="/wp-content/uploads/houses/full/' . $current_house . '.jpg">';}
			
			echo '<form  method="post" enctype="multipart/form-data" id="fullupload'. $current_house .'">
				<input type="file" name="upload_full" id="upload_full'. $current_house .'" accept="image/jpeg" onchange="document.getElementById(\'fullupload'. $current_house .'\').submit();">
				<input type="hidden" id="full_id" name="full_id" value='. $current_house .'>
				</form></td></tr>';
				

   }	echo '</table></div>'; 
 
}


function sashami_add_house_page() {
	?>
	<div class='wrap'>
    <h2>Добавить дом</h2>
	</div>
	<form action="/submit-form" method="post">
        <label for="address">Address</label><br>
        <input type="text" id="address" name="address"><br><br>
        <label for="neighborhood">Neighborhood</label><br>
        <input type="text" id="neighborhood" name="neighborhood"><br><br>
        <input type="submit" value="Добавить" class="button button-primary">
    </form>
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