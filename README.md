# Laravel HTTP to Curl

Simple tool to dump the raw curl command from Laravel HTTP Request.

## Installation

You can pull in the package via composer:

``` bash
composer require --dev jigarakatidus/laravel-http-to-curl
```

The package will automatically register itself

## Usage

### Basic GET Request

```php
Http::ddWithCurl()
    ->get('https://example.com/api/resource');
```

Outputs

```bash
curl -H 'User-Agent: GuzzleHttp/7' -X 'GET' 'https://example.com/api/resource'
```

### GET Request with Query Parameters

```php
Http::ddWithCurl()
    ->get('https://example.com/api/resource', [
        'param1' => 'value1',
        'param2' => 'value2',
    ]);
```

Outputs

```bash
curl -H 'User-Agent: GuzzleHttp/7' -X 'GET' 'https://example.com/api/resource?param1=value1&param2=value2'
```

### POST Request with JSON Payload

```php
Http::ddWithCurl()
    ->acceptJson()
    ->post('https://example.com/api/resource', [
        'key1' => 'value1',
        'key2' => 'value2',
    ]);
```

Outputs

```bash
curl -H 'User-Agent: GuzzleHttp/7' -H 'Accept: application/json' -H 'Content-Type: application/json' -X 'POST' 'https://example.com/api/resource' -d '{"key1":"value1","key2":"value2"}'
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Pull Requests are welcome.

## Security

If you've found a bug regarding security please mail [jigar.tidus@gmail.com](mailto:jigar.tidus@gmail.com) instead of using the issue tracker.

## Credits

- [Jigar Dhulla](https://github.com/jigarakatidus)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
