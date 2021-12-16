<?php


namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class HotelDetailsController extends Controller
{
    public function hotelDetailAction(Request $request, $hotelsDestinationId, $bookingDestId)
    {
        $hashUri = hash("md5", $request->getUri());
        if(file_exists($_SERVER['DOCUMENT_ROOT']."/".$hashUri.".json")) {
            $string = file_get_contents($_SERVER['DOCUMENT_ROOT']."/".$hashUri.".json");
            return response()->json(json_decode($string,true));
        }
        $response = Http::withHeaders([
            'x-rapidapi-host' => 'hotels4.p.rapidapi.com',
            'x-rapidapi-key' => '593306b5d1mshdb24b1868312486p10efaejsne3c42c137c35'
        ])->get('https://hotels4.p.rapidapi.com/properties/get-details', [
            'currency' => 'EUR',
            'locale' => 'en_US',
            'id' => $hotelsDestinationId,
        ]);

        $data = $response->json();

        foreach ($data['data']['body']['overview']['overviewSections'] as $section) {
            foreach ($section['content'] as $content) {
                $res['content'][$section['type']][] = $content;
            }
        }

        $property = $data['data']['body']['propertyDescription'];
        $res['property']['name'] = $property['name'];
        $res['property']['starRatingTitle'] = $property['starRatingTitle'];
        $res['property']['starRating'] = $property['starRating'];
        $res['property']['roomTypeNames'] = $property['roomTypeNames'];
        $res['property']['tagline'] = $property['tagline'];
        $res['property']['freebies'] = $property['freebies'];
        $res['property']['essentialTravelersMessage'] = $data['data']['body']['essentialTravelersMessage'] ?? '';
        $res['property']['hygieneAndCleanliness'] = $data['data']['body']['hygieneAndCleanliness'] ?? '';

        $res['transportation'] = $data['transportation']['transportLocations'];
        $res['neighborhood'] = $data['neighborhood']['neighborhoodName'];


        $reviewResp = Http::withHeaders([
            'x-rapidapi-host' => 'booking-com.p.rapidapi.com',
            'x-rapidapi-key' => '593306b5d1mshdb24b1868312486p10efaejsne3c42c137c35'
        ])->get('https://booking-com.p.rapidapi.com/v1/hotels/reviews', [
            'sort_type' => 'SORT_MOST_RELEVANT',
            'locale' => 'en-gb',
            'language_filter' => 'en-gb',
            'hotel_id' => $bookingDestId,
        ]);

        $review = $reviewResp->json();

        foreach ($review['result'] as $rev) {
            $res['review'][] = [
                'pros' => $rev['pros'],
                'cons' => $rev['cons'],
                'average_score' => $rev['average_score'],
                'author' => $rev['author']['name']
            ];
        }
        if($res != []) {
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/" . $hashUri . ".json", json_encode($res));
        }

        return response()->json($res);
    }
}
