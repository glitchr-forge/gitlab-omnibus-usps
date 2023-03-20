<?php

namespace Omnibus\USPS;

use Omnibus\Core\GatewayFactory;

class USPSGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'omnibus.factory_name' => 'usps',
            'omnibus.factory_title' => 'United States Postal Service'
        ]);
    }
}