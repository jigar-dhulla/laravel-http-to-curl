# Extended Http to dump and die with Curl command

This will create a macro for `PendingRequest` which will expose the new method `ddWithCurl` for `Http` Facade.

## Installation

You can pull in the package via composer:

``` bash
composer require jigarakatidus/laravel-http-to-curl --dev
```

The package will automatically register itself

## Usage

```php
Http::ddWithCurl('variable')
    ->acceptJson()
    ->asForm()
    ->withBasicAuth('username', 'password')
    ->get('https://example.com/padfhj', [
        'foo' => 'foobar',
        'bar' => 'barfoo',
    ]);
```

Outputs

```bash
curl -H 'User-Agent: GuzzleHttp/7' -H 'Authorization: Basic dXNlcm5hbWU6cGFzc3dvcmQ=' -H 'Host: example.com' -H 'Accept: application/json' -H 'Content-Type: application/x-www-form-urlencoded' -X 'GET' 'https://example.com/padfhj?foo=foobar&bar=barfoo'
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