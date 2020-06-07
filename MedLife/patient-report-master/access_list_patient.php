<!DOCTYPE HTML>
<html>
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
    <link rel="stylesheet" href="/css/access_list_patient_style.css">
    <title>Access List</title>
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
require_once "vendor/autoload.php";
use MicrosoftAzure\Storage\Table\TableRestProxy;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;

// createtable();
$cnt = 0;
$cnt_labs = 0;

$error = "";
if(isset($_POST['add'])) {
  add_user($current_user);
}

$all_access = array();

function createtable(){
    $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
    $tableClient = TableRestProxy::createTableService($connectionString);
    try {
        $tableClient->createTable("AccessLab");
    }
    catch(ServiceException $e){
      $code = $e->getCode();
      $error_message = $e->getMessage();
      echo $code.": ".$error_message."<br />";
    }

}

?>

<body bgcolor="#696969">

<div class="topnav">
  <a id="logout" href="logout.php" style="background-color: #1ab188"> Logout </a>
  <a id="messages" href="patient_message.php">Messages</a>
  <a id="notif" href="patient_notification.php">Notifications</a>
  <a id="timeline" href="timeline.php">Report Timeline</a>
  <a id="access_list_patient" href="access_list_patient.php" style="background-color: #a0b3b0; color: black">Access List</a>
  <a id="patient_profile" href="patient_profile.php" href="#home">Profile</a>
</div>

<div>
  <br>
</div>

<div class="right-panel">
  <form class="form-search" method="POST" action="access_list_patient.php">
      <input type="text" class="search-query" placeholder="Doctor/Lab username*" id="add_id" name="add_id" style="margin-left: 5%; width: 80%">
      <input type="text" class="search-query" placeholder="Role*" id="role" name="role" style="margin-left: 5%; width: 80%">
      <div id="remove-search">
          <span id="x">x</span>
      </div>
      <br>
      <br>
<?php

// Add doctor/lab to patient's access table and patient to doctor/lab's access table respectively.
function add_user(string $current_user){
  $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
  $tableClient = TableRestProxy::createTableService($connectionString);
  $add_username = $_POST['add_id'];
  $role = $_POST['role'];

  if ((strcmp($add_username, "")==0) && (strcmp($role, "")==0)){
      echo '<script> alert("Username and Role field cannot be empty"); </script>';
  }elseif (strcmp($add_username, "")==0) {
      echo '<script> alert("Username field cannot be empty"); </script>';
  }elseif (strcmp($role, "")==0) {
      echo '<script> alert("Role field cannot be empty"); </script>';
  }else{
      $filter = "PartitionKey eq '".$role."'";
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
        if(strcmp($user, $add_username )== 0)
        {
          $accountexists = 1;
          break;
        }
      }

      if ($accountexists==0){
        echo '<script> alert("The user does not exist."); </script>';
      }else{
        $entity = new Entity();
        $entity->setPartitionKey($current_user);
        $entity->setRowKey($add_username);
        $entity->addProperty("Username",EdmType::STRING,$add_username);
        $entity->addProperty("Role",EdmType::STRING,$role);

        try{
            $tableClient->insertEntity("AccessList", $entity);
        }
        catch(ServiceException $e){
            $code = $e->getCode();
            $error_message = $e->getMessage();
        }

        if (strcmp($role, "Doctor")==0){
          $entity2 = new Entity();
          $entity2->setPartitionKey($add_username);
          $entity2->setRowKey($current_user);
          $entity2->addProperty("Patient",EdmType::STRING,$current_user);

          try{
              $tableClient->insertEntity("AccessDoctor", $entity2);
          }
          catch(ServiceException $e){
              $code = $e->getCode();
              $error_message = $e->getMessage();
          }

          //Create an empty convo between Patient and Doctor in the Messages table.
          $entity3 = new Entity();
          $entity3->setPartitionKey($current_user);
          $entity3->setRowKey($add_username);
          $entity3->addProperty("Patient", EdmType::STRING,$current_user);
          $entity3->addProperty("Doctor", EdmType::STRING,$add_username);
          $entity3->addProperty("Convo", EdmType::STRING,"");
          try{
              $tableClient->insertEntity("Messages", $entity3);
          }
          catch(ServiceException $e){
              $code = $e->getCode();
              $error_message = $e->getMessage();
          }
        }else{
          $entity2 = new Entity();
          $entity2->setPartitionKey($add_username);
          $entity2->setRowKey($current_user);
          $entity2->addProperty("Patient",EdmType::STRING,$current_user);

          try{
              $tableClient->insertEntity("AccessLab", $entity2);
          }
          catch(ServiceException $e){
              $code = $e->getCode();
              $error_message = $e->getMessage();
          }
        }
      }
    }
}
?>
      <button type="submit" class="add-user" name="add">Add User</button>
  </form>
</div>


  <div class="accessList" id="myList">

    <?php

    show_all($current_user);
    // echo $cnt;

    $error = "";
    for($i=0; $i<$GLOBALS['cnt']; $i++){
      $add_temp = 'remove'.$i;
      if(isset($_POST[$add_temp])) {
        remove_user($current_user, $i);
        unset($_POST);
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
        echo
        "<script>
        window.onload = function() {
            if (!window.location.hash) {
                window.location = window.location + '#loaded';
                window.location.reload();
            }
        }
        </script>";
      }
    }

    //show all the users who have access to the patients profile and reports.
    function show_all( string $current_user){

      $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
      $tableClient = TableRestProxy::createTableService($connectionString);

      $filter = "PartitionKey eq '".$current_user."'";
      // echo $filter;
      try    {
          $result_doc = $tableClient->queryEntities("AccessList", $filter);
      }
      catch(ServiceException $e){
          $code = $e->getCode();
          $error_message = $e->getMessage();
          echo $code.": ".$error_message."<br />";
      }

      $entities_doc = $result_doc->getEntities();

      $labs = array();
      foreach($entities_doc as $entity){
          if (strcmp($entity->getPropertyValue("Role"), "Doctor")==0){
            $remove_temp = "remove".$GLOBALS['cnt'];
            $doc = $entity->getPropertyValue("Username");

            try{
                $result = $tableClient->getEntity("Users", "Doctor", $doc);
            }
            catch(ServiceException $e){
                $code = $e->getCode();
                $error_message = $e->getMessage();
                echo $code.": ".$error_message."<br />";
            }

            $doc_entity = $result->getEntity();

            $doc_fname = $doc_entity->getPropertyValue("FirstName");
            $doc_lname = $doc_entity->getPropertyValue("LastName");

            $doc_fullname = $doc_fname." ".$doc_lname;

            echo '<div class="square"><p style="color:#19517f;font-weight: bold;font-size: 1.1vw;"> Dr. '.$doc_fullname.'</p> 
            <form method="POST" action="access_list_patient.php">
            <button type="submit"  style="float: right; margin-top:-40px;" class="btn btn-primary" name="'.$remove_temp.'">Remove</button>
            </form>
            </div>';
            $GLOBALS['cnt'] = $GLOBALS['cnt']+1;
            array_push($GLOBALS['all_access'], $doc);
          }else{
            array_push($labs, $entity);
          }
      }
      foreach($labs as $entity){
        $remove_temp = "remove".$GLOBALS['cnt'];
        $lab = $entity->getPropertyValue("Username");

        try{
            $result = $tableClient->getEntity("Users", "Lab", $lab);
        }
        catch(ServiceException $e){
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code.": ".$error_message."<br />";
        }

        $lab_entity = $result->getEntity();

        $lab_fname = $lab_entity->getPropertyValue("FirstName");

        echo '<div class="square"><p style="color:#964333;font-weight: bold;font-size: 1.1vw;"> Lab: '.$lab_fname.'</p> 
            <form method="POST" action="access_list_patient.php">
            <button type="submit"  style="float: right; margin-top:-40px;" class="btn btn-primary" name="'.$remove_temp.'">Remove</button>
            </form>
            </div>';
        $GLOBALS['cnt'] = $GLOBALS['cnt']+1;
        array_push($GLOBALS['all_access'], $lab);
      }
      $GLOBALS['cnt_labs'] = count($labs);
    }
    ?>

  <?php
  // Removes chosen user from current user's Access Table and current user from chosen user's Access Table.
    function remove_user($current_user, $i){
      require_once "vendor/autoload.php";
      $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
      $tableClient = TableRestProxy::createTableService($connectionString);
      $del_user = $GLOBALS['all_access'][$i];
      // echo $del_user;
      try{
          $tableClient->deleteEntity("AccessList", $current_user, $del_user);
      }
      catch(ServiceException $e){
          $code = $e->getCode();
          $error_message = $e->getMessage();
          echo $code.": ".$error_message."<br />";
      }

      if ($i<($GLOBALS['cnt']-$GLOBALS['cnt_labs'])){
        try{
            $tableClient->deleteEntity("AccessDoctor", $del_user, $current_user);
        }
        catch(ServiceException $e){
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code.": ".$error_message."<br />";
        }

        try{
            $tableClient->deleteEntity("Messages", $current_user, $del_user);
        }
        catch(ServiceException $e){
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code.": ".$error_message."<br />";
        }
      }else{
        try{
            $tableClient->deleteEntity("AccessLab", $del_user, $current_user);
        }
        catch(ServiceException $e){
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code.": ".$error_message."<br />";
        }
      }
    } 
  ?>

    <script src="./js/jquery.min.js"></script>
    <script src="./js/jquery.form.js"></script>
    <script>
    $("a").click(function (event) {
        if ($(this).hasClass("disabled")) {
            event.preventDefault();
        }
        $(this).addClass("disabled");
    });
      
      $('.form-search .search-btn').on('click', function(e){
          e.preventDefault();
          var query = $.trim($(this).prevAll('.search-query').val()).toLowerCase();
        });


      $(".search-query").keyup(function() {
          $("#x").show();
          if ($.trim($(".search-query").val()) == "") {
              $("#x").hide();
          }
      });

      $("#x").click(function() {
        $(".search-query").val("");
        $(this).hide();
      });

    </script>

</body>
</html>