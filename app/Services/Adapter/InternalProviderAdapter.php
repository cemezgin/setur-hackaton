<?php

namespace App\Services\Adapter;

use DateTime;

class InternalProviderAdapter implements AdapterInterface
{

    public function locationAPIProvider(string $searchQuery, $checkin, $checkout, $adults)
    {
        $date1 = new DateTime($checkin);
        $date2 = new DateTime($checkout);

        (int)$days = $date2->diff($date1)->format('%a');

        $content = file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/setur_data.csv");
        $data = str_getcsv($content, "\n");
        array_walk($data, function (&$a) use ($data) {
            $a = str_getcsv($a);
        });
        $res = [];

        foreach ($data as $datum) {
            $randomPrice = rand(10, 250);
            $rate = rand(1, 10);
            $totalPrice = $randomPrice * $days;
            $lat = round((float)str_replace(",",".", $datum[4]), 2);
            $long = round((float)str_replace(",",".", $datum[3]), 2);
            $hash = md5($lat . $long);
            $url = mb_strtolower(str_replace(" ", "-", $datum[1]));

            if (preg_match(sprintf('/^%s (\w+)/i', $searchQuery), $datum[1])) {
                $res[$hash] = [
                    'region' => '',
                    'city' => '',
                    'country' => '',
                    'locationId' => '',
                    'image_url' => '',
                    'name' => $datum[1],
                    'latitude' => $lat,
                    'longitude' => $long,
                    'detail' => ['setur' => [
                        'rate' => $rate,
                        'total_price' => $totalPrice,
                        'provider' => 'setur',
                        'url' => sprintf("https://www.setur.com.tr/%s?checkinDate=%s&checkoutDate=%s",$url , $checkin, $checkout)
                    ]]
                ];
            }
        }

        return $res;
    }

}
