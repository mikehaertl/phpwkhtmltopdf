Find documentation on the project page: http://mikehaertl.github.com/phpwkhtmltopdf/

#### ADDED: bin auto detect

```php
# Default usage
$pdf = new \WkHtmlToPdf([
    'bin' => (BIN_PATH.'/wkhtmltopdf')
]);
```

```php
# NEW: Auto detect executable location (only unix/linux)
$pdf = new \WkHtmlToPdf();
```

```php
# NEW: Auto detect executable location using executable name (only unix/linux)
$pdf = new \WkHtmlToPdf([
    'bin' => 'wkhtmltopdf'
]);
```
