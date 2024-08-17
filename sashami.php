<?php
/*
 * Plugin Name:       Sashami Map Admin
 * Description:       Manage the houses by artist Sashami on the map
 * Version:           1.1.0
 * Author:            c0chonnet
 * Author URI:        https://github.com/c0chonnet
 */
 
 if (!defined('ABSPATH')) {
  die('Denied');
}

define('SASHAMI_MAP_ADMIN_PATH', plugin_dir_path(__FILE__));
require_once(ABSPATH.'wp-admin/includes/file.php');
include(ABSPATH . 'wp-includes/pluggable.php');

function sashami_updated($id) {
	global $wpdb;
	global $current_user;
	$wpdb->update('sashami_houses', array(
        'edited' => date('Y-m-d H:i:s', current_time('timestamp')),
        'admin' => wp_get_current_user()->display_name), array('id' => $id));
}

function sashami_upload_image($format, $id, $file) {
	$image_dir = $format == 'png' ? '/wp-content/uploads/houses/icon/' : '/wp-content/uploads/houses/full/' ;
	$short_dir = $format == 'png' ? '/houses/icon' : '/houses/full' ;
	$image_ext = $format == 'png' ? '.png' : '.jpg' ;
	
	if	(file_exists(ABSPATH . $image_dir . $id . $image_ext)){
		wp_delete_file(ABSPATH . $image_dir . $id . $image_ext);
	}
	
	$upload_dir_filter = function($upload) use ($short_dir) {
        $upload['subdir'] = $short_dir . $upload['subdir'];
        $upload['path'] = $upload['basedir'] . $upload['subdir'];
        $upload['url'] = $upload['baseurl'] . $upload['subdir'];
        return $upload;
    };
    
    $upload_prefilter = function($file) use ($id, $image_ext) {
        $file['name'] = $id . $image_ext;
        return $file;
    };

    add_filter('upload_dir', $upload_dir_filter);
    add_filter('wp_handle_upload_prefilter', $upload_prefilter);
	

	$uploadedfile = $file;
	$upload_overrides = array( 'test_form' => false );
	wp_handle_upload($uploadedfile, $upload_overrides);
	
    remove_filter('upload_dir', $upload_dir_filter);
    remove_filter('wp_handle_upload_prefilter', $upload_prefilter);
	
	sashami_updated($id);

}

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
	<a href="?page=sashami_add_house"><button class="button" style="padding:5px;"><span class="dashicons dashicons-admin-home" style="font-size:24px;"></span> Новый дом</button></a>
	
	<div class="button" style="padding:5px;"><span class="dashicons dashicons-upload" style="font-size:24px;"></span>  Массовая загрузка 
	<form id="mass_upload_form" name="mass_upload_form" class="alignright" method="post" enctype="multipart/form-data">
	<input type="file" id="mass_upload" name="mass_upload" accept=".csv" onchange="document.getElementById('mass_upload_form').submit();">
	</form>
	
	</div>

	<a href="?page=sashami_admin_menu&action=update_map"><button class="button button-primary" style="padding:5px;"><span class="dashicons dashicons-update" style="font-size:24px;"></span> Обновить карту</button></a>
	<?php
	
	/* --- GEOJSON MAKING --- */

if (isset($_GET['action']) && $_GET['action'] == 'update_map') {
	global $wpdb;
	$result = $wpdb->get_results('SELECT * FROM sashami_houses AS s 
	LEFT JOIN (SELECT id ch_id, year ch_year, width ch_width, height ch_height, materials ch_materials, sold ch_sold, 
	owner ch_owner, parent_id ch_parent_id FROM sashami_houses WHERE parent_id IS NOT NULL AND parent_id != 0 AND deleted IS null) t 
	ON s.id = t.ch_parent_id WHERE deleted IS null AND (parent_id IS NULL OR parent_id=0) ORDER BY id');
	
$features = [];
$featureIndex = [];

foreach ($result as $row) {
    if (isset($featureIndex[$row->id])) {
        if (!is_null($row->ch_id)) {
            $features[$featureIndex[$row->id]]['properties']['children'][] = [
                "id" => $row->ch_id,
                "year" => $row->ch_year,
                "width" => $row->ch_width,
                "height" => $row->ch_height,
                "materials" => $row->ch_materials,
                "sold" => $row->ch_sold,
                "owner" => $row->ch_owner
            ];
        }
    } else {
        $house = [
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
                "built" => $row->built,
                "children" => [] 
            ]
        ];

        if (!is_null($row->ch_id)) {
            $house['properties']['children'][] = [
                "id" => $row->ch_id,
                "year" => $row->ch_year,
                "width" => $row->ch_width,
                "height" => $row->ch_height,
                "materials" => $row->ch_materials,
                "sold" => $row->ch_sold,
                "owner" => $row->ch_owner
            ];
        }

        $features[] = $house;
        $featureIndex[$row->id] = count($features) - 1;
    }
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

/* --- ACTION MASS UPLOAD --- */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['mass_upload'])) {
	$houses_array = array_map('str_getcsv', file($_FILES['mass_upload']['tmp_name']));
	$num_houses = count($houses_array)-1;
	foreach (array_slice($houses_array,1) as $house) {
		global $wpdb;
		global $current_user;
		$wpdb->insert('sashami_houses', array(
        'point_type' => $house[0],
        'address' => $house[1],
        'lon' => $house[2], 
		'lat' => $house[3],
		'neighborhood' => $house[4],
		'year' => $house[5],
		'materials' => $house[6],
		'width' => $house[7],
		'height' => $house[8],
		'sold' => $house[9],
		'owner' => $house[10],
		'text' => $house[11],
		'edited' => date('Y-m-d H:i:s', current_time('timestamp')),
		'admin' => wp_get_current_user()->display_name
		));
	}
	echo '<p>Информация о '. $num_houses .' доме/домах загружена в базу</p>
	<a href="?page=sashami_admin_menu"><button class="button"><span class="dashicons dashicons-undo"></span> Назад к списку домов</button></a>';
	exit;
}

	
/* --- INFO MESSAGE --- */
    
	global $wpdb;
	$result = $wpdb->get_results('SELECT COUNT(*) count_houses FROM sashami_houses WHERE DELETED IS NULL 
	AND point_type=0 AND (parent_id =0 OR parent_id IS NULL)');
	
	$ids_on_map = [];
	$json = file_get_contents(SASHAMI_MAP_ADMIN_PATH . '/includes/map.geojson');
	$json_data = json_decode($json,true); 
	foreach($json_data['features'] as $feature) {
		array_push($ids_on_map,$feature['properties']['id']);
	}
	
    echo '<p>В базе данных '. $result[0]->count_houses . ' дома/домов, на <a href="/map" target="_blank">карте</a> отображается '. count($ids_on_map) .'. Недавно добавленные
	дома, дома без локации и дома без медиа файлов не отображены. Если у дома несколько версий разных лет, отображен только "родитель".</p>';

/* --- TABLE --- */

	global $wpdb;
	$result = $wpdb->get_results('SELECT * FROM sashami_houses WHERE DELETED IS NULL 
	AND point_type=0 ORDER BY id DESC');
	
	echo '<table style="font-size:12px;margin:9px;padding:11px;line-height:1.2;
	background-color:rgba(255,255,255,0.4);text-align: left;border-collapse:separate;
	border-spacing: 15px 15px;">
  <thead>	
  <tr>
    <th>ID</th>
	<th>Parent ID</th>
    <th>Last Edited</th>
    <th>Address</th>
	<th>Lon</th>
	<th>Lat</th>
	<th>Year</th>
	<th>Materials</th>
	<th>Text</th>
	<th>Icon</th>
	<th>Full</th>
  </tr><thead>';
  
	foreach($result as $row) {
		$current_house = $row->id;
		$not_listed = '';
		
		if (!in_array($current_house, $ids_on_map)) {
			$not_listed = '<span class="dashicons dashicons-hidden" style="color:rgba(255, 128, 128,0.9);"></span>';
		}
		
		echo 
		   '<tr>
			<td ><a style="text-decoration:none;" href="?page=sashami_add_house&id='. $current_house .'">   '. $not_listed .'
			<span class="dashicons dashicons-edit"></span></a>' . $current_house .'</td>
			<td>' . $row->parent_id . '</td>
			<td>' . $row->edited .' by '. $row->admin .'</td>
			<td>' . $row->address . '</td>
			<td>' . $row->lon . '</td>
			<td>' . $row->lat . '</td>
			<td>' . $row->year . '</td>
			<td>' . $row->materials . '</td>
			<td>' . $row->text . '</td><td>';
			
			if	(file_exists(ABSPATH . '/wp-content/uploads/houses/icon/' . $current_house . '.png')){		
				echo '<img width="60px" src="/wp-content/uploads/houses/icon/' . $current_house . '.png">';}
	        else {echo '<img width="60px" src="/wp-content/uploads/noimage.png">';}
				
			 echo '</td><td>';
				
			if	(file_exists(ABSPATH .'/wp-content/uploads/houses/full/' . $current_house . '.jpg')){
				echo '
			<img width="60px" src="/wp-content/uploads/houses/full/' . $current_house . '.jpg">';}
			 else {echo '<img width="60px" src="/wp-content/uploads/noimage.png">';}
			
			echo '</td></tr>';
				

   }	echo '</table></div>'; 
 
}

/* --- ADD OR EDIT HOUSE PAGE --- */

function sashami_add_house_page() {
    
	$lon_value = '';
	$lat_value = '';
	$id_value = '';
	$is_get_id = isset($_GET['id']);
	$default_lon = (float)26.7200;
	$default_lat = (float)58.3800;
	
	if ($is_get_id) {
		$id_value = $_GET['id'];
		global $wpdb;
		$result = $wpdb->get_results('SELECT * FROM sashami_houses WHERE id = '. $id_value .'');
		$lon_value = $result[0]->lon;
	    $lat_value= $result[0]->lat;
				
		if ($lon_value != 0 &&  $lat_value != 0) {
			$default_lon = $lon_value;
			$default_lat = $lat_value;			
		}
		
	}
	
	$existing_house = $is_get_id ? $result[0]->address : '';
	$send_form_value = $is_get_id ? 'Обновить' : 'Добавить';
	$id_input = $is_get_id ? '<input type="hidden" id="id" name="id" value='. $id_value .'>' : '';
	$action_header = $is_get_id ? 'Редактировать дом' : 'Добавить дом';
	$neighborhood_value = $is_get_id ? $result[0]->neighborhood : '';
	$year_value = $is_get_id ? $result[0]->year : null;
	$materials_value = $is_get_id ? $result[0]->materials : '';
	$width_value = $is_get_id ? $result[0]->width : null;
	$height_value = $is_get_id ? $result[0]->height : null;
	$sold_value = $is_get_id ? $result[0]->sold : '';
	$selected_0 = $sold_value == 0 ? 'selected' : '';
	$selected_1 = $sold_value == 1 ? 'selected' : '';
	$selected_2 = $sold_value == 2 ? 'selected' : '';
	$owner_value = $is_get_id ? $result[0]->owner : '';
	$text_value = $is_get_id ? $result[0]->text : '';
	$houseinfo_value = $is_get_id ? $result[0]->house_info : '';
	$built_value = $is_get_id ? $result[0]->built : null;
	$parent_value = $is_get_id ? $result[0]->parent_id : null;
	
	echo'<div class="wrap">
    <h2>'. $action_header .'</h2>
    <h3>'. $existing_house .'</h3>
	    
		<table><tr>
		<td>
        <form method="post" id="new_house" class="form" enctype="multipart/form-data">		
		<label for="upload_png">Icon</label><br>'; 
		 if	(file_exists(ABSPATH .'/wp-content/uploads/houses/icon/' . $id_value . '.png')){
		echo '
		   <img width="90px" src="/wp-content/uploads/houses/icon/' . $id_value . '.png">';}
			 else {echo '<img width="100px" src="/wp-content/uploads/noimage.png">';}
			 
		echo '<br><br><input type="file" name="upload_png" id="upload_png" accept="image/png"><br><br>
		</td>
		
		<td>	 
		<label for="upload_full">Full</label><br>';
		
		if	(file_exists(ABSPATH .'/wp-content/uploads/houses/full/' . $id_value . '.jpg')){
		echo '
		   <img width="90px" src="/wp-content/uploads/houses/full/' . $id_value . '.jpg">';}
			 else {echo '<img width="100px" src="/wp-content/uploads/noimage.png">';}
			 
		echo '<br><br><input type="file" name="upload_full" id="upload_full" accept="image/jpeg"><br><br>
		</td>
		
		<td></td>
		
		<td></td>
		</tr>
	
	
	     <tr>
        <td><label for="address">Address</label><br>
		<input type="text" id="address" name="address" value="'. $existing_house .'"><br>'. $id_input .'</td>
		
        <td> <label for="neighborhood">Neighborhood</label><br>
        <input type="text" id="neighborhood" name="neighborhood" value="'. $neighborhood_value .'"><br></td>
		
		<td><label for="year">Year</label><br>
        <input type="number" id="year" name="year" value="'. $year_value  .'"><br></td>
		
		<td><label for="materials">Materials</label><br>
        <input type="text" id="materials" name="materials" value="'. $materials_value .'"><br></td>
		
		</tr>
		
		<tr>
		<td><label for="width">Width</label><br>
        <input type="number" id="width" name="width" value='. $width_value .'><br></td>
		
		<td><label for="height">Height</label><br>
        <input type="number" id="height" name="height" value='. $height_value .'><br></td>
		
		
		<td><label for="sold">Sold</label><br>
        <select id="sold" name="sold">
		<option value="0" '. $selected_0 .'>Available</option>
        <option value="1" '. $selected_1 .'>Sold</option>
        <option value="2" '. $selected_2 .'>Not for sale</option>
		<select><br></td>
		
		<td><label for="owner">Owner</label><br>
        <input type="text" id="owner" name="owner" value="'. $owner_value .'"><br></td></tr>
		
		<tr>
		
		<td>
		<label for="lon">Longitude</label><br>
        <input  type="number" step="0.000000000000001" id="lon" name="lon" value="'. $lon_value .'"><br>
		</td>
		
		<td>
		<label for="lat">Latitude</label><br>
        <input type="number" step="0.000000000000001" id="lat" name="lat" value="'. $lat_value .'"><br>
		</td>
		
		<td>
		<label for="parent_id">Parent</label><br>
        <input type="number" id="parent_id" name="parent_id" value='. $parent_value .'><br> </td>
		</tr>
		
		<tr><td colspan="4">';
		
		$drag = __('Перемести', 'leaflet-map');

        echo do_shortcode('[leaflet-map zoom=16 zoomcontrol doubleClickZoom height=300 width=100% lat='. $default_lat . ' lng='. $default_lon .' scrollwheel]');
        echo do_shortcode(sprintf('[leaflet-marker draggable visible] %s [/leaflet-marker]',
            $drag   ));
        ?>
		
		<script>
		(function() {
            var originalLog = console.log;
            console.log = function(message) {
                originalLog.apply(console, arguments);

                if (typeof message === 'string' && message.includes('leaflet-marker')) {
                    var latMatch = message.match(/lat=([0-9.-]+)/);
                    var lngMatch = message.match(/lng=([0-9.-]+)/);

                    if (latMatch && lngMatch) {
                        var lat = latMatch[1];
                        var lng = lngMatch[1];

                        document.getElementById('lon').value = lng;
						 document.getElementById('lat').value = lat;
                    }
                }
            };
        })();
		</script>
		
         <?
		
        echo '</td></tr>
		
		<br><br> 

		<tr><td colspan="2"> <label for="text">Text</label><br>
        <textarea id="text" name="text" cols="50 " rows="7" maxlength="500">'. $text_value .'</textarea><br><br></td>
		
		
		<td colspan="2">
		<label for="built">Built</label><br>
        <input type="number" id="built" name="built" value='. $built_value .'><br>
		
		<label for="house_info">House info</label><br>
        <textarea id="house_info" name="house_info" cols="50" rows="4" maxlength="500">'. $houseinfo_value .'</textarea><br><br>
		</td>
		
		</tr>
		</table> <br>
		
        <input type="submit" value="'. $send_form_value .'" class="button button-primary">
    </form></div>';
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	
	$values_array = array(
	    'point_type' => 0,
	    'address' => $_REQUEST['address'],
		'neighborhood' => $_REQUEST['neighborhood'],
		'year' => $_REQUEST['year'],
		'materials' => $_REQUEST['materials'],
		'width' => $_REQUEST['width'],
		'height' => $_REQUEST['height'],
		'sold' => $_REQUEST['sold'],
		'owner' => $_REQUEST['owner'],
		'lon' => $_REQUEST['lon'],
		'lat' => $_REQUEST['lat'],
		'text' => $_REQUEST['text'],
		'house_info' => $_REQUEST['house_info'],
		'built' => $_REQUEST['built'],
        'parent_id' => $_REQUEST['parent_id']			
		);
		
	if (isset($_REQUEST['id'])) {
		$id = $_REQUEST['id'];
		global $wpdb;
		$wpdb->update ('sashami_houses', 
		$values_array,
		array('id' => $id));
		sashami_updated($id);
	}
	
	else {
		global $wpdb;
		$wpdb->insert('sashami_houses', 
		$values_array);
		$id = $wpdb->insert_id;
		sashami_updated($id);
	}
    
	if (isset($_FILES['upload_png']) && $_FILES['upload_png']['size'] > 0) {
		sashami_upload_image('png', $id, $_FILES['upload_png']);
	}
	
	if (isset($_FILES['upload_full']) && $_FILES['upload_full']['size'] > 0) {
		sashami_upload_image('jpg', $id, $_FILES['upload_full']);
	}
	
}
	
	
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