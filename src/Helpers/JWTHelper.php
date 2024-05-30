<?php declare(strict_types=1);

namespace Wexo\EasyTranslate\Helpers;

use Wexo\EasyTranslate\Core\Content\JWTToken;

class JWTHelper
{
    public function parseJwtToken(string $token): JWTToken
    {
        $tokenParts = explode(".", $token);
        $tokenHeader = base64_decode($tokenParts[0]);
        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtHeader = json_decode($tokenHeader);
        $jwtPayload = json_decode($tokenPayload);

        return new JWTToken($jwtHeader, $jwtPayload);
    }
}
