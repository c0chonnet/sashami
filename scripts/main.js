const sidebarDefault = document.getElementById('sidebar-content').innerHTML

function closeSidebar() {
  document.getElementById("sidebar").style.visibility = "hidden";
  document.getElementById("menu-logo").style.visibility = "visible";
} 

function openInfo() {
  document.getElementById("menu-logo").style.visibility = "hidden";
  document.getElementById("sidebar").style.visibility = "visible";
  document.getElementById('sidebar-content').innerHTML = sidebarDefault;
    
} 

// ---- MAP SETTINGS

const mapOptions = {
    center: [58.372618, 26.732262],
    zoom: 14,
    zoomControl: false,
    scrollWheelZoom: true,
    maxZoom: 17,
    minZoom: 13,
};

const mymap = L.map('map', mapOptions);
L.control.zoom({
    position: 'topright' 
}).addTo(mymap);

// ---- TILE

const tileUrl = '';
L.tileLayer(tileUrl, {
    attribution: 'Tiles by Mapbox 🎨sashami 💻vaenaton',
}).addTo(mymap);

// ---- GEOJSON

// --EDIT
const geojsonUrl = 'test1.geojson';
//-- EDIT


fetch(geojsonUrl).then(response => response.json()).then(data => {
	
	
    L.geoJSON(data, {
        pointToLayer: function (feature, latlng) {
            var customIcon = L.icon({
				// --EDIT
                iconUrl: 'houses/icon/' + feature.properties.id + '.png',
				// --EDIT
                iconSize: [,100],
                popupAnchor: [0, 0],
            });
            return L.marker(latlng, {
                icon: customIcon
            });
        },

        onEachFeature: function (feature, layer) {
// -- EDIT!!!
			var sidebarOwner = ''
			var sidebarSold = ''
			var sidebarBuilt = ''
			var sidebarExtraInfo = ''
			
			if (feature.properties.owner !== '') {
				sidebarOwner = `<tr>
			   <th scope="row">Owner</th>
		       <td>${feature.properties.owner}</td>
			</tr>`
			}
			
			if (feature.properties.sold == 0) {
				sidebarSold = `
		       <a href="https://www.facebook.com/sashamiart" target="_blank" class="available badge rounded-pill bg-secondary">&#10149; For sale</a>`
			}
						
			if (feature.properties.built !== '') {
				sidebarBuilt = `Built in ${feature.properties.built}.`
			}
			
			if (feature.properties.house_info !== '') {
				sidebarExtraInfo  = `${feature.properties.house_info}`
			}
			
			
			
            var sidebarContent = `<a href="javascript:void(0)" class="sidebar-close" onclick="closeSidebar()">&#x2715;<a>
			<div class="sidebar-header"><img class="housecover" src="houses/full/${feature.properties.id}.png">
			<br>              
			<h2>${feature.properties.address}	
			</h2>
			<span class="neighborhood badge rounded-pill">${feature.properties.neighborhood}</span>
			${sidebarSold}
			</div>
			
			<div class="sidebar-info">
						
			<table class="sidebar-facts table table-sm">
				<tbody>
					<tr>
					   <th scope="row">Year of creation</th>
					   <td>${feature.properties.year}</td>
					</tr>
			
					<tr>
					  <th scope="row">Size</th>
					   <td>${feature.properties.width}x${feature.properties.height} cm</td>
					</tr>
			
			<tr>
			   <th scope="row">Materials</th>
			   <td>${feature.properties.materials}</td>
			</tr>
			
			${sidebarOwner}
				</tbody>
			   </table>
			   
			   <h3>Sasha's comment</h3>
			   <p>${feature.properties.text}</p>
			   
			   <div class="sidebar-extra-info">
			   <h3>About this house</h3>
			   <p>${sidebarBuilt} ${sidebarExtraInfo}</p>
			   </div>
			   
			   </div>`;
				
             layer.on('click', function () {
				document.getElementById("menu-logo").style.visibility = "hidden";
				document.getElementById("sidebar").style.visibility = "visible";
                document.getElementById('sidebar-content').innerHTML = sidebarContent;
            });
        }
    }).addTo(mymap);
	
var options = {
  geojsonServiceAddress: "https://sashami.opsti.ee/wp-content/uploads/test1.geojson",
  placeholderMessage: "Search by address or house description",
  notFoundMessage: "No results",
  notFoundHint:""
  
};
$("#searchContainer").GeoJsonAutocomplete(options);

})

