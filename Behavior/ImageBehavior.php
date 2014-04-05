<?php

// require_once('AbstractBehavior.php');
// require_once('BehaviorInterface.php');

namespace WkHtmlToPdf\Behavior;

class ImageBehavior extends AbstractBehavior implements BehaviorInterface {
	public function getContentType() {
		return "image/jpeg";
	}

    /**
     * @param string $filename the filename of the output file
     * @return string the wkhtmltopdf command string
     */
    public function getCommand($filename)
    {
        $command = $this->enableEscaping ? escapeshellarg($this->getBin()) : $this->getBin();

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
     * @return mixed the temporary file filename or false on error (triggers file creation)
     */
    public function getFilename()
    {
        if ($this->tmpFile===null) {
	    	$filename = preg_replace('/\..+/', '', basename(tempnam($this->getTmpDir(),'tmp_WkHtmlToPdf_')));
	        $tmpFile = $this->getTmpDir() . '/'. $filename . '.jpg';
            //$tmpFile = tempnam($this->getTmpDir(),'tmp_WkHtmlToPdf_');

            if ($this->createFile($tmpFile)===true) {
                $this->tmpFile = $tmpFile;
            } else {
                return false;
            }
        }

        return $this->tmpFile;
    }
}