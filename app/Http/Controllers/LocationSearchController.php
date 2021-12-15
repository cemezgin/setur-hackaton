<?php

namespace App\Http\Controllers;

use App\Services\Adapter\BookingComAdapter;
use App\Services\Adapter\HotelsComAdapter;
use App\Services\PriceSelector;
use Illuminate\Http\Request;

class LocationSearchController extends Controller
{
    public function locationSearchAction(Request $request)
    {
        $providers = [
            'bookingcom' => BookingComAdapter::class,
            'hotelscom' => HotelsComAdapter::class,
        ];

        foreach ($providers as $name => $provider) {
            $selector = new PriceSelector(new $provider);
            $result = $selector->selectLocation($request->get('location'), $request->get('checkIn'), $request->get('checkOut'), $request->get('adult'));
            $locationList[$name] = $result;
        }

        $res = array_merge_recursive($locationList['bookingcom'], $locationList['hotelscom']);
        foreach ($res as $key => $item) {
            if (isset($item['detail'])) {
                $bestOldPrice = 100000000;
                foreach ($item['detail'] as $provider => $priceItem) {
                    $bestPrice = $priceItem['total_price'];
                    if ($bestPrice < $bestOldPrice) {
                        $bestOldPrice = $bestPrice;
                        $selected = $provider;
                    }
                    $prc = [
                        'price' => $bestOldPrice,
                        'provider' => $selected
                    ];
                }

                array_push($res[$key], $prc);
            }
        }
        return response()->json($res);
    }
}
