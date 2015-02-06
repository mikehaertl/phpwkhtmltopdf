<?php
namespace mikehaertl\wkhtmlto;

use mikehaertl\tmp\File;

/**
 * Pdf
 *
 * This class is a slim wrapper around wkhtmltopdf.
 *
 * @author  Michael Härtl <haertl.mike@gmail.com>
 * @version 2.0.2-dev
 * @license http://www.opensource.org/licenses/MIT
 */
class Pdf
{
    // Regular expression to detect HTML strings
    const REGEX_HTML = '/<html/i';

    // prefix for tmp files
    const TMP_PREFIX = 'tmp_wkhtmlto_pdf_';

    /**
     * @var string the name of the `wkhtmltopdf` binary. Default is `wkhtmltopdf`. You can also
     * configure a full path here.
     */
    public $binary = 'wkhtmltopdf';

    /**
     * @var array options to pass to the Command constructor. Default is none.
     */
    public $commandOptions = array();

    /**
     * @var string|null the directory to use for temporary files. If null (default) the dir is autodetected.
     */
    public $tmpDir;

    /**
     * @var bool whether to ignore any errors if some PDF file was still created. Default is false.
     */
    public $ignoreWarnings = false;

    /**
     * @var bool whether the old version 9 of wkhtmltopdf is used (slightly different syntax). Default is false.
     */
    public $version9 = false;

    /**
     * @var bool whether the PDF was created
     */
    protected $isCreated = false;

    /**
     * @var array global options for wkhtmltopdf as array('--opt1', '--opt2'=>'val', ...)
     */
    protected $options = array();

    /**
     * @var array list of wkhtmltopdf objects as arrays
     */
    protected $objects = array();

    /**
     * @var File the temporary PDF file
     */
    protected $tmpPdfFile;

    /**
     * @var array list of tmp file objects. This is here to keep a reference to File and thus avoid
     * too early call of File::__destruct() if the file is not referenced anymore.
     */
    protected $tmpFiles = array();

    /**
     * @var Command the command instance that executes wkhtmltopdf
     */
    protected $command;

    /**
     * @var string the detailed error message. Empty string if none.
     */
    protected $error = '';

    /**
     * @param array|string $options global options for wkhtmltopdf or page URL, HTML or PDF/HTML filename
     */
    public function __construct($options = null)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        } elseif (is_string($options)) {
            $this->addPage($options);
        }
    }

    /**
     * Add a page object to the output
     *
     * @param  string $input   either a URL, a HTML string or a PDF/HTML filename
     * @param  array  $options optional options for this page
     * @return Pdf the Pdf instance for method chaining
     */
    public function addPage($input, $options = array())
    {
        $options = $this->processOptions($options);
        $options['inputArg'] = $this->processInput($input);
        $this->objects[] = $options;
        return $this;
    }

    /**
     * Add a cover page object to the output
     *
     * @param  string $input   either a URL, a HTML string or a PDF/HTML filename
     * @param  array  $options optional options for the cover page
     * @return Pdf the Pdf instance for method chaining
     */
    public function addCover($input, $options = array())
    {
        $options['input'] = ($this->version9 ? '--' : '').'cover';
        $options['inputArg'] = $this->processInput($input);
        $this->objects[] = $options;
        return $this;
    }

    /**
     * Add a TOC object to the output
     *
     * @param  array $options optional options for the table of contents
     * @return Pdf the Pdf instance for method chaining
     */
    public function addToc($options = array())
    {
        $options['input'] = ($this->version9 ? '--' : '')."toc";
        $this->objects[] = $options;
        return $this;
    }

    /**
     * Save the PDF to given filename (triggers PDF creation)
     *
     * @param  string $filename to save PDF as
     * @return bool whether PDF was created successfully
     */
    public function saveAs($filename)
    {
        if (!$this->isCreated && !$this->createPdf()) {
            return false;
        }
        if (!$this->tmpPdfFile->saveAs($filename)) {
            $this->error = "Could not save PDF as '$filename'";
            return false;
        }
        return true;
    }

    /**
     * Send PDF to client, either inline or as download (triggers PDF creation)
     *
     * @param  string|null $filename the filename to send. If empty, the PDF is streamed inline.
     * @param  bool        $inline   whether to force inline display of the PDF, even if filename is present.
     * @return bool whether PDF was created successfully
     */
    public function send($filename = null, $inline = false)
    {
        if (!$this->isCreated && !$this->createPdf()) {
            return false;
        }
        $this->tmpPdfFile->send($filename, 'application/pdf', $inline);
        return true;
    }

    /**
     * Set global option(s)
     *
     * @param  array $options list of global PDF options to set as name/value pairs
     * @return Pdf the Pdf instance for method chaining
     */
    public function setOptions($options = array())
    {
        $options = $this->processOptions($options);
        foreach ($options as $key => $val) {
            if (is_int($key)) {
                $this->options[] = $val;
            } elseif ($key[0]!=='_' && property_exists($this, $key)) {
                $this->$key = $val;
            } else {
                $this->options[$key] = $val;
            }
        }
        return $this;
    }

    /**
     * @return Command the command instance that executes wkhtmltopdf
     */
    public function getCommand()
    {
        if ($this->command === null) {
            $options = $this->commandOptions;
            if (!isset($options['command'])) {
                $options['command'] = $this->binary;
            }
            $this->command = new Command($options);
        }
        return $this->command;
    }

    /**
     * @return string the detailed error message. Empty string if none.
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return string the filename of the temporary PDF file
     */
    public function getPdfFilename()
    {
        if ($this->tmpPdfFile===null) {
            $this->tmpPdfFile = new File('', '.pdf', self::TMP_PREFIX, $this->tmpDir);
        }
        return $this->tmpPdfFile->getFileName();
    }

    /**
     * Run the Command to create the tmp PDF file
     *
     * @return bool whether creation was successful
     */
    protected function createPdf()
    {
        if ($this->isCreated) {
            return false;
        }
        $command = $this->getCommand();
        $fileName = $this->getPdfFilename();

        $command->addArgs($this->options);
        foreach ($this->objects as $object) {
            $command->addArgs($object);
        }
        $command->addArg($fileName, null, true);    // Always escape filename
        if (!$command->execute()) {
            $this->error = $command->getError();
            if (!(file_exists($fileName) && filesize($fileName)!==0 && $this->ignoreWarnings)) {
                return false;
            }
        }
        $this->isCreated = true;
        return true;
    }

    /**
     * @param string $input
     * @return File|string a File object if the input is a html string. The unchanged input otherwhise.
     */
    protected function processInput($input)
    {
        if (preg_match(self::REGEX_HTML, $input)) {
            return $this->tmpFiles[] = new File($input, '.html', self::TMP_PREFIX, $this->tmpDir);
        } else {
            return $input;
        }
    }

    /**
     * @param array $options list of options as name/value pairs
     * @return array options with raw content converted to tmp files where neccessary
     */
    protected function processOptions($options = array())
    {
        foreach ($options as $key => $val) {
            $urlRequired = preg_match('/^(header|footer)-html$/', $key);
            if ($urlRequired
                && !(is_file($val) || preg_match('/^(https?:)?\/\//i', $val) || $val === strip_tags($val))
            ) {
                $options[$key] = new File($val, '.html', self::TMP_PREFIX, $this->tmpDir);
            }
        }
        return $options;
    }
}
