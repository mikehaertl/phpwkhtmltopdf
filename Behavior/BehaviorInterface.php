<?php

namespace WkHtmlToPdf\Behavior;

/**
 * @author Oleksandr Knyga <oleksandrknyga@gmail.com>
 * @license http://www.opensource.org/licenses/MIT
 */
interface BehaviorInterface {
	/**
	 * @return string Content type header value
	 */
	public function getContentType();

    /**
     * Add a page object to the output
     *
     * @param string $input either a URL, a HTML string or a PDF/HTML filename
     * @param array $options optional options for this page
     */
    public function addPage($input,$options=array());

    /**
     * Add a cover page object to the output
     *
     * @param string $input either a URL or a PDF filename
     * @param array $options optional options for this page
     */
    public function addCover($input,$options=array());

    /**
     * Add a TOC object to the output
     *
     * @param array $options optional options for the table of contents
     */
    public function addToc($options=array());

    /**
     * Save the file to given filename (triggers file creation)
     *
     * @param string $filename to save file
     * @return bool whether file was created successfully
     */
    public function saveAs($filename);

    /**
     * Send file to client, either inline or as download (triggers file creation)
     *
     * @param mixed $filename the filename to send. If empty, the file is streamed inline.
     * @param bool $inline whether to force inline display of the file, even if filename is present.
     * @return bool whether file was created successfully
     */
    public function send($filename=null,$inline=false);

    /**
     * Set global option(s)
     *
     * @param array $options list of global options to set as name/value pairs
     */
    public function setOptions($options=array());

    /**
     * @param array $options that should be applied to all pages as name/value pairs
     */
    public function setPageOptions($options=array());

    /**
     * @param array $options list of options as name/value pairs
     * 
     * @return array options processed
     */
    public function processOptions($options=array());

    /**
     * @return string the full path to the wkhtmltopdf binary.
     */
    public function getBin();

    /**
     * @return string the full path to the xvfb-run binary
     */
    public function getXvfbRunBin();

    /**
     * @return bool whether we're on a Windows OS
     */
    public function getIsWindows();

    /**
     * @return mixed the detailled error message including the wkhtmltopdf command or null if none
     */
    public function getError();

    /**
     * @return string path to temp directory
     */
    public function getTmpDir();

    /**
     * @param string $filename the filename of the output file
     * @return string the wkhtmltopdf command string
     */
    public function getCommand($filename);

    /**
     * @return mixed the temporary file filename or false on error (triggers file creation)
     */
    public function getFilename();
}