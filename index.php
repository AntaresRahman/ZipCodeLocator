<!--
index.php
ZipCodeLocator is a website that serves as a zip code locator
- the user clicks a point on the US map, and
- the web page displays the closest zip codes nearest to that point
-->

<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="initial-scale = 1, user-scalable = no">
    <link rel="stylesheet" type="text/css" href="css/style.css" media="screen" />
<!--    <script src="js/Chart.js"></script>-->
	<title>Zip Locater</title>
</head>

<body>
	<div>
          
        <form id="h1" method="get" action="index.php">
          ZIP CODE LOCATOR
          &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
          <input id="button-top" type="submit" name="createDB-button" value="Create DB">
          <input id="button-top" type="submit" name="dropDB-button" value="Drop DB">
        </form>
          
    	<article>
          <div id="canvasFrame">
            <canvas id="myCanvas" width="1300" height="600">
                Your browser does not support the canvas element.
            </canvas>
          </div>
        </article>

        <div id="form">
          <form method="get" action="index.php">
              Latitude:  <input id="ypos" type="text" name="ypos" readonly="readonly">
              Longitude:  <input id="xpos" type="text" name="xpos" readonly="readonly">
            &nbsp;

            Items per page  
            <select id="box" name="items" type="submit">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="20">20</option>
            </select>

            &nbsp;

            <input id="box" type="submit" name="nearby-button" value="List Nearby Zip Codes">
          </form>
  
          <?php	
              function latLonToMiles($lat1, $lon1, $lat2, $lon2){  //haversine formula
                  $R = 3961;  // radius of the Earth in miles
                  $dlon = ($lon2 - $lon1)*M_PI/180;
                  $dlat = ($lat2 - $lat1)*M_PI/180;
                  $lat1 *= M_PI/180;
                  $lat2 *= M_PI/180;
                  $a = pow(sin($dlat/2),2) + cos($lat1) * cos($lat2) * pow(sin($dlon/2),2) ;
                  $c = 2 * atan2( sqrt($a), sqrt(1-$a) ) ;
                  $d = number_format($R * $c, 2);
                  return $d;	
              }

              $db_conn = mysql_connect("localhost", "root", "");
              if (!$db_conn)
                  die("Unable to connect: " . mysql_error());

      //        click createDB-button
              if (isset($_GET['createDB-button'])) {
                $retval = mysql_query( "CREATE DATABASE zipDB;", $db_conn );
                if ($retval)
                    echo "Database ready!";
                else //DB already exists
                    echo "Database already exists!";

                mysql_select_db("zipDB", $db_conn);
                $cmd = "CREATE TABLE zip(
                          zip varchar(5) NOT NULL PRIMARY KEY,
                          city varchar(35),
                          state varchar(2),
                          lat float(7,4),
                          lon float(7,4),
                          time int(3) 
                        );";
              }

      //        click dropDB-button
              if (isset($_GET['dropDB-button'])) {
                $retval = mysql_query( "DROP DATABASE zipDB;", $db_conn );
                if(!$retval ) //DB doesn't exist
                    echo('No database available!');
                else
                  echo "Database deleted successfully!";	
              }

      //        click nearby-button
              if (isset($_GET['nearby-button'])) {
                if (($_GET['ypos'])>0) {
                  mysql_query("DROP DATABASE zipDB;", $db_conn );
                  sleep(1);										
                  mysql_query("CREATE DATABASE zipDB;", $db_conn);
                  // --- CREATE THE TABLE
                  mysql_select_db("zipDB", $db_conn);
                  $cmd = "CREATE TABLE zip (
                            zip varchar(5) NOT NULL PRIMARY KEY,
                            city varchar(35),
                            state varchar(2),
                            lat float(7,4),
                            lon float(7,4),
                            time int(3)
                      );";
                  mysql_query($cmd);

                  $cmd = "LOAD DATA LOCAL INFILE 'zip_codes_usa.csv' INTO TABLE zip
                          FIELDS TERMINATED BY ',';";
                  mysql_query($cmd);

                  $pointerLon = $_GET['xpos'];
                  $pointerLat = $_GET['ypos'];
                  $numitems = $_GET['items'];

                  $cmd = "SELECT *, 
                              SQRT(POW((lon-$pointerLon),2)+POW((lat-$pointerLat),2)) as distance
                          FROM zip ORDER BY distance ASC limit $numitems;";
                  $records = mysql_query($cmd);

                  //create table
                  echo "<table>".PHP_EOL;	
                  echo( "<tr><td>Zip Code</td><td>City</td><td>State</td><td>Lat</td><td>Lon</td><td>Distance (miles)</td><td>Time Difference (ET)</td></tr>" . PHP_EOL ); 	
                  while($row = mysql_fetch_array($records)){
                       echo( "<tr><td id='zipCol'>" .$row['zip'] . "</td> <td id='cityCol'>" .$row['city']. "</td> <td id='stateCol'>" 
                       .$row['state']."</td><td id='latCol'>" .$row['lat']."</td> <td id='lonCol'>" 
                       .$row['lon']. "</td> <td id='distCol'>" .(latLonToMiles($row['lat'], $row['lon'], $pointerLat, $pointerLon)).
                       "</td> <td id='timeCol'>" .$row['time']. "</td></tr>" . PHP_EOL ); 
                  }
                  echo " </table>".PHP_EOL;

                  echo "<script> items.value = " . $numitems. "</script>";
                }

                else
                  echo "Please click on the map to specify a location.";
              }

              mysql_close($db_conn);
          ?>
        </div>
    </div>

    <script type="text/javascript">   
      
        var canvas=document.getElementById("myCanvas");
        var c=canvas.getContext("2d");
      
        //draws map and target symbol
        //stores and fetches old target location for lat, lon and target
        function draw() {        
            var img = new Image();  
            var w, h;

            img.onload = function() {  			  	  
                w=canvas.width;		// resize the canvas to the new image size
                h=canvas.height;					
                c.drawImage(img, 0, 0, w, h );

                if( sessionStorage.getItem("storedY")) {
                    drawCircle(sessionStorage.storedX, sessionStorage.storedY);		
                    xpos.value = sessionStorage.storedtx;
                    ypos.value = sessionStorage.storedty;
                }
            } 
			//static map image taken from Google Inc.
            img.src ="http://maps.googleapis.com/maps/api/staticmap?center="
                      +"38.3699392,-95.0512334&zoom=4&size=1300x340&sensor=false";
        }

        //gets mouse click position
        function getMousePos(canvas, events){
            var obj = canvas;
            var top = 0, left = 0;
            var mX = 0, mY = 0;
            while (obj && obj.tagName != 'BODY') { //accumulate offsets up to 'BODY'
                top += obj.offsetTop;
                left += obj.offsetLeft;
                obj = obj.offsetParent;
            }
            mX = events.clientX - left + window.pageXOffset;
            mY = events.clientY - top + window.pageYOffset;
            return { x: mX, y: mY };
        }

        //creates the target symbol
        function drawCircle(x, y) {
            c.beginPath();
            c.arc(x,y,2,0,2*Math.PI);
            c.fillStyle = 'white';
            c.strokeStyle = "#33CCCC";
            c.fill();
            c.stroke();
            c.closePath();
          
            c.beginPath();
            c.arc(x,y,15,0,2*Math.PI);
            c.fillStyle = 'rgba(0,0,0,0.1)';
            c.strokeStyle = "black";
            c.fill();
            c.stroke();
            c.closePath();
        }

        //main function that takes of calculations and drawing
        window.onload = function(){
            draw();
            canvas.addEventListener('mousedown', function(events){
              var mousePos = getMousePos(canvas, events);
              var tx = document.getElementById("xpos");
              tx.value = (0.04333*mousePos.x - 123.1208).toFixed(4); //lon
              var ty = document.getElementById("ypos");
              ty.value = (-0.0374*mousePos.y + 49.5998).toFixed(4); //lat
              
              //stores old target position and lat, lon values
              if(typeof(Storage)!=="undefined") {
                  sessionStorage.storedX = mousePos.x;
                  sessionStorage.storedY = mousePos.y;
                  sessionStorage.storedtx = tx.value;
                  sessionStorage.storedty = ty.value;
              }
              sessionStorage.clicked = true; //if map was clicked
              draw();
              
            }, false);
        }

    </script>

</body>
</html>