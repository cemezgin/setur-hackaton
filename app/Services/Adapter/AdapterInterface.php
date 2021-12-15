<?php

namespace App\Services\Adapter;

interface AdapterInterface
{
    public function locationAPIProvider(string $searchQuery, $checkin, $checkout, $adults);
}

