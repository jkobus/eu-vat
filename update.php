<?php

require_once __DIR__ . '/vendor/autoload.php';

const EU_API = 'https://ec.europa.eu/taxation_customs/tedb/ws/VatRetrievalService.wsdl';

$countries = [
    'AT', 'BE', 'BG', 'HR', 'CY', 'CZ',
    'DK', 'EE', 'FI', 'FR', 'DE', 'GR',
    'HU', 'IE', 'IT', 'LV', 'LT', 'LU',
    'MT', 'NL', 'PL', 'PT', 'RO', 'SK',
    'SI', 'ES', 'SE',
];

$soap = new SoapClient(EU_API);
$cnCodes = [];
$vatRates = [];

foreach ($countries as $country) {

    // it seems that GR returns data for Germany
    // that's why Greece had to be renamed to EL :)
    if($country === 'GR') $country = 'EL';

    try {
        $response = $soap->__soapCall('retrieveVatRates', [
            'retrieveVatRatesRequest' => [
                'memberStates' => [$country],
                'situationOn' => (new DateTimeImmutable())->format('Y-m-d'),
            ],
        ]);

        $response = ((array) $response);

        foreach ($response['vatRateResults'] as $vatRate) {
            if(isset($vatRate->cnCodes->code)) {
                foreach ($vatRate->cnCodes as $code) {
                    if(is_array($code)) {
                        foreach ($code as $c) {
                            $cnCodes[$c->value] = [
                                'code' => $c->value,
                                'description' => stripNbsp($c->description),
                            ];
                        }
                    } else {
                        $cnCodes[$code->value] = [
                            'code' => $code->value,
                            'description' => stripNbsp($code->description),
                        ];
                    }
                }
            }
        }

        file_put_contents(__DIR__ . '/data/' . $country . '.json', json_encode($response, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        echo 'Updated ' . $country . PHP_EOL;

    } catch (\Throwable $e) {
        echo 'Failed during update of ' . $country . PHP_EOL;
        echo $e->getMessage();
    }
}

ksort($cnCodes);
file_put_contents(__DIR__ . '/data/cnCodes.json', json_encode($cnCodes, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

echo 'Done' . PHP_EOL;

function stripNbsp(string $text): string {
    return str_replace("\xc2\xa0", ' ', $text);
}