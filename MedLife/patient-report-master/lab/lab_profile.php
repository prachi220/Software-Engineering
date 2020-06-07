<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
    <link rel="stylesheet" href="../css/lab_profile_style.css">
    <title>Lab Profile</title>
    <meta charset="utf-8">
</head>
      <?php

session_start();
$current_user = $_SESSION['current_user'];

use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
use MicrosoftAzure\Storage\Blob\Models\PublicAccessType;

use WindowsAzure\Common\ServicesBuilder;
use MicrosoftAzure\Storage\Common\ServiceException;
use MicrosoftAzure\Storage\Table\Models\Entity;
use MicrosoftAzure\Storage\Table\Models\EdmType;
use MicrosoftAzure\Storage\Table\Models\QueryEntitiesOptions;
require_once "../vendor/autoload.php";
use MicrosoftAzure\Storage\Table\TableRestProxy;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;

// createtable();
$error = "";
if(isset($_POST['submit'])) {
  submit_details($current_user);
}

if (isset($_SESSION['current_user'])) {
  query($current_user);
}

$filter = "PartitionKey eq 'Lab'";
$connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
$tableClient = TableRestProxy::createTableService($connectionString);
try    {
    $result = $tableClient->queryEntities("Users", $filter);
}
catch(ServiceException $e){
    $code = $e->getCode();
    $error_message = $e->getMessage();
    echo $code.": ".$error_message."<br />";
}

$entities = $result->getEntities();
$name = "";
foreach($entities as $entity){
	$user = $entity->getPropertyValue("Username");
	if (strcmp($user, $current_user) == 0){
		  $LabName = $entity->getPropertyValue("FirstName");
    	$name = $LabName;
	}
}
?>

<body bgcolor="#696969">
<link rel="stylesheet" href="../css/lab_profile_style.css">
<div class="topnav">
  <img src="../logo.png" style="margin-bottom: -0.6%">
  <a id="logout" href="logout.php" style="background-color: #1ab188"> Logout </a>
  <a href="lab_image_upload.php">Upload Reports</a>
  <a href="lab_access_list.php">Access List</a>
  <a href="lab_profile.php" href="#home" style="background-color: #a0b3b0; color: black">Profile</a>
</div>
<div id="wrapper">

  <div class="float form_container">
  <form method="post" action="lab_profile.php">
  <div class="formSection readOnly">
    <div class="row">
      <div class="col-md-12">
          <div class="panel panel-info">
            <div class="panel-heading" style="background-color: #31708f; color: white">
                <div class="pull-left">
                    <i class="glyphicon glyphicon-user"></i> Lab Information
                </div>
                <div class="clearfix"></div>
            </div>
            <div class="panel-body" style="background-color: #d9edf7">
                <div class="row">
                    <div >
                      <div>
                          <div class="col-md-4">
                          <label for="fname">Lab Name</label>
                          </div>
                          <div class="col-md-8">
                          <input type="text" style="width: 70%" id="fname" value="<?php echo $_SESSION['fname']; ?>" name="fname" placeholder="Enter first Name" disabled>
                          </div>
                        </div>
                        <br>
                        <br>
                        <br>
                        <div>
                          <div class="col-md-4">
                          <label for="address">Address of Clinic/Hospital</label>
                          </div>
                          <div class="col-md-8">
                          <input type="text" name="address" style="width: 70%" id="address" value="<?php echo $_SESSION['address']; ?>" placeholder="Address of Clinic/Hospital" disabled>
                          </div>
                        </div>
                        <br>
                        <br>
                        <br>
                        <div>
                          <div class="col-md-4">
                          <label for="time">Working days and hours</label>
                          </div>
                          <div class="col-md-8">
                          <input type="text" name="time" style="width: 33%" id="time" value="<?php echo $_SESSION['time']; ?>" placeholder="Office days and hours" disabled>
                          </div>
                        </div>
                        <br>
                        <br>
                        <br>
                        <div>
                          <div class="col-md-4">
                          <label for="contact">Lab Contact Numbers (if any)</label>
                          </div>
                          <div class="col-md-8">
                          <input type="text" name="contact" style="width: 45%" id="contact" value="<?php echo $_SESSION['contact']; ?>" placeholder="Office Contact Numbers" disabled>
                          </div>
                        </div>
                    </div>
                </div>
            </div>
          </div>
      </div>
    </div>
    
    <div class="row" >
        <div class="col-md-12" style="margin-top: -15px">
          <button type="button" class="editButton">Edit Details</button>
          <div class="actionButtons">
            <input type="submit" value="Submit" name="submit" method="post">
            <a href="#" class="cancelButton">Cancel</a>
          </div>  
        </div>
        
      </div>
  </div>

<?php
// Call only when new table has to be created. The name of new table should be different from existing ones.
function createtable(){
    $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
    $tableClient = TableRestProxy::createTableService($connectionString);
    try {
        $tableClient->createTable("Labs");
    }
    catch(ServiceException $e){
      $code = $e->getCode();
      $error_message = $e->getMessage();
      echo $code.": ".$error_message."<br />";
    }

}

function submit_details(string $current_user){
	$connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
	$tableClient = TableRestProxy::createTableService($connectionString);
	$fname = $_POST['fname'];
	$address = $_POST['address'];
	$time = $_POST['time'];
	$contact = $_POST['contact'];
	require_once '../vendor/autoload.php';
	$entity = new Entity();
	$entity->setPartitionKey($current_user);
	$entity->setRowKey($current_user);
	$entity->addProperty("LabName",EdmType::STRING,$fname);
	$entity->addProperty("Address",EdmType::STRING,$address);
	$entity->addProperty("Time",EdmType::STRING,$time);
	$entity->addProperty("Contact",EdmType::STRING,$contact);
	try{
	    $tableClient->insertOrReplaceEntity("Labs", $entity);
	    // echo "Profile updated for".$current_user;
	}
	catch(ServiceException $e){
	    $code = $e->getCode();
	    $error_message = $e->getMessage();
	}
	// $filter = "PartitionKey eq '".$current_user."'";
	// try    {
	//     $result = $tableClient->queryEntities("Labs", $filter);
	// }
	// catch(ServiceException $e){
	//     $code = $e->getCode();
	//     $error_message = $e->getMessage();
	//     echo $code.": ".$error_message."<br />";
	// }
	// $entities = $result->getEntities();
	// foreach($entities as $entity){
	//     echo $entity->getPropertyValue("LabName")."<br />";
	// }
}

function query($current_user){
  $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
  $tableClient = TableRestProxy::createTableService($connectionString);

  // echo "stuck here";
  try    {
    $result = $tableClient->getEntity("Labs", $current_user, $current_user);
  }
  catch(ServiceException $e){
    $code = $e->getCode();
    $error_message = $e->getMessage();
    echo $code.": ".$error_message."<br />";
  }

  $entity = $result->getEntity();

  $fname = $entity->getPropertyValue("LabName");
  $address = $entity->getPropertyValue("Address");
  $time = $entity->getPropertyValue("Time");
  $contact = $entity->getPropertyValue("Contact");

  $_SESSION['fname'] = $fname;
  $_SESSION['address'] = $address;
  $_SESSION['time'] = $time;
  $_SESSION['contact'] = $contact;

}

?>
    <br>
    <div class="row">
      <!-- <input type="submit" value="Submit" name="submit" method="post"> -->
      <!-- <button type="submit" class="button button-block" name="submit" method="post"/>Submit</button> -->
    </div>
  </form>
  </div>
</div>
    <script src="../js/jquery.min.js"></script>
    <script src="../js/jquery.form.js"></script>
    <script>
    $(document).on('change', '#image_upload_file', function () {
    var progressBar = $('.progressBar'), bar = $('.progressBar .bar'), percent = $('.progressBar .percent');

    $('#image_upload_form').ajaxForm({
        beforeSend: function() {
        progressBar.fadeIn();
            var percentVal = '0%';
            bar.width(percentVal)
            percent.html(percentVal);
        },
        uploadProgress: function(event, position, total, percentComplete) {
            var percentVal = percentComplete + '%';
            bar.width(percentVal)
            percent.html(percentVal);
        },
        success: function(html, statusText, xhr, $form) {   
        obj = $.parseJSON(html);  
        if(obj.status){   
          var percentVal = '100%';
          bar.width(percentVal)
          percent.html(percentVal);
          $("#imgArea>img").prop('src',obj.image_medium);     
        }else{
          alert(obj.error);
        }
        },
      complete: function(xhr) {
        progressBar.fadeOut();      
      } 
    }).submit();    

    });
    </script>

    <script>
      var oldValues = null;
              
      $(document)
      .on("click", ".editButton", function () {

          var section = $(this).closest(".formSection");
          var inputs = section.find("input");
          var selects = section.find("select");

          section
            .removeClass("readOnly");
          oldValues = {};
          inputs
            .each(function () { oldValues[this.id] = $(this).val(); })
            .prop("disabled", false);
          selects
            .each(function () { oldValues[this.id] = $(this).val(); })
            .prop("disabled", false);
      })

      .on("click", ".cancelButton", function (e) {

          var section = $(this).closest(".formSection");
          var otherSections = $(".formSection").not(section);
          var inputs = section.find("input");
          var selects = section.find("select");

          section
            .addClass("readOnly");

          $('button').prop("disabled", false);

          inputs
            .each(function() { $(this).val(oldValues[this.id]); })
            .prop("disabled", true)
          selects
            .each(function() { $(this).val(oldValues[this.id]); })
            .prop("disabled", true)

          e.stopPropagation();
          e.preventDefault();
      });

    </script>
</body>
</html>