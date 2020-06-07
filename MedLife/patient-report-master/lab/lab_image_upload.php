<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8">
    <link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
    <link rel="stylesheet" href="../css/lab_image_upload_style.css">
    <title>Upload Image</title>

<script type='text/javascript'>
function preview_image(event) {
  var reader = new FileReader();
  reader.onload = function() {
    var output = document.getElementById('output_image');
    console.log(output);
    output.src = reader.result;
    console.log(output.src);
  }
  reader.readAsDataURL(event.target.files[0]);
}
</script>

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
if(isset($_POST['upload'])) {
  imageupload($current_user);
}

function createtable(){
    $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
    $tableClient = TableRestProxy::createTableService($connectionString);
    try {
        $tableClient->createTable("Tags");
    }
    catch(ServiceException $e){
      $code = $e->getCode();
      $error_message = $e->getMessage();
      echo $code.": ".$error_message."<br />";
    }

}
?>

<body bgcolor="#696969">
<link rel="stylesheet" href="../css/lab_image_upload_style.css">
<div class="topnav">
  <img src="../logo.png" style="margin-bottom: -0.6%">
  <a id="logout" href="logout.php" style="background-color: #1ab188"> Logout </a>
  <a href="lab_image_upload.php" style="background-color: #a0b3b0; color: black">Upload Reports</a>
  <a href="lab_access_list.php">Access List</a>
  <a href="lab_profile.php" href="#home">Profile</a>
</div>


<div class="wrapper">
  <form method="POST" action="lab_image_upload.php" enctype="multipart/form-data">
  <div><img id="output_image"/></div>
  <div class="upload-btn-wrapper">
    <button class="btn">Select Image</button><br/>
    <input type="file" accept="image/*" onchange="preview_image(event)" name="UploadFileName" /><br/>
  </div><br/>
  <?php
  if ($_GET['val']!=NULL){
    echo '<input type="text" class="input_text" id="pid" name="pid" value="'.$_GET['val'].'"><br/>';
  }else{
    echo '<input type="text" class="input_text" id="pid" name="pid" placeholder="Patient Username*"><br/>';
  }
  ?>
  <input type="text" class="input_text" id="did" name="did" placeholder="Doctor Username*"><br/>
  <input type="text" class="input_text" id="tags" name="tags" placeholder="Tags (separated by Comma)"><br/>

<?php

// Tags are added to the table with blob_id as the row key
function add_tags(string $tags, string $pt_id, string $doc_id, string $report_id, string $current_user){
  $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
  $tableClient = TableRestProxy::createTableService($connectionString);
  if (preg_match("/^[A-Z]/", $pt_id )==true){
      echo "First letter of pt_id should be small";
  }else{
      $entity = new Entity();
      $entity->setPartitionKey($pt_id);
      $entity->setRowKey($report_id);
      $entity->addProperty("Patient",EdmType::STRING,$pt_id);
      $entity->addProperty("tags",EdmType::STRING,$tags);
      $entity->addProperty("Report_id",EdmType::STRING,$report_id);
      $entity->addProperty("Lab",EdmType::STRING,$current_user);

      $comment = "No doctor's comment yet";
      $entity2 = new Entity();
      $entity2->setPartitionKey($pt_id);
      $entity2->setRowKey($report_id);
      $entity2->addProperty("Doctor",EdmType::STRING,$doc_id);
      $entity2->addProperty("Doc_comment",EdmType::STRING,$comment);
      $entity2->addProperty("Report_id",EdmType::STRING,$report_id);

      try{
          $tableClient->insertEntity("Tags", $entity);
      }
      catch(ServiceException $e){
          $code = $e->getCode();
          $error_message = $e->getMessage();
      }
      try{
          $tableClient->insertEntity("Comments", $entity2);
      }
      catch(ServiceException $e){
          $code = $e->getCode();
          $error_message = $e->getMessage();
      }
  }
}

// On successful upload, notification is sent to the patient regarding new upload
function send_notification(string $current_user, string $pt_id){
  $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
  $tableClient = TableRestProxy::createTableService($connectionString);

  try{
      $result = $tableClient->getEntity("Users", "Lab", $current_user);
  }
  catch(ServiceException $e){
      $code = $e->getCode();
      $error_message = $e->getMessage();
      echo $code.": ".$error_message."<br />";
  }
  $lab_entity = $result->getEntity();
  $lab_fname = $lab_entity->getPropertyValue("FirstName");

  $num2 = (string) time();
  $notif_id = "notif_".$num2;
  $notif = $lab_fname." uploaded a new report.";

  $entity = new Entity();
  $entity->setPartitionKey($pt_id);
  $entity->setRowKey($notif_id);
  $entity->addProperty("Type",EdmType::STRING,"Lab");
  $entity->addProperty("Notif",EdmType::STRING,$notif);
  $entity->addProperty("Notif_id",EdmType::STRING,$notif_id);
  $entity->addProperty("Notif_Date", EdmType::DATETIME, new DateTime());

  try{
      $tableClient->insertEntity("Notifications", $entity);
  }
  catch(ServiceException $e){
      $code = $e->getCode();
      $error_message = $e->getMessage();
  }
}

// Image is uploaded in blob to the container with the patients username. 
// Tag is added corresponding to the blob name
// Notification is sent to the patient on successful upload
function imageupload(string $current_user){
    $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
    $tableClient = TableRestProxy::createTableService($connectionString);
    $pt_id = $_POST['pid'];
    $doc_id = $_POST['did'];
    $tags = $_POST['tags'];

    if ((strcmp($pt_id,"")==0) && (strcmp($doc_id,"")==0)){
        echo '<script> alert("Patient and Doctor username cannot be empty"); </script>';
    }elseif (strcmp($pt_id,"")==0) {
        echo '<script> alert("Patient username cannot be empty"); </script>';
    }elseif (strcmp($doc_id,"")==0) {
        echo '<script> alert("Doctor username cannot be empty"); </script>';
    }else{

      $filter = "PartitionKey eq 'Patient'";
      try{
          $result = $tableClient->queryEntities("Users", $filter);
      }
      catch(ServiceException $e){
          $code = $e->getCode();
          $error_message = $e->getMessage();
          echo $code.": ".$error_message."<br />";
      }
      $entities1 = $result->getEntities();
      $accountexists = 0;
      foreach($entities1 as $entit){
        $user = $entit->getPropertyValue("Username");
        if(strcmp($user, $pt_id )== 0)
        {
          $accountexists = 1;
          break;
        }
      }

      if ($accountexists==0){
        echo '<script> alert("The patient does not exist."); </script>';
        return;
      }

      $filter1 = "PartitionKey eq 'Doctor'";
      try{
          $result1 = $tableClient->queryEntities("Users", $filter1);
      }
      catch(ServiceException $e){
          $code = $e->getCode();
          $error_message = $e->getMessage();
          echo $code.": ".$error_message."<br />";
      }
      $entities2 = $result1->getEntities();
      $accountexists1 = 0;
      foreach($entities2 as $entit){
        $user = $entit->getPropertyValue("Username");
        if(strcmp($user, $doc_id )== 0)
        {
          $accountexists1 = 1;
          break;
        }
      }

      if ($accountexists1==0){
        echo '<script> alert("The doctor does not exist."); </script>';
        return;
      }

      $tableClient = TableRestProxy::createTableService($connectionString);
      $filter = "PartitionKey eq '".$pt_id."'";
      try    {
          $result_doc = $tableClient->queryEntities("AccessList", $filter);
      }
      catch(ServiceException $e){
          $code = $e->getCode();
          $error_message = $e->getMessage();
          echo $code.": ".$error_message."<br />";
      }

      $entities_doc = $result_doc->getEntities();
      $is_access = 0;
      foreach($entities_doc as $entity){
          if (strcmp($entity->getPropertyValue("Username"), $current_user)==0){
              $is_access=1;
              break;
          }
      }
      if ($is_access){
      
        $blobClient = BlobRestProxy::createBlobService($connectionString);
        $containerName = $pt_id;      
        $num2 = (string) time();
        $fileToUpload = $pt_id."_".$num2;

        # Upload file as a block blob
        // echo "Uploading image... ".PHP_EOL;
        $content = fopen($_FILES["UploadFileName"]["tmp_name"], 'r');
        add_tags($tags, $pt_id, $doc_id, $fileToUpload, $current_user);
        //Upload blob
        $blobClient->createBlockBlob($containerName, $fileToUpload, $content);
        echo '<script> alert("Image uploaded successfully!"); </script>';
        send_notification($current_user, $pt_id);
        unset($_SESSION['patient']);
      }else{
        echo '<script> alert("Patient has not given you access"); </script>';
      }
    }
}
?>
  <input type="submit" value="Upload Report" name="upload" method="post">
  </form>
</div>

</body>
</html>