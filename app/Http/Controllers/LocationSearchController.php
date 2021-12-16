<?php

namespace App\Http\Controllers;

use App\Services\Adapter\BookingComAdapter;
use App\Services\Adapter\HotelsComAdapter;
use App\Services\PriceSelector;
use Illuminate\Http\Request;

class LocationSearchController extends Controller
{
    public const KEY = '7a611efc65msh8772606504dfc89p113280jsn2592cdb0aabe';
    public function locationSearchAction(Request $request)
    {
        $providers = [
            'bookingcom' => BookingComAdapter::class,
            'hotelscom' => HotelsComAdapter::class,
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

        $res = array_merge_recursive($locationList['bookingcom'], $locationList['hotelscom']);
        foreach ($res as $key => $item) {
            if (isset($item['detail'])) {
                $bestOldPrice = 100000000;
                foreach ($item['detail'] as $priceItem) {
                    $bestPrice = $priceItem['total_price'];
                    if ($bestPrice < $bestOldPrice) {
                        $bestOldPrice = $bestPrice;
                        $selected = $priceItem['provider'];
                    }
                    $prc = [
                        'price' => $bestOldPrice,
                        'provider' => $selected
                    ];
                }

                array_push($res[$key]['detail'], $prc);
                foreach ($res[$key]['detail'] as $prov => $priceDetail) {
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
        if($res != []) {
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/" . $hashUri . ".json", json_encode($res));
        }
        return response()->json($res);
    }
}
