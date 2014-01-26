# CHANGELOG

## 1.2.1

 * Issue #29: Add Xvfb support

## 1.2.0

A minor change in the options was introduced in this release. If you used the `bin`
option before you have to rename it to `binPath` now. Please check the docs for
full documentation.

 * Issue #27: Add autodetection of wkhtmltopdf binary on Unix based systems (thanks eusonlito)
 * Issue #28: Implement optional passing of environment variables to proc_open (thanks eusonlito)
 * Issue #30: Bug with options without an argument

## 1.1.6

 * Issue #21: Add support for wkhtmltopdf 0.9 versions

## 1.1.5

 * Add composer autoloading (thanks igorw)
 * Issue #10: Improve error reporting

## 1.1.4

 * Add composer.jsone

## 1.1.3

 * Made getCommand() public to ease debugging
 * Issue #6: Fix typo that prevented shell escaping on windows
 * Issue #5: Updated docs: wkhtmltopdf can not process PDF files

## 1.1.2

 * Issue #4: Fix issue with longer PDFs

## 1.1.1

 * Issue #2: Fix escaping of arguments
 * Issue #3: Fix HTML detection regex


## 1.1.0

 * Issue #1: Allow to add HTML as string


## 1.0.0

 * Initial release
