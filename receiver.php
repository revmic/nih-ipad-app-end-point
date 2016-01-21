<?php
/**
 *
 * Data store end-point for NIH Toolbox iPad application
 *
 * This end-point responds to two requests, a
 * 'test' and a 'store' request. On 'test' the end-point will
 * respond with a short JSON message:
 *  { "error": 0, "message": "ok" }
 * on send it expects one or more files with the following
 * POST arguments: action=store
 * .
 *
 * Usage:
 *
 * Login:
 *   curl -F "action=test" https://abcd-report.ucsd.edu/applications/ipad-app/d/sA.php
 *   Result: Error HTML "Unauthorized"
 *
 *   curl --user <user name> -F "action=test" https://abcd-report.ucsd.edu/applications/ipad-app/d/sA/r.php
 *   Result: asks for password for the given user, responds with  { "message": "ok" }
 *
 * Store files:
 *   curl --user <user name> -F "site=A" -F "action=store" https://abcd-report.ucsd.edu/applications/ipad-app/d/sA/r.php
 *   Result: Error json message: {"message":"Error: no files attached to upload"}
 *
 *   echo "1,2,3,4" > test.csv
 *   curl --user <user name> -F "site=A" -F "action=store" -F "upload=@test.csv" https://abcd-report.ucsd.edu/applications/ipad-app/d/sA/r.php
 *   Result: A single file is stored
 *  
 *   echo "1,2,3,4,5" > test2.csv
 *   curl --user <user name> -F "site=A" -F "action=store" -F "upload[]=@test.csv" -F "upload[]=@test2.csv" https://abcd-report.ucsd.edu/applications/ipad-app/d/sA/r.php
 *   Result: Two files are stored on the server
 *
 */

if (!isset($_SERVER['PHP_AUTH_USER'])) {
   echo (json_encode( array( "message" => "Error: no user logged in" ), JSON_PRETTY_PRINT ) );
   return;
}

$action = 'test'; // 'test' or 'store'
$site = explode("/", getcwd());
if (count($site) > 2) {
  $site = $site[count($site)-1];
} else {
  return;
}

if (isset($_POST['action'])) {
  $action = $_POST['action'];
}

$party = $_SERVER['REMOTE_ADDR'];
if (strlen($party) > 0) {
  // sanitize the name before using it
  $party = mb_ereg_replace("([^\w\s\d\-_~,;:\[\]\(\).])", '', $party);
  $party = mb_ereg_replace("([\.]{2,})", '', $party);
}

function repError( $msg ) {
   echo (json_encode( array( "error" => 1, "message" => $msg ), JSON_PRETTY_PRINT ) );
   return;
}
function repOk( $msg ) {
   echo (json_encode( array( "error" => 0, "message" => $msg ), JSON_PRETTY_PRINT ) );
   return;
}

if ($action == 'test') {
   repOk("ok");
} elseif ($action == 'store') {
   if ($_FILES['upload']) {
     $uploads_dir = '/var/www/html/applications/ipad-app/d/'.$site;
     if (!is_dir($uploads_dir)) {
        if (!mkdir($uploads_dir, 0777, true)) {
	   repError( "Error: Failed to create site directory for storage" );
        }
     }
     // either we get a single file uploaded or a whole lot of them
     if (is_array($_FILES["upload"]["error"])) {
        $count = 0;
        foreach ($_FILES["upload"]["error"] as $key => $error) {
            if ($error == UPLOAD_ERR_OK) {
               $tmp_name = $_FILES["upload"]["tmp_name"][$key];
	       # sanitize the name here
 	       $name = $_FILES["upload"]["name"][$key];
	       // sanitize the name before using it
	       $name = mb_ereg_replace("([^\w\s\d\-_~,;:\[\]\(\).])", '', $name);
	       $name = mb_ereg_replace("([\.]{2,})", '', $name);
	       $name = $name."_".$party."_".date(DATE_ATOM);

	       $ok = move_uploaded_file($tmp_name, $uploads_dir."/".$name);
               if ($ok) {
                  $count = $count + 1;
	       } else {
	       	  repError( "Error: failed storing file $uploads_dir/$name" );
               }
            } else {
	       repError( "Error: upload error" );
            }
        }
	if ($count > 0) {
           repOk("Info: ".$count." file".($count > 1?"s":"")." stored" );
        } else {
           repError("Error: no file was stored." ); // there is such a thing as too much error checking
        }
     } else { // if we only get a single file uploaded
        if ( $_FILES["upload"]["error"] == UPLOAD_ERR_OK ) {
           $tmp_name = $_FILES["upload"]["tmp_name"]; 
 	   $name = $_FILES["upload"]["name"];
           // sanitize the name before using it
           $name = mb_ereg_replace("([^\w\s\d\-_~,;:\[\]\(\).])", '', $name);
           $name = mb_ereg_replace("([\.]{2,})", '', $name);
           $name = $name."_".$party."_".date(DATE_ATOM);
	   $ok = move_uploaded_file($tmp_name, $uploads_dir."/".$name);
           if ($ok) {
	      repOk( "Info: file stored" );
	   } else {
	      repError( "Error: failed storing file $uploads_dir/$name" );
           }
        } else {
	    repError( "Error: upload error" );
        }
     }
   } else {
     repError( "Error: no files attached to upload" );
   }
} else {
     repError( "Error: unknown action" );
}

?>