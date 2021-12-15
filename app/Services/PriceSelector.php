<?php


namespace App\Services;

use App\Services\Adapter\AdapterInterface;

class PriceSelector
{
    private $adapter;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function selectLocation($location, $checkin, $checkout, $adults)
    {
        return $this->adapter->locationAPIProvider($location, $checkin, $checkout, $adults);
    }

}
