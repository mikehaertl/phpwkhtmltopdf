<?php

namespace WkHtmlToPdf\Behavior;

/**
 * @author Oleksandr Knyga <oleksandrknyga@gmail.com>
 * @license http://www.opensource.org/licenses/MIT
 */
class PdfBehavior extends AbstractBehavior implements BehaviorInterface {
	public function getContentType() {
		return "application/pdf";
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
}