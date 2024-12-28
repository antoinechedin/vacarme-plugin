<?php
$map_hyperlinks = get_posts(array(
    'numberposts' => -1,
    'post_type' => 'map-hyperlink',
));
$json_map_hyperlinks = array_map(function ($post) {
    $geojson = json_decode($post->post_content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    return json_encode(array(
        'id' => $post->ID,
        'title' => $post->post_title,
        'geojson' => $geojson
    ));
}, $map_hyperlinks);
?>

<div class="wrap">
    <div class="worldmap-editor widefat">
        <div class="editor-sidebar">
            <h1 class="wp-heading-inline"><?php _e('Hyperlinks', 'vacarme-plugin') ?></h1>

            <a class="page-title-action" onclick="create()"><?php _e('Add New Hyperlink', 'vacarme-plugin') ?></a>
            <hr class="wp-header-end">
            <div class="tablenav top">
                <!-- <button type="button" class="button"><?php _e('Add New Hyperlink', 'vacarme-plugin') ?></button> -->
            </div>
            <table class="widefat wp-list-table fixed striped table-view-list posts">
                <tbody id="map-hyperlink-list" class="the-list">

                </tbody>
            </table>
        </div>
        <div id="worldmap" class="worldmap"></div>
        <script>
            const defaultStyle = {
                color: '#808080'
            };
            const selectedStyle = {
                color: '#ff0000'
            };

            let mapHyperlinks = [];

            function edit(id) {
                mapHyperlinks.forEach((mapHyperlink) => {
                    if (mapHyperlink.id == id) {
                        mapHyperlink.selected = !mapHyperlink.selected;
                        mapHyperlink.onSelected();
                        mapHyperlink.focus();
                    } else {
                        mapHyperlink.selected = false;
                        mapHyperlink.onSelected();
                    }
                });
            }

            function save(id) {
                let spinner = document.getElementById(`edit-${id}-spinner`);
                spinner.classList.add('is-active');

                let mapHyperlink = mapHyperlinks.find(e => e.id == id);
                console.assert(mapHyperlink !== undefined, `Couldn't find mapHyperlink with id ${id}`);
                let newGeojson = Object.assign({}, mapHyperlink.geojson);
                newGeojson.properties.linkedPostId = document.getElementById(`edit-${id}-linked_post_id`).value;
                newGeojson.properties.minZoom = document.getElementById(`edit-${id}-post_min_zoom`).value;
                newGeojson.properties.maxZoom = document.getElementById(`edit-${id}-post_max_zoom`).value;
                newGeojson.geometry.coordinates = mapHyperlink.getGeometryCoordinates();

                let model = {
                    title: document.getElementById(`edit-${id}-post_title`).value,
                    content: JSON.stringify(newGeojson),
                    status: 'publish'
                };
                if (!mapHyperlink.isNew()) {
                    model.id = mapHyperlink.id;
                }
                new wp.api.models.MapHyperlink(model).save().done((response) => {
                    spinner.classList.remove('is-active');
                    console.log(response);
                    mapHyperlink.id = response.id;
                    mapHyperlink.title = response.title.raw;
                    mapHyperlink.setGeojson(JSON.parse(response.content.raw));
                })
            }

            function cancel(id) {
                mapHyperlinks.forEach((mapHyperlink) => {
                    if (mapHyperlink.id != id) {
                        return;
                    }

                    mapHyperlink.selected = false;
                    mapHyperlink.onSelected();
                });
            }

            let createCount = 0;

            function create() {
                let bounds = map.getBounds().pad(-0.3);
                let zoom = Math.round(map.getZoom());
                let jsonMapHyperlink = {
                    id: `new-${createCount}`,
                    title: '<?php _e('New hyperlink', 'vacarme-plugin') ?>',
                    geojson: {
                        type: 'Feature',

                        properties: {

                            minZoom: zoom,
                            maxZoom: zoom,
                            linkedPostId: null,
                        },
                        geometry: {
                            type: 'Polygon',
                            coordinates: [
                                [
                                    [bounds.getWest().toFixed(2), bounds.getNorth().toFixed(2)],
                                    [bounds.getEast().toFixed(2), bounds.getNorth().toFixed(2)],
                                    [bounds.getEast().toFixed(2), bounds.getSouth().toFixed(2)],
                                    [bounds.getWest().toFixed(2), bounds.getSouth().toFixed(2)],
                                    [bounds.getWest().toFixed(2), bounds.getNorth().toFixed(2)]
                                ]
                            ]
                        }
                    }
                };

                let listItem = new MapHyperlink(jsonMapHyperlink);
                mapHyperlinks.unshift(listItem);
                let listContainer = document.getElementById('map-hyperlink-list');
                listContainer.insertBefore(listItem.tableRow, listContainer.firstChild);
                listItem.mapLayer.addTo(map);
                edit(jsonMapHyperlink.id);
                createCount++;
            }

            class MapHyperlink {
                constructor(jsonMapHyperlink) {
                    this.selected = false;
                    this.id = jsonMapHyperlink.id;
                    this.title = jsonMapHyperlink.title;
                    this.hiddenTableRow = document.createElement('tr');
                    this.hiddenTableRow.classList.add('hidden');
                    this.setGeojson(jsonMapHyperlink.geojson);
                }

                setGeojson(geojson) {
                    this.geojson = geojson;
                    // Row
                    if (this.tableRow === undefined) {
                        this.tableRow = document.createElement('tr');
                    }
                    this.tableRow.id = `post-${this.id}`;
                    this.tableRow.onclick = () => edit(this.id);
                    let name = this.title;
                    if (this.isNew()) {
                        name += ' — <span class="post-state"><?php _e('Draft') ?>';
                    }
                    this.tableRow.innerHTML = `
                        <td><strong>${name}</span></strong></td>
                    `;
                    // Edit row
                    if (this.editTableRow === undefined) {
                        this.editTableRow = document.createElement('tr');
                    }
                    this.editTableRow.id = `edit-${this.id}`;
                    this.editTableRow.classList.add('inline-edit-row' /*, 'inline-edit-row-page', 'quick-edit-row', 'quick-edit-row-page', 'inline-editor'*/ );
                    this.editTableRow.innerHTML = `
                        <td>
                            <div class="inline-edit-row inline-edit-wrapper" role="region">
                                <fieldset>
                                    <legend class="inline-edit-legend"><?php _e('Edit') ?></legend>
                                    <div class="inline-edit-col">
                                        <label>
							                <span class="title"><?php _e('Title') ?></span>
                                            <span class="input-text-wrap"><input id="edit-${this.id}-post_title" type="text" name="post_title" class="ptitle" value=""></span>
                                        </label>
                                        <label>
                                            <span class="title"><?php _e('Page') ?></span>
                                            <select id="edit-${this.id}-linked_post_id" name="linked_post_id">
                                            <?php
                                            foreach (get_pages(array('hierarchical' => true)) as $post) {
                                                $depth = count(get_post_ancestors($post));
                                                echo '<option value="' . $post->ID . '">' . str_repeat('&nbsp;&nbsp;&nbsp;', $depth) . $post->post_title . '</option>';
                                            }
                                            ?>
                                            </select>
                                        </label>
                                        <label>
							                <span class="title"><?php _e('Min zoom', 'vacarme-plugin') ?></span>
                                            <span class="input-text-wrap"><input type="text" id="edit-${this.id}-post_min_zoom" name="post_min_zoom" value=""></span>
                                        </label>
                                        <label>
							                <span class="title"><?php _e('Max Zoom', 'vacarme-plugin') ?></span>
                                            <span class="input-text-wrap"><input type="text" id="edit-${this.id}-post_max_zoom" name="post_max_zoom" value=""></span>
                                        </label>
                                    </div>
                                </fieldset>
                                <div class="submit inline-edit-save">
									<input type="hidden" id="_inline_edit" name="_inline_edit" value="1f663d9e2d">
                                    <button type="button" class="button button-primary save" onclick="save('${this.id}')">${this.isNew() ? '<?php _e('Publish') ?>' : '<?php _e('Update') ?>'}</button>
                                    <button type="button" class="button cancel" onclick="cancel('${this.id}')"><?php _e('Cancel') ?></button>
                                    <span id="edit-${this.id}-spinner" class="spinner"></span>
                                    <input type="hidden" name="post_view" value="list">
                                    <input type="hidden" name="screen" value="edit-page">
                                    <div class="notice notice-error notice-alt inline hidden"><p class="error"></p></div>			</div>
                            </div>
                        </td>
                    `;
                    // Bounds
                    let latLngs = L.GeoJSON.coordsToLatLngs(this.geojson.geometry.coordinates[0]);
                    this.northEast = latLngs[0];
                    this.southWest = latLngs[2];
                    this.center = L.latLngBounds(this.northEast, this.southWest).getCenter();
                    // Rect layer
                    if (this.mapLayer === undefined) {
                        this.mapLayer = L.rectangle(L.latLngBounds(this.northEast, this.southWest), defaultStyle);
                    } else {
                        this.mapLayer.setBounds(L.latLngBounds(this.northEast, this.southWest));
                        this.mapLayer.setStyle(defaultStyle);
                    }
                    // Marker layers
                    if (this.markers === undefined) {
                        this.markers = [];
                        this.markers.push(
                            L.marker(latLngs[0], {
                                draggable: true
                            }).on('drag', (e) => {
                                this.northEast = e.latlng;
                                this.updateCoordinates();
                            })
                        );
                        this.markers.push(
                            L.marker(latLngs[1], {
                                draggable: true
                            }).on('drag', (e) => {
                                this.northEast.lat = e.latlng.lat;
                                this.southWest.lng = e.latlng.lng;
                                this.updateCoordinates();
                            })
                        );
                        this.markers.push(
                            L.marker(latLngs[2], {
                                draggable: true
                            }).on('drag', (e) => {
                                this.southWest = e.latlng;
                                this.updateCoordinates();
                            })
                        );
                        this.markers.push(
                            L.marker(latLngs[3], {
                                draggable: true
                            }).on('drag', (e) => {
                                this.northEast.lng = e.latlng.lng;
                                this.southWest.lat = e.latlng.lat;
                                this.updateCoordinates();
                            })
                        );
                        this.resizeMarkerLayer = L.featureGroup(this.markers);
                    }
                }

                onSelected() {
                    if (this.selected) {
                        this.tableRow.parentNode.insertBefore(this.editTableRow, this.tableRow.nextSibling);
                        this.editTableRow.parentNode.insertBefore(this.hiddenTableRow, this.editTableRow);
                        this.tableRow.style.display = 'none';
                        this.resetEditForm();

                        this.mapLayer.setStyle(selectedStyle);
                        this.resizeMarkerLayer.addTo(map);
                    } else {
                        this.editTableRow.remove();
                        this.hiddenTableRow.remove();
                        this.tableRow.style = '';

                        this.mapLayer.setStyle(defaultStyle);
                        this.resizeMarkerLayer.removeFrom(map);
                    }
                }

                focus() {
                    map.setView(this.mapLayer.getBounds().getCenter(), this.geojson.properties.zoom);
                }

                updateCoordinates() {
                    this.mapLayer.setBounds(L.latLngBounds(this.northEast, this.southWest));
                    this.markers[0].setLatLng(this.northEast);
                    this.markers[1].setLatLng(L.latLng(this.northEast.lat, this.southWest.lng));
                    this.markers[2].setLatLng(this.southWest);
                    this.markers[3].setLatLng(L.latLng(this.southWest.lat, this.northEast.lng));
                }

                resetEditForm() {
                    document.getElementById(`edit-${this.id}-post_title`).value = this.title;
                    document.getElementById(`edit-${this.id}-linked_post_id`).value = this.geojson.properties.linkedPostId;
                    document.getElementById(`edit-${this.id}-post_min_zoom`).value = this.geojson.properties.minZoom;
                    document.getElementById(`edit-${this.id}-post_max_zoom`).value = this.geojson.properties.maxZoom;
                }

                getGeometryCoordinates() {
                    return [
                        [
                            [this.northEast.lng.toFixed(2), this.northEast.lat.toFixed(2)],
                            [this.southWest.lng.toFixed(2), this.northEast.lat.toFixed(2)],
                            [this.southWest.lng.toFixed(2), this.southWest.lat.toFixed(2)],
                            [this.northEast.lng.toFixed(2), this.southWest.lat.toFixed(2)],
                            [this.northEast.lng.toFixed(2), this.northEast.lat.toFixed(2)]
                        ]
                    ]
                }

                isNew() {
                    return this.id.toString().startsWith('new');
                }

            }

            const jsonMapHyperlinks = [
                <?php echo join(',', $json_map_hyperlinks) ?>
            ];

            function buildList() {
                let listContainer = document.getElementById('map-hyperlink-list');
                jsonMapHyperlinks.forEach((jsonMapHyperlink) => {
                    let mapHyperlink = new MapHyperlink(jsonMapHyperlink)
                    mapHyperlinks.push(mapHyperlink);
                    listContainer.appendChild(mapHyperlink.tableRow);
                    mapHyperlink.mapLayer.addTo(map);
                });
            }

            let map = null;

            window.onload = (event) => {
                const ZoomViewer = L.Control.extend({
                    onAdd() {
                        const container = L.DomUtil.create('div');
                        container.style.width = '200px';
                        container.style.background = 'rgba(255,255,255,0.7)';
                        container.style.textAlign = 'left';
                        map.on('zoomstart zoom zoomend', (ev) => {
                            container.innerHTML = `Zoom level: ${map.getZoom()}`;
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
                            container.innerHTML = `Coordinates: [${ev.latlng.lng.toFixed(2)}, ${ev.latlng.lat.toFixed(2)}]`;
                        });
                        return container
                    }
                });

                map = L.map('worldmap', {
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

                buildList();


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


                // let hyperlinksLayer = L.geoJSON(oldHyperlinks, {
                //     style: hyperlinksStyle,
                //     onEachFeature: hyperlinksOnEachFeature
                // }).addTo(map);
                // map.on('zoomend', (e) => hyperlinksLayer.resetStyle());
            }

            function focus() {
                console.log('click');
            }
        </script>
    </div>
</div>
<?php
?>