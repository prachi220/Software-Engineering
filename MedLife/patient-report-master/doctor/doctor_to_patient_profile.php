<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
    <link rel="stylesheet" href="../css/patient_profile_style.css">
    <title>Patient Profile</title>
    <meta charset="utf-8">
    </head>
</head>
<?php
session_start();


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

if ($_GET['val']){
  $_SESSION['current_patient'] = $_GET['val'];
}
$current_patient = $_SESSION['current_patient'];
if (isset($_SESSION['current_patient'])) {
  query_details($current_patient);
}
?>

<body bgcolor="#696969">
<link rel="stylesheet" href="../css/patient_profile_style.css">

<div class="topnav">
  <img src="../logo.png" style="margin-bottom: -0.6%">
  <a href="logout.php" style="background-color: #1ab188"> Logout </a>
  <a href="doctor_message.php">Messages</a>
  <a href="doctor_access_list.php" style="background-color: #a0b3b0; color: black">Access List</a>
  <a href="doctor_profile.php" href="#home">Profile</a>
</div>

<div id="wrapper">
  <div class="float form_container">
    <form method="post" action="doctor_to_patient_profile.php">
      <br>
      <div class="formSection readOnly">
      <div class="row">
          <div class="col-md-5">
              <div class="panel panel-info">
                <div class="panel-heading" style="background-color: #31708f; color: white">
                    <div class="pull-left">
                        <i class="glyphicon glyphicon-user"></i> Personal Information
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="panel-body" style="background-color: #d9edf7">
                    <div class="row">
                        <div >
                          <div>
                              <div class="col-md-4">
                              <label for="fname">First Name</label>
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
                              <label for="lname">Last Name</label>
                              </div>
                              <div class="col-md-8">
                              <input type="text" name="lname" style="width: 70%" id="lname" value="<?php echo $_SESSION['lname']; ?>" placeholder="Enter last Name" disabled>
                              </div>
                            </div>
                            <br>
                            <br>
                            <br>
                            <div>
                              <div class="col-md-4">
                              <label for="age">Age</label>
                              </div>
                              <div class="col-md-8">
                              <input type="number" style="margin-top: 1vh" id="age" name="age" value="<?php echo $_SESSION['age']; ?>" placeholder="Enter Your Age" disabled>
                              </div>
                            </div>
                            <br>
                            <br>
                            <br>
                            <div>
                              <div class="col-md-4">
                              <label for="sex">Sex</label>
                              </div>
                              <div class="col-md-8">
                              <input type="text" name="sex" style="width: 33%" id="sex" value="<?php echo $_SESSION['sex']; ?>" placeholder="Male/Female" disabled>
                              </div>
                            </div>
                            <br>
                            <br>
                            <br>
                            <div>
                              <div class="col-md-4">
                              <label for="bgroup">Blood Group</label>
                              </div>
                              <div class="col-md-8">
                              <input type="text" name="bgroup" style="width: 45%" id="bgroup" value="<?php echo $_SESSION['bgroup']; ?>" placeholder="Enter Blood group" disabled>
                              </div>
                            </div>
                        </div>
                    </div>
                </div>
              </div>
          </div>
          <div class="col-md-6">
              <div class="panel panel-danger">
                <div class="panel-heading" style="background-color: #d9534f; color: white">
                    <div class="pull-left">
                        <i class="glyphicon glyphicon-plus"></i> Medical Information
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="panel-body" style="background-color: #f2dede">
                    <div class="row">
                        <div >
                          <div>
                              <div class="col-md-4">
                              <label for="allergies">Allergies (if any)</label>
                              </div>
                              <div class="col-md-8">
                              <textarea name="allergies" id="allergies" cols="50" rows="3" placeholder="Allergies such as pollen, dust, peanuts etc." disabled><?php echo $_SESSION['allergies']; ?></textarea>
                              </div>
                            </div>
                            <br>
                            <br>
                            <br>
                            <br>
                            <div>
                              <div class="col-md-4">
                              <label for="habits">Habits (if any)</label>
                              </div>
                              <div class="col-md-8">
                              <textarea name="habits" id="habits" cols="50" rows="3" placeholder="Habits such as smoking/drinking/drugs" disabled><?php echo $_SESSION['habits']; ?></textarea>
                              </div>
                            </div>
                            <br>
                            <br>
                           <div>
                              <div class="col-md-4">
                              <label for="illness">Family History of illness (if any)</label>
                              </div>
                              <div class="col-md-8">
                              <textarea name="illness" id="illness" cols="50" rows="3" placeholder="Enter family history of illness" disabled><?php echo $_SESSION['illness']; ?></textarea>
                              </div>
                            </div>
                            <br>
                            <br>
                            <div>
                              <div class="col-md-4">
                              <label for="diseases">Past major diseases (if any)</label>
                              </div>
                              <div class="col-md-8">
                              <textarea name="diseases" id="diseases" cols="50" rows="3" placeholder="Enter past major diseases" disabled><?php echo $_SESSION['diseases']; ?></textarea>
                              </div>
                            </div>
                            <br>
                            <br>
                            <div>
                              <div class="col-md-4">
                              <label for="surgeries">Past surgeries (if any)</label>
                              </div>
                              <div class="col-md-8">
                              <textarea name="surgeries" id="surgeries" cols="50" rows="3" placeholder="Enter past surgeries" disabled><?php echo $_SESSION['surgeries']; ?></textarea>
                              </div>
                            </div>
                            
                        </div>
                    </div>
                </div>
              </div>
          </div>
      <a href="doctor_access_list.php" style="background-color:#23838c; color:white; border-radius: 6px; text-decoration:none; border: 1px solid #fff; padding: 5px"> Go back </a>
      </div>
</div>
<?php

// Fetch profile details of the current user
function query_details($current_user){
  $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
  $tableClient = TableRestProxy::createTableService($connectionString);

  try    {
    $result = $tableClient->getEntity("Patients", $current_user, $current_user);
  }
  catch(ServiceException $e){
    $code = $e->getCode();
    $error_message = $e->getMessage();
    echo $code.": ".$error_message."<br />";
  }

  $entity = $result->getEntity();

  $fname = $entity->getPropertyValue("FirstName");
  $lname = $entity->getPropertyValue("LastName");
  $age = $entity->getPropertyValue("Age");
  $sex = $entity->getPropertyValue("Sex");
  $bgroup = $entity->getPropertyValue("Bgroup");
  $allergies = $entity->getPropertyValue("Allergies");
  $habits = $entity->getPropertyValue("Habits");
  $illness = $entity->getPropertyValue("Illness");
  $diseases = $entity->getPropertyValue("Diseases");
  $surgeries = $entity->getPropertyValue("Surgeries");

  $_SESSION['fname'] = $fname;
  $_SESSION['lname'] = $lname;
  $_SESSION['age'] = $age;
  $_SESSION['sex'] = $sex;
  $_SESSION['bgroup'] = $bgroup;
  $_SESSION['allergies'] = $allergies;
  $_SESSION['habits'] = $habits;
  $_SESSION['illness'] = $illness;
  $_SESSION['diseases'] = $diseases;
  $_SESSION['surgeries'] = $surgeries;

}

?>
  </form>

  </div>

</div>
    <script src="../js/jquery.min.js"></script>
    <script src="../js/jquery.form.js"></script>
    <script type="text/javascript">
      $(document).ready(function(){
        $("#edit_details").click(function(){
          $("#fname").prop("readonly",false);
          $("#lname").prop("readonly",false);
          $("#age").prop("readonly",false);
          $("#sex").prop("readonly",false);
          $("#bgroup").prop("readonly",false);
          $("#allergies").prop("readonly",false);
          $("#habits").prop("readonly",false);
          $("#illness").prop("readonly",false);
          $("#diseases").prop("readonly",false);
          $("#surgeries").prop("readonly",false);
        });
      });
    </script>
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
              var textareas = section.find("textarea");

              section
                .removeClass("readOnly");
              oldValues = {};
              inputs
                .each(function () { oldValues[this.id] = $(this).val(); })
                .prop("disabled", false);
              textareas
                .each(function () { oldValues[this.id] = $(this).val(); })
                .prop("disabled", false);
          })

          .on("click", ".cancelButton", function (e) {

              var section = $(this).closest(".formSection");
              var otherSections = $(".formSection").not(section);
              var inputs = section.find("input");
              var textareas = section.find("textarea");

              section
                .addClass("readOnly");

              $('button').prop("disabled", false);

              inputs
                .each(function() { $(this).val(oldValues[this.id]); })
                .prop("disabled", true)
              textareas
                .each(function() { $(this).val(oldValues[this.id]); })
                .prop("disabled", true)

              e.stopPropagation();
              e.preventDefault();
          });

        </script>
</body>
</html>