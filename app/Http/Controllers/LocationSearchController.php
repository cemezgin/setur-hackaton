<?php

namespace App\Http\Controllers;

use App\Services\Adapter\BookingComAdapter;
use App\Services\Adapter\HotelsComAdapter;
use App\Services\Adapter\InternalProviderAdapter;
use App\Services\PriceSelector;
use Illuminate\Http\Request;

class LocationSearchController extends Controller
{
    public function locationSearchAction(Request $request)
    {
        $flag = true;
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/old_bookings_track.json")) {
            $oldTrack = file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/old_bookings_track.json");
            $oldBooking = json_decode($oldTrack, true);
        }

        $providers = [
            'bookingcom' => BookingComAdapter::class,
            'hotelscom' => HotelsComAdapter::class,
            'setur' => InternalProviderAdapter::class
        ];
        $hashUri = hash("md5", $request->getUri());
        if(file_exists($_SERVER['DOCUMENT_ROOT']."/".$hashUri.".json")) {
            $string = file_get_contents($_SERVER['DOCUMENT_ROOT']."/".$hashUri.".json");
            return response()->json(json_decode($string,true));
        }

        foreach ($providers as $name => $provider) {
            $selector = new PriceSelector(new $provider);
            $result = $selector->selectLocation($request->get('location'), $request->get('checkIn'), $request->get('checkOut'), $request->get('adult'));
            $locationList[$name] = $result;
        }

        $res = array_merge_recursive($locationList['bookingcom'], $locationList['hotelscom'], $locationList['setur']);
        foreach ($res as $key => $item) {
            if (isset($item['detail'])) {
                $bestOldPrice = 100000000;
                $selected = '';
                foreach ($item['detail'] as $priceItem) {
                    $bestPrice = $priceItem['total_price'] ?? $bestOldPrice;
                    if ($bestPrice <= $bestOldPrice) {
                        $bestOldPrice = $bestPrice;
                        $selected = $priceItem['provider'];
                    }

                    $prc = [
                        'total_price' => $bestOldPrice,
                        'provider' => $selected == '' ?  $priceItem['provider'] : $selected,
                        'url' => $priceItem['url'],
                        'rate' => $priceItem['rate']
                    ];
                }
                    array_push($res[$key]['detail'], $prc);

                foreach ($res[$key]['detail'] as $prov => $priceDetail) {
                    $name = is_array($res[$key]['name']) ? $res[$key]['name'][0] : $res[$key]['name'];

                    if(isset($oldBooking[0][1][$prov]) && $oldBooking[0][1][$prov]['hotelName'] == $name) {
                        $res[$key]['detail'][$prov]['repeat'] = true;
                        $flag = false;
                    }

                    if ($prov != "0") {
                       if($prov == $res[$key]['detail'][0]['provider']) {
                           $res[$key]['detail'][$prov]['best'] = true;
                           continue;
                       }
                    }
                }

                unset($res[$key]['detail'][0]);
                rsort($res[$key]['detail']);

            }
        }
        if($res != [] && $flag) {
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/" . $hashUri . ".json", json_encode($res));
        }
        return response()->json($res);
    }
}
