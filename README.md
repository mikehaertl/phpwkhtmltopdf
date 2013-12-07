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

#### ADDED: Set xvfb


```php
# Set xvfb auto detect executable xvfb-run location (only unix/linux)
$pdf->setXvfb(true);
```

```php
# Set xvfb bin manually
$pdf->setXvfb('/usr/bin/xvfb-run');
```

```php
# Disable xvfb load
$pdf->setXvfb(false);
```

#### ADDED: proc_open env arguments

```php
$pdf = new \WkHtmlToPdf([
    'bin' => (BIN_PATH.'/wkhtmltopdf'),
    'procEnv' => ['option1' => 'value1']
]);
```
