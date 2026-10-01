<?php

namespace Omnibus\Usps\Action;

use Omnibus\Action\ActionInterface;
use Omnibus\Action\ApiAwareInterface;
use Omnibus\Action\ApiAwareTrait;
use Omnibus\Model\Address;
use Omnibus\Model\PickupPoint;
use Omnibus\Request\Pickup;
use Omnibus\Request\Request;
use Omnibus\Usps\Api;

/** The Locations API: post offices and drop-off points near an address. */
final class PickupAction implements ActionInterface, ApiAwareInterface
{
    /** @use ApiAwareTrait<Api> */
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    public function supports(Request $request): bool
    {
        return $request instanceof Pickup;
    }

    public function execute(Request $request): void
    {
        \assert($request instanceof Pickup);
        $near = $request->near;
        $data = $this->api->call('GET', '/locations/v3/dropoff-locations', null, array_filter(['streetAddress' => $near->line(0) ?: null, 'city' => $near->city, 'ZIPCode' => substr($near->postcode, 0, 5), 'radius' => 20, 'mailClass' => 'PRIORITY_MAIL']));
        $points = [];
        foreach ($data['locations'] ?? $data['dropoffLocations'] ?? [] as $location) {
            $address = $location['address'] ?? $location;
            $hours = [];
            foreach ($location['hours'] ?? [] as $i => $day) {
                $hours[$i + 1] = [[$day['open'] ?? '', $day['close'] ?? '']];
            }
            $points[] = new PickupPoint('usps', (string) ($location['facilityID'] ?? $location['locationID'] ?? $location['id'] ?? ''), (string) ($location['facilityName'] ?? $location['name'] ?? 'USPS'),
                new Address((string) ($location['facilityName'] ?? ''), array_values(array_filter([$address['streetAddress'] ?? null])), (string) ($address['ZIPCode'] ?? ''), (string) ($address['city'] ?? ''), 'US'),
                isset($location['latitude']) ? (float) $location['latitude'] : null, isset($location['longitude']) ? (float) $location['longitude'] : null,
                $hours, isset($location['distance']) ? (int) round(((float) $location['distance']) * 1609) : null);
        }
        $request->setResult(\array_slice($points, 0, $request->limit));
    }
}
