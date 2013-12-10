PHP WkHtmlToPdf
===============

PHP WkHtmlToPdf provides a simple and clean interface to ease PDF creation with
[wkhtmltopdf](http://code.google.com/p/wkhtmltopdf/).

**The [wkhtmltopdf](http://code.google.com/p/wkhtmltopdf/) binary must be installed and working on your system.**
The static binary is preferred but it should also work with the non static version. In fact
we provide a workaround that uses an xvfb buffer. See below for more details.
You can also use `composer` to install the binaries from `h4cc/wkhtmltopdf-i386` or `h4cc/wkhtmltopdf-amd64`.

## Quickstart

```php
<?php
require_once('WkHtmlToPdf.php');

$pdf = new WkHtmlToPdf;

// Add a HTML file, a HTML string or a page from a URL
$pdf->addPage('/home/joe/page.html');
$pdf->addPage('<html>....</html>');
$pdf->addPage('http://google.com');

// Add a cover (same sources as above are possible)
$pdf->addCover('mycover.html');

// Add a Table of contents
$pdf->addToc();

// Save the PDF
$pdf->saveAs('/tmp/new.pdf');

// ... or send to client for inline display
$pdf->send();

// ... or send to client as file download
$pdf->send('test.pdf');
```


## Setting options

The wkhtmltopdf binary accepts different types of options:

 * global options (e.g. to set the document's DPI)
 * page options (e.g. to supply a custom CSS file for a page)
 * toc options (e.g. to set a TOC header)

Please see `wkhtmltopdf -H` for a full explanation. All options are passed as array, for example:

```php
// Global PDF options
$options = array(
    'no-outline',           // option without argument
    'encoding' => 'UTF-8',  // option with argument
);
$pdf = new WkHtmlToPdf($options);
```

Page options can be supplied on each call to `addPage()`. But you can also set default
options that will be applied to all pages:

```php
$pdf = new WkHtmlToPdf($globalOptions); // Set global PDF options
$pdf->setOptions($globalOptions);       // Set global PDF options (alternative)
$pdf->setPageOptions($pageOptions);     // Set default page options
$pdf->addPage($page, $pageOptions);     // Add page with options (overrides default page options)
$pdf->addCover($page, $pageOptions);    // Add cover with options (overrides default page options)
```

Toc options can be passed to `addToc()`:

```php
$pdf->addToc($tocOptions);              // Add TOC with options
```

## Special global options

There are some special options to configure PHP WkHtmlToPdf. They can be passed to the constructor
or be set via `setOptions()`:

 * `binPath`: Full path to the wkhtmltopdf binary. Required on Windows systems and optionally autodetected if not set on other OS.
 * `binName`: Base name of the binary to use for autodetection. Default is `wkhtmltopdf`.
 * `tmp`: Path to tmp directory. Defaults to the PHP temp dir.
 * `enableEscaping`: Whether arguments to wkhtmltopdf should be escaped. Default is true.
 * `version9`: Whether to use command line syntax for wkhtmltopdf < 0.10.
 * `procEnv`: Optional array with environment variables for the `proc_open()` call.

If you installed `wkhtmltopdf` via composer, you have to set `binPath` to the right binary:

```php
$pdf = new WkHtmlToPdf(array(
   // Use `wkhtmltopdf-i386` or `wkhtmltopdf-amd64`
   'binPath' => 'path/to/vendor/bin/wkhtmltopdf-i386'
));
```

## Error handling

`saveAs()` and `save()` will return false on error. In this case the detailed error message from
wkhtmltopdf can be obtained through `getError()`:

```php
<?php
if (!$pdf->send()) {
    throw new Exception('Could not create PDF: '.$pdf->getError());
}
```

## Using the dynamically linked binary

WORK IN PROGRESS:

On some linux distributions `wkhtmltopdf` is available as a package. But unfortunately only the
dynamically linked binary is included, so you need an xvfb server to create PDFs. We have
included two workarounds for this: Either start an xvfb server from an init script (recommended)
or let the wrapper fire up an X buffer for each PDF created.

### Init script for xvfb

You can use a init script as described [here](https://coderwall.com/p/tog9eq) (Thanks to Larry Williamson).
It's not required to change the class file anymore. Instead you can supply the display configuration in `procEnv`:

```php
<?php
$pdf = new WkHtmlToPdf(array(
    'procEnv' => array( 'DISPLAY' => ':0' ),
));
```

### Autostart xvfb for each PDF

WIP, coming soon.


## Note for Windows users

If you use double quotes (`"`) or percent signs (`%`) as option values, they may get converted to spaces.
You can set `enableEscaping` to false in this case. But then you have to take care of proper escaping yourself.
In some cases it may be neccessary to surround your argument values with extra double quotes.

I also found that some options don't work on Windows (tested with wkhtmltopdf 0.11 rc2), like the
`user-style-sheet` option described below.


## Full example

For me `wkhtmltopdf` seems to create best results with smart shrinking turned off.
But then I had scaling issues which went away after I set all margins to zero and instead
added the margins through CSS. You can also use `cm` or `in` in CSS as this is more apropriate for print styles.

```php
<?php
// Create a new WKHtmlToPdf object with some global PDF options
$pdf = new WkHtmlToPdf(array(
    'no-outline',         // Make Chrome not complain
    'margin-top'    => 0,
    'margin-right'  => 0,
    'margin-bottom' => 0,
    'margin-left'   => 0,
));

// Set default page options for all following pages
$pdf->setPageOptions(array(
    'disable-smart-shrinking',
    'user-style-sheet' => 'pdf.css',
));

// Add a page. To override above page defaults, you could add
// another $options array as second argument.
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
