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
                <tbody id="the-list">
                </tbody>
            </table>
        </div>
        <div id="worldmap" class="worldmap"></div>
        <script>
            let mapHyperlinks = [];

            function edit(id) {
                mapHyperlinks.forEach((mapHyperlink) => {
                    if (mapHyperlink.id == id || mapHyperlink.selected) {
                        mapHyperlink.selected = !mapHyperlink.selected;
                        if (mapHyperlink.selected) {
                            mapHyperlink.showEdit();
                            mapHyperlink.focus();
                        } else {
                            mapHyperlink.cancelDelete();
                            mapHyperlink.cancelEdit();
                        }
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
                    mapHyperlink.id = response.id;
                    mapHyperlink.title = response.title.raw;
                    mapHyperlink.setGeojson(JSON.parse(response.content.raw));
                }).fail((response) => {
                    console.error(response);
                    spinner.classList.remove('is-active');
                    mapHyperlink.setErrorMessage(response.status, response.responseJSON.message);
                });
            }

            function deleteComfirm(id) {
                let spinner = document.getElementById(`edit-${id}-delete-spinner`);
                spinner.classList.add('is-active');

                let index = mapHyperlinks.findIndex(e => e.id == id);
                console.assert(index >= 0, `Couldn't find mapHyperlink with id ${id}`);
                let mapHyperlink = mapHyperlinks[index];

                new wp.api.models.MapHyperlink({
                    id: id,
                }).destroy().done((response) => {
                    console.log(response);
                    spinner.classList.remove('is-active');
                    mapHyperlinks.splice(index, 1);
                    mapHyperlink.destroy();
                    delete mapHyperlink;
                }).fail((response) => {
                    console.error(response);
                    spinner.classList.remove('is-active');
                    mapHyperlink.setErrorMessage(response.status, response.responseJSON.message);
                });
            }

            function cancelEdit(id) {
                mapHyperlinks.forEach((mapHyperlink) => {
                    if (mapHyperlink.id != id) {
                        return;
                    }

                    mapHyperlink.selected = false;
                    mapHyperlink.cancelEdit();
                });
            }

            function showDeleteComfirm(id) {
                let mapHyperlink = mapHyperlinks.find(e => e.id == id);
                let actions = document.getElementById(`edit-${id}-actions`);
                actions.style.display = 'none';
                actions.parentNode.insertBefore(mapHyperlink.deleteComfirm, actions.nextSibling);
            }

            function cancelDelete(id) {
                let mapHyperlink = mapHyperlinks.find(e => e.id == id);
                mapHyperlink.cancelDelete();
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
                let listContainer = document.getElementById('the-list');
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
                            <div class="inline-edit-wrapper" role="region">
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
                                <div id="edit-${this.id}-actions" class="submit inline-edit-save">
                                    <button type="button" class="button button-primary save" onclick="save('${this.id}')">${this.isNew() ? '<?php _e('Publish') ?>' : '<?php _e('Update') ?>'}</button>
                                    <button type="button" class="button cancel" onclick="cancelEdit('${this.id}')"><?php _e('Cancel') ?></button>
                                    <span id="edit-${this.id}-spinner" class="spinner"></span>
                                    <span class="trash" style="margin-left:auto;">
                                        <a id="edit-${this.id}-trash" class="submitdelete" onclick="showDeleteComfirm('${this.id}')" style="color:#b32d2e;cursor:pointer;"><?php _e('Delete') ?></a>
                                    </span>
                                </div>
                                <div id="edit-${this.id}-notice-error" class="notice notice-error notice-alt inline hidden"><p class="error"></p></div>
                            </div>
                        </td>
                    `;

                    // Delete Confirm
                    if (this.deleteComfirm === undefined) {
                        this.deleteComfirm = document.createElement('div');
                    }
                    this.deleteComfirm.classList.add('submit', 'inline-edit-save');
                    this.deleteComfirm.innerHTML = `
                        <b style="color: #b32d2e;margin-left:auto;margin-right:8px;"><?php _e('Are you sure?', 'vacarme-plugin') ?></b>
                        <button type="button" class="button button-delete" onclick="deleteComfirm('${this.id}')"><?php _e('Delete') ?></button>
                        <button type="button" class="button cancel" onclick="cancelDelete('${this.id}')"><?php _e('Cancel') ?></button>
                        <span id="edit-${this.id}-delete-spinner" class="spinner"></span>
                    `;

                    // Bounds
                    let latLngs = L.GeoJSON.coordsToLatLngs(this.geojson.geometry.coordinates[0]);
                    this.northEast = latLngs[0];
                    this.southWest = latLngs[2];
                    this.center = L.latLngBounds(this.northEast, this.southWest).getCenter();
                    // Rect layer
                    if (this.mapLayer === undefined) {
                        this.mapLayer = L.rectangle(L.latLngBounds(this.northEast, this.southWest), {});
                        this.mapLayer.on('click', (e) => {
                            if (this.selected) return;
                            edit(this.id);
                        })
                    } else {
                        this.mapLayer.setBounds(L.latLngBounds(this.northEast, this.southWest));
                    }
                    this.updateLayerStyles();

                    let markerOptions = {
                        icon: handleIcon,
                        draggable: true,
                    }

                    // Marker layers
                    if (this.markers === undefined) {
                        this.markers = [];
                        this.markers.push(
                            L.marker(latLngs[0], markerOptions).on('drag', (e) => {
                                this.northEast = e.latlng;
                                this.updateCoordinates();
                            })
                        );
                        this.markers.push(
                            L.marker(latLngs[1], markerOptions).on('drag', (e) => {
                                this.northEast.lat = e.latlng.lat;
                                this.southWest.lng = e.latlng.lng;
                                this.updateCoordinates();
                            })
                        );
                        this.markers.push(
                            L.marker(latLngs[2], markerOptions).on('drag', (e) => {
                                this.southWest = e.latlng;
                                this.updateCoordinates();
                            })
                        );
                        this.markers.push(
                            L.marker(latLngs[3], markerOptions).on('drag', (e) => {
                                this.northEast.lng = e.latlng.lng;
                                this.southWest.lat = e.latlng.lat;
                                this.updateCoordinates();
                            })
                        );
                        this.resizeMarkerLayer = L.featureGroup(this.markers);
                    }
                }

                showEdit() {
                    this.tableRow.parentNode.insertBefore(this.editTableRow, this.tableRow.nextSibling);
                    this.editTableRow.parentNode.insertBefore(this.hiddenTableRow, this.editTableRow);
                    this.tableRow.style.display = 'none';
                    this.resetEditForm();
                    this.resizeMarkerLayer.addTo(map);
                    this.updateLayerStyles();
                }

                cancelEdit() {
                    this.editTableRow.remove();
                    this.hiddenTableRow.remove();
                    this.tableRow.style = '';
                    this.resizeMarkerLayer.removeFrom(map);
                    this.updateLayerStyles();
                    this.clearErrorMessage();
                }

                destroy() {
                    this.tableRow.remove();
                    this.editTableRow.remove();
                    this.hiddenTableRow.remove();
                    this.deleteComfirm.remove();
                    this.resizeMarkerLayer.removeFrom(map);
                    this.mapLayer.removeFrom(map);
                }

                focus() {
                    map.setView(this.mapLayer.getBounds().getCenter(), this.geojson.properties.minZoom);
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

                updateLayerStyles() {
                    let zoom = map.getZoom();
                    let insideZoom = zoom >= this.geojson.properties.minZoom && zoom < this.geojson.properties.maxZoom;
                    let style = {
                        color: this.selected ? (insideZoom ? '#3388ff' : '#b32d2e') : '#808080',
                        // fillOpacity: insideZoom ? 1.0 : 0.3,
                    }
                    this.mapLayer.setStyle(style);
                }

                cancelDelete() {
                    let actions = document.getElementById(`edit-${this.id}-actions`);
                    actions.style.display = '';
                    this.deleteComfirm.remove();
                    this.clearErrorMessage();
                }

                setErrorMessage(status, message) {
                    let errorContainer = document.getElementById(`edit-${this.id}-notice-error`);
                    if (errorContainer === null) {
                        return;
                    }
                    errorContainer.classList.remove('hidden');
                    errorContainer.firstChild.textContent = `(${status}) ${message}`;
                }

                clearErrorMessage() {
                    let errorContainer = document.getElementById(`edit-${this.id}-notice-error`);
                    if (errorContainer === null) {
                        return;
                    }
                    errorContainer.classList.add('hidden');
                    errorContainer.firstChild.textContent = '';
                }
            }

            const jsonMapHyperlinks = [
                <?php echo join(',', $json_map_hyperlinks) ?>
            ];

            function buildList() {
                let listContainer = document.getElementById('the-list');
                jsonMapHyperlinks.forEach((jsonMapHyperlink) => {
                    let mapHyperlink = new MapHyperlink(jsonMapHyperlink)
                    mapHyperlinks.push(mapHyperlink);
                    listContainer.appendChild(mapHyperlink.tableRow);
                    mapHyperlink.mapLayer.addTo(map);
                });
            }

            let map = null;

            const handleIcon = L.divIcon({
                html: '<svg viewBox="0 0 12 12" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:svg="http://www.w3.org/2000/svg"></svg>',
                iconSize: [12, 12],
            });

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

                map.on('zoomend', (e) => {
                    mapHyperlinks.forEach((hyperlink) => {
                        hyperlink.updateLayerStyles();
                    })
                })

                const zoomViewerControl = (new ZoomViewer()).addTo(map);
                const coordinatesViewerControl = (new CoordinatesViewer()).addTo(map);

                buildList();


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