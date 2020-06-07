<!DOCTYPE HTML>
<html>
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
    <link rel="stylesheet" href="../css/patient_notification_style.css">
    <title>Notifications</title>
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
$cnt = 0;

$all_access = array();

function createtable(){
    $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
    $tableClient = TableRestProxy::createTableService($connectionString);
    try {
        $tableClient->createTable("Notifications");
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
  <img src="../logo.png" style="margin-bottom: -0.6%">
  <a id="logout" href="logout.php" style="background-color: #1ab188"> Logout </a>
  <a id="messages" href="patient_message.php">Messages</a>
  <a id="notif" href="patient_notification.php"  style="background-color: #a0b3b0; color: black">Notifications</a>
  <a id="timeline" href="timeline.php">Report Timeline</a>
  <a id="access_list_patient" href="access_list_patient.php">Access List</a>
  <a id="patient_profile" href="patient_profile.php" href="#home">Profile</a>
</div>

<div class="right-panel">
  <input type="text" class="search-query" placeholder="Search notifications" style="margin-left: 5%; width: 80%">
  <div id="remove-search">
      <span id="x">x</span>
  </div>
</div>

<br>
  <div class="accessList" id="myList">

    <?php

    show_all($current_user);

    // Fetches all the notifications in the notification table and displays them 
    function show_all( string $current_user){

      $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
      $tableClient = TableRestProxy::createTableService($connectionString);

      $filter = "PartitionKey eq '".$current_user."'";
      // echo $filter;
      try    {
          $result_doc = $tableClient->queryEntities("Notifications", $filter);
      }
      catch(ServiceException $e){
          $code = $e->getCode();
          $error_message = $e->getMessage();
          echo $code.": ".$error_message."<br />";
      }

      $entities_doc = $result_doc->getEntities();
      $entities_doc = array_reverse($entities_doc);
      $labs = array();
      foreach($entities_doc as $entity){
          $remove_temp = "remove".$GLOBALS['cnt'];
          $notif = $entity->getPropertyValue("Notif");
          $notif_date = $entity->getPropertyValue("Notif_Date");
          $type = $entity->getPropertyValue("Type");

          $date = $notif_date->format('Y-m-d H:i:s');

          echo '<div class="square"> <a href="timeline.php?" style="text-decoration: none; font-weight: bold; font-size: 1.1vw; color:black;">'.$notif.' : '.$date.'</a></div>';
          // page2.php?val=1
          $GLOBALS['cnt'] = $GLOBALS['cnt']+1;
      }
    }
    ?>


</div>
    <script src="../js/jquery.min.js"></script>
    <script src="../js/jquery.form.js"></script>
    <script>
      $("a").click(function (event) {
          if ($(this).hasClass("disabled")) {
              event.preventDefault();
          }
          $(this).addClass("disabled");
      });


      $(".search-query").keyup(function() {
          $("#x").show();
          if ($.trim($(".search-query").val()) == "") {
              $("#x").hide();
              $('div.square').removeClass('hidden');
          }else{
            var query = $.trim($(".search-query").val()).toLowerCase();
            $('div.square').each(function(){
              var $this = $(this);
                if($this.text().toLowerCase().indexOf(query) === -1){
                  $this.closest('div.square').addClass('hidden');
                }else{
                  $this.closest('div.square').removeClass('hidden');
                }
            });
          }
      });

      $("#x").click(function() {
        $(".search-query").val("");
        $('div.square').removeClass('hidden');
        $(this).hide();
      });
    </script>

</body>
</html>