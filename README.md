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

## Full example

For me `wkhtmltopdf` seems to create best results with smart shrinking turned off. 
But then i had scaling issues which went away if i set all margins to zero and instead
add the margins through CSS. We can also use `cm` in CSS as this is more apropriate for print styles.

```php
<?php
$pdf = new WkHtmlToPdf(array(
    'margin-top'    => 0,
    'margin-right'  => 0,
    'margin-bottom' => 0,
    'margin-left'   => 0,
);

$pdf->setPageOptions(array(
    'disable-smart-shrinking',
    'user-style-sheet' => 'pdf.css',
);

$pdf->addPage('demo.html');

$pdf->send();
```

**demo.html**
```html
<!DOCTYPE html>
<head>
</head>
<body>

    <div id="print-area">
        <div id="header">
            This is an example header.
        </div>
        <div id="content">
            <h1>Demo</h1>
            <p>This is example content</p>
        </div>
        <div id="footer">
            This is an example footer.
        </div>
    </div>

</body>
</html>
```

**pdf.css**
```css
/* Define page size. Requires print-area adjustment! */
body {
    margin:     0;
    padding:    0;
    width:      21cm;
    height:     29.7cm;
}

/* Printable area */
#print-area {
    position:   relative;
    top:        1cm;
    left:       1cm;
    width:      19cm;
    height:     27.6cm;

    font-size:      10px;
    font-family:    Arial;
}

#header {
    height:     3cm;

    background: #ccc;
}
#footer {
    position:   absolute;
    bottom:     0;
    width:      100%;
    height:     3cm;

    background: #ccc;
}
```
