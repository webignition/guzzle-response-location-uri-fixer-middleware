<?php

namespace webignition\Guzzle\Middleware\ResponseLocationUriFixer;

use GuzzleHttp\Middleware;
use Psr\Http\Message\ResponseInterface;
use webignition\UnparseableUrlFixer\UnparseableUrlFixer;

class Factory
{
    const LOCATION_HEADER_NAME = 'Location';

    public static function create()
    {
        return Middleware::mapResponse(function (ResponseInterface $response) {
            $is3xxStatusCode = '3' === substr((string) $response->getStatusCode(), 0, 1);
            if (!$is3xxStatusCode) {
                return $response;
            }

            if (!$response->hasHeader(self::LOCATION_HEADER_NAME)) {
                return $response;
            }

            $location = $response->getHeaderLine(self::LOCATION_HEADER_NAME);

            $unparseableUrlFixer = new UnparseableUrlFixer();
            $mutatedLocation = $unparseableUrlFixer->fix($location);

            if ($mutatedLocation !== $location) {
                $response = $response->withoutHeader(self::LOCATION_HEADER_NAME);
                $response = $response->withHeader(self::LOCATION_HEADER_NAME, $mutatedLocation);
            }

            return $response;
        });
    }
}
