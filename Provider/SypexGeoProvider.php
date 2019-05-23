<?php

namespace YamilovS\SypexGeoBundle\Provider;

use Geocoder\Collection;
use Geocoder\Exception\Exception;
use Geocoder\Exception\UnsupportedOperation;
use Geocoder\Model\Address;
use Geocoder\Model\AddressCollection;
use Geocoder\Provider\AbstractProvider;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use YamilovS\SypexGeoBundle\Manager\SypexGeoManager;

class SypexGeoProvider extends AbstractProvider implements Provider
{
    /** @var SypexGeoManager */
    private $sypexGeoManager;

    public function __construct(SypexGeoManager $sypexGeoManager)
    {
        $this->sypexGeoManager = $sypexGeoManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'sypex_geo';
    }

    /**
     * @param GeocodeQuery $query
     *
     * @return Collection
     *
     * @throws Exception
     */
    public function geocodeQuery(GeocodeQuery $query): Collection
    {
        $address = $query->getText();
        if (!filter_var($address, FILTER_VALIDATE_IP)) {
            throw new UnsupportedOperation('The SypexGeo provider does not support street addresses, only IP addresses.');
        }

        if (in_array($address, ['127.0.0.1', '::1'])) {
            return new AddressCollection([$this->getLocationForLocalhost()]);
        }

        $results = $this->sypexGeoManager->getCity($address);

        if (!is_array($results) || !array_key_exists('city', $results)) {
            return new AddressCollection([]);
        }

        $addresses = [];
        $addresses[] = Address::createFromArray([
            'latitude' => isset($results['city']['lat']) ? $results['city']['lat'] : 0,
            'longitude' => isset($results['city']['lon']) ? $results['city']['lon'] : 0,
            'locality' => isset($results['city']['name_ru']) ? $results['city']['name_ru'] : null,
            'adminLevels' => isset($results['region']['name_ru']) ? [
                [
                    'name' => $results['region']['name_ru'],
                    'code' => ($results['region']['okato'] ? $results['region']['okato'] : null),
                    'level' => 1
                ]
            ] : [],
            'country' => isset($results['country']['name_ru']) ? $results['country']['name_ru'] : null,
        ]);

        return new AddressCollection($addresses);
    }

    /**
     * @param ReverseQuery $query
     *
     * @return Collection
     *
     * @throws Exception
     */
    public function reverseQuery(ReverseQuery $query): Collection
    {
        throw new UnsupportedOperation('The SypexGeo provider is not able to do reverse geocoding.');
    }
}
