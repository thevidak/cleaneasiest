<input id="pac-input" />
<div id="type-selector" class="controls">
      <input type="radio" name="type" id="changetype-all" checked="checked">
      <label for="changetype-all">All</label>

      <input type="radio" name="type" id="changetype-establishment">
      <label for="changetype-establishment">Establishments</label>

      <input type="radio" name="type" id="changetype-address">
      <label for="changetype-address">Addresses</label>

      <input type="radio" name="type" id="changetype-geocode">
      <label for="changetype-geocode">Geocodes</label>
    </div>
<label for="inputIsiBerita"> Latitude:</label>
<input type="text" class="form-control" required name="latitude">
<label for="inputIsiBerita"> Longitude</label>
<input type="text" class="form-control" required name="longitude">
<div id="google-map"></div>

<style>
html,
body,
#map {
  height: 100%;
  width: 100%;
  margin: 0px;
  padding: 0px
}
</style>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBOmnOIO9aAka9eS5Ve_D65WRqpuWwRcTw"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script>
    /*
    function initMap() {
    var map = new google.maps.Map(document.getElementById('google-map'), {
        center: {
        lat: -7.0157404,
        lng: 110.4171283
        },
        zoom: 12
    });
    
    var input = (
        document.getElementById('pac-input')
    );

    var types = document.getElementById('type-selector');
    map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
    map.controls[google.maps.ControlPosition.TOP_LEFT].push(types);

    var autocomplete = new google.maps.places.Autocomplete(input);
    autocomplete.bindTo('bounds', map);

    var infowindow = new google.maps.InfoWindow();
    var marker = new google.maps.Marker({
        map: map,
        anchorPoint: new google.maps.Point(0, -29),
        draggable: true
    });
    google.maps.event.addListener(marker, 'dragend', function() {
        document.getElementsByName('latitude')[0].value = marker.getPosition().lat();
        document.getElementsByName('longitude')[0].value = marker.getPosition().lng();
    })

    autocomplete.addListener('place_changed', function() {
        infowindow.close();
        marker.setVisible(false);
        var place = autocomplete.getPlace();
        if (!place.geometry) {
        window.alert("Autocomplete's returned place contains no geometry");
        return;
        }


        if (place.geometry.viewport) {
        map.fitBounds(place.geometry.viewport);
        } else {
        map.setCenter(place.geometry.location);
        map.setZoom(17); 
        }
        marker.setIcon( ({
        url: 'http://maps.google.com/mapfiles/ms/icons/red.png',
        size: new google.maps.Size(71, 71),
        origin: new google.maps.Point(0, 0),
        anchor: new google.maps.Point(17, 34),
        scaledSize: new google.maps.Size(35, 35)
        }));
        marker.setPosition(place.geometry.location);
        marker.setVisible(true);

        var address = '';
        if (place.address_components) {
        address = [
            (place.address_components[0] && place.address_components[0].short_name || ''),
            (place.address_components[1] && place.address_components[1].short_name || ''),
            (place.address_components[2] && place.address_components[2].short_name || '')
        ].join(' ');
        }

        var latitude = place.geometry.location.lat();
        var longitude = place.geometry.location.lng();

        $("input[name=coordinate]").val(address);
        $("input[name=latitude]").val(latitude);
        $("input[name=longitude]").val(longitude);

        infowindow.setContent('<div><strong>' + place.name + '</strong><br>' + address);
        infowindow.open(map, marker);
    });

    function setupClickListener(id, types) {
        var radioButton = document.getElementById(id);
        radioButton.addEventListener('click', function() {
        autocomplete.setTypes(types);
        });
    }

    setupClickListener('changetype-all', []);
    setupClickListener('changetype-address', ['address']);
    setupClickListener('changetype-establishment', ['establishment']);
    setupClickListener('changetype-geocode', ['geocode']);
    */    
    //}
    //google.maps.event.addDomListener(window, "load", initMap);
    

</script>