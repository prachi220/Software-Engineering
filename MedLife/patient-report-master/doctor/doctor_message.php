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
  send_message($current_patient, $current_user);
  echo "<meta http-equiv='refresh' content='0'>";
}

$patient_array = array();

?>

<body bgcolor="#696969">

<div class="topnav">
  <img src="../logo.png" style="margin-bottom: -0.6%">
  <a id="logout" href="logout.php" style="background-color: #1ab188"> Logout </a>
  <a href="doctor_message.php"  style="background-color: #a0b3b0; color: black">Messages</a>
  <a href="doctor_access_list.php">Access List</a>
  <a href="doctor_profile.php" href="#home">Profile</a>
</div>

<div class="right-panel">
  <input type="text" class="search-query" placeholder="Search Patients" style="margin-left: 5%; width: 80%">
  <div id="remove-search">
      <span id="x">x</span>
  </div>
  <br>
  <br>
  <ul class="userlist">
  <?php

  show_all($current_user);

  // Shows all the patients who have given access to the doctor.
  function show_all( string $current_user){

    $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
    $tableClient = TableRestProxy::createTableService($connectionString);

    $filter = "PartitionKey eq '".$current_user."'";
    try    {
        $result_pat = $tableClient->queryEntities("AccessDoctor", $filter);
    }
    catch(ServiceException $e){
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message."<br />";
    }

    $entities_pat = $result_pat->getEntities();

    $count = 0;
    foreach($entities_pat as $entity){
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

      array_push($GLOBALS['patient_array'], $pat);
      
      $href_name = "link".$count;
      $href_val = "?link=".$count;
      if($_GET['link'] != ''){
        if($_GET['link'] != ''.$count){
          echo '<a style="display: block;" href="'.$href_val.'" name="'.$href_name.'"><li class="square">'.$pat_fullname.'</li></a>';
        }else{
          echo '<a style="display: block;" href="'.$href_val.'" name="'.$href_name.'"><li class="square-active">'.$pat_fullname.'</li></a>';
        }    
      }else{
        if($_SESSION['current_patient'] != $pat){
          echo '<a style="display: block;" href="'.$href_val.'" name="'.$href_name.'"><li class="square">'.$pat_fullname.'</li></a>';
        }else{
          echo '<a style="display: block;" href="'.$href_val.'" name="'.$href_name.'"><li class="square-active">'.$pat_fullname.'</li></a>';
        }
      }
      $count += 1;
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
$cnt = count($GLOBALS['patient_array']);
for ($i=0; $i < $cnt; $i++) { 
  $link=$_GET['link'];
  if ($link == ''.$i){
    $_SESSION['current_patient'] = $GLOBALS['patient_array'][$i];
    $current_patient = $_SESSION['current_patient'];
    // show_message($current_user, $current_patient);
    break;
  }
}

if ($current_patient!=NULL){
  show_message($current_patient, $current_user);
}

//Shows chat with the selected patient.
function show_message($current_patient, $current_user){
    $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
    $tableClient = TableRestProxy::createTableService($connectionString);

    $filter = "PartitionKey eq '".$current_patient."'";
    // echo $filter;
    try    {
        $result = $tableClient->getEntity("Messages", $current_patient, $current_user);
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

//Appends new message in the chat with the selected patient
function send_message($current_patient, $current_user){
    $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
    $tableClient = TableRestProxy::createTableService($connectionString);

    $filter = "PartitionKey eq '".$current_patient."'";
    try    {
        $result = $tableClient->getEntity("Messages", $current_patient, $current_user);
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

  <form align="center" class="send-message-form" method="post" action="doctor_message.php">
    <br/><br/><br/><br/>
    <input type="text" name="msg" placeholder="Type your message here" size="80" style="height: 35px; margin-left:5%;"/>
    <input type="submit" name="send" value="Send" method="post" style="font-size : 15px; width: 100px; height: 35px; color:#000000;"; bgcolor="#C0C0C0">
  </form>
</div>
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