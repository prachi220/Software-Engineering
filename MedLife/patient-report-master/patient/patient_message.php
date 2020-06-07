<!DOCTYPE HTML>
<html>
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
    <link rel="stylesheet" href="../css/patient_message_style.css">
    <title>Access List</title>
</head>

<?php
session_start();
$current_user = $_SESSION['current_user'];
$current_doctor = $_SESSION['current_doctor'];
$doctor_array = array();

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


function createtable(){
    $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
    $tableClient = TableRestProxy::createTableService($connectionString);
    try {
        $tableClient->createTable("Messages");
    }
    catch(ServiceException $e){
      $code = $e->getCode();
      $error_message = $e->getMessage();
      echo $code.": ".$error_message."<br />";
    }
}

if(isset($_POST['send'])) {
  send_message($current_user, $current_doctor);
  echo "<meta http-equiv='refresh' content='0'>";
}

?>

<body bgcolor="#696969">

<div class="topnav">
  <img src="../logo.png" style="margin-bottom: -0.6%">
  <a id="logout" href="logout.php" style="background-color: #1ab188"> Logout </a>
  <a id="messages" href="patient_notification.php"  style="background-color: #a0b3b0; color: black">Messages</a>
  <a id="notif" href="patient_notification.php">Notifications</a>
  <a id="timeline" href="timeline.php">Report Timeline</a>
  <a id="access_list_patient" href="access_list_patient.php">Access List</a>
  <a id="patient_profile" href="patient_profile.php" href="#home">Profile</a>
</div>

<div class="right-panel">
  <input type="text" class="search-query" placeholder="Search Doctors" style="margin-left: 5%; width: 80%">
  <div id="remove-search">
      <span id="x">x</span>
  </div>
  <br>
  <br>
  <ul class="userlist">
  <?php
  show_all($current_user);

  //Fetches all the doctors to whom the current user has given access. The doctor who is being chatted with is highlighted.
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

    $count = 0;
    foreach($entities_doc as $entity){
        if (strcmp($entity->getPropertyValue("Role"), "Doctor")==0){
          $doc = $entity->getPropertyValue("Username");
          array_push($GLOBALS['doctor_array'], $doc);
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
          $href_name = "link".$count;
          $href_val = "?link=".$count;
          if($_GET['link'] != ''){
            if($_GET['link'] != ''.$count){
              echo '<a style="display: block; text-decoration: none;" href="'.$href_val.'" name="'.$href_name.'"><li class="square">'.$doc_fullname.'</li></a>';
            }else{
              echo '<a style="display: block; text-decoration: none;" href="'.$href_val.'" name="'.$href_name.'"><li class="square-active">'.$doc_fullname.'</li></a>';
            }
          }else{
            if($_SESSION['current_doctor'] != $doc){
              echo '<a style="display: block; text-decoration: none;" href="'.$href_val.'" name="'.$href_name.'"><li class="square">'.$doc_fullname.'</li></a>';
            }else{
              echo '<a style="display: block; text-decoration: none;" href="'.$href_val.'" name="'.$href_name.'"><li class="square-active">'.$doc_fullname.'</li></a>';
            }
          }
          $count += 1;
        }
    }
  }
  ?>
  </ul>
</div>

<div class="messages-containers">
  <br>
  <div class="tableclass">
    <table width="100%">
  <?php
  $cnt = count($GLOBALS['doctor_array']);
  for ($i=0; $i < $cnt; $i++) { 
    $link=$_GET['link'];
    if ($link == ''.$i){
      $_SESSION['current_doctor'] = $GLOBALS['doctor_array'][$i];
      $current_doctor = $_SESSION['current_doctor'];
      // show_message($current_user, $current_doctor);
      break;
    }
  }

  if ($current_doctor!=NULL){
    show_message($current_user, $current_doctor);
  }
  
  // Once the current doctor is chosen, this function shows the conversation with the doctor.
  function show_message($current_user, $current_doctor){
      $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
      $tableClient = TableRestProxy::createTableService($connectionString);

      $filter = "PartitionKey eq '".$current_user."'";
      // echo $filter;
      try    {
          $result = $tableClient->getEntity("Messages", $current_user, $current_doctor);
      }
      catch(ServiceException $e){
          $code = $e->getCode();
          $error_message = $e->getMessage();
          echo $code.": ".$error_message."<br />";
      }

      $entity = $result->getEntity();
      $chat = $entity->getPropertyValue("Convo");

      if (strcmp($chat, "")==0){
        echo '<p align="center">No Conversation to show</p>';
      }else{
      $messages = explode("<br/>", $chat);
        foreach($messages as $msgstr){
          if (!empty($msgstr)){
            $tmsgvar = explode(":", $msgstr);
            $usr = $tmsgvar[0];
            $msg = $tmsgvar[1];
            if($usr==$current_user){
              echo '<tr><td class="user-message">'.$msg.'</td></tr>';
            }else{
              echo '<tr><td class="other-message">'.$msg.'</td></tr>';
            }
            
          }
        }
      }
  }

  // New message typed is appended to the current conversation between the patient and the doctor.
  function send_message($current_user, $current_doctor){
      $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
      $tableClient = TableRestProxy::createTableService($connectionString);

      $filter = "PartitionKey eq '".$current_user."'";
      try    {
          $result = $tableClient->getEntity("Messages", $current_user, $current_doctor);
      }
      catch(ServiceException $e){
          $code = $e->getCode();
          $error_message = $e->getMessage();
          echo $code.": ".$error_message."<br />";
      }

      $entity = $result->getEntity();
      $chat = $entity->getPropertyValue("Convo");
      $message = $_POST['msg'];
      $chat = $chat."<br/>".$current_user.":".$message;
      $entity->setPropertyValue("Convo", $chat); //Modified DueDate.
      try    {
          $tableClient->updateEntity("Messages", $entity);
      }
      catch(ServiceException $e){
          $code = $e->getCode();
          $error_message = $e->getMessage();
          echo $code.": ".$error_message."<br />";
      }
  }

  ?>
  </table>
  </div>

  <form align="center" class="send-message-form" method="post" action="patient_message.php">
    <br/><br/><br/><br/>
    <input type="text" name="msg" placeholder="Type your message here" size="80" style="height: 35px; margin-left:5%;"/>
    <input type="submit" name="send" value="Send" method="post" style="font-size : 15px; width: 100px; height: 35px; color:#000000;"; bgcolor="#C0C0C0">
  </form>
</div>
<!-- <div class="outer-report-container">  -->


<!-- </div> -->
    <script src="../js/jquery.min.js"></script>
    <script src="../js/jquery.form.js"></script>
    <script>
      function reload() {
        $(".tableclass").load(location.href + " .tableclass>*", "");
      }
      setInterval(reload, 1000);
      
      $("a").click(function (event) {
          if ($(this).hasClass("disabled")) {
              event.preventDefault();
          }
          $(this).addClass("disabled");
      });

      $(document).ready(function(){
        var ul = $("ul");
        var li = ul.children("a");
        li.detach().sort(function(a,b){
          if(a.children[0].className=="square-active") return -1;
          if(b.children[0].className=="square-active") return +1;
          return 0;
        });
        ul.append(li);
        var scroll=$('.tableclass');
        scroll.scrollTop(scroll.prop("scrollHeight"));
      })

      $(".search-query").keyup(function() {
          $("#x").show();
          if ($.trim($(".search-query").val()) == "") {
              $("#x").hide();
              $('li.square').removeClass('hidden');
          }else{
            var query = $.trim($(".search-query").val()).toLowerCase();
            $('li.square').each(function(){
              var $this = $(this);
                if($this.text().toLowerCase().indexOf(query) === -1){
                  $this.closest('li.square').addClass('hidden');
                }else{
                  $this.closest('li.square').removeClass('hidden');
                }
            });
          }
      });

      $("#x").click(function() {
        $(".search-query").val("");
        $('li.square').removeClass('hidden');
        $(this).hide();
      });

    </script>

</body>
</html>