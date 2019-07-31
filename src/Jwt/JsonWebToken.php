<?php

namespace Jinpy666\Jwt;

/**
 *  Details:
 * http://tools.ietf.org/html/draft-ietf-oauth-json-web-token-08
 * Class JsonWebToken
 *
 * @package Jinpy666\Jwt
 */
class JsonWebToken
{

    /**
     * Create JSON web token
     *
     * @param array  $claimSet Claim set
     * @param string $key      Secret key
     * @params array $header Header (optional)
     *
     * @return string JSON Web Token
     */
    public function encode($claimSet, $key, $header = null)
    {
        // Default header
        if ($header === null) {
            $header = [
                'typ' => 'JWT',
                'alg' => 'HS256',
            ];
        }

        $header = $this->encodeJson($header);
        // @todo.js format json
        $header = $this->encodeBase64Url($header);

        // Claimset
        $claimeSet = $this->encodeJson($claimSet);
        // @todo.js format json
        $payload = $this->encodeBase64Url($claimeSet);

        // Signing the Encoded JWS Header and Encoded JWS Payload with the HMAC
        // SHA-256 algorithm and base64url encoding
        $data = $header . $payload;
        $hash = hash_hmac('sha256', $data, $key);

        // Concatenating these parts in this order with
        // period characters between the parts
        return $header . '.' . $payload . '.' . $hash;
    }

    /**
     * Decode JSON web token
     *
     * @param string $token Token
     * @param string $key   Secret key
     *
     * @return array
     */
    public function decode($token, $key)
    {
        $error = null;
        $parts = explode('.', $token);
        if (count($parts) < 3) {
            return ['valid' => false];
        }
        $header = $this->decodeBase64Url($parts[0]);
        $header = $this->decodeJson($header);

        $claimeSet = $this->decodeBase64Url($parts[1]);
        $claimeSet = $this->decodeJson($claimeSet);

        $hash = $parts[2];

        // create token
        $tokenTest = $this->encode($claimeSet, $key, $header);

        // compare tokens
        $isValid = $token === $tokenTest;
        $error   = (!$isValid) ? 'Token invalid' : null;

        // check expired
        $now = time();
        if ($isValid && isset($claimeSet['exp'])) {
            $isNotExpired = $now < $claimeSet['exp'];
            $isValid      = $isNotExpired;
            $error        = (!$isValid) ? 'Token expired' : null;
        }
        // Check not before
        if ($isValid && isset($claimeSet['nbf'])) {
            $isNotBefore = $now >= $claimeSet['nbf'];
            $isValid     = $isNotBefore;
            $error       = (!$isValid) ? 'Token not before' : null;
        }
        $result = [
            'header'   => $header,
            'claimset' => $claimeSet,
            'hash'     => $hash,
            'valid'    => $isValid,
        ];
        if (isset($error)) {
            $result['error'] = $error;
        }

        return $result;
    }

    /**
     * Encodes an string or array to UTF-8
     *
     * @param string $str data
     *
     * @return string Encoded string
     */
    protected function encodeUtf8($str)
    {
        if ($str === null || $str === '') {
            return $str;
        }

        if (is_array($str)) {
            foreach ($str as $key => $value) {
                $str[$key] = $this->encodeUtf8($value);
            }

            return $str;
        } else {
            if (!mb_check_encoding($str, 'UTF-8')) {
                return mb_convert_encoding($str, 'UTF-8');
            } else {
                return $str;
            }
        }
    }

    /**
     * Json encoder
     *
     * @param array $array   Array to encode
     * @param int   $options Options (optional)
     *
     * @return string Json encoded string
     */
    protected function encodeJson($array, $options = 0)
    {
        return json_encode($this->encodeUtf8($array), $options);
    }

    /**
     * Json decoder
     *
     * @param string $strJson Json string
     *
     * @return array Array
     */
    protected function decodeJson($strJson)
    {
        return json_decode($strJson, true);
    }

    /**
     * Base64url encoding (RFC4648)
     * http://tools.ietf.org/html/rfc4648
     *
     * @param string $data data
     *
     * @return string
     */
    protected function encodeBase64Url($data)
    {
        $result = base64_encode($data);
        $result = strtr($result, '+/', '-_');
        $result = str_replace('=', '', $result);

        return $result;
    }

    /**
     * Base64url decoding (RFC4648)
     *
     * @param string $base64data Base64url encoded string
     *
     * @return string
     */
    protected function decodeBase64Url($base64data)
    {
        $base64data .= str_repeat('=', (4 - (strlen($base64data) % 4)));
        $base64data = strtr($base64data, '-_', '+/');
        return base64_decode($base64data);
    }
}
