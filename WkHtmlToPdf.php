<?php
/**
 * WkHtmlToPdf
 *
 * This class is a slim wrapper around wkhtmltopdf.
 *
 * It provides a simple and clean interface to ease PDF creation with wkhtmltopdf.
 * The wkhtmltopdf binary must be installed and working on your system. The static
 * binary is preferred but this class should also work with the non static version,
 * even though a lot of features will be missing.
 *
 * Basic use
 * ---------
 *
 *      $pdf = new WkHtmlToPdf;
 *
 *      // Add a HTML file, a HTML string or a page from URL
 *      $pdf->addPage('/home/joe/page.html');
 *      $pdf->addPage('<html>....</html>');
 *      $pdf->addPage('http://google.com');
 *
 *      // Add a cover (same sources as above are possible)
 *      $pdf->addCover('mycover.html');
 *
 *      // Add a Table of contents
 *      $pdf->addToc();
 *
 *      // Save the PDF
 *      $pdf->saveAs('/tmp/new.pdf');
 *
 *      // ... or send to client for inline display
 *      $pdf->send();
 *
 *      // ... or send to client as file download
 *      $pdf->send('test.pdf');
 *
 *
 * Setting options
 * ---------------
 *
 * The wkhtmltopdf binary knows different types of options (please see wkhtmltopdf -H):
 *
 *      * global options (e.g. to set the document's DPI)
 *      * page options (e.g. to supply a custom CSS file for a page)
 *      * toc options (e.g. to set a TOC header)
 *
 * In addition this class also supports global page options: You can set default page options
 * that will be applied to every page you add. You can also override these defaults per page:
 *
 *      $pdf = new WkHtmlToPdf($options);   // Set global PDF options
 *      $pdf->setOptions($options);         // Set global PDF options (alternative)
 *      $pdf->setPageOptions($options);     // Set default page options
 *      $pdf->addPage($page, $options);     // Add page with options (overrides default page options)
 *      $pdf->addCover($page, $options);    // Add cover with options (overrides default page options)
 *      $pdf->addToc($options);             // Add TOC with options
 *
 *
 * Special global options
 * ----------------------
 *
 *  * bin:              Path to the wkhtmltopdf binary. Defaults to /usr/bin/wkhtmltopdf.
 *  * tmp:              Path to tmp directory. Defaults to PHP temp dir.
 *  * enableEscaping:   Wether arguments to wkhtmltopdf should be escaped. Default is true.
 *
 *
 * Error handling
 * --------------
 *
 * saveAs() and send() will return false on error. In this case the detailed error message
 * from wkhtmltopdf can be obtained through getError():
 *
 *      if(!$pdf->send())
 *          throw new Exception('Could not create PDF: '.$pdf->getError());
 *
 *
 * Note for Windows users
 * ----------------------
 *
 * If you use double quotes (") or percent signs (%) as option values, they may get
 * converted to spaces. You can set `enableEscaping` to `false` in this case. But
 * then you have to take care of proper escaping yourself. In some cases it may be
 * neccessary to surround your argument values with extra double quotes.
 *
 *
 * @author Michael Härtl <haertl.mike@gmail.com> (sponsored by PeoplePerHour.com)
 * @version 1.1.4
 * @license http://www.opensource.org/licenses/MIT
 */
class WkHtmlToPdf
{
    protected $bin = '/usr/bin/wkhtmltopdf';

    protected $enableEscaping = true;

    protected $options = array();
    protected $pageOptions = array();
    protected $objects = array();

    protected $tmp;
    protected $tmpFile;
    protected $tmpFiles = array();

    protected $error;

    // Regular expression to detect HTML strings
    const REGEX_HTML = '/<html/i';

    /**
     * @param array $options global options for wkhtmltopdf (optional)
     */
    public function __construct($options=array())
    {
        if($options!==array())
            $this->setOptions($options);
    }

    /**
     * Remove temporary PDF file and pages when script completes
     */
    public function __destruct()
    {
        if($this->tmpFile!==null)
            unlink($this->tmpFile);

        foreach($this->tmpFiles as $tmp)
            unlink($tmp);
    }

    /**
     * Add a page object to the output
     *
     * @param string $input either a URL, a HTML string or a PDF/HTML filename
     * @param array $options optional options for this page
     */
    public function addPage($input,$options=array())
    {
        $options['input'] = preg_match(self::REGEX_HTML, $input) ? $this->createTmpFile($input) : $input;
        $this->objects[] = array_merge($this->pageOptions,$options);
    }

    /**
     * Add a cover page object to the output
     *
     * @param string $input either a URL or a PDF filename
     * @param array $options optional options for this page
     */
    public function addCover($input,$options=array())
    {
        $options['input'] = "cover $input";
        $this->objects[] = array_merge($this->pageOptions,$options);
    }

    /**
     * Add a TOC object to the output
     *
     * @param array $options optional options for the table of contents
     */
    public function addToc($options=array())
    {
        $options['input'] = "toc";
        $this->objects[] = $options;
    }

    /**
     * Save the PDF to given filename (triggers PDF creation)
     *
     * @param string $filename to save PDF as
     * @return bool wether PDF was created successfully
     */
    public function saveAs($filename)
    {
        if(($pdfFile = $this->getPdfFilename())===false)
            return false;

        copy($pdfFile,$filename);
        return true;
    }

    /**
     * Send PDF to client, either inline or as download (triggers PDF creation)
     *
     * @param mixed $filename the filename to send. If empty, the PDF is streamed.
     * @return bool wether PDF was created successfully
     */
    public function send($filename=null)
    {
        if(($pdfFile = $this->getPdfFilename())===false)
            return false;

        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: application/pdf');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '.filesize($pdfFile));

        if($filename!==null)
            header("Content-Disposition: attachment; filename=\"$filename\"");

        readfile($pdfFile);
        return true;
    }

    /**
     * Set global option(s)
     *
     * @param array $options list of global options to set as name/value pairs
     */
    public function setOptions($options)
    {
        foreach($options as $key=>$val)
            if($key==='bin')
                $this->bin = $val;
            elseif($key==='tmp')
                $this->tmp = $val;
            elseif($key==='enableEscaping')
                $this->enableEscaping = (bool)$val;
            elseif(is_int($key))
                $this->options[] = $val;
            else
                $this->options[$key] = $val;
    }

    /**
     * @param array $options that should be applied to all pages as name/value pairs
     */
    public function setPageOptions($options=array())
    {
        $this->pageOptions = $options;
    }

    /**
     * @return mixed the detailled error message including the wkhtmltopdf command or null if none
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return string path to temp directory
     */
    public function getTmpDir()
    {
        if($this->tmp===null)
            $this->tmp = sys_get_temp_dir();

        return $this->tmp;
    }

    /**
     * @param string $filename the filename of the output file
     * @return string the wkhtmltopdf command string
     */
    public function getCommand($filename)
    {
        $command = $this->enableEscaping ? escapeshellarg($this->bin) : $this->bin;

        $command .= $this->renderOptions($this->options);

        foreach($this->objects as $object)
        {
            $command .= ' '.$object['input'];
            unset($object['input']);
            $command .= $this->renderOptions($object);
        }

        return $command.' '.$filename;
    }

    /**
     * @return mixed the temporary PDF filename or false on error (triggers PDf creation)
     */
    protected function getPdfFilename()
    {
        if($this->tmpFile===null)
        {
            $tmpFile = tempnam($this->getTmpDir(),'tmp_WkHtmlToPdf_');

            if($this->createPdf($tmpFile)===true)
                $this->tmpFile = $tmpFile;
            else
                return false;
        }

        return $this->tmpFile;
    }

    /**
     * Create the temporary PDF file
     */
    protected function createPdf($fileName)
    {
        $command = $this->getCommand($fileName);

        // we use proc_open with pipes to fetch error output
        $descriptors = array(
            2   => array('pipe','w'),
        );
        $process = proc_open($command, $descriptors, $pipes, null, null, array('bypass_shell'=>true));

        if(is_resource($process)) {

            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            $result = proc_close($process);

            if($result!==0)
                $this->error = "Could not run command $command:\n$stderr";
        } else
            $this->error = "Could not run command $command";

        return $this->error===null;
    }

    /**
     * Create a tmp file with given content
     *
     * @param string $content the file content
     * @return string the path to the created file
     */
    protected function createTmpFile($content)
    {
        $tmpFile = tempnam($this->getTmpDir(),'tmp_WkHtmlToPdf_');
        rename($tmpFile, ($tmpFile.='.html'));
        file_put_contents($tmpFile, $content);

        $this->tmpFiles[] = $tmpFile;

        return $tmpFile;
    }

    /**
     * @param array $options for a wkhtml, either global or for an object
     * @return string the string with options
     */
    protected function renderOptions($options)
    {
        $out = '';
        foreach($options as $key=>$val)
            if(is_numeric($key))
                $out .= " --$val";
            else
                $out .= " --$key ".($this->enableEscaping ? escapeshellarg($val) : $val);

        return $out;
    }
}
