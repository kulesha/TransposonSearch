<html>
 <head>
  <title>Transposon search</title>
 </head>
 <body>
<h2>Transposon search</h2>

<form method="post" action="#" enctype="multipart/form-data">
Choose a file in CSV format: <input type="file" name="src" /> <br/>
Max distance : <input type="text" name="mind" value="1000"/> <br/>
<input type="submit" name="search" value="Find"/>
</form>
<?php
MAX_DISTANCE = 1000;

if (array_key_exists("search", $_GET)) {
   echo "Performing search ... ";

}
?>
 </body>
</html>		
