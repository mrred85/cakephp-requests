# CakePHP Requests

Requests is a replacement for `file_get_contents` function for external urls and uses `curl` package for creating requests.

**IMPORTANT:** You need `curl` extension installed and enabled in your `php.ini` configuration file.

## Install Requests
- Create `Utility` folder in `src` if doesn't exists.
- Copy the `Requests.php` and `RequestsInterface.php` files from `src > Utility` to your **Utility** folder.

### Requirements
- PHP >= 7.1.x
- CakePHP >= 3.5.x

## Allowed methods
```php
    // GET
    Requests::get(string $url, array $context = []);
    // POST
    Requests::post(string $url, array $context = []);
    // PUT
    Requests::put(string $url, array $context = []);
    // PATCH
    Requests::patch(string $url, array $context = []);
    // DELETE
    Requests::delete(string $url, array $context = []);
```

## Context options and types
- fields: [a=>b, c=>d] or 'a=b&c=d' or null
- user_password: 'user:password' or null
- headers: ['type'=>'value', 'type:value'] or []
- proxy: null
- proxy_type: 'http' or see below
- timeout: 0 (seconds)
- connection_timeout: 0 (seconds)
- ssl_verify: false

## Proxy
`Requests` has a `proxy` method included:
```php
    $proxyObject = Requests::proxy('https://username:password@hostname:9090/');

    echo $proxyObject->scheme;
    echo $proxyObject->host;
    echo $proxyObject->port;
    echo $proxyObject->username;
    echo $proxyObject->password;
    echo $proxyObject->proxy;
```
You can also use `proxy` context key in request and `proxy_type`:

**Proxy types:**
- http (default)
- socks4
- socks5

**Usage:**
```php
    $result = Requests::post('https://github.com/', [
        'proxy' => 'http://username:password@hostname:9090/',
        'proxy_type' => 'http'
    ]);

    echo $result->getErrorNumber();
    echo $result->getErrorMessage();
```

## Example
Load `Requests` package and make a request. Get response code and full output as result.
```php
namespace App\Controller;

use App\Utility\Requests;
use Cake\Controller\Controller;

class AppController extends Controller
{
    // Your code
    public function index()
    {
        $result = \App\Utility\Requests::get('https://github.com/', [
            'timeout' => 10,
            'connection_timeout' => 10
        ]);

        echo $result->getHttpResponseCode();
        echo $result->getOutput();
    }
    // Your code
}
```

Enjoy ;)
