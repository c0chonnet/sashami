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


fetch('/wp-content/plugins/sashami/config.json')
.then(response => response.json())
.then(data => {
    const tileUrl = data.mapbox.tileurl;

    L.tileLayer(tileUrl, {
        attribution: 'Tiles by Mapbox 🎨sashami 💻vaenaton'
    }).addTo(mymap);
})
.catch(error => {
    console.error('Error fetching the JSON file:', error);
});

// ---- GEOJSON


const geojsonUrl = '/wp-content/plugins/sashami/includes/map.geojson';

var neighborhoods = [];
var layers = {};

fetch(geojsonUrl)
.then(response => response.json())
.then(data => {
    data.features.forEach(feature => {
        if (feature.properties && feature.properties.neighborhood && !neighborhoods.includes(feature.properties.neighborhood)) {
            neighborhoods.push(feature.properties.neighborhood);
        }
    });

    neighborhoods.forEach(neighborhoodName => {
        layers[neighborhoodName] = L.geoJson(data, {
            filter: function (feature) {
                return feature.properties.neighborhood === neighborhoodName;
            },
            pointToLayer: function (feature, latlng) {
                var customIcon = L.icon({
                    iconUrl: '/wp-content/uploads/houses/icon/' + feature.properties.id + '.png',
                    iconSize: [, 100],
                    popupAnchor: [0, 0],
                });
                return L.marker(latlng, {
                    icon: customIcon
                });
            },
            onEachFeature: function (feature, layer) {
                var sidebarOwner = '';
                var sidebarSold = '';
                var sidebarBuilt = '';
                var sidebarExtraInfo = '';

                if (feature.properties.owner !== null) {
                    sidebarOwner = `<tr>
                                    <th scope="row">Owner</th>
                                    <td>${feature.properties.owner}</td>
                                </tr>`;
                }

                if (feature.properties.sold == 0) {
                    sidebarSold = `
                                    <a href="https://www.facebook.com/sashamiart" target="_blank" class="available badge rounded-pill bg-secondary">&#10149; For sale</a>`;
                }

                if (feature.properties.built !== null) {
                    sidebarBuilt = `Built in ${feature.properties.built}.`;
                }

                if (feature.properties.house_info !== null) {
                    sidebarExtraInfo = `${feature.properties.house_info}`;
                }

                var sidebarContent = `<a href="javascript:void(0)" class="sidebar-close" onclick="closeSidebar()">&#x2715;<a>
                                <div class="sidebar-header"><img class="housecover" src="/wp-content/uploads/houses/full/${feature.properties.id}.jpg">
                                <br>              
                                <h2>${feature.properties.address}</h2>
                                </div>
                                <div class="sidebar-info">
                                    <span class="neighborhood badge rounded-pill">${feature.properties.neighborhood}</span>
                                    ${sidebarSold}			
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
        });
    });

    L.control.layers(null, layers).addTo(mymap);
    for (var key in layers) {
        layers[key].addTo(mymap);
    }
})
.catch(error => console.error('Error fetching GeoJSON data:', error));
