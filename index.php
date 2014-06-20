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
if (array_key_exists("search", $_POST)) {
   echo "<h3> Performing search ... </h3>";
}

echo "Distance : ", $_POST["mind"], "<br/>";

?>

 </body>
</html>		
