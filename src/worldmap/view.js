/**
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#view-script
 */

// Generated using list_tiles.sh from vacarme-tools
const tagTileSet = new Set(["4/6_12", "4/6_2", "4/10_6", "4/5_10", "4/5_2", "4/5_13", "4/10_10",
    "4/6_3", "4/9_12", "4/8_5", "4/7_2", "4/9_10", "4/10_7", "4/11_6", "4/4_12", "4/4_2", "4/9_11",
    "4/9_5", "4/6_14", "4/5_12", "4/11_9", "4/10_11", "4/11_8", "4/5_3", "4/5_11", "4/11_10", "4/6_1",
    "4/10_12", "4/6_13", "4/7_7", "4/4_10", "4/6_8", "4/10_9", "4/4_3", "4/6_10", "4/6_9", "4/11_7",
    "4/6_11", "4/9_13", "4/7_14", "4/4_11", "5/8_23", "5/21_22", "5/18_11", "5/19_22", "5/10_5",
    "5/16_10", "5/15_5", "5/20_24", "5/18_10", "5/9_21", "5/13_26", "5/21_20", "5/14_28", "5/12_18",
    "5/12_27", "5/9_20", "5/10_4", "5/13_25", "5/13_28", "5/12_26", "5/10_23", "5/11_23", "5/16_11",
    "5/20_22", "5/13_24", "5/19_21", "5/8_6", "5/13_7", "5/12_3", "5/11_22", "5/14_5", "5/13_4",
    "5/12_21", "5/21_21", "5/13_17", "5/12_28", "5/11_24", "5/11_25", "5/11_21", "5/20_13", "5/22_14",
    "5/13_18", "5/8_24", "5/12_17", "5/19_23", "5/9_6", "5/20_14", "5/20_23", "3/1_1", "3/1_0", "3/4_6",
    "3/5_4", "3/3_4", "3/3_3", "3/5_2", "3/2_0", "3/2_1", "3/4_2", "3/2_5", "3/4_4", "3/6_5", "3/4_5",
    "3/6_6", "3/3_5", "3/2_7", "3/5_6", "3/5_3", "3/5_1", "3/4_3", "3/3_1", "3/2_6", "3/5_5", "3/3_6",
    "2/1_0", "2/3_3", "2/3_3", "2/2_2", "2/2_0", "2/1_3", "2/2_1", "2/2_3", "2/0_0", "2/3_2"]);

// window.onload = (event) => {

let map = L.map('worldmap', {
    crs: L.CRS.Simple,
    zoomDelta: 0.25,
    zoomSnap: 0,
    minZoom: 0,
    maxZoom: 7,
    maxBounds: [[0, 0], [-256, 256]],
}).setView([-128, 128], 2.5);

let labelsPane = map.createPane('labels');
labelsPane.style.zIndex = 450;
labelsPane.style.pointerEvents = 'none';

let mapLayer = L.tileLayer('http://89.168.46.40/map/0_map_layer/{z}/{y}_{x}.jpg', {
    minZoom: 0,
    maxZoom: 7,
    bounds: [[0, 0], [-256, 256]],
    attribution: 'Vacarme JDR'
}).addTo(map);

let tagLayer = L.tileLayer('', {
    pane: 'labels',
    minZoom: 0,
    maxZoom: 7,
    maxBounds: [[0, 0], [-256, 256]],
}).addTo(map);
tagLayer.createTile = function (coords, done) {
    const { x, y, z } = coords;

    const key = `${z}/${y}_${x}`;
    const url = `http://89.168.46.40/map/1_tag_layer/${key}.png`;

    const img = document.createElement('img');
    img.src = tagTileSet.has(key) ? url : "";
    img.alt = '';
    img.onload = () => done(null, img);
    img.onerror = (e) => done(e, img);

    return img;
};

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
