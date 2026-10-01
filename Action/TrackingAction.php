<?php

namespace Omnibus\Usps\Action;

use Omnibus\Action\ActionInterface;
use Omnibus\Action\ApiAwareInterface;
use Omnibus\Action\ApiAwareTrait;
use Omnibus\Model\Tracking as TrackingModel;
use Omnibus\Model\TrackingEvent;
use Omnibus\Request\Request;
use Omnibus\Request\Tracking;
use Omnibus\Usps\Api;
use Omnibus\Usps\Mapping;

/** The Tracking API: the package's events, oldest first. */
final class TrackingAction implements ActionInterface, ApiAwareInterface
{
    /** @use ApiAwareTrait<Api> */
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    public function supports(Request $request): bool
    {
        return $request instanceof Tracking;
    }

    public function execute(Request $request): void
    {
        \assert($request instanceof Tracking);
        $data = $this->api->call('GET', '/tracking/v3/tracking/'.rawurlencode($request->trackingNumber), null, ['expand' => 'DETAIL']);
        $events = [];
        foreach ($data['trackingEvents'] ?? [] as $event) {
            $events[] = new TrackingEvent(new \DateTimeImmutable((string) ($event['eventTimestamp'] ?? 'now')), Mapping::status(null, $event['eventType'] ?? null), (string) ($event['eventType'] ?? ''), trim(implode(' ', array_filter([$event['eventCity'] ?? null, $event['eventState'] ?? null, $event['eventZIP'] ?? null]))) ?: null, $event['eventCode'] ?? null);
        }
        usort($events, static fn (TrackingEvent $a, TrackingEvent $b) => $a->at <=> $b->at);
        $request->setResult(new TrackingModel('usps', $request->trackingNumber, Mapping::status($data['statusCategory'] ?? $data['status'] ?? null, $events ? $events[array_key_last($events)]->description : null), $events));
    }
}
