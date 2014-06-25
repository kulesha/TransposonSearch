<?php
require "../lib/TransposonSearch.php";

class mainTest extends PHPUnit_Framework_TestCase {

      protected function setUp() {
      		$this->csv_file = 'sample.csv';
		$this->assertEquals(true, file_exists($this->csv_file));
		$opts = array();
		$opts["max_distance"] = 1000;
		$this->ts = new TransposonSearch($opts);
      }
      public function testParser() {
      	     $fh = fopen($this->csv_file, "r");
      	     $this->assertEquals(284, $this->ts->parseCSV($fh));
	     fclose($fh);

      	     $this->assertEquals(50, $this->ts->analizeReads());

      	     $this->assertEquals(2, $this->ts->printResultsAsHTML());
      }


}

?>