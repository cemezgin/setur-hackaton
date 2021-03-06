<?php

namespace App\Services\Adapter;

use Illuminate\Support\Facades\Http;

class BookingComAdapter implements AdapterInterface
{
    private $response;

    public function locationAPIProvider(string $searchQuery, $checkin, $checkout, $adults): array
    {
        $response = Http::withHeaders([
            'x-rapidapi-host' => 'booking-com.p.rapidapi.com',
            'x-rapidapi-key' => env("RAPID_KEY")
        ])->get('https://booking-com.p.rapidapi.com/v1/hotels/locations', [
            'locale' => 'en-gb',
            'name' => $searchQuery,
        ]);

        if ($response->failed()) {
            return $response->json();
        } else {
            $this->response = $response;
            return $this->prepareForMatch($checkin, $checkout, $adults);
        }
    }

    private function mapHotel(array $response, $checkin, $checkout, $adults)
    {
        $price = $response['result'][0]['min_total_price'] ?? '';
        if (isset($response['result'][0]['composite_price_breakdown'])) {
            $priceField = $response['result'][0]['composite_price_breakdown'];
            if(isset($priceField['discounted_amount'])) {
                $price = $priceField['discounted_amount'];
            }
        }

        $price = isset($price['value']) ? $price['value'] : $price;
        $hotel['bookingcom']= [
            'rate' => $response['result'][0]['review_score'],
            'provider' => 'bookingcom',
            'total_price' => round($price,2),
            'url' => sprintf("%s?checkin=%s&checkout=%s&req_adults=%s",$response['result'][0]['url'],$checkin,$checkout,$adults)
        ];

        return $hotel;
    }

    private function prepareForMatch($checkin, $checkout, $adults, $children = 0, $room = 1): array
    {
        $hotels = [];
        $decoded = $this->response->json();
        foreach ($decoded as $item) {
            if ($item['dest_type'] == 'hotel') {
                $hash = md5( round($item['latitude'], 2) . round($item['longitude'], 2));
                $hotels[$hash] = [
                    'name' => $item['name'],
                    'region' => $item['region'],
                    'city' => $item['city_name'],
                    'country' => $item['country'],
                    'locationId' => ['bookingcom' => $item['dest_id']],
                    'latitude' => round($item['latitude'], 2),
                    'longitude' => round($item['longitude'],2),
                    'image_url' => $item['image_url'],
                    'detail' => $this->hotelSearchProvider($item['dest_id'], $checkin, $checkout, $adults)
                ];
            }
        }

        return $hotels;
    }

    public function hotelSearchProvider($destId, $checkin, $checkout, $adults, $children = 0, $room = 1)
    {
        $response = Http::withHeaders([
            'x-rapidapi-host' => 'booking-com.p.rapidapi.com',
            'x-rapidapi-key' => env("RAPID_KEY")
        ])->get('https://booking-com.p.rapidapi.com/v1/hotels/search', [
            'units' => 'metric',
            'order_by' => 'popularity',
            'filter_by_currency' => 'EUR',
            'dest_type' => 'hotel',
            'locale' => 'en-gb',
            'dest_id' => $destId,
            'checkin_date' => $checkin,
            'checkout_date' => $checkout,
            'adults_number' => $adults,
            'room_number' => $room
        ]);
        if ($response->failed()) {
            return $response->json();
        } else {
            return $this->mapHotel($response->json(), $checkin, $checkout, $adults);
        }

    }

}
