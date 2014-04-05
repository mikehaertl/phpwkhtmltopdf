<?php

// require_once('Behavior/ImageBehavior.php');
// require_once('Behavior/PdfBehavior.php');

namespace WkHtmlToPdf;
use WkHtmlToPdf\Behavior\ImageBehavior;
use WkHtmlToPdf\Behavior\PdfBehavior;


/**
 * WkHtmlToPdf
 *
 * This class is a slim wrapper around wkhtmltopdf.
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 * @author Oleksandr Knyga <oleksandrknyga@gmail.com>
 * @version 1.2.5-dev
 * @license http://www.opensource.org/licenses/MIT
 */
class WkHtmlToPdf
{
    const PDF = 'pdf';
    const IMAGE = 'image';

    private $behavior;

    /**
     * @param array $options global options for wkhtmltopdf (optional)
     */
    public function __construct($options = array(), $type = 'pdf')
    {
        switch($type) {
            case self::IMAGE:
            $this->behavior = new ImageBehavior($options); break;
            default:
            case self::PDF:
            $this->behavior = new PdfBehavior($options);
        }
    }

    /**
     * Add a page object to the output
     *
     * @param string $input either a URL, a HTML string or a PDF/HTML filename
     * @param array $options optional options for this page
     */
    public function addPage($input,$options=array()) {
        return $this->behavior->addPage($input,$options);
    }

    /**
     * Add a cover page object to the output
     *
     * @param string $input either a URL or a PDF filename
     * @param array $options optional options for this page
     */
    public function addCover($input,$options=array()) {
        return $this->behavior->addCover($input,$options);
    }

    /**
     * Add a TOC object to the output
     *
     * @param array $options optional options for the table of contents
     */
    public function addToc($options=array()) {
        return $this->behavior->addToc($options);
    }

    /**
     * Save the file to given filename (triggers file creation)
     *
     * @param string $filename to save file
     * @return bool whether file was created successfully
     */
    public function saveAs($filename) {
        return $this->behavior->saveAs($filename);
    }

    /**
     * Send file to client, either inline or as download (triggers file creation)
     *
     * @param mixed $filename the filename to send. If empty, the file is streamed inline.
     * @param bool $inline whether to force inline display of the file, even if filename is present.
     * @return bool whether file was created successfully
     */
    public function send($filename=null,$inline=false) {
        return $this->behavior->send($filename, $inline);
    }

    /**
     * Set global option(s)
     *
     * @param array $options list of global options to set as name/value pairs
     */
    public function setOptions($options=array()) {
        return $this->behavior->setOptions($options);
    }

    /**
     * @param array $options that should be applied to all pages as name/value pairs
     */
    public function setPageOptions($options=array()) {
        return $this->behavior->setPageOptions($options);
    }

    /**
     * @param array $options list of options as name/value pairs
     * 
     * @return array options processed
     */
    public function processOptions($options=array()) {
        return $this->behavior->processOptions($options);
    }

    /**
     * @return string the full path to the wkhtmltopdf binary.
     */
    public function getBin() {
        return $this->behavior->getBin();
    }

    /**
     * @return string the full path to the xvfb-run binary
     */
    public function getXvfbRunBin() {
        return $this->behavior->getXvfbRunBin();
    }

    /**
     * @return bool whether we're on a Windows OS
     */
    public function getIsWindows() {
        return $this->behavior->getIsWindows();
    }

    /**
     * @return mixed the detailled error message including the wkhtmltopdf command or null if none
     */
    public function getError() {
        return $this->behavior->getError();
    }

    /**
     * @return string path to temp directory
     */
    public function getTmpDir() {
        return $this->behavior->getTmpDir();
    }

    /**
     * @param string $filename the filename of the output file
     * @return string the wkhtmltopdf command string
     */
    public function getCommand($filename) {
        return $this->behavior->getCommand($filename);
    }

    /**
     * @return mixed the temporary file filename or false on error (triggers file creation)
     */
    public function getFilename() {
        return $this->behavior->getFilename();
    }
}
