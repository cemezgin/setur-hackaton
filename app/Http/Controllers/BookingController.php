<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BookingController extends Controller
{

    public function bookingTrack(Request $request)
    {
        $user = 1;
        $oldTrack = [];
        $hotelName = $request->get('hotelName');
        $provider = $request->get('provider');
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/old_bookings_track.json")) {
            $oldTrack = file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/old_bookings_track.json");
            $oldTrack = json_decode($oldTrack, true);
        }

        $old[$user][$provider]= [
            'hotelName' => $hotelName,
        ];

        array_push($oldTrack, $old);

        file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/old_bookings_track.json", json_encode($oldTrack));
    }

}
