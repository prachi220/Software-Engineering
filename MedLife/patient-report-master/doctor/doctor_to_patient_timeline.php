<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8">
    <link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
    <link rel="stylesheet" href="../css/doctor_to_patient_timeline_style.css">
    <title>Patient Timeline</title>
    <meta charset="utf-8">
    </head>
</head>
<?php
session_start();

$current_doctor = $_SESSION['current_user'];
if ($_GET['val']){
  $_SESSION['current_patient'] = $_GET['val'];
}
$current_patient = $_SESSION['current_patient'];

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

$doctor_array = array();
$blob_array = array();  
$lab_array = array();

?>
<body bgcolor="#696969">

<div class="topnav">
  <img src="../logo.png" style="margin-bottom: -0.6%">
  <a href="logout.php" style="background-color: #1ab188"> Logout </a>
  <a href="doctor_message.php">Messages</a>
  <a href= "doctor_access_list.php" style="background-color: #a0b3b0; color: black">Access List</a>
  <a href= "doctor_profile.php" href="#home">Profile</a>
</div>

<div class="right-panel">
  <form class="form-search" method="POST" action="doctor_to_patient_timeline.php">
      <input type="text" class="search-query" placeholder="Search tag within reports" style="margin-left: 5%; width: 80%">
      <div id="remove-search">
          <span id="x">x</span>
      </div>
      <br>
      <br>

      <button type="submit" class="search-btn" method="post">Search</button>
  </form>
  <?php
  $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
  $filter = "PartitionKey eq '".$current_patient."'";
  $tableClient = TableRestProxy::createTableService($connectionString);
  try{
            $result = $tableClient->getEntity("Users", "Patient", $current_patient);
        }
        catch(ServiceException $e){
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code.": ".$error_message."<br />";
        }

        $pat_entity = $result->getEntity();

        $pat_fname = $pat_entity->getPropertyValue("FirstName");
        $pat_lname = $pat_entity->getPropertyValue("LastName");

        $pat_fullname = $pat_fname." ".$pat_lname;

  echo '<div style="margin-top:20%; width:100%; text-align: center;"> <h3 style=""> Timeline of:</h3> <h1 style="text-align: center;color: #206be5">'.$pat_fullname.' </h1> <a href="doctor_access_list.php" style="background-color:#dce7e8; border-radius: 6px; text-decoration:none; border: 1px solid #fff; padding: 5px"> Go back </a></div>'  ?>
</div>

<div class="outer-report-container"> 

<?php    

show_reports($current_patient, $current_doctor);

$error = "";
$cnt = count($GLOBALS['blob_array']);
for($i=0; $i<$cnt; $i++){
  $add_temp = 'add'.$i;
  if(isset($_POST[$add_temp])) {
      echo $add_temp;
    add_comment($current_patient, $i, $current_doctor);
    echo "<meta http-equiv='refresh' content='0'>";
  }
}

for($i=0; $i<$cnt; $i++){
  $edit_temp = 'edit'.$i;
  echo $i;
  if(isset($_POST[$edit_temp])) {
    edit_tags($current_patient, $i, $current_doctor);
    echo "<meta http-equiv='refresh' content='0'>";
  }
}

//Replaces the entity with new tag value in the tags table for the current user and chosen report
//Doctor can edit tags only for those reports to which he has editable access
function edit_tags($current_patient, $i, $current_doctor){
  $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
  $filter = "PartitionKey eq '".$current_patient."'";
  $tableClient = TableRestProxy::createTableService($connectionString);
  $tag_temp = 'tag'.$i;
  $new_tag = $_POST[$tag_temp];

  $filter = "PartitionKey eq '".$current_patient."'";
  // console.log( $cnt);
  $blob = $GLOBALS['blob_array'][$i];
  $report_id = $blob->getName();

  $entity1 = new Entity();
  $entity1->setPartitionKey($current_patient);
  $entity1->setRowKey($report_id);
  $entity1->addProperty("Lab",EdmType::STRING,$GLOBALS['lab_array'][$i]);
  $entity1->addProperty("tags",EdmType::STRING,$new_tag);
  $entity1->addProperty("Report_id",EdmType::STRING,$report_id);

  try{
      $tableClient->insertOrReplaceEntity("Tags", $entity1);
  }
  catch(ServiceException $e){
      $code = $e->getCode();
      $error_message = $e->getMessage();
  }
}

// Doctor's comments are notified to the patient through notifications.
function send_notification(string $current_doctor, string $current_patient){
  $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
  $tableClient = TableRestProxy::createTableService($connectionString);

  try{
      $result = $tableClient->getEntity("Users", "Doctor", $current_doctor);
  }
  catch(ServiceException $e){
      $code = $e->getCode();
      $error_message = $e->getMessage();
      echo $code.": ".$error_message."<br />";
  }
  $doc_entity = $result->getEntity();
  $doc_fname = $doc_entity->getPropertyValue("FirstName");
  $doc_lname = $doc_entity->getPropertyValue("LastName");

  $num2 = time();
  $notif_id = "notif_".$num2;
  $notif = $doc_fname." ".$doc_lname." posted a new comment.";

  $entity = new Entity();
  $entity->setPartitionKey($current_patient);
  $entity->setRowKey($notif_id);
  $entity->addProperty("Type",EdmType::STRING,"Doctor");
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

//Doctor can add comments only for those reports to which he has editable access
function add_comment(string $current_patient, $i, string $current_doctor){
  echo "in add_comment";
  echo $i;
  $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
  $filter = "PartitionKey eq '".$current_patient."'";
  $tableClient = TableRestProxy::createTableService($connectionString);
  $dc_temp = 'doctor_comment'.$i;
  $new_comment = $_POST[$dc_temp];
  // console.log( $cnt);
  $blob = $GLOBALS['blob_array'][$i];
  $report_id = $blob->getName();

  $entity1 = new Entity();
  $entity1->setPartitionKey($current_patient);
  $entity1->setRowKey($report_id);
  $entity1->addProperty("Doctor",EdmType::STRING,$GLOBALS['doctor_array'][$i]);
  $entity1->addProperty("Doc_comment",EdmType::STRING,$new_comment);
  $entity1->addProperty("Report_id",EdmType::STRING,$report_id);

  try{
      $tableClient->insertOrReplaceEntity("Comments", $entity1);
      send_notification($current_doctor, $current_patient);
  }
  catch(ServiceException $e){
      $code = $e->getCode();
      $error_message = $e->getMessage();
  }
}

// All the reports of the patients are visible to the doctor
function show_reports(string $current_patient, string $current_doctor){ 
  // echo "curr user".$current_patient; 
  $containerName = $current_patient;
  $url = "";
  $tags_array = array();
  $comment_array = array();
  try{
      // Get blob.
      require_once '../vendor/autoload.php';
      $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
      $filter = "PartitionKey eq '".$current_patient."'";
      $tableClient = TableRestProxy::createTableService($connectionString);
      // echo $filter;
      try    {
          $user_tags = $tableClient->queryEntities("Tags", $filter);
      }
      catch(ServiceException $e){
          $code = $e->getCode();
          $error_message = $e->getMessage();
          echo $code.": ".$error_message."<br />";
      }
      $patient_tags = $user_tags->getEntities();

      try{
          $user_comments = $tableClient->queryEntities("Comments", $filter);
      }
      catch(ServiceException $e){
          $code = $e->getCode();
          $error_message = $e->getMessage();
          echo $code.": ".$error_message."<br />";
      }
      $doc_comments = $user_comments->getEntities();

      $blobClient = BlobRestProxy::createBlobService($connectionString);
      $blob_list = $blobClient->listBlobs($containerName);
      $blobs = $blob_list->getBlobs();
      $strlen = strlen($current_patient);

      foreach($blobs as $blob)
      {
        $name =  $blob->getName();
        foreach($patient_tags as $p_info){
          $rid = $p_info->getPropertyValue("Report_id");
          $tags = $p_info->getPropertyValue("tags");
          $lid = $p_info->getPropertyValue("Lab");
          if (strcmp($rid, $name) == 0){
            array_push($tags_array, $tags);
            array_push($GLOBALS['lab_array'], $lid);
            break;
          }
        }
        foreach($doc_comments as $d_info){
          $rid1 = $d_info->getPropertyValue("Report_id");
          $did = $d_info->getPropertyValue("Doctor");
          $comment = $d_info->getPropertyValue("Doc_comment");
          if (strcmp($rid1, $name) == 0){
            array_push($GLOBALS['doctor_array'], $did);
            array_push($comment_array, $comment);
            break;
          }
        }
        array_push($GLOBALS['blob_array'], $blob);
      }
  }
  catch(ServiceException $e){
    $code = $e->getCode();
    $error_message = $e->getMessage();
    echo $code.": ".$error_message."<br />";
  }
  $url = "";
  try{
    $GLOBALS['blob_array'] = array_reverse($GLOBALS['blob_array']);
    $GLOBALS['tags_array'] = array_reverse($GLOBALS['tags_array']);
    $GLOBALS['lab_array'] = array_reverse($GLOBALS['lab_array']);
    $GLOBALS['doctor_array'] = array_reverse($GLOBALS['doctor_array']);
    $comment_array = array_reverse($comment_array);

    $iterator = new MultipleIterator;
    $iterator->attachIterator(new ArrayIterator($GLOBALS['blob_array']));
    $iterator->attachIterator(new ArrayIterator($tags_array));
    $iterator->attachIterator(new ArrayIterator($GLOBALS['lab_array']));
    $iterator->attachIterator(new ArrayIterator($GLOBALS['doctor_array']));
    $iterator->attachIterator(new ArrayIterator($comment_array));

    $count = 0;
    foreach($iterator as $values)
    {
      $blob = $values[0];
      $tag = $values[1];
      $lab = $values[2];
      $doctor = $values[3];
      $comment = $values[4];

      $name =  $blob->getName();
      $url = $blob->getUrl();

      if ($doctor!=NULL){

      try{
          $result = $tableClient->getEntity("Users", "Doctor", $doctor);
      }
      catch(ServiceException $e){
          $code = $e->getCode();
          $error_message = $e->getMessage();
          echo $code.": ".$error_message."<br />";
      }

      $doc_entity = $result->getEntity();

      $doc_fname = $doc_entity->getPropertyValue("FirstName");
      $doc_lname = $doc_entity->getPropertyValue("LastName");

      try{
          $result1 = $tableClient->getEntity("Users", "Lab", $lab);
      }
      catch(ServiceException $e){
          $code = $e->getCode();
          $error_message = $e->getMessage();
          echo $code.": ".$error_message."<br />";
      }

      $lab_entity = $result1->getEntity();

      $lab_fname = $lab_entity->getPropertyValue("FirstName");

      $comm_temp = "Dr. ".$doc_fname." ".$doc_lname."'s Comment:";
      $add_temp = "add".$count;
      $dc_temp = "doctor_comment".$count;
      $edit_temp = "edit".$count;
      $tag_temp = "tag".$count;

      if (strcmp($current_doctor, $doctor)==0){

        echo '
        <div class="report-container">
          <div class="report-picture-column">
            <div class="report-picture">
              <a href="report_img.php?url='.$url.'" target="_blank">
                <img src="'.$url.'">
              </a>
            </div>
          </div>
          <div class="report-details-column">
            <div>
              <h1 style="color:white; font-size: 4.2vh"> Lab Name: </h1>
              <textarea name="lab_name" cols="2" style="height: 6.3vh; resize: none;" readonly>'.$lab_fname.'</textarea>
                <br>
            </div>
            <div>
              <form class="form-submit-comment" method="POST" action="doctor_to_patient_timeline.php">
                <h1 style="color:white; font-size: 4.2vh"> Tags: </h1>
                <textarea class="tag" name="'.$tag_temp.'" cols="2" style="height: 6.3vh; resize: none;">'.$tag.' </textarea>
                <button type="submit" class="submit-btn" name="'.$edit_temp.'">Edit Tags</button>
              </form>
            </div>
            <div style="margin-top: 5%">
              <form class="form-submit-comment" method="POST" action="doctor_to_patient_timeline.php">
                    <h1 style="color:white; font-size: 4.2vh">'.$comm_temp.'</h1>
                    <textarea class = "" name="'.$dc_temp.'" cols="5"  style="height: 40vh; resize: none;">'.$comment.'</textarea>
                    <button type="submit" class="submit-btn" name="'.$add_temp.'">Add Comment</button>
              </form>
            </div>
          </div>
        </div>';
      }else{
        echo '
        <div class="report-container">
          <div class="report-picture-column">
            <div class="report-picture">
              <a href="report_img.php?url='.$url.'" target="_blank">
                <img src="'.$url.'">
              </a>
            </div>
          </div>
          <div class="report-details-column">
            <div>
              <h1 style="color:white; font-size: 4.2vh"> Lab Name: </h1>
              <textarea name="lab_name" cols="2" style="height: 6.3vh; resize: none;" readonly>'.$lab_fname.'</textarea>
                <br>
            </div>
            <div>
              <h1 style="color:white; font-size: 4.2vh"> Tags: </h1>
              <textarea class="tag" name="tag" cols="2" style="height: 6.3vh; resize: none;" readonly>'.$tag.' </textarea>
              <button disabled>Edit Tags</button>
            </div>
            <div style="margin-top: 5%">
              <form class="form-submit-comment" method="POST" action="doctor_to_patient_timeline.php">
                    <h1 style="color:white; font-size: 4.2vh">'.$comm_temp.'</h1>
                    <textarea class = "" name="'.$dc_temp.'" cols="5"  style="height: 40vh; resize: none;">'.$comment.'</textarea>
                    <button disabled>Add Comment</button>
              </form>
            </div>
          </div>
        </div>';
      }
      $count = $count + 1;
    }
    }
  }
  catch(ServiceException $e){
      $code = $e->getCode();
      $error_message = $e->getMessage();
      echo $code.": ".$error_message."<br />";
  }
  echo "</div>";
}
?>


    <script src="../js/jquery.min.js"></script>
    <script src="../js/jquery.form.js"></script>
    <script>
      function reloadPage() {
          var currentDocumentTimestamp = new Date(performance.timing.domLoading).getTime();
          // Current Time //
          var now = Date.now();
          // Total Process Lenght as Minutes //
          var tenSec = 10 * 1000;
          // End Time of Process //
          var plusTenSec = currentDocumentTimestamp + tenSec;
          if (now > plusTenSec) {
              location.reload();
          }
      }

      $('.form-search .search-btn').on('click', function(e){
          e.preventDefault(); //stops reloading the page
          var query = $.trim($(this).prevAll('.search-query').val()).toLowerCase();
          console.log(query);

          $('div.report-container .tag').each(function(){
                     var $this = $(this);
                if($this.text().toLowerCase().indexOf(query) === -1)
                  $this.closest('div.report-container').addClass('hidden');
          });
        });


      $(".search-query").keyup(function() {
          $("#x").show();
          if ($.trim($(".search-query").val()) == "") {
              $("#x").hide();
        $('div.report-container').removeClass('hidden');
          }
      });

      $("#x").click(function() {
        $(".search-query").val("");
        $('div.report-container').removeClass('hidden');
        $(this).hide();
      });

    </script>
</body>
</html>
