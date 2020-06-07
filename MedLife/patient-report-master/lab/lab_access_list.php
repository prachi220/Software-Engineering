<!DOCTYPE HTML>
<html>
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
    <link rel="stylesheet" href="../css/doctor_access_list_style.css">
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
        $tableClient->createTable("AccessDoctor");
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
  <a href="logout.php" style="background-color: #1ab188"> Logout </a>
  <a href="lab_image_upload.php">Upload Reports</a>
  <a href="lab_access_list.php" style="background-color: #a0b3b0; color: black">Access List</a>
  <a href="lab_profile.php" href="#home">Profile</a>
</div>

<div>
  <br>
</div>

  <div class="accessList" id="myList">

    <?php

    show_all($current_user);

    //Displays a list of all the patients who have given access to the Lab
    function show_all( string $current_user){

      $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
      $tableClient = TableRestProxy::createTableService($connectionString);

      $filter = "PartitionKey eq '".$current_user."'";
      // echo $filter;
      try    {
          $result_doc = $tableClient->queryEntities("AccessLab", $filter);
      }
      catch(ServiceException $e){
          $code = $e->getCode();
          $error_message = $e->getMessage();
          echo $code.": ".$error_message."<br />";
      }

      $entities_doc = $result_doc->getEntities();

      $labs = array();
      foreach($entities_doc as $entity){
          $pat = $entity->getPropertyValue("Patient");
          
          try{
              $result = $tableClient->getEntity("Users", "Patient", $pat);
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
          $_SESSION['patient'] = $pat;

          // echo $pat;

          echo '<div class="square"><p style="display: inline; color:#535b5a;font-weight: bold;font-size: calc(8px + 1.5vw);">'.$pat_fullname.' </p> <a href="lab_image_upload.php?val='.$pat.'" style="float: right" class="btn btn-primary">Upload Report</a></div>';
          // page2.php?val=1
          $GLOBALS['cnt'] = $GLOBALS['cnt']+1;
          array_push($GLOBALS['all_access'], $entity->getPropertyValue("Username"));
      }
    }
    ?>


<!-- </div> -->
    <script src="../js/jquery.min.js"></script>
    <script src="../js/jquery.form.js"></script>
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
          // console.log(query);
        });


      $(".search-query").keyup(function() {
          $("#x").show();
          if ($.trim($(".search-query").val()) == "") {
              $("#x").hide();
        // $('div.report-container').removeClass('hidden');
          }
      });

      $("#x").click(function() {
        $(".search-query").val("");
        // $('div.report-container').removeClass('hidden');
        $(this).hide();
      });

    </script>

</body>
</html>