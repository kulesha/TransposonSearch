<?php

class TransposonSearch {
	public $reads;
    public $results;
    public $MAX_DISTANCE;
	public $format;
	public $header;
	public $stats;
		
    public function __construct($opts) {
    	$this->reads = array();
      	$this->results = array();
      	$this->header = array();
	    $this->MAX_DISTANCE = 100000;
	    $this->format = array(0, 1, 2, 3);
		$this->stats["total"] = array();
		$this->stats["close"] = array();
		$this->stats["tcount"] = 0;
		$this->stats["ccount"] = 0;
		    
	    if ( array_key_exists("max_distance", $opts)) {
	       $this->MAX_DISTANCE = $opts["max_distance"];
	    }
	    
	    if ( array_key_exists("format", $opts)) {
	       $this->format = explode(',', $opts["format"]);
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
		  	array_push($this->header,$data);
		  }
		  $lc++;
	    }

	    usort($this->reads, 
	     	function($a, $b) {
	     	  return (strcmp($a["chr_id"], $b["chr_id"]) * 10 + ($a["s"] > $b["s"]));
		}
	    );

#	    if ($ok < $lc) {
#	      echo $lc - $ok , " invalid entries\n";
#	    }
	    return $ok;
	}
	public function addRead( $data ) {
    	$entry = array(
	    	"read" => $data[$this->format[0]],
	    	"chr" => $data[$this->format[1]],
	     	"s" => $data[$this->format[2]],
		    "e" => $data[$this->format[3]]
	    );

        if (!preg_match('/^[0-9XY]+$/', $entry["chr"]) || !ctype_digit($entry["s"]) || !ctype_digit($entry["e"])) {
#        	echo "<br/>Invalid data : ", join(' * ', $data);
        	return 0;
        }
        
        if (array_key_exists($entry["chr"], $this->stats["total"])) {
        	$this->stats["total"][$entry["chr"]]++;
        } else {
        	$this->stats["total"][$entry["chr"]] = 1;
        }
        
	    $entry["chr_id"] = is_numeric($entry["chr"]) ? sprintf("%08d", $entry["chr"]) : $entry["chr"];
	    $entry["org"] = $data;
	    	    
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
	      	  	      	  
	      	  
#	      	  echo "Add to ",$e["label"], "<br/>";
              $new_group = 0;
              break;
	    	}
	  	  }
          
          # if we the read is not close to any of the existing groups create a new group
	  	  if ($new_group) {
#	    	$label = $r["g"] ? $r["g"] :  sprintf("%s:%d-%d", $r["chr"], $r["s"], $r["e"]);
	    	$label = sprintf("%s:%d-%d", $r["chr"], $r["s"], $r["e"]);
	    	
	    	if (! array_key_exists($label, $results)) {
	      	  $results[$label] = array();
	          $results[$label]["reads"] = array();
	          $results[$label]["chr"] = $r["chr"];
#	          $results[$label]["label"] = $label;
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
		
		foreach ($this->results as $grp) {
			if (array_key_exists($grp["chr"], $this->stats["close"])) {
        		$this->stats["close"][$grp["chr"]]++;
        	} else {
        		$this->stats["close"][$grp["chr"]] = 1;
        	}        
		}
		
        return sizeof($this->results);
	}

    public function closeEnough($a, $b) {
    	if ( !strcmp($a["chr"], $b["chr"])) {
    	  $sa = $a["s"];
    	  $ea = $a["e"];
    	  
    	  $sb = $b["s"];
    	  $eb = $b["e"];
    	  
    	  $ca = ( $sa + $ea ) / 2;
    	  $cb = ( $sb + $eb ) / 2;
    	  
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

	    foreach ($this->header as &$line ) {
	    	    		echo '<tr><th>', join ('</th><th>', $line), '</th></tr>';	  	
	    }
	    
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
#	    		echo '<tr class="'.$eclass.'"><td>', join ('</td><td>', array($p["read"], $p["chr"], $p["s"], $p["e"], $p["r"], $p["l"], $p["g"],$p["g1"], $p["g2"])), '</td></tr>';
	    		echo '<tr class="'.$eclass.'"><td>', join ('</td><td>', $p["org"]), '</td></tr>';
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
	
	public function printStatsAsHTML() {
	    echo "<table>";
	    echo "<tr><th>Max distance</th><td>", $this->MAX_DISTANCE, "</td></tr>";
	    echo "<tr><th>Total entries</th><td>", sizeof($this->reads), "</td></tr>";
	    echo "<tr><th>Close entries</th><td>", sizeof($this->results), "</td></tr>";
	    echo "</table>";
if (0) {	    
	    echo "<table>";
	    foreach ($this->stats["total"] as $chr => $num) {
	    	$ccount = array_key_exists($chr, $this->stats["close"]) ? $this->stats["close"][$chr] : 0;
	    	echo "<tr><td>$chr</td><td>$num</td><td>$ccount</td></tr>";
	    }
	    echo "</table>";
}

echo '<h3>Distribution of reads by chromosomes</h3> 	    
	    <div id="chartContainer">
  <script src="http://d3js.org/d3.v3.min.js"></script>
  <script src="http://dimplejs.org/dist/dimple.v2.0.0.min.js"></script>
  <script type="text/javascript">
    var svg = dimple.newSvg("#chartContainer", 800, 400);
    var data = [ ';
    
    foreach ($this->stats["total"] as $chr => $num) {
      $ccount = array_key_exists($chr, $this->stats["close"]) ? $this->stats["close"][$chr] : 0;
	    	
	  echo sprintf("{'Chromosome' : '%s', 'Total' : %d, 'Close' : %d },", $chr, $num, $ccount);
	}    
	  #echo sprintf("{'Chromosome' : '%s', 'Total' : %d, 'Close' : %d}", " ", 0 , 0);
	#  echo "{ }";
	  
    echo '
    ];
     
    var chart = new dimple.chart(svg, data);
//	chart.defaultColors = [
 //new dimple.color("#FF0000", "Blue"), // Set a red fill with a blue stroke
 //new dimple.color("#00FF00"), // Set a green fill with a darker green stroke
 //new dimple.color("rgb(0, 0, 255)") // Set a blue fill with a darker blue stroke
 //]; 
	
	x = chart.addCategoryAxis("x", "Chromosome");
	y = chart.addMeasureAxis("y", "Total");
	y2= chart.addMeasureAxis("y", "Close");
	
	var maxT = Math.max.apply(Math, data.map(function(o){return o.Total;}))
	
    y2.overrideMax = maxT / 2;
	x.addOrderRule("Chromosome");
   chart.addSeries("Total", dimple.plot.bubble, [x,y]);
   chart.addSeries("Close", dimple.plot.bar, [x,y2]);
   //chart.addLegend(530, 100, 60, 300, "Right");
	chart.draw();

  </script>
</div>
';
	}
	

}

?>