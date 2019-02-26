<?php

namespace webignition\Guzzle\Middleware\ResponseLocationUriFixer;

use GuzzleHttp\Middleware;
use Psr\Http\Message\ResponseInterface;

class Factory
{
    public static function create()
    {
        return Middleware::mapResponse(function (ResponseInterface $response) {
            $is3xxStatusCode = '3' === substr($response->getStatusCode(), 0, 1);
            if (!$is3xxStatusCode) {
                return $response;
            }

            $location = $response->getHeaderLine('Location');
            if (empty($location)) {
                return $response;
            }

            $matches = [];
            $invalidSchemePattern = '#[a-z]+:///#';

            if (preg_match($invalidSchemePattern, $location, $matches)) {
                $invalidScheme = $matches[0];
                $validScheme = substr($invalidScheme, 0, -1);

                $mutatedLocation = preg_replace($invalidSchemePattern, $validScheme, $location);

                var_dump($location);
                var_dump($mutatedLocation);

                $response = $response->withoutHeader('location');
                $response = $response->withHeader('location', $mutatedLocation);
            }

            return $response;
        });
    }
}
