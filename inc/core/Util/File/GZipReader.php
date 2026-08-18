<?php

/**
 * This file contains class::GZipReader
 * @package Runalyze\Util\File
 */

namespace Runalyze\Util\File;

/**
 * Class for reading big gzipped files
 * 
 * @author Hannes Christiansen
 * @package Runalyze\Util\File
 */
class GZipReader {
	/**
	 * Resource for file
	 * @var resource
	 */
	protected $Resource;

	/**
	 * Constructor
	 * @param string $fileName
	 * @throws \RuntimeException
	 */
	public function __construct($fileName) {
		if (is_readable($fileName)) {
			$this->Resource = gzopen($fileName, 'r');
		}

		if (! $this->Resource) {
			throw new \RuntimeException('The file "'.$fileName.'" couldn\'t be opened for reading.');
		}
	}

	/**
	 * Destructor
	 */
	public function __destruct() {
		if ($this->isOpen()) {
			$this->close();
		}	
	}

	/**
	 * Read one line
	 */
	public function readLine() {
		return gzgets($this->Resource);
	}

	/**
	 * End of file?
	 * @return bool
	 */
	public function eof(): bool {
		return gzeof($this->Resource);
	}

	/**
	 * Is file open?
	 * @return bool
	 */
	protected function isOpen(): bool {
		return null !== $this->Resource;
	}

	/**
	 * Close
	 */
	public function close() {;
		gzclose($this->Resource);

		$this->Resource = null;
	}
}
