# omnibus/usps

USPS for [glitchr/omnibus](https://github.com/glitchr-studio/omnibus): prices (Domestic and
International Prices APIs), domestic labels (Labels API, paid through the Payments API), tracking
(Tracking API) and drop-off locations (Locations API) - the USPS APIs v3 with OAuth2.

```yaml
omnibus:
    gateways:
        usps:
            factory: usps
            options:
                client_id: '%env(USPS_CLIENT_ID)%'
                client_secret: '%env(USPS_CLIENT_SECRET)%'
                sandbox: true                        # apis-tem.usps.com
                crid: '%env(USPS_CRID)%'             # for labels: the payer's Customer Registration ID,
                mid: '%env(USPS_MID)%'               #   Mailer ID and
                account: '%env(USPS_EPS_ACCOUNT)%'   #   Enterprise Payment System account
                rates: [...]                         # optional: configured prices instead of the Prices API
```

Weights go in pounds and sizes in inches: the conversion is here. The service is the mail class
(USPS_GROUND_ADVANTAGE, PRIORITY_MAIL, PRIORITY_MAIL_EXPRESS; the international classes for prices).
Shipment options: `sender_state` and `recipient_state` (two letters, labels need them),
`mail_classes` (the classes to price), `price_type` (COMMERCIAL, RETAIL), `label_format` (PDF, ZPL).
International labels are not offered: they need a customs form.

Credentials: an app in the [USPS Developer Portal](https://developer.usps.com) with the Prices,
Labels, Tracking, Locations and Payments APIs; a Business Customer Gateway account gives the
CRID, the MID and the EPS account that pays for labels.

Built from USPS's published API documentation and tested on recorded answers; not yet run against
the test environment: that needs the credentials above.

License: LGPL-3.0-or-later.
