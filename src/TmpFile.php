<?php
namespace mikehaertl\wkhtmlto;

/**
 * TmpFile
 *
 * A utility class to create/destroy files with temporary content:
 *
 *   $tmp = new TmpFile('test content', '/tmp', '.html');
 *   $path = $tmp->getFileName();
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 * @version 2.0.0-dev
 * @license http://www.opensource.org/licenses/MIT
 */
class TmpFile
{
    protected $_fileName;

    /**
     * Constructor
     *
     * @param string $content the tmp file content
     * @param string|null $suffix the optional suffix for the tmp file
     * @param string|null $directory directory where the file should be created. Autodetected if not provided.
     * @return void
     */
    public function __construct($content, $suffix = null, $directory = null)
    {
        if ($directory===null) {
            $directory = self::getTempDir();
        }

        $this->_fileName = tempnam($directory,'tmp_wkthmlto_');
        if ($suffix!==null) {
            $newName = $this->_fileName.$suffix;
            rename($this->_fileName, $newName);
            $this->_fileName = $newName;
        }
        file_put_contents($this->_fileName, $content);
    }

    /**
     * Delete tmp file on shutdown
     */
    public function __destruct()
    {
        unlink($this->_fileName);
    }

    /**
     * Send tmp file to client, either inline or as download
     *
     * @param string|null $filename the filename to send. If empty, the file is streamed inline.
     * @param string the Content-Type header
     * @param bool $inline whether to force inline display of the file, even if filename is present.
     */
    public function send($name = null, $contentType, $inline = false)
    {
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: '.$contentType);
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '.filesize($this->_fileName));

        if ($name!==null || $inline) {
            $disposition = $inline ? 'inline' : 'attachment';
            header("Content-Disposition: $disposition; filename=\"$name\"");
        }

        readfile($this->_fileName);
    }

    /**
     * @return string the full file name
     */
    public function getFileName()
    {
        return $this->_fileName;
    }

    /**
     * @return string the path to the temp directory
     */
    public static function getTempDir()
    {
        if (function_exists('sys_get_temp_dir')) {
            return sys_get_temp_dir();
        } elseif ( ($tmp = getenv('TMP')) || ($tmp = getenv('TEMP')) || ($tmp = getenv('TMPDIR')) ) {
            return realpath($tmp);
        } else {
            return '/tmp';
        }
    }

    /**
     * @return string the full file name
     */
    public function __toString()
    {
        return $this->_fileName;
    }
}
