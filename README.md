# jwt


Example:
=======

```php

// Secret password
$key = '1234';

// Claimset
$claimSet = array(
    // user id
    'iss' => '1',
    // Expiration Time
    'exp' => strtotime(date('Y-m-d H:i:s') . ' + 1 day')
);

$jwt = new \Jinpy666\Jwt\JsonWebToken();

// Create token
$token = $jwt->encode($claimSet, $key);

// check token
$claimDecoded = $jwt->decode($token, $key);

if($claimDecoded['valid'] == true) {
    echo 'Token is valid';
} else {
    echo 'Error: Token is not valid';
}

```
