<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\HotelListController;
use App\Http\Controllers\HotelDetailsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocationSearchController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('location', [LocationSearchController::class, 'locationSearchAction']);
Route::get('hotel/{destinationId}/{bookingId}', [HotelDetailsController::class, 'hotelDetailAction']);
Route::get('hotel-review/{bookingId}', [HotelDetailsController::class, 'getReview']);
Route::get('hotel-list-compare', [HotelDetailsController::class, 'getListAction']);
Route::get('booking', [BookingController::class, 'bookingTrack']);
Route::get('hotels/{string}', [HotelListController::class, 'hotelList']);
