<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch border-radius-10">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3">
                            <h5 class="font-weight-bold">{{ $pageTitle }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card border-radius-10">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 p-2">
                                {{ Form::select('driver_id', [], request('driver_id'), [
                                    'class' => 'form-control select2js',
                                    'data-placeholder' => __('message.select_field', ['name' => __('message.driver')]),
                                    'data-allow-clear' => 'true',
                                    'id' => 'driverSearch',
                                    'disabled' => true,
                                ]) }}
                            </div>
                            <div class="col-md-3 p-2">
                                {{ Form::select('ongoing_driver_id', [], request('ongoing_driver_id'), [
                                    'class' => 'form-control select2js',
                                    'data-placeholder' => __('message.ongoing_driver'),
                                    'data-allow-clear' => 'true',
                                    'id' => 'ongoing_driver_id',
                                    'disabled' => true,
                                ]) }}
                            </div>
                        </div>
                        <div class="border-radius-10" id="map" style="height: 600px;"></div>
                        <div id="map-empty" class="text-center text-muted mt-3 d-none">لا يوجد سائقين اونلاين</div>
                        <div id="maplegend" class="">

                            <div>
                                <img src="{{ asset('images/online.png') }}" /> {{ __('message.online') }}
                            </div>
                            <div>
                                <img src="{{ asset('images/ontrip.png') }}" /> {{ __('message.in_service') }}
                            </div>
                            <div>
                                <img src="{{ asset('images/offline.png') }}" /> {{ __('message.offline') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    @section('bottom_script')
        <script>
            $(document).ready(function() {
                let map;
                let markers = {};
                const initialMarkers = @json($markers ?? []);

                initializeMap();
                updateDriverMarkers(initialMarkers);
                toggleEmptyMessage(initialMarkers);

                function initializeMap() {
                    const defaultLocation = new google.maps.LatLng(20.947940, 72.955786);
                    const mapOptions = {
                        zoom: 1.5,
                        center: defaultLocation,
                        mapTypeId: google.maps.MapTypeId.ROADMAP
                    };
                    map = new google.maps.Map(document.getElementById('map'), mapOptions);

                    const legend = document.getElementById("maplegend");
                    map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(legend);
                    $('#maplegend').removeClass('d-none');
                }

                function updateDriverMarkers(locations) {
                    clearAllMarkers();

                    locations.forEach(location => {
                        const driverId = location.id;

                        const icon = location.is_online ?
                            (location.is_available ? "{{ asset('images/online.png') }}" :
                                "{{ asset('images/ontrip.png') }}") :
                            "{{ asset('images/offline.png') }}";

                        const position = new google.maps.LatLng(location.latitude, location.longitude);

                        const marker = new google.maps.Marker({
                            position: position,
                            map: map,
                            icon: icon,
                            title: location.display_name
                        });

                        markers[driverId] = marker;

                        google.maps.event.addListener(marker, 'click', function() {
                            showDriverInfo(location, marker);
                        });
                    });
                }

                function clearAllMarkers() {
                    for (const markerId in markers) {
                        if (markers[markerId]) {
                            markers[markerId].setMap(null);
                        }
                    }
                    markers = {};
                }

                function showDriverInfo(driver, marker) {
                    const contentString = `
                        <div class="map_driver_detail">
                            <ul class="list-unstyled mb-0">
                                <li><i class="fa fa-address-card"></i>: ${driver.display_name || '-'}</li>
                                <li><i class="fa fa-phone"></i>: ${driver.contact_number || '-'}</li>
                            </ul>
                        </div>`;
                    const infowindow = new google.maps.InfoWindow({
                        content: contentString
                    });
                    infowindow.open(map, marker);
                }


                function resetMap() {
                    map.setZoom(1.5);
                    map.setCenter(new google.maps.LatLng(20.947940, 72.955786));
                    updateDriverMarkers(initialMarkers);
                    toggleEmptyMessage(initialMarkers);
                }

                function toggleEmptyMessage(locations) {
                    if (!locations || locations.length === 0) {
                        $('#map-empty').removeClass('d-none');
                    } else {
                        $('#map-empty').addClass('d-none');
                    }
                }
            });
        </script>
    @endsection

</x-master-layout>
