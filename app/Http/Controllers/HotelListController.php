<?php

namespace App\Http\Controllers;

class HotelListController extends Controller
{
    public function hotelList()
    {
        $content = file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/otel_feature.csv");
        $data = str_getcsv($content, "\n");
        array_walk($data, function (&$a) use ($data) {
            $a = str_getcsv($a);
        });

        foreach ($data as $datum) {
            $res[$datum[1]] = $datum[1];
        }

        return response()->json($res);
    }

}
