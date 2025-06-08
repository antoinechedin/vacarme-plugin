/**
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#view-script
 */

// window.onload = (event) => {
var map = L.map('worldmap', {
    crs: L.CRS.Simple,
    zoomDelta: 0.25,
    zoomSnap: 0,
}).setView([-128, 128], 2.5);
L.tileLayer('http://89.168.46.40/map/{z}/{y}_{x}.jpg', {
    maxZoom: 7,
    minZoom: 1,
    attribution: '&copy; Vacarme'
}).addTo(map);

function hyperlinksStyle(feature) {
    let zoom = map.getZoom();
    return {
        stroke: false,
        fillOpacity: 0,
        fill: zoom >= feature.properties.minZoom && zoom < feature.properties.maxZoom,
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
);

map.on('zoomend', (e) => {
    hyperlinksLayer.resetStyle();
});

map.addLayer(hyperlinksLayer);
// }
