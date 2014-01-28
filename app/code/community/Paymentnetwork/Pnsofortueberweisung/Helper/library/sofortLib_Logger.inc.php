<?php
/// \cond
/**
 * A basic implementation of logging mechanism intended for debugging
 *
 * Copyright (c) 2012 Payment Network AG
 *
 * $Date: 2012-05-21 16:53:26 +0200 (Mo, 21. Mai 2012) $
 * @version SofortLib 1.5.0  $Id: sofortLib_Logger.inc.php 4191 2012-05-21 14:53:26Z niehoff $
 * @author Payment Network AG http://www.payment-network.com (integration@sofort.com)
 *
 */
class SofortLibLogger {
	
	public $fp = null;
	
	public $maxFilesize = 1048576;
	
	
	public function SofortLibLogger() {
		// intentionally left empty
	}
	
	
	/**
	 * Logs $msg to a file which path is being set by it's unified ressource locator
	 * @param String $message
	 * @param URI $uri
	 */
	public function log($message, $uri) {
		if ($this->logRotate($uri)) {
			$this->fp = fopen($uri, 'w');
			fclose($this->fp);
		}
		
		$this->fp = fopen($uri, 'a');
		fwrite($this->fp, '['.date('Y-m-d H:i:s').'] '.$message."\n");
		fclose($this->fp);
	}
	
	
	/**
	 * Copy the content of the logfile to a backup file if file size got too large
	 * Put the old log file into a tarball for later reference
	 * @param URI $uri
	 */
	public function logRotate($uri) {
		$date = date('Y-m-d_h-i-s', time());
		
		if (file_exists($uri)) {
			if ($this->fp != null && filesize($uri) != false && filesize($uri) >= $this->maxFilesize) {
				$oldUri = $uri;
				// file ending
				$ending = $ext = pathinfo($oldUri, PATHINFO_EXTENSION);
				$newUri = dirname($oldUri).'/log_'.$date.'.'.$ending;
				rename($oldUri, $newUri);
				
				if (file_exists($oldUri)) {
					unlink($oldUri);
				}
				
				return true;
			}
		}
		
		return false;
	}
}
/// \endcond
?>