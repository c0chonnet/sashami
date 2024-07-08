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
    center: [58.376999, 26.721319],
    zoom: 15,
    zoomControl: false,
    scrollWheelZoom: true,
    maxZoom: 18,
    minZoom: 14
};

const mymap = L.map('map', mapOptions);
L.control.zoom({
    position: 'topright'
}).addTo(mymap);
mymap.setMaxBounds([[58.41250326804044, 26.6432176754798], [58.33454971978788, 26.804915110270183]]);

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
                var icon_url = '/wp-content/uploads/houses/icon/' + feature.properties.id + '.png';
                var customIcon = L.icon({
                    iconUrl: icon_url,
                    iconSize: [, 110]
                });

                var customIcon2 = L.icon({
                    iconUrl: icon_url,
                    iconSize: [, 60]
                });

                var customIcon3 = L.icon({
                    iconUrl: icon_url,
                    iconSize: [, 40]
                });

                var marker = L.marker(latlng, {
                    icon: customIcon2,
                    title: feature.properties.address
                })

                    mymap.on('zoomend', function (e) {
                    if (mymap.getZoom() > 16) {
                        marker.setIcon(customIcon);
                    } else if (mymap.getZoom() > 14) {
                        marker.setIcon(customIcon2);
                    } else {
                        marker.setIcon(customIcon3);
                    }
                });

                return marker;

            },

            onEachFeature: function (feature, layer) {
                var sidebarOwner = '';
                var sidebarSold = '';
                var sidebarBuilt = '';
                var sidebarExtraInfo = '';
                var sidebarInfoHeader = '';

                if (feature.properties.owner !== null && feature.properties.owner !== '') {
                    sidebarOwner = `<tr>
                                    <th scope="row">Owner</th>
                                    <td>${feature.properties.owner}</td>
                                </tr>`;
                }

                if (feature.properties.sold == 0) {
                    sidebarSold = `
                                    <a href="https://www.facebook.com/sashamiart" target="_blank" class="available badge rounded-pill bg-secondary">&#10149; For sale</a>`;
                }

                if (feature.properties.built !== null && feature.properties.built != 0) {
                    sidebarInfoHeader = '<h3>About this house</h3>';
                    sidebarBuilt = `Built in ${feature.properties.built}.`;
                }

                if (feature.properties.house_info !== null) {
                    sidebarInfoHeader = '<h3>About this house</h3>';
                    sidebarExtraInfo = `${feature.properties.house_info}`;
                }

                var sidebarContent = `<a href="javascript:void(0)" class="sidebar-close" onclick="closeSidebar()">&#x2715;<a>
                                <img class="sidebar-header" src="/wp-content/uploads/houses/full/${feature.properties.id}.jpg">
                                <br>    							
                                <h2>${feature.properties.address}</h2>
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
                                                <td>${feature.properties.width}x${feature.properties.height} mm</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Materials</th>
                                                <td>${feature.properties.materials}</td>
                                            </tr>
                                            ${sidebarOwner}
                                        </tbody>
                                    </table>
                                    <p>${feature.properties.text}</p>
                                    <div class="sidebar-extra-info">
                                        ${sidebarInfoHeader}
                                        <p>${sidebarBuilt} ${sidebarExtraInfo}</p>
								</div>`;

                layer.on('click', function () {
                    document.getElementById("menu-logo").style.visibility = "hidden";
                    document.getElementById("sidebar").style.visibility = "visible";
                    document.getElementById('sidebar-content').innerHTML = sidebarContent;
                });
            }
        });
    });

    for (var key in layers) {
        layers[key].addTo(mymap);
    }

    function toggleLayer(layerName) {
        var layer = layers[layerName];
        if (mymap.hasLayer(layer)) {
            mymap.removeLayer(layer);
            $('#' + layerName).removeClass('badge-primary').addClass('badge-light');
        } else {
            mymap.addLayer(layer);
            $('#' + layerName).removeClass('badge-light').addClass('badge-primary');
        }
    }

     function initializeLayerControl() {
        var controlPane = $('#layer-control');
        for (var key in layers) {
            var badge = $('<span></span>')
                .attr('id', key)
                .addClass('badge badge-custom badge-primary rounded-pill')
                .text(key)
                .click((function(name) {
                    return function() {
                        toggleLayer(name);
                    };
                })(key));
            controlPane.append(badge);
        }
    }

    $(document).ready(function() {
        initializeLayerControl();
    });

})
.catch(error => console.error('Error fetching GeoJSON data:', error));

