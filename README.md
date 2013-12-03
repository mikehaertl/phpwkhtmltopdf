Find documentation on the project page: http://mikehaertl.github.com/phpwkhtmltopdf/

FORK UPDATES
--------

#### BIN auto detect


```php
# Default usage
$pdf = new \WkHtmlToPdf([
    'bin' => (BIN_PATH.'/wkhtmltopdf')
]);
```

```php
# NEW: Auto detect executable location using executable name (only unix/linux)
$pdf = new \WkHtmlToPdf([
    'bin' => 'wkhtmltopdf'
]);
```

```php
# NEW: Auto detect executable location (only unix/linux)
$pdf = new \WkHtmlToPdf();

#### Set xvfb


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

#### Added proc_open env arguments

```php
$pdf = new \WkHtmlToPdf([
    'bin' => (BIN_PATH.'/wkhtmltopdf'),
    'procEnv' => array('option1' => 'value1')
]);
```