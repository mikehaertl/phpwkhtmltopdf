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
 * Basic use:
 *
 *      $pdf = new WkHtmlToPdf;
 *      $pdf->addPage('http://google.com');
 *      $pdf->addPage('/home/joe/my.pdf');
 *      $pdf->addCover('mycover.pdf');
 *      $pdf->addToc();
 *
 *      // Save the PDF
 *      $pdf->saveAs('/tmp/new.pdf');
 *
 *      // Send to client for inline display
 *      $pdf->send();
 *
 *      // Send to client as file download
 *      $pdf->send('test.pdf');
 *
 * Setting options:
 *
 *      $pdf = new WkHtmlToPdf($options);   // Set global PDF options
 *      $pdf->setOptions($options);         // Set global PDF options (alternative)
 *      $pdf->setPageOptions($options);     // Set global default page options
 *      $pdf->addPage($page, $options);     // Set page options (overrides default page options)
 *
 * Example options:
 *
 *      // See "wkhtmltopdf -H" for all available options
 *      $options=array(
 *          'no-outline',
 *          'margin-top'    =>0,
 *          'margin-right'  =>0,
 *      );
 *
 * Extra global options:
 *
 *      bin: path to the wkhtmltopdf binary. Defaults to /usr/bin/wkhtmltopdf.
 *      tmp: path to tmp directory. Defaults to PHP temp dir.
 *
 * Error handling:
 *
 *      saveAs() and save() will return false on error. In this case the detailed error message
 *      from wkhtmltopdf can be obtained through getError().
 *
 * @author Michael Härtl <haertl.mike@gmail.com> (sponsored by PeoplePerHour.com)
 * @version 1.0.0
 * @license http://www.opensource.org/licenses/MIT
 */
class WkHtmlToPdf
{
    protected $bin='/usr/bin/wkhtmltopdf';

    protected $options=array();
    protected $pageOptions=array();
    protected $objects=array();

    protected $tmp;
    protected $tmpFile;

    protected $error;

    /**
     * @param array $options global options for wkhtmltopdf (optional)
     */
    public function __construct($options=array())
    {
        if($options!==array())
            $this->setOptions($options);
    }

    /**
     * Remove temporary PDF file when script completes
     */
    public function __destruct()
    {
        if($this->tmpFile!==null)
            unlink($this->tmpFile);
    }

    /**
     * Add a page object to the output
     *
     * @param string $input either a URL or a PDF filename
     * @param array $options optional options for this page
     */
    public function addPage($input,$options=array())
    {
        $options['input']=$input;
        $this->objects[]=array_merge($this->pageOptions,$options);
    }

    /**
     * Add a cover page object to the output
     *
     * @param string $input either a URL or a PDF filename
     * @param array $options optional options for this page
     */
    public function addCover($input,$options=array())
    {
        $options['input']="cover $input";
        $this->objects[]=array_merge($this->pageOptions,$options);
    }

    /**
     * Add a TOC object to the output
     *
     * @param string $input either a URL or a PDF filename
     * @param array $options optional options for this page
     */
    public function addToc($options=array())
    {
        $options['input']="toc";
        $this->objects[]=$options;
    }

    /**
     * Save the PDF to given filename (triggers PDF creation)
     *
     * @param string $filename to save PDF as
     * @return bool wether PDF was created successfully
     */
    public function saveAs($filename)
    {
        if(($pdfFile=$this->getPdfFilename())===false)
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
        if(($pdfFile=$this->getPdfFilename())===false)
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
                $this->bin=$val;
            elseif($key==='tmp')
                $this->tmp=$val;
            elseif(is_int($key))
                $this->options[]=$val;
            else
                $this->options[$key]=$val;
    }

    /**
     * @param array $options that should be applied to all pages as name/value pairs
     */
    public function setPageOptions($options=array())
    {
        $this->pageOptions=$options;
    }

    /**
     * @return mixed the detailled error message including the wkhtmltopdf command or null if none
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return mixed the temporary PDF filename or false on error (triggers PDf creation)
     */
    protected function getPdfFilename()
    {
        if($this->tmpFile===null)
        {
            if($this->tmp===null)
                $this->tmp=sys_get_temp_dir();

            $tmpFile=tempnam($this->tmp,'tmp_WkHtmlToPdf_');

            if($this->createPdf($tmpFile)===true)
                $this->tmpFile=$tmpFile;
            else
                return false;
        }

        return $this->tmpFile;
    }

    /**
     * @param string $filename the filename of the output file
     * @return string the wkhtmltopdf command string
     */
    protected function getCommand($filename)
    {
        $command=$this->bin;

        $command.=$this->renderOptions($this->options);

        foreach($this->objects as $object)
        {
            $command.=' '.$object['input'];
            unset($object['input']);
            $command.=$this->renderOptions($object);
        }

        return $command.' '.$filename;
    }

    /**
     * Create the temporary PDF file
     */
    protected function createPdf($fileName)
    {
        $command=$this->getCommand($fileName);

        // we use proc_open with pipes to fetch error output
        $descriptors=array(
            1=>array('pipe','w'),
            2=>array('pipe','w'),
        );
        $process=proc_open($command, $descriptors, $pipes);

        if(is_resource($process)) {

            $stdout=stream_get_contents($pipes[1]);
            $stderr=stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            $result=proc_close($process);

            if($result!==0)
                $this->error="Could not run command $command:\n$stderr";
        } else
            $this->error="Could not run command $command";

        return $this->error===null;
    }

    /**
     * @param array $options for a wkhtml, either global or for an object
     * @return string the string with options
     */
    protected function renderOptions($options)
    {
        $out='';
        foreach($options as $key=>$val)
            if(is_numeric($key))
                $out.=" --$val";
            else
                $out.=" --$key $val";

        return $out;
    }
}
