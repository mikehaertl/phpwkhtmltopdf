<?php

//require_once('lib/phpwkhtmltopdf/WkHtmlToPdf.php');
namespace WkHtmlToPdf;

/**
 * The DirWkHtmlToPdf looks for html files in provided directory and converts them to pdf files
 * Author: Oleksandr Knyga <oleksandrknyga@gmail.com>
 */
class DirWkHtmlToPdf {
	/**
	 * WkHtmlToPdf wrapper object
	 * Goal: convert html to pdf
	*/
	private $wkHtmlToPdf = null;

	/**
	 * WkHtmlToPdf wrapper object
	 * Goal: convert html to image
	*/
	private $wkHtmlToImage = null;

	/**
	 * Directory name to scan
	 * @var String or null
	 */
	private $inputDir = null;

	/**
	 * Directory to save files
	 * @var String or null
	 */
	private $outputDir = null;

	/**
	 * Extension to search
	 * @var string
	 */
	private $extension = 'html';

	public function __construct($wkHtmlToPdfOptions, $wkHtmlToImageOptions, $inputDir, $outputDir, $extension = 'html') {
		$this->wkHtmlToPdf = new WkHtmlToPdf($wkHtmlToPdfOptions);
		$this->wkHtmlToImage = new WkHtmlToPdf($wkHtmlToImageOptions, WkHtmlToPdf::IMAGE);
		$this->inputDir = $inputDir;
		$this->outputDir = $outputDir;
		$this->extension = $extension;
	}

	/**
	 * Get files iterator with extension needed
	 * @return RegexIterator Iterator throught files
	 */
	private function getFilesIterator() {
		$pattern = '/^.+\.'.$this->extension.'$/i';
		$directory = new \RecursiveDirectoryIterator($this->inputDir);
		$iterator = new \RecursiveIteratorIterator($directory);
		$regex = new \RegexIterator($iterator, $pattern, \RecursiveRegexIterator::GET_MATCH);

		return $regex;
	}

	/**
	 * Get files array with extension needed
	 * @return array Array of files
	 */
	private function getFiles() {
		$it = $this->getFilesIterator();
		$arr = array();

		foreach($it as $file) {
			$arr[] = preg_replace('/\\\/i', '/', $file[0]);
		}

		return $arr;
	}

	public function convertFiles() {
		$arr = $this->getFiles();

		foreach($arr as $file) {
			$cloneWkHtmlToPdf = clone $this->wkHtmlToPdf;
			$cloneWkHtmlToImage = clone $this->wkHtmlToImage;

			$pinfo = pathinfo($file);
			preg_match('/[^\/\\\]+$/', $pinfo['dirname'], $matches);
			$dirname = $this->outputDir . '/' . $matches[0];
			$filename = $pinfo['filename'];

			if(!is_dir($dirname)) {
				mkdir($dirname);	
			}
			
			$cloneWkHtmlToPdf->addPage($file);
			$cloneWkHtmlToPdf->saveAs("$dirname/$filename.pdf");

			$cloneWkHtmlToImage->addPage($file);
			$cloneWkHtmlToImage->saveAs("$dirname/$filename.jpg");			
		}
	}
}