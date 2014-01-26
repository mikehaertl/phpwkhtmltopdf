PHP WkHtmlToPdf
===============

PHP WkHtmlToPdf provides a simple and clean interface to ease PDF creation with
[wkhtmltopdf](http://code.google.com/p/wkhtmltopdf/).

**The [wkhtmltopdf](http://code.google.com/p/wkhtmltopdf/) binary must be installed and working on your system.**
See the section below for details.

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


## Note for Windows users

If you use double quotes (`"`) or percent signs (`%`) as option values, they may get converted to spaces.
You can set `enableEscaping` to false in this case. But then you have to take care of proper escaping yourself.
In some cases it may be neccessary to surround your argument values with extra double quotes.

I also found that some options don't work on Windows (tested with wkhtmltopdf 0.11 rc2), like the
`user-style-sheet` option described below.


## How to install the `wkhtmltopdf` binary

As mentioned before the PHP class is just a convenient frontend for the `wkhtmltopdf` command. So you need to
install it on your system before you can use the class. On Linux there are two flavours of this binary:

 *  Statically linked: Instead of installing the package you can also download a statically linked version
    from [wkhtmltopdf's homepage](https://code.google.com/p/wkhtmltopdf/). This will not require any X server
    and thus is the recommended way to use the class.
 *  Dynamically linked: This is what you get for example on Ubuntu if you install the wkhtmltopdf package.
    It will work, but requires an X server which is usually not available on headless webservers. We have
    integrated a workaround for this that uses a virtual X framebuffer (Xvfb) which we will describe below.

### Statically linked binary

You can either download and unzip the correct package for your architecture from
[https://code.google.com/p/wkhtmltopdf/](https://code.google.com/p/wkhtmltopdf/)
or use `composer` to install the binaries from `h4cc/wkhtmltopdf-i386` or `h4cc/wkhtmltopdf-amd64`.
In both cases you have to tell the class where to find the binary.

```php
<?php
$pdf = new WkHtmlToPdf(array(
    'binPath' => '/path/to/your/wkhtmltopdf',
    ...
));
```

### Dynamically linked binary with Xvfb

If you have to use the dynamically linked binary as provided by some Linux versions, you have two
options. You can use

 * the built in Xvfb support or
 * a standalone Xvfb server

Both require the Xvfb package to be available on the system and both also have some drawbacks.

#### Built in Xvfb support

This wraps each call to `wkhtmltopdf` in `xvfb-run`. The latter will run an X instance without
all the overhead of a full X session. The drawback here is, that a new session is fired up for
each an every PDF you create, which will create quite some extra load on your CPU. So this setup
is only recommended for low frequency sites.

To enable the built in support you have to set `enableXvfb`. There are also some options you can set.

```php
<?php
$pdf = new WkHtmlToPdf(array(
    // Enable built in Xvfb support
    'enableXvfb' => true,

    // If this is not set, the xvfb-run binary is autodected
    'xvfbRunBin' => '/usr/bin/xvfb-run',

    // By default the following options are passed to xvfb-run.
    // So only use this option if you want/have to change this.
    'xvfbRunOptions' =>  ' --server-args="-screen 0, 1024x768x24" ',
));
```

#### Using a standalone Xvfb process

A better way might be, to start a Xvfb process once and reuse it for all your PHP requests
(thanks to Larry Williamson for [the original idea](https://coderwall.com/p/tog9eq)).
This requires that you have root access to your machine as you have to add a startup script
for that process. We have provided an example script for Ubuntu [here](https://gist.github.com/eusonlito/7889622)
(Thanks eusonlito). You can put to `/etc/init.d/` and add it to your startup files with
`update-rc.d xvfb defaults 10`. It should be easy to adapt the script for other Linux versions.

If your `Xvfb` process is running, you just have to tell the class to use this X display for
rendering. This is done via an environment variable.

```php
<?php
$pdf = new WkHtmlToPdf(array(
    'procEnv' => array( 'DISPLAY' => ':0' ),
));
```

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
