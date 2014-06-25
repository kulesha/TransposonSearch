<html>
 <head>
  <title>Transposon search</title>
 </head>
 <body>
<h2>Transposon search</h2>

<form method="post" action="#" enctype="multipart/form-data">
Choose a file in CSV format: <input type="file" name="src_file" /> <br/>
<!--Enter URL : <input type="text" name="src_url" value="https://s3-eu-west-1.amazonaws.com/ek-bucket-test/traspozon.csv"/> <br/>
-->
Enter URL : <input type="text" name="src_url" value="http://localhost:8092/test/sample.csv"/> <br/>

<?php
$md = 1000;
if (array_key_exists("distance", $_POST)) {
  $md = $_POST["distance"];
}

echo "Max distance : <input type='text' name='distance' value='$md'/> <br/>";

?>

<input type="submit" name="search" value="Find"/>
</form>

<?php

if (array_key_exists("search", $_POST)) {
   include_once("lib/TransposonSearch.php");
   $opts = array();
   $opts["max_distance"] = $_POST["distance"];
   $ts = new TransposonSearch($opts);
   $total = 0;
   
   if (array_key_exists("src_file", $_FILES)) {
   	 $source = $_FILES["src_file"];
   	 if ($source && $source["name"]) {
   	 	try {
 	   			if ( !isset($source['error']) || is_array($source['error'])) {
        			throw new RuntimeException('Invalid parameters.');
       			}
   	 			switch ($source['error']) {
        		case UPLOAD_ERR_OK:
            		break;
        		case UPLOAD_ERR_NO_FILE:
            		throw new RuntimeException('No file sent.');
        		case UPLOAD_ERR_INI_SIZE:
        		case UPLOAD_ERR_FORM_SIZE:
            		throw new RuntimeException('Exceeded filesize limit.');
        		default:
            		throw new RuntimeException('Unknown errors.');
        		}
        		
        		$fname = $source["tmp_name"];
     	echo "Parsing $fname <br/>";
     	$fh = fopen($fname, "r");
     	$total = $ts->parseCSV($fh);
     	fclose($fh);
        		
        } catch (RuntimeException $e) {
        	echo "Exception $e ";              	
    	}    
   	 }
   }
   
     if (!$total && array_key_exists("src_url", $_POST)) {
     	$fname = $_POST["src_url"];
     	echo "Parsing $fname <br/>";
     	$fh = fopen($fname, "r");
     	$total = $ts->parseCSV($fh);
     	fclose($fh);
   	}
   
   if ($total) {     
   	$gr_num = $ts->analizeReads();		 
   	$diffs = $ts->printResultsAsHTML();
   	$distance = $ts->MAX_DISTANCE;   	
	 
     echo "<hr/>Total reads: $total<br/>Max distance: $distance<br/>Close groups: $gr_num<br/>Different reads: $diffs";
     
   }
	
#   echo "TS : ", var_dump($ts), "<br/>";
}



?>

 </body>
</html>		
