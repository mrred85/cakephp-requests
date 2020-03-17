<?php

/**
 * Requests Utility
 *
 * @link https://github.com/mrred85/cakephp-requests
 * @copyright 2016 - present Victor Rosu. All rights reserved.
 * @license Licensed under the MIT License.
 */

namespace App\Utility;

use Exception;
use InvalidArgumentException;

/**
 * Replacement for file_get_contents function for external urls
 *
 * @package App\Utility
 * @implements RequestsInterface
 * @method static Requests get(string $url, array $context = [])
 * @method static Requests post(string $url, array $context = [])
 * @method static Requests put(string $url, array $context = [])
 * @method static Requests patch(string $url, array $context = [])
 * @method static Requests delete(string $url, array $context = [])
 */
class Requests implements RequestsInterface
{
    /**
     * @var array
     */
    private static $requestInfo;

    /**
     * @var array
     */
    private static $requestResult;

    /**
     * Create headers array
     *
     * @param array $headers Headers values
     * @return array
     */
    private static function headers(array $headers)
    {
        $result = [];
        foreach ($headers as $k => $value) {
            if ($value) {
                if ($k) {
                    $result[] = trim($k) . ': ' . trim($value);
                } else {
                    $result[] = trim($value);
                }
            }
        }

        return $result;
    }

    /**
     * Request fields formatter
     *
     * @param string|array $fields Request fields
     * @return string|null
     */
    private static function fields($fields)
    {
        if (is_array($fields)) {
            return http_build_query($fields);
        }

        return $fields;
    }

    /**
     * Create cURL request
     *
     * @param string $method Request method
     * @param string $url Request URL
     * @param array $context Request context
     * @return Requests
     * @throws Exception
     *
     * ### Context values
     * - fields: [a=>b, c=>d] or 'a=b&c=d'
     * - user_password: 'user:password'
     * - headers: array of headers to send
     * - timeout: 0 (in seconds)
     * - connection_timeout: 0 (in seconds)
     * - proxy: string
     * - proxy_type: http / socks4 / socks5
     * - ssl_verify: verification for https urls
     */
    private static function req(string $method, string $url, array $context = [])
    {
        if (!extension_loaded('curl')) {
            throw new Exception('Module "cURL" is not available on your web server!');
        }

        $url = filter_var($url, FILTER_VALIDATE_URL);
        $context = array_merge([
            'fields' => null,
            'user_password' => null,
            'headers' => [],
            'proxy' => null,
            'proxy_type' => 'http',
            'timeout' => 0,
            'connection_timeout' => 0,
            'ssl_verify' => false
        ], $context);
        $fields = static::fields($context['fields']);
        $options = [];
        switch (strtolower($method)) {
            case 'post':
                $qs = '';
                $options += [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $fields
                ];
                break;
            case 'put':
            case 'patch':
            case 'delete':
                $qs = '';
                $options += [
                    CURLOPT_CUSTOMREQUEST => strtoupper($method),
                    CURLOPT_POSTFIELDS => $fields
                ];
                break;
            case 'get':
            default:
                $qs = $fields ? '?' . $fields : '';
                break;
        }
        if ($context['user_password']) {
            $options += [
                CURLOPT_USERPWD => $context['user_password']
            ];
        }
        if ($context['connection_timeout']) {
            $options += [
                CURLOPT_CONNECTTIMEOUT => $context['connection_timeout']
            ];
        }
        if ($context['timeout']) {
            $options += [
                CURLOPT_TIMEOUT => $context['timeout']
            ];
            if (!$context['connection_timeout']) {
                $options += [
                    CURLOPT_CONNECTTIMEOUT => $context['timeout'] / 2
                ];
            }
        }
        if ($context['proxy']) {
            switch ($context['proxy_type']) {
                case 'socks4':
                    $proxyType = CURLPROXY_SOCKS4;
                    break;
                case 'socks5':
                    $proxyType = CURLPROXY_SOCKS5;
                    break;
                case 'http':
                default:
                    $proxyType = CURLPROXY_HTTP;
                    break;
            }
            $proxy = static::proxy($context['proxy']);
            if ($proxy) {
                $options += [
                    CURLOPT_PROXY => $proxy->proxy,
                    CURLOPT_PROXYTYPE => $proxyType
                ];
            }
        }
        $options += [
            CURLOPT_URL => $url . $qs,
            CURLOPT_USERAGENT => filter_input(INPUT_SERVER, 'HTTP_USER_AGENT'),
            CURLOPT_HTTPHEADER => static::headers($context['headers']),
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYHOST => (bool)$context['ssl_verify'],
            CURLOPT_SSL_VERIFYPEER => (bool)$context['ssl_verify']
        ];
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $output = curl_exec($ch);
        static::$requestInfo = curl_getinfo($ch);
        static::$requestResult = [
            'output' => $output,
            'error_nr' => curl_errno($ch),
            'error_message' => curl_error($ch)
        ];
        curl_close($ch);

        return new static();
    }

    /**
     * Request Type
     *
     * @param string $name Request type
     * @param array $arguments Request $url and $context
     * @return Requests
     * @throws Exception
     */
    public static function __callStatic($name, $arguments)
    {
        $allowedTypes = ['get', 'post', 'put', 'patch', 'delete'];
        if (!in_array(strtolower($name), $allowedTypes)) {
            throw new InvalidArgumentException('Request type is not valid!');
        }
        $url = null;
        if (!empty($arguments[0]) && is_string($arguments[0])) {
            $url = trim($arguments[0]);
        }
        $context = [];
        if (!empty($arguments[1]) && is_array($arguments[1])) {
            $context = $arguments[1];
        }
        if (!$url) {
            throw new InvalidArgumentException('Request URL cannot be null!');
        }

        return static::req($name, $url, $context);
    }

    /**
     * @param string $proxyString Request proxy URL address
     *
     * Parse proxy URL
     * http://username:password@hostname:9090/
     * and return reformatted value
     *
     * @return object|null
     */
    public static function proxy(string $proxyString)
    {
        $proxyString = filter_var($proxyString, FILTER_VALIDATE_URL);
        if ($proxyString) {
            $parts = parse_url($proxyString);

            $proxy = $parts['scheme'] . '://';
            if (!empty($parts['user']) && !empty($parts['pass'])) {
                $proxy .= $parts['user'] . ':' . $parts['pass'] . '@';
            }
            $proxy .= $parts['host'];
            if (!empty($parts['port'])) {
                $proxy .= ':' . $parts['port'];
            }
            $proxy .= '/';

            return (object)[
                'scheme' => $parts['scheme'],
                'host' => $parts['host'],
                'port' => $parts['port'] ?? null,
                'username' => $parts['user'] ?? null,
                'password' => $parts['pass'] ?? null,
                'proxy' => $proxy
            ];
        }

        return null;
    }

    /**
     * Get request output
     *
     * @return string|bool
     */
    public function getOutput()
    {
        return static::$requestResult['output'];
    }

    /**
     * Get request HTTP response code
     *
     * @return int
     */
    public function getHttpResponseCode()
    {
        return (int)static::$requestInfo['http_code'];
    }

    /**
     * Total transaction time in seconds for last transfer
     *
     * @return float
     */
    public function getTotalTime()
    {
        return (float)static::$requestInfo['total_time'];
    }

    /**
     * Get request error number
     *
     * @return int
     */
    public function getErrorNumber()
    {
        return (int)static::$requestResult['error_nr'];
    }

    /**
     * Get request error message
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return static::$requestResult['error_message'];
    }

    /**
     * Get textual representation of error code
     *
     * @return string
     */
    public function getErrorCodeMessage()
    {
        return curl_strerror(static::getErrorNumber());
    }

    /**
     * All request Information. For debug purposes
     *
     * @return array
     */
    //@codingStandardsIgnoreStart
    public function __getAll()
    {
        return static::$requestResult;
    }
    //@codingStandardsIgnoreEnd
}
