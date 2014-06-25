<?php

class TransposonSearch {
	public $reads;
    public $results;
    public $MAX_DISTANCE;

    public function __construct($opts) {
    	$this->reads = array();
      	$this->results = array();
	    $this->MAX_DISTANCE = 100000;
	    if ( array_key_exists("max_distance", $opts)) {
	       $this->MAX_DISTANCE = $opts["max_distance"];
	    }
    }

	public function parseCSV( $fh ) {
    	$lc = 0;
	    $ok = 0;
      	while (!feof($fh)) {
	      $line = fgets($fh);
		  if (strlen($line) < 10) {
		  	continue;
		  }

		  $data = explode(',', $line);
		  if ($this->addRead($data)) {
		  	$ok++;
		  } else {
		  	echo "Invalid number of fields at line $lc";
		  }
		  $lc++;
	    }

	    usort($this->reads, 
	     	function($a, $b) {
	     	  #return (strcmp($a["g"], $b["g"]) * 100 + strcmp($a["chr"], $b["chr"]) * 10 + ($a["s"] > $b["s"]));
	     	  return (strcmp($a["chr"], $b["chr"]) * 10 + ($a["s"] > $b["s"]));
		}
	    );

	    if ($ok < $lc) {
	      echo $lc - $ok , " invalid entries\n";
	    }
	    return $ok;
	}

    public function addRead( $data ) {
    	if (sizeof($data) < 7) {
	      return 0;
	    }

	    if ($data[6] === '-') {
	     	# indicates no corresponding gene is found 
	      $data[6] = '';
	    }

      	$entry = array(
	    	"read" => $data[0],
	     	"chr" => $data[1],
		    "s" => $data[2],
		    "e" => $data[3],
		    "l" => $data[4],
		    "r" => $data[5],
		    "g" => $data[6]
	    );
	  
	    array_push($this->reads, $entry);
	    return 1;
	}

    public function analizeReads() {
    	$results = array();

      	foreach ($this->reads as &$r) {
          $new_group = 1;
	  	  # try to find if the read is close enough to any of the already processed ones
	  	  foreach ($results as &$e) {
	  	  	# compare the coordinates of the read to the last read of the group
	  	  	$le = end($e["reads"]);
	  	  	reset($e["reads"]);
	    	if ($this->closeEnough($le, $r)) {
	      	  array_push($e["reads"], $r);
              $new_group = 0;
              break;
	    	}
	  	  }
          
          # if we the read is not close to any of the existing groups create a new group
	  	  if ($new_group) {
	    	$label = $r["g"] ? $r["g"] :  sprintf("%s:%d-%d", $r["chr"], $r["s"], $r["e"]);
	    	if (! array_key_exists($label, $results)) {
	      	  $results[$label] = array();
	          $results[$label]["reads"] = array();
	    	}
	    	array_push($results[$label]["reads"], $r);
	  	  }
		}
	
		# we are only interested in groups that have two or more reads
		$this->results = array_filter($results, function($el) {
	    	if (sizeof($el["reads"]) > 1) {
	      		return 1;
	    	}
	    	return 0;
		});
		
		
        return sizeof($this->results);
	}

    public function closeEnough($a, $b) {
    	if ( $a["chr"] === $b["chr"]) {
    	  $sa = $a["s"];
    	  $ea = $a["e"];
    	  if ($sa > $ea) {
       	    $sa = $ea;
       	    $ea = $a["s"];
    	  }

    	  $sb = $b["s"];
    	  $eb = $b["e"];
    	  if ($sb > $eb) {
       	    $sb = $eb;
       	    $eb = $b["s"];
    	  }
	    
    	  $ca = $sa + ($ea - $sa) / 2;
    	  $cb = $sb + ($eb - $sb) / 2;
    	  if (abs($ca - $cb) < $this->MAX_DISTANCE) {
            return 1;
    	  }
  		}
  		return 0;
	}
      
    public function printResultsAsHTML() {
    	$c = 0;
	echo '
<style>
.same {
    background-color: #98f5ff;
}

.diff {
    background-color: #f08080;
}

.error {
    background-color: #ffee99;
}

</style>
<table style="font-size:80%">
	';

        foreach ($this->results as $gr => $d ) {
	  		$reads = array();
          	foreach ($d["reads"] as &$r) {
	    		$reads[$r["read"]] = 1;
	  		}
	  		$eclass = 'same';
	  		if (sizeof($reads) > 1) {
	    		$eclass = 'diff';
	    		$c++;
	  		}
          	foreach ($d["reads"] as &$p) {
	    		echo '<tr class="'.$eclass.'"><td>', join ('</td><td>', array($p["read"], $p["chr"], $p["s"], $p["e"], $p["r"], $p["l"], $p["g"])), '</td></tr>';
	  		}
	  		echo '<tr><td colspan=10>&nbsp;</td></tr>';
	  		
		}
		echo '
</table>
</body>
</html>
';
		return $c;
	}

}

?>