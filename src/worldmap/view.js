/**
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#view-script
 */

// window.onload = (event) => {

var map = L.map('worldmap', {
    crs: L.CRS.Simple,
    zoomDelta: 0.25,
    zoomSnap: 0,
}).setView([-128, 128], 2.5);

map.createPane('labels');
map.getPane('labels').style.zIndex = 450;
map.getPane('labels').style.pointerEvents = 'none';

let mapLayer = L.tileLayer('http://89.168.46.40/map/0_map_layer/{z}/{y}_{x}.jpg', {
    maxZoom: 7,
    minZoom: 0,
    attribution: '&copy; Vacarme'
}).addTo(map);

let tagLayer = L.tileLayer('http://89.168.46.40/map/1_tag_layer/{z}/{y}_{x}.png', {
    errorTileUrl: 'http://89.168.46.40/map/empty.png',
    pane: 'labels',
}).addTo(map);

function hyperlinksStyle(feature) {
    let zoom = map.getZoom();
    return {
        stroke: false,
        fillOpacity: 0,
        fill: zoom >= feature.properties.minZoom && zoom < feature.properties.maxZoom,
        fillColor: '#000000',
        className: 'map-hyperlink'
    }
}

function onEachHyperlinks(feature, layer) {
    console.assert(feature.properties && feature.properties.url, "Feature is missing the url property");

    layer.on('click', (e) => {
        window.open(feature.properties.url);
    });
}

let hyperlinksLayer = L.geoJSON(
    geojsonHyperlinks,
    {
        style: hyperlinksStyle,
        onEachFeature: onEachHyperlinks
    }
).addTo(map);

map.on('zoomend', (e) => {
    hyperlinksLayer.resetStyle();
});
// }
