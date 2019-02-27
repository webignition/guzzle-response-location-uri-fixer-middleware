<?php

namespace webignition\Guzzle\Middleware\ResponseLocationUriFixer;

use GuzzleHttp\Middleware;
use Psr\Http\Message\ResponseInterface;

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

            $response = self::fixTripleSlashAfterHttpScheme($response);

            return $response;
        });
    }

    private static function fixTripleSlashAfterHttpScheme(ResponseInterface $response): ResponseInterface
    {
        $location = $response->getHeaderLine(self::LOCATION_HEADER_NAME);

        $matches = [];
        $invalidSchemePattern = '#[a-z]+:///#';

        if (preg_match($invalidSchemePattern, $location, $matches)) {
            $invalidScheme = $matches[0];
            $validScheme = substr($invalidScheme, 0, -1);

            $mutatedLocation = (string) preg_replace($invalidSchemePattern, $validScheme, $location);

            $response = $response->withoutHeader(self::LOCATION_HEADER_NAME);
            $response = $response->withHeader(self::LOCATION_HEADER_NAME, $mutatedLocation);
        }

        return $response;
    }
}
