<?php

namespace App\Http\Controllers;

class HotelListController extends Controller
{
    public function hotelList($string)
    {
        $content = file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/otel_feature.csv");
        $data = str_getcsv($content, "\n");
        array_walk($data, function (&$a) use ($data) {
            $a = str_getcsv($a);
        });

        foreach ($data as $datum) {
            $res[$datum[1]] = $datum[1];
        }

        $matches = preg_grep(sprintf('/\\b%s?\\b/i', $string), $res);
        $keys = array_keys($matches);

        return response()->json($keys);
    }

}
