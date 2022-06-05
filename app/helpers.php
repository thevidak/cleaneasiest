<?php

function googleAPICalculateDistance($starting_location, $ending_location) {
        
        $start = $starting_location['latitude'] . "," . $starting_location['longitude'];
        $end = $ending_location['latitude'] . "," . $ending_location['longitude'];

        $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
            'origins' => $start,
            'destinations' => $end,
            'key' => env('GOOGLE_API_KEY',false)
        ]);

        $calulated_distances = json_decode($response->body());

        return $calulated_distances->rows[0]->elements[0];
        
        /*
        return from api is in this format
        "distance": {
            "text": "1.2 km",
            "value": 1204
        },
        "duration": {
            "text": "6 mins",
            "value": 336
        },
        "status": "OK"
        */
    }

function googleAPIGetTimeRemainingInSeconds ($starting_location, $ending_location) {
    return googleAPICalculateDistance($starting_location, $ending_location)->duration->value;
}
function googleAPIGetTimeRemainingFormated ($starting_location, $ending_location) {
    return googleAPICalculateDistance($starting_location, $ending_location)->duration->text;
}
function googleAPIGetDistanceInMeters ($starting_location, $ending_location) {
    return googleAPICalculateDistance($starting_location, $ending_location)->distance->value;
}
function googleAPIGetDistanceFormated ($starting_location, $ending_location) {
    return googleAPICalculateDistance($starting_location, $ending_location)->distance->text;
}
function googleAPIGetDistanceAndDurationFormated ($starting_location, $ending_location) {
    $result = googleAPICalculateDistance($starting_location, $ending_location);
    return [
        "distance" => $result->distance->text,
        "duration" => $result->duration->text,
    ];
}
function googleAPIGetGeoLocationFromAddress ($address) {
/*
    $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
        'address' => $address,
        'sensor' => FALSE,
        'key' => env('GOOGLE_API_KEY',false)
    ]);

    $calulated_response = json_decode($response->body());

    return [
        "latitude" => $calulated_response->results[0]->geometry->location->lat,
        "longitude" => $calulated_response->results[0]->geometry->location->lng,
    ];
*/
    return [
        "latitude" => 44.813206,
        "longitude" => 20.42967,
    ];
}

function gFormatTime ($seconds) {
    $minutes = (int)($seconds/60);
    if ($minutes < 60) {
        return $minutes . "min";
    }
    else if ($minutes < 1440){
        $hours = (int)($minutes/60);
        return $hours . "h";
    }
    else {
        $days = (int)($minutes/1440);
        return $days == 1 ? $days . " dan" : $days . " dana";
    }
}