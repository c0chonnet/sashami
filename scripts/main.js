// ---- ALLOW COOKIES

function gaTag() {
    const script1 = document.createElement('script');
    const script2 = document.createElement('script');

    script1.src = 'https://www.googletagmanager.com/gtag/js?id=G-YEBZD0NRL8';
    script1.async = 'async';

    script2.textContent = `
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-YEBZD0NRL8');`;

    document.head.appendChild(script1);
    document.head.appendChild(script2);

};

if (!document.cookie.includes('_ga=')) {
    document.getElementById("cookies").style.visibility = "visible";
} else {
    gaTag();
};

function cookiesAccept() {
    document.getElementById("cookies").style.visibility = "hidden";
    gaTag();
}

// ---- CLOSE&OPEN SIDEBAR

const sidebarDefault = document.getElementById('sidebar-content').innerHTML

function closeSidebar() {
    document.getElementById("sidebar").style.visibility = "hidden";
    document.getElementById("menu-logo").style.visibility = "visible";
}

// ---- OPEN HOUSE INFO

function openInfo() {
    document.getElementById("menu-logo").style.visibility = "hidden";
    document.getElementById("sidebar").style.visibility = "visible";
    document.getElementById('sidebar-content').innerHTML = sidebarDefault;

}

// ---- OPEN ADDITIONAL INFO

function openChild(id, ch_id) {
    sidebarContent = '';
    sidebarContent = chHouses[id][ch_id];
    document.getElementById('sidebar-content').innerHTML = sidebarContent;
    document.getElementById(`year${ch_id}`).classList.remove('badge-light');
    document.getElementById(`year${ch_id}`).classList.add('badge-primary');
}

// ---- MAP SETTINGS

const mapOptions = {
    center: [58.376999, 26.721319],
    zoom: 16,
    zoomControl: false,
    scrollWheelZoom: true,
    maxZoom: 18,
    minZoom: 14
};

const mymap = L.map('map', mapOptions);
mymap.setMaxBounds([[58.41250326804044, 26.6432176754798], [58.33454971978788, 26.804915110270183]]);

// ---- TILE


fetch('/wp-content/plugins/sashami/config.json') //restricted on Mapbox level
.then(response => response.json())
.then(data => {
    const tileUrl = data.mapbox.tileurl;

    L.tileLayer(tileUrl, {
        attribution: `© <a href="https://www.mapbox.com/about/maps/" target="_blank"> Mapbox </a> 
		© <a href="http://www.openstreetmap.org/copyright" target="_blank"> OpenStreetMap </a> | 
		<a href="https://labs.mapbox.com/contribute/" target="_blank"> <strong>Improve this map</strong> </a>
		🎨 <a href="https://www.instagram.com/sashami_art/" target="_blank"> sashami </a> 
		💻<a href="https://www.vaenaton.com/about" target="_blank"> vaenaton </a>`
    }).addTo(mymap);
})

.catch(error => {
    console.error('Error fetching the JSON file:', error);
});

// ---- GEOJSON (HOUSES AND THEIR CARDS)
console.info(`Clicking on houses is causing an 'Invalid object' error, even though all the coordinates are present. 
This issue might be related to the non-standard icons.`); //TODO

var neighborhoods = [];
var layers = {};
var chHouses = {};

map.features.forEach(feature => {
    if (feature.properties && feature.properties.neighborhood && !neighborhoods.includes(feature.properties.neighborhood)) {
        neighborhoods.push(feature.properties.neighborhood);
    }
});

neighborhoods.forEach(neighborhoodName => {
    layers[neighborhoodName] = L.geoJson(map, {
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
                iconSize: [, 30]
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
            var altSold = '';

            if (feature.properties.children.length === 0) {
                if (feature.properties.owner !== null && feature.properties.owner !== '') {
                    sidebarOwner = `<tr>
                                    <th scope="row">Owner</th>
                                    <td>${feature.properties.owner}</td>
                                </tr>`;
                }

                if (feature.properties.sold == 0) {
                    sidebarSold = `
                                    <a href="https://www.facebook.com/sashamiart" target="_blank" class="available badge rounded-pill bg-secondary">&#10149; For sale</a>`;
                    altSold = 'The artwork is available for purchase.';
                }

                if (feature.properties.built !== null && feature.properties.built != 0) {
                    sidebarInfoHeader = '<h3>About this house</h3>';
                    sidebarBuilt = `Built in ${feature.properties.built}.`;
                }

                if (feature.properties.house_info !== null && feature.properties.house_info !== '') {
                    sidebarInfoHeader = '<h3>About this house</h3>';
                    sidebarExtraInfo = `${feature.properties.house_info}`;
                }

                var sidebarContent = `<a href="javascript:void(0)" class="sidebar-close" onclick="closeSidebar()">&#x2715;</a>
                                <div class="sidebar-header">
								<img class="sidebar-header-pic" alt='${feature.properties.address} by Sashami. ${feature.properties.year}, ${feature.properties.materials}. ${altSold}' src="/wp-content/uploads/houses/full/${feature.properties.id}.jpg">
                                <br>    							
                                <h2>${feature.properties.address}</h2>
								<span class="neighborhood badge rounded-pill">${feature.properties.neighborhood}</span>${sidebarSold}	
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
								</div></div>`;

                layer.on('click', function () {
                    document.getElementById("menu-logo").style.visibility = "hidden";
                    document.getElementById("sidebar").style.visibility = "visible";
                    document.getElementById('sidebar-content').innerHTML = sidebarContent;
                });

                // ---- FOR HOUSES WITH SEVERAL DRAWINGS

            } else {

                var chContent = {};
                var yearsRow = '';
                var sidebarContent = '';

                if (feature.properties.owner !== null && feature.properties.owner !== '') {
                    sidebarOwner = `<tr>
                                    <th scope="row">Owner</th>
                                    <td>${feature.properties.owner}</td>
                                </tr>`;
                }

                if (feature.properties.sold == 0) {
                    sidebarSold = `
                                    <a href="https://www.facebook.com/sashamiart" target="_blank" class="available badge rounded-pill bg-secondary">&#10149; For sale</a>`;
                    altSold = 'The artwork is available for purchase.';
                }

                if (feature.properties.built !== null && feature.properties.built != 0) {
                    sidebarInfoHeader = '<h3>About this house</h3>';
                    sidebarBuilt = `Built in ${feature.properties.built}.`;
                }

                if (feature.properties.house_info !== null && feature.properties.house_info !== '') {
                    sidebarInfoHeader = '<h3>About this house</h3>';
                    sidebarExtraInfo = `${feature.properties.house_info}`;
                }

                sidebarContent = `<a href="javascript:void(0)" class="sidebar-close" onclick="closeSidebar()">&#x2715;</a>

								
                                <div class="sidebar-header">
								<img class="sidebar-header-pic" alt='${feature.properties.address} by Sashami. ${feature.properties.year}, ${feature.properties.materials}. ${altSold}' src="/wp-content/uploads/houses/full/${feature.properties.id}.jpg">
                                <br>    							
                                <h2>${feature.properties.address}</h2>
								<span class="neighborhood badge rounded-pill">${feature.properties.neighborhood}</span>${sidebarSold}	
								</div>
								
								

                                <div class="sidebar-info">
                   
                                    <table class="sidebar-facts table table-sm">
                                        <tbody>
                                            <tr>
                                                <th scope="row">Year of creation</th>
                                                <td>`

                    yearsRow = `<span class="badge badge-year badge-light rounded-pill" id="year${feature.properties.id}" onclick="openChild(${feature.properties.id},${feature.properties.id})">${feature.properties.year}</span>`;
                feature.properties.children.forEach(child => {
                    yearsRow += `<span class="badge badge-year badge-light rounded-pill" id="year${child.id}" onclick="openChild(${feature.properties.id},${child.id})">${child.year}</span>`;
                });

                sidebarContent += yearsRow;
                sidebarContent += `</td>
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
								</div></div>`

                chContent[feature.properties.id] = sidebarContent;

                feature.properties.children.forEach(child => {
                    var sidebarOwner = '';
                    var sidebarSold = '';
                    var altSold = '';
                    var chSlide = '';

                    if (child.owner !== null && child.owner !== '') {
                        sidebarOwner = `<tr>
                                    <th scope="row">Owner</th>
                                    <td>${child.owner}</td>
                                </tr>`;
                    }

                    if (child.sold == 0) {
                        sidebarSold = `
                                    <a href="https://www.facebook.com/sashamiart" target="_blank" class="available badge rounded-pill bg-secondary">&#10149; For sale</a>`;
                        altSold = 'The artwork is available for purchase.';
                    }

                    chSlide = `
                                <a href="javascript:void(0)" class="sidebar-close" onclick="closeSidebar()">&#x2715;</a>
						        <div class="sidebar-header">
								<img class="sidebar-header-pic" alt='${feature.properties.address} by Sashami. ${child.year}, ${child.materials}. ${altSold}' src="/wp-content/uploads/houses/full/${child.id}.jpg">
                                <br>    							
                                <h2>${feature.properties.address}</h2>
								<span class="neighborhood badge rounded-pill">${feature.properties.neighborhood}</span>${sidebarSold}	
								</div>

                                <div class="sidebar-info">
                   
                                    <table class="sidebar-facts table table-sm">
                                        <tbody>
                                            <tr>
                                                <th scope="row">Year of creation</th>
                                                <td>${yearsRow}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Size</th>
                                                <td>${child.width}x${child.height} mm</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Materials</th>
                                                <td>${child.materials}</td>
                                            </tr>
                                            ${sidebarOwner}
                                        </tbody>
                                    </table>
								
                                    <p>${feature.properties.text}</p>
                                    <div class="sidebar-extra-info">
                                        ${sidebarInfoHeader}
                                        <p>${sidebarBuilt} ${sidebarExtraInfo}</p>
								</div></div>`;
                    chContent[child.id] = chSlide;
                });

                chHouses[feature.properties.id] = chContent;

                layer.on('click', function () {
                    document.getElementById("menu-logo").style.visibility = "hidden";
                    document.getElementById("sidebar").style.visibility = "visible";
                    openChild(feature.properties.id, feature.properties.id);

                });
            }

        }
    });
});

// ---- NEIGHBORHOODS CONTROL

lCollection = []
for (var key in layers) {
    lCollection.push(layers[key]);
    layers[key].addTo(mymap);
};

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
            .click((function (name) {
                    return function () {
                        toggleLayer(name);
                    };
                })(key));
        controlPane.append(badge);
    }
}

$(document).ready(function () {
    initializeLayerControl();
});

// ---- SEARCH

// TODO normalize unicode characters for better search

var fuse = new Fuse(map.features, {
    keys: [
        'properties.address',
        'properties.owner',
        'properties.materials',
        'properties.text'
    ]
}); 


var controlSearch = new L.Control.Search({
    propertyName: 'address',
    position: 'topright',
	tooltipLimit: 20,
	textPlaceholder:'Address, materials, owner...',
    layer: L.layerGroup(lCollection),
    initial: false,
    collapsed: false,
    textErr: 'Address not found',
    zoom: 18,
	buildTip: function (text,val) {
		var tipOwner = val.layer.feature.properties.owner == '' ? '' : val.layer.feature.properties.owner + '; ' ;
		var info = tipOwner + val.layer.feature.properties.materials;
		return '<a href="#">'+ text + ' <span class="tip-info">' + info + '</span>' +'</a>';
	},
	
	filterData: function(text, records) {
			var jsons = fuse.search(text),
				ret = {}, key;
			
			for(var i in jsons) {
				key = jsons[i].properties.address;
				ret[ key ]= records[key];
			}

			console.log(jsons,ret);
			return ret;
		},
    marker: {
        icon: false,
        circle: false
    }
});

// ---- SEARCH AND SIDEBAR CLOSE AND OPEN LOGIC

controlSearch.on('search:locationfound', function (e) {
    e.layer.fire('click');
});

controlSearch.on('search:locationfound', function () {
    this._input.value = '';
    this._input.blur();
});

mymap.addControl(controlSearch);

mymap.on('click', function () {
    controlSearch._input.value = '';
    controlSearch._hideTooltip();
    closeSidebar();

});

document.getElementById("searchtext28").addEventListener('focus', function () {
    closeSidebar();
});

// ---- ZOOM

L.control.zoom({
    position: 'topright'
}).addTo(mymap);

// ---- PRIVACY POLICY

function privacyPolicy() {
    document.getElementById("sidebar").style.visibility = "visible";
    document.getElementById("menu-logo").style.visibility = "hidden";
    fetch('/wp-content/plugins/sashami/templates/pp.html')
    .then(response => {
        if (!response.ok) {
            throw new Error('Error accesing file');
        }
        return response.text();
    })
    .then(privacyP => {
        document.getElementById('sidebar-content').innerHTML = privacyP;
    })
    .catch(error => {
        console.error('Error:', error);
    });

}
