<?php

namespace Omnibus\Usps;

use Omnibus\Model\Address;
use Omnibus\Model\TrackingStatus;

/** USPS's shapes for ours: pounds and inches, two-letter states, ZIP codes. */
final class Mapping
{
    public const CLASSES = ['USPS_GROUND_ADVANTAGE' => 'USPS Ground Advantage', 'PRIORITY_MAIL' => 'Priority Mail', 'PRIORITY_MAIL_EXPRESS' => 'Priority Mail Express', 'FIRST-CLASS_PACKAGE_INTERNATIONAL_SERVICE' => 'First-Class Package International', 'PRIORITY_MAIL_INTERNATIONAL' => 'Priority Mail International', 'PRIORITY_MAIL_EXPRESS_INTERNATIONAL' => 'Priority Mail Express International'];

    public static function pounds(int $grams): float
    {
        return round(max(0.01, $grams / 453.592), 2);
    }

    public static function inches(?int $cm): ?float
    {
        return null === $cm ? null : round($cm / 2.54, 1);
    }

    /** @param array<string, mixed> $extra */
    public static function address(Address $a, array $extra = []): array
    {
        $names = explode(' ', trim($a->name), 2);

        return array_filter([
            'firstName' => $names[0] ?? '',
            'lastName' => $names[1] ?? $names[0] ?? '',
            'firm' => $a->company,
            'streetAddress' => $a->line(0),
            'secondaryAddress' => $a->line(1) ?: null,
            'city' => $a->city,
            'state' => $extra['state'] ?? null,
            'ZIPCode' => 'US' === strtoupper($a->country) ? substr($a->postcode, 0, 5) : null,
            'postalCode' => 'US' === strtoupper($a->country) ? null : $a->postcode,
            'country' => 'US' === strtoupper($a->country) ? null : $a->country,
            'countryISOAlpha2Code' => 'US' === strtoupper($a->country) ? null : strtoupper($a->country),
            'phone' => $a->phone ? preg_replace('/\D+/', '', $a->phone) : null,
            'email' => $a->email,
        ], static fn ($v) => null !== $v && '' !== $v);
    }

    public static function status(?string $category, ?string $eventType = null): TrackingStatus
    {
        $c = strtolower((string) $category);
        $e = strtolower((string) $eventType);

        return match (true) {
            str_contains($c, 'delivered') || str_contains($e, 'delivered') => TrackingStatus::DELIVERED,
            str_contains($c, 'out for delivery') || str_contains($e, 'out for delivery') => TrackingStatus::OUT_FOR_DELIVERY,
            str_contains($c, 'available for pickup') || str_contains($e, 'available for pickup') => TrackingStatus::AVAILABLE_FOR_PICKUP,
            str_contains($c, 'pre-shipment') || str_contains($e, 'pre-shipment') || str_contains($e, 'label created') => TrackingStatus::PENDING,
            str_contains($c, 'alert') || str_contains($e, 'alert') || str_contains($e, 'notice left') => TrackingStatus::EXCEPTION,
            str_contains($c, 'return') || str_contains($e, 'return to sender') => TrackingStatus::RETURNED,
            str_contains($c, 'transit') || str_contains($e, 'arrived') || str_contains($e, 'departed') || str_contains($e, 'accept') || str_contains($e, 'in transit') => TrackingStatus::IN_TRANSIT,
            default => TrackingStatus::UNKNOWN,
        };
    }
}
