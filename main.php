<?php

$apiKey = "9eb61b6aa98a57d4201f19b0253c92aa";

class GeolocationService {
    private $apiKey;

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function getGeolocation($address) {
        $url = "http://api.positionstack.com/v1/forward?access_key={$this->apiKey}&query=" . urlencode($address);
        $data = json_decode(file_get_contents($url), true);

        if (!empty($data['data'])) {
            return [
                'latitude' => $data['data'][0]['latitude'],
                'longitude' => $data['data'][0]['longitude']
            ];
        } else {
            return null;
        }
    }

    public function calculateDistance($from, $to) {
        $latFrom = deg2rad($from['latitude']);
        $lonFrom = deg2rad($from['longitude']);
        $latTo = deg2rad($to['latitude']);
        $lonTo = deg2rad($to['longitude']);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return round($angle * 6371, 2);
    }

    public function sortLocationsByDistance($locations) {
        usort($locations, function ($a, $b) {
            return $a['distance'] - $b['distance'];
        });

        return $locations;
    }

    public function writeLocationsToCsv($locations, $filename) {
        $file = fopen($filename, 'w');

        foreach ($locations as $index => $item) {
            $sortedNumber = $index + 1;
            $distanceFormated = number_format($item['distance'], 2) . " km";
            fputcsv($file, [$sortedNumber, $distanceFormated, $item['name'], $item['address']]);
        }

        fclose($file);
    }
}

$geolocationService = new GeolocationService($apiKey);

$addresses = [
    "Adchieve HQ" => "Sint Janssingel 92, 5211DA 's-Hertogenbosch, Nederland",
    "Eastern Enterprise B.V." => "Deldenerstraat 70, 7551AH Hengelo, Nederland",
    "Eastern Enterprise" => "46/1 Office no 1 Ground Floor, Dada House, Inside dada silk mills compound, Udhana Main Rd, near Chhaydo Hospital, Surat, 394210, India",
    "Adchieve Rotterdam" => "Weena 505, 3013AL Rotterdam, Nederland",
    "Sherlock Holmes" => "221B Baker St., Londen, Verenigd Koninkrijk",
    "The White House" => "1600 Pennsylvania Avenue, Washington, D.C., VS",
    "The Empire State Building" => "350 Fifth Avenue, New York City, NY 10118, VS",
    "The Pope" => "Saint Martha House, 00120 Citta del Vaticano, Vaticaanstad",
    "Neverland" => "5225 Figueroa Mountain Road, Los Olivos, Calif. 93441, VS",
];

$adchieveHQ = $geolocationService->getGeolocation($addresses["Adchieve HQ"]);

if ($adchieveHQ) {
    $locations = [];
    foreach ($addresses as $name => $address) {
        $location = $geolocationService->getGeolocation($address);
        if ($location) {
            $distance = $geolocationService->calculateDistance($adchieveHQ, $location);
            $locations[] = ["name" => $name, "distance" => $distance, "address" => $address];
        }
    }

    $sortedLocations = $geolocationService->sortLocationsByDistance($locations);
    $geolocationService->writeLocationsToCsv($sortedLocations, 'distances.csv');

    echo "Results written to distances.csv\n";

    // Output sorted locations to console
    foreach ($sortedLocations as $location) {
        echo "<p>{$location['name']}: {$location['distance']} km</p>";
    }
} else {
    echo "Error: Could not retrieve location of Adchieve HQ\n";
}