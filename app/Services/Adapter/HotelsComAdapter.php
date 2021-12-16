<?php

namespace App\Services\Adapter;

use Illuminate\Support\Facades\Http;

class HotelsComAdapter implements AdapterInterface
{
    private $response;
    const HOTELS_KEY = '593306b5d1mshdb24b1868312486p10efaejsne3c42c137c35';
    public function locationAPIProvider(string $searchQuery, $checkin, $checkout, $adults)
    {
        $response = Http::withHeaders([
            'x-rapidapi-host' => 'hotels-com-provider.p.rapidapi.com',
            'x-rapidapi-key' => self::HOTELS_KEY
        ])->get('https://hotels-com-provider.p.rapidapi.com/v1/destinations/search', [
            'locale' => 'en_US',
            'currency' => 'EUR',
            'query' => $searchQuery,
        ]);

        if ($response->failed()) {
            return $response->json();
        } else {
            $this->response = $response;
            return $this->prepareForMatch($checkin, $checkout, $adults);
        }
    }

    private function prepareForMatch($checkin, $checkout, $adults): array
    {
        $hotels = [];
        $decoded = $this->response->json();
        foreach ($decoded['suggestions'] as $item) {
            if ($item['group'] == 'HOTEL_GROUP') {
                foreach ($item['entities'] as $entity) {
                    $hash = hash("md5", round($entity['latitude'], 2) . round($entity['longitude'], 2));

                    $caption = strip_tags($entity['caption']);
                    $location = explode(',', $caption);

                    $country = $location[count($location) - 1] ?? '';
                    $region = $location[count($location) - 2] ?? '';
                    $city = $location[count($location) - 3] ?? '';

                    $hotels[$hash] = [
                        'name' => $entity['name'],
                        'region' => trim($region),
                        'city' => trim($city),
                        'country' => trim($country),
                        'locationId' => ['hotelscom' => $entity['destinationId']],
                        'latitude' => round($entity['latitude'], 2),
                        'longitude' => round($entity['longitude'],2),
                        'detail' => $this->hotelSearchProvider($entity['destinationId'], $checkin, $checkout, $adults)
                    ];
                }
            }
        }

        return $hotels;
    }

    private function mapHotel(array $response)
    {
//        $lat = round($response['header']['hotelLocation']['coordinates']['latitude'], 2);
//        $long = round($response['header']['hotelLocation']['coordinates']['longitude'], 2);
//        $hash = hash("md5" , $lat . $long);
        $parsed = isset($response['featuredPrice']['fullyBundledPricePerStay']) ?
            explode(" ", $response['featuredPrice']['fullyBundledPricePerStay']) :
        '0';

        $parse = $parsed[1] ?? '';

        $hotel['hotelscom'] = [
            'daily_price' => [
                'value' => $response['featuredPrice']['currentPrice']['plain'] ?? '',
                'currency' => 'EUR'
                ],
            'total_price' => intval(str_replace(".","",$parse)) ?? 0
        ];

        return $hotel;
    }

    public function hotelSearchProvider($destId, $checkin, $checkout, $adults)
    {
            $response = Http::withHeaders([
                'x-rapidapi-host' => 'hotels-com-provider.p.rapidapi.com',
                'x-rapidapi-key' => self::HOTELS_KEY
            ])->get('https://hotels-com-provider.p.rapidapi.com/v1/hotels/booking-details', [
                'currency' => 'EUR',
                'locale' => 'en_US',
                'hotel_id' => $destId,
                'checkin_date' => $checkin,
                'checkout_date' => $checkout,
                'adults_number' => $adults,
            ]);

            return $this->mapHotel($response->json());
    }
}
