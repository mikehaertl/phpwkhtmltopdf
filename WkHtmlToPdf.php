<?php
/**
 * WkHtmlToPdf
 *
 * This class is a slim wrapper around wkhtmltopdf.
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 * @version 1.2.6-dev
 * @license http://www.opensource.org/licenses/MIT
 */
class WkHtmlToPdf
{
    protected $binPath;
    protected $binName = 'wkhtmltopdf';

    protected $ignoreWarnings = false;
    protected $enableEscaping = true;
    protected $version9 = false;

    protected $options = array();
    protected $pageOptions = array();
    protected $objects = array();

    protected $tmp;
    protected $tmpFile;
    protected $tmpFiles = array();

    protected $procEnv;

    protected $isWindows;

    protected $enableXvfb = false;
    protected $xvfbRunBin;
    protected $xvfbRunOptions = ' --server-args="-screen 0, 1024x768x24" ';

    protected $error;
    protected $warning;

    protected $localOptions = array(
        'binName',
        'binPath',
        'enableEscaping',
        'enableXvfb',
        'ignoreWarnings',
        'procEnv',
        'tmp',
        'version9',
        'xvfbRunBin',
        'xvfbRunOptions',
    );

    // Regular expression to detect HTML strings
    const REGEX_HTML = '/<html/i';

    /**
     * @param array $options global options for wkhtmltopdf (optional)
     */
    public function __construct($options=array())
    {
        if ($options!==array()) {
            $this->setOptions($options);
        }
    }

    /**
     * Remove temporary PDF file and pages when script completes
     */
    public function __destruct()
    {
        if ($this->tmpFile!==null) {
            unlink($this->tmpFile);
        }

        foreach($this->tmpFiles as $tmp) {
            unlink($tmp);
        }
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
        $this->objects[] = array_merge($this->pageOptions,$this->processOptions($options));
    }

    /**
     * Add a cover page object to the output
     *
     * @param string $input either a URL or a PDF filename
     * @param array $options optional options for this page
     */
    public function addCover($input,$options=array())
    {
        $options['input'] = ($this->version9 ? '--' : '')."cover $input";
        $this->objects[] = array_merge($this->pageOptions,$options);
    }

    /**
     * Add a TOC object to the output
     *
     * @param array $options optional options for the table of contents
     */
    public function addToc($options=array())
    {
        $options['input'] = ($this->version9 ? '--' : '')."toc";
        $this->objects[] = $options;
    }

    /**
     * Save the PDF to given filename (triggers PDF creation)
     *
     * @param string $filename to save PDF as
     * @return bool whether PDF was created successfully
     */
    public function saveAs($filename)
    {
        if (($pdfFile = $this->getPdfFilename())===false) {
            return false;
        }

        copy($pdfFile,$filename);
        return true;
    }

    /**
     * Send PDF to client, either inline or as download (triggers PDF creation)
     *
     * @param mixed $filename the filename to send. If empty, the PDF is streamed inline.
     * @param bool $inline whether to force inline display of the PDF, even if filename is present.
     * @return bool whether PDF was created successfully
     */
    public function send($filename=null,$inline=false)
    {
        if (($pdfFile = $this->getPdfFilename())===false) {
            return false;
        }

        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: application/pdf');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '.filesize($pdfFile));

        if ($filename!==null || $inline) {
            $disposition = $inline ? 'inline' : 'attachment';
            header("Content-Disposition: $disposition; filename=\"$filename\"");
        }

        readfile($pdfFile);
        return true;
    }

    /**
     * Set global option(s)
     *
     * @param array $options list of global options to set as name/value pairs
     */
    public function setOptions($options=array())
    {
        $options = $this->processOptions($options);
        foreach ($options as $key=>$val) {
            if(in_array($key, $this->localOptions, true)) {
                $this->$key = $val;
            } elseif (is_int($key)) {
                $this->options[] = $val;
            } else {
                $this->options[$key] = $val;
            }
        }
    }

    /**
     * @param array $options that should be applied to all pages as name/value pairs
     */
    public function setPageOptions($options=array())
    {
        $this->pageOptions = $this->processOptions($options);
    }

    /**
     * @param array $options list of options as name/value pairs
     *
     * @return array options processed
     */
    public function processOptions($options=array())
    {
        foreach ($options as $key=>$val) {
            if (preg_match('/^(header|footer)-html$/', $key) &&
                !(is_file($val) || preg_match('/^(https?:)?\/\//i',$val) || $val===strip_tags($val))) {
                $options[$key] = $this->createTmpFile($val);
            }
        }

        return $options;
    }

    /**
     * @return string the full path to the wkhtmltopdf binary.
     */
    public function getBin()
    {
        if ($this->binPath===null) {
            if ($this->getIsWindows()) {
                return '';
            } else {
                $this->binPath = trim(shell_exec('which '.$this->binName));
            }
        }
        return $this->binPath;
    }

    /**
     * @return string the full path to the xvfb-run binary
     */
    public function getXvfbRunBin()
    {
        if ($this->xvfbRunBin===null) {
            if ($this->getIsWindows()) {
                return null;
            } else {
                $this->xvfbRunBin = trim(shell_exec('which xvfb-run'));
            }
        }
        return $this->xvfbRunBin;
    }

    /**
     * @return bool whether we're on a Windows OS
     */
    public function getIsWindows()
    {
        if ($this->isWindows===null) {
            $this->isWindows = strtoupper(substr(PHP_OS, 0, 3))==='WIN';
        }
        return $this->isWindows;
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
        if ($this->tmp===null) {
            if (function_exists('sys_get_temp_dir')) {
                $this->tmp = sys_get_temp_dir();
            } elseif ( ($tmp = getenv('TMP')) || ($tmp = getenv('TEMP')) || ($tmp = getenv('TMPDIR')) ) {
                $this->tmp = realpath($tmp);
            } else {
                $this->tmp = '/tmp';
            }
        }

        return $this->tmp;
    }

    /**
     * @param string $filename the filename of the output file
     * @return string the wkhtmltopdf command string
     */
    public function getCommand($filename)
    {
        $command = $this->escape($this->getBin());

        $command .= $this->renderOptions($this->options);

        foreach($this->objects as $object)
        {
            $command .= ' '.$this->escape($object['input']);
            unset($object['input']);
            $command .= $this->renderOptions($object);
        }

        return $command.' '.$this->escape($filename);
    }

    /**
     * @return mixed the temporary PDF filename or false on error (triggers PDf creation)
     */
    public function getPdfFilename()
    {
        if ($this->tmpFile===null) {
            $tmpFile = tempnam($this->getTmpDir(),'tmp_WkHtmlToPdf_');

            if ($this->createPdf($tmpFile)===true) {
                $this->tmpFile = $tmpFile;
            } else {
                return false;
            }
        }

        return $this->tmpFile;
    }

    /**
     * Create the temporary PDF file
     */
    protected function createPdf($fileName)
    {
        $command = $this->getCommand($fileName);

        if($this->enableXvfb) {
            $command = $this->xvfbRunCommand($command);
        }

        // we use proc_open with pipes to fetch error output
        $descriptors = array(
            2   => array('pipe','w'),
        );
        $process = proc_open($command, $descriptors, $pipes, null, $this->procEnv, array('bypass_shell'=>true));

        if (is_resource($process)) {

            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            $result = proc_close($process);

            if ($result!==0) {
                if (!file_exists($fileName) || filesize($fileName)===0) {
                    $this->error = "Could not run command $command:\n$stderr";
                } elseif (!$this->ignoreWarnings) {
                    $this->error = "Warning: An error occured while creating the PDF:\n$stderr";
                }
            }
        } else {
            $this->error = "Could not run command $command";
        }

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
            if (is_numeric($key)) {
                $out .= " --$val";
            } elseif (is_array($val)) {
                foreach($val as $vkey => $vval) {
                    if(is_numeric($vkey)) {
                        $out .= " --$key ".$this->escape($vval);
                    } else {
                        $out .= " --$key ".$this->escape($vkey).' '.$this->escape($vval);
                    }
                }
            } else {
                $out .= " --$key ".$this->escape($val);
            }

        return $out;
    }

    /**
     * @param mixed $val value to escape
     * @return string the escaped value if enableEscaping is set. Unchanged value otherwhise.
     */
    protected function escape($val)
    {
        return $this->enableEscaping ? escapeshellarg($val) : $val;
    }

    /**
     * Wrap the given command in a call to xvfb-run
     *
     * @param string $command the command to wrap in xvfb-run
     * @return string the command string with the xvfb-run call prepended
     */
    protected function xvfbRunCommand($command)
    {
        $xvfbRun = $this->getXvfbRunBin();
        if(!$xvfbRun) {
            return $command;
        }

        return $xvfbRun.$this->xvfbRunOptions.$command;
    }
}
