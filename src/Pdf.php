<?php
namespace mikehaertl\wkhtmlto;

use mikehaertl\tmp\File;

/**
 * Pdf
 *
 * This class is a slim wrapper around wkhtmltopdf.
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 * @license http://www.opensource.org/licenses/MIT
 */
class Pdf
{
    // Type hints for `addPage()` and `addCover()`
    const TYPE_HTML = 'html';
    const TYPE_XML = 'xml';

    // Regular expression to detect HTML strings
    const REGEX_HTML = '/<(?:!doctype )?html/i';

    // Regular expression to detect XML strings
    const REGEX_XML = '/<\??xml/i';

    // Regular expression to detect URL strings
    const REGEX_URL = '/^(https?:)?\/\//i';

    // Regular expression to detect options that expect an URL or a file name,
    // so we need to create a tmp file for the content.
    const REGEX_OPTS_TMPFILE = '/^((header|footer)-html|(xsl|user)-style-sheet)$/i';

    // Prefix for tmp files
    const TMP_PREFIX = 'tmp_wkhtmlto_pdf_';

    // Maximum length of a file path if PHP_MAXPATHLEN is not defined
    const MAX_PATHLEN = 255;

    /**
     * @var string the name of the `wkhtmltopdf` binary. Default is
     * `wkhtmltopdf`. You can also configure a full path here.
     */
    public $binary = 'wkhtmltopdf';

    /**
     * @var array options to pass to the Command constructor. Default is none.
     */
    public $commandOptions = array();

    /**
     * @var string|null the directory to use for temporary files. If null
     * (default) the dir is autodetected.
     */
    public $tmpDir;

    /**
     * @var bool whether to ignore any errors if some PDF file was still
     * created. Default is false.
     */
    public $ignoreWarnings = false;

    /**
     * @var bool whether the old version 9 of wkhtmltopdf is used (slightly
     * different syntax). Default is false.
     */
    public $version9 = false;

    /**
     * @var bool whether the PDF was created
     */
    protected $_isCreated = false;

    /**
     * @var array global options for `wkhtmltopdf` as `['--opt1', '--opt2' =>
     * 'val', ...]`
     */
    protected $_options = array();

    /**
     * @var array list of wkhtmltopdf objects as arrays
     */
    protected $_objects = array();

    /**
     * @var \mikehaertl\tmp\File the temporary PDF file
     */
    protected $_tmpPdfFile;

    /**
     * @var \mikehaertl\tmp\File[] list of tmp file objects. This is here to
     * keep a reference to `File` and thus avoid too early call of
     * [[File::__destruct]] if the file is not referenced anymore.
     */
    protected $_tmpFiles = array();

    /**
     * @var Command the command instance that executes wkhtmltopdf
     */
    protected $_command;

    /**
     * @var string the detailed error message. Empty string if none.
     */
    protected $_error = '';

    /**
     * @param array|string $options global options for wkhtmltopdf, a page URL,
     * a HTML string or a filename
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
     * @param string $input either a URL, a HTML string or a filename
     * @param array $options optional options for this page
     * @param string|null $type a type hint if the input is a string of known
     * type. This can either be `TYPE_HTML` or `TYPE_XML`. If `null` (default)
     * the type is auto detected from the string content.
     * @return static the Pdf instance for method chaining
     */
    public function addPage($input, $options = array(), $type = null)
    {
        $options['inputArg'] = $this->ensureUrlOrFile($input, $type);
        $this->_objects[] = $this->ensureUrlOrFileOptions($options);
        return $this;
    }

    /**
     * Add a cover page object to the output
     *
     * @param string $input either a URL, a HTML string or a filename
     * @param array $options optional options for the cover page
     * @param string|null $type a type hint if the input is a string of known
     * type. This can either be `TYPE_HTML` or `TYPE_XML`. If `null` (default)
     * the type is auto detected from the string content.
     * @return static the Pdf instance for method chaining
     */
    public function addCover($input, $options = array(), $type = null)
    {
        $options['input'] = ($this->version9 ? '--' : '') . 'cover';
        $options['inputArg'] = $this->ensureUrlOrFile($input, $type);
        $this->_objects[] = $this->ensureUrlOrFileOptions($options);
        return $this;
    }

    /**
     * Add a TOC object to the output
     *
     * @param array $options optional options for the table of contents
     * @return static the Pdf instance for method chaining
     */
    public function addToc($options = array())
    {
        $options['input'] = ($this->version9 ? '--' : '') . 'toc';
        $this->_objects[] = $this->ensureUrlOrFileOptions($options);
        return $this;
    }

    /**
     * Save the PDF to given filename (triggers PDF creation)
     *
     * @param string $filename to save PDF as
     * @return bool whether PDF was created successfully
     */
    public function saveAs($filename)
    {
        if (!$this->_isCreated && !$this->createPdf()) {
            return false;
        }
        if (!$this->_tmpPdfFile->saveAs($filename)) {
            $this->_error = "Could not save PDF as '$filename'";
            return false;
        }
        return true;
    }

    /**
     * Send PDF to client, either inline or as download (triggers PDF creation)
     *
     * @param string|null $filename the filename to send. If empty, the PDF is
     * streamed inline.
     * @param bool $inline whether to force inline display of the PDF, even if
     * filename is present.
     * @param array $headers a list of additional HTTP headers to send in the
     * response as an array. The array keys are the header names like
     * 'Cache-Control' and the array values the header value strings to send.
     * Each array value can also be another array of strings if the same header
     * should be sent multiple times. This can also be used to override
     * automatically created headers like 'Expires' or 'Content-Length'. To suppress
     * automatically created headers, `false` can also be used as header value.
     * @return bool whether PDF was created successfully
     */
    public function send($filename = null, $inline = false, $headers = array())
    {
        if (!$this->_isCreated && !$this->createPdf()) {
            return false;
        }
        $this->_tmpPdfFile->send($filename, 'application/pdf', $inline, $headers);
        return true;
    }

    /**
     * Get the raw PDF contents (triggers PDF creation).
     * @return string|bool The PDF content as a string or `false` if the PDF
     * wasn't created successfully.
     */
    public function toString()
    {
        if (!$this->_isCreated && !$this->createPdf()) {
            return false;
        }
        return file_get_contents($this->_tmpPdfFile->getFileName());
    }

    /**
     * Set global option(s)
     *
     * @param array $options list of global PDF options to set as name/value pairs
     * @return static the Pdf instance for method chaining
     */
    public function setOptions($options = array())
    {
        // #264 tmpDir must be set before calling ensureUrlOrFileOptions
        if (isset($options['tmpDir'])) {
            $this->tmpDir = $options['tmpDir'];
            unset($options['tmpDir']);
        }
        $options = $this->ensureUrlOrFileOptions($options);
        foreach ($options as $key => $val) {
            if (is_int($key)) {
                $this->_options[] = $val;
            } elseif ($key[0] !== '_' && property_exists($this, $key)) {
                $this->$key = $val;
            } else {
                $this->_options[$key] = $val;
            }
        }
        return $this;
    }

    /**
     * @return Command the command instance that executes wkhtmltopdf
     */
    public function getCommand()
    {
        if ($this->_command === null) {
            $options = $this->commandOptions;
            if (!isset($options['command'])) {
                $options['command'] = $this->binary;
            }
            $this->_command = new Command($options);
        }
        return $this->_command;
    }

    /**
     * @return string the detailed error message. Empty string if none.
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * @return string the filename of the temporary PDF file
     */
    public function getPdfFilename()
    {
        if ($this->_tmpPdfFile === null) {
            $this->_tmpPdfFile = new File('', '.pdf', self::TMP_PREFIX, $this->tmpDir);
        }
        return $this->_tmpPdfFile->getFileName();
    }

    /**
     * Run the Command to create the tmp PDF file
     *
     * @return bool whether creation was successful
     */
    protected function createPdf()
    {
        if ($this->_isCreated) {
            return false;
        }
        $command = $this->getCommand();
        $fileName = $this->getPdfFilename();

        $command->addArgs($this->_options);
        foreach ($this->_objects as $object) {
            $command->addArgs($object);
        }
        $command->addArg($fileName, null, true);    // Always escape filename
        if (!$command->execute()) {
            $this->_error = $command->getError();
            if (!(file_exists($fileName) && filesize($fileName) !== 0 && $this->ignoreWarnings)) {
                return false;
            }
        }
        $this->_isCreated = true;
        return true;
    }

    /**
     * This method creates a temporary file if the passed argument is neither a
     * File instance or URL nor contains XML or HTML and is also not a valid
     * file name.
     *
     * @param string|File $input the input argument File to check
     * @param string|null $type a type hint if the input is a string of known
     * type. This can either be `TYPE_HTML` or `TYPE_XML`. If `null` (default)
     * the type is auto detected from the string content.
     * @return \mikehaertl\tmp\File|string a File object if the input is a HTML
     * or XML string. The unchanged input otherwhise.
     */
    protected function ensureUrlOrFile($input, $type = null)
    {
        if ($input instanceof File) {
            $this->_tmpFiles[] = $input;
            return $input;
        } elseif (preg_match(self::REGEX_URL, $input)) {
            return $input;
        } elseif ($type === self::TYPE_XML || $type === null && preg_match(self::REGEX_XML, $input)) {
            $ext = '.xml';
        } else {
            // First check for obvious HTML content to avoid is_file() as much
            // as possible as it can trigger open_basedir restriction warnings
            // with long strings.
            $isHtml = $type === self::TYPE_HTML || preg_match(self::REGEX_HTML, $input);
            if (!$isHtml) {
                $maxPathLen = defined('PHP_MAXPATHLEN') ?
                    constant('PHP_MAXPATHLEN') : self::MAX_PATHLEN;
                if (strlen($input) <= $maxPathLen && is_file($input)) {
                    return $input;
                }
            }
            $ext = '.html';
        }
        $file = new File($input, $ext, self::TMP_PREFIX, $this->tmpDir);
        $this->_tmpFiles[] = $file;
        return $file;
    }

    /**
     * @param array $options list of options as name/value pairs
     * @return array options with raw HTML/XML/String content converted to tmp
     * files where neccessary
     */
    protected function ensureUrlOrFileOptions($options = array())
    {
        foreach ($options as $key => $val) {
            // Some options expect a URL or a file name, so check if we need a temp file
            if (is_string($val) && preg_match(self::REGEX_OPTS_TMPFILE, $key) ) {
                $file = $this->ensureUrlOrFile($val);
                if ($file instanceof File) {
                    $options[$key] = $file;
                }
            }
        }
        return $options;
    }
}
