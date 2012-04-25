# PHP wkhtmltopdf

This class is a slim wrapper around wkhtmltopdf.

It provides a simple and clean interface to ease PDF creation with wkhtmltopdf.
The wkhtmltopdf binary must be installed and working on your system. The static
binary is preferred but this class should also work with the non static version,
even though a lot of features will be missing.

This project is sponsored by [PeoplePerHour](http://www.peopleperhour.com).

## Basic use

```php
<?php
$pdf = new WkHtmlToPdf;
$pdf->addPage('http://google.com');
$pdf->addPage('/home/joe/my.pdf');
$pdf->addCover('mycover.pdf');
$pdf->addToc();

// Save the PDF
$pdf->saveAs('/tmp/new.pdf');

// Send to client for inline display
$pdf->send();

// Send to client as file download
$pdf->send('test.pdf');
```

## Setting options

```php
<?php
$pdf = new WkHtmlToPdf($options);   // Set global PDF options
$pdf->setOptions($options);         // Set global PDF options (alternative)
$pdf->setPageOptions($options);     // Set global default page options
$pdf->addPage($page, $options);     // Set page options (overrides default page options)
```

### Example options

```php
<?php
// See "wkhtmltopdf -H" for all available options
$options=array(
    'no-outline',
    'margin-top'    =>0,
    'margin-right'  =>0,
);
```

### Extra global options

* bin: path to the wkhtmltopdf binary. Defaults to `/usr/bin/wkhtmltopdf`.
* tmp: path to tmp directory. Defaults to PHP temp dir.

## Error handling

`saveAs()` and `save()` will return false on error. In this case the detailed error message from wkhtmltopdf can be obtained through `getError()`.

