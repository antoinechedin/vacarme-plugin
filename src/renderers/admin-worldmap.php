<?php
$location_posts = get_posts(array(
    'numberposts' => -1,
    'post_type' => 'vacarme_map_location',
));
$geoJson_array = array_map(function ($post) {
    $json = json_decode($post->post_content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    $json['id'] = $post->ID;
    $json['properties']['title'] = $post->post_title;
    return json_encode($json);
}, $location_posts);
?>
<div class="wrap">
    <div class="worldmap-editor widefat">
        <div class="editor-sidebar">
            <table class="widefat wp-list-table fixed striped table-view-list posts">
                <tbody id="map-hyperlink-list">

                </tbody>
            </table>
        </div>
        <div id="worldmap" class="worldmap"></div>
        <script>
            class MapHyperlink {
                constructor(geojson) {
                    this.geojson = geojson;
                }

                getElement() {
                    if (this.element !== undefined) {
                        return this.element;
                    }

                    this.element = document.createElement('tr');
                    this.element.id = `post-${this.geojson.id}`;
                    this.element.innerHTML = `
                        <td><strong>${this.geojson.properties.title}</strong></td>
                    `;
                    return this.element;
                }
            }

            const oldHyperlinks = [
                <?php echo join(',', $geoJson_array) ?>
            ];

            function buildList() {
                let listContainer = document.getElementById('map-hyperlink-list');
                oldHyperlinks.forEach((geojson) => {
                    let listItem = new MapHyperlink(geojson)
                    listContainer.appendChild(listItem.getElement());
                });
            }

            window.onload = (event) => {
                buildList();
                const ZoomViewer = L.Control.extend({
                    onAdd() {
                        const container = L.DomUtil.create('div');
                        container.style.width = '200px';
                        container.style.background = 'rgba(255,255,255,0.7)';
                        container.style.textAlign = 'left';
                        map.on('zoomstart zoom zoomend', (ev) => {
                            container.innerHTML = `
                                Zoom level: $ {
                                    map.getZoom()
                                }
                                `;
                        });
                        return container;
                    }
                });

                const CoordinatesViewer = L.Control.extend({
                    onAdd() {
                        const container = L.DomUtil.create('div');
                        container.style.width = '200px';
                        container.style.background = 'rgba(255,255,255,0.7)';
                        container.style.textAlign = 'left';
                        map.on('mousemove', (ev) => {
                            container.innerHTML = `
                                Coordinates: [$ {
                                    ev.latlng.lng.toFixed(2)
                                }, $ {
                                    ev.latlng.lat.toFixed(2)
                                }] `;
                        });
                        return container
                    }
                });

                let map = L.map('worldmap', {
                    crs: L.CRS.Simple,
                    zoomDelta: 0.25,
                    zoomSnap: 0,
                }).setView([-128, 128], 2.5);
                L.tileLayer('http://89.168.46.40/map/{z}/{y}_{x}.jpg', {
                    maxZoom: 7,
                    // minZoom: 3,
                    attribution: '&copy; Vacarme'
                }).addTo(map);

                const zoomViewerControl = (new ZoomViewer()).addTo(map);
                const coordinatesViewerControl = (new CoordinatesViewer()).addTo(map);


                function hyperlinksStyle(feature) {
                    let zoom = map.getZoom();
                    return {
                        stroke: false,
                        // fill: zoom >= feature.properties.zoom && zoom < feature.properties.zoom + 1
                        fill: true
                    }
                }

                function hyperlinksOnEachFeature(feature, layer) {
                    if (feature.properties && feature.properties.url) {
                        layer.on('click', (ev) => {
                            const link = L.DomUtil.create('a')
                            // #TODO: urls are hardcoded in gejson, it should be automatically computed from the post id
                            window.open(feature.properties.url);
                        });
                    }
                }

                let hyperlinksLayer = L.geoJSON(oldHyperlinks, {
                    style: hyperlinksStyle,
                    onEachFeature: hyperlinksOnEachFeature
                }).addTo(map);
                map.on('zoomend', (e) => hyperlinksLayer.resetStyle());
            }

            function focus() {
                console.log('click');
            }
        </script>
    </div>
</div>
<?php
?>