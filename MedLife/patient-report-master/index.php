<!DOCTYPE HTML>
<html>
  <head>
    <title>Login Page</title>
    <link rel="stylesheet" href="../css/index_style.css">
    <meta charset="utf-8">
    <script src="./js/jquery.min.js"></script>
    <script src="./js/jquery.form.js"></script>
  </head>
  <body>
    <h1 style="color:#314652; font-size:400%;" align="center">MedLife</h1>
      <div class="form">      
        <ul class="tab-group">
          <li class="tab active"><a href="#signup">Sign Up</a></li>
          <li class="tab"><a href="#login">Log In</a></li>
        </ul>        
        <div class="tab-content">
          <div id="signup">   
            <h1>Sign Up for Free</h1>            
            <form method="post" action="index.php">
              <div class="top-row">
                <div class="field-wrap">
                  <label>
                    First Name/ Lab Name<span class="req">*</span>
                  </label>
                  <input type="text" name="fname" required autocomplete="off" />
                </div>           
                <div class="field-wrap">
                  <label>
                    Last Name
                  </label>
                  <input type="text" name="lname"/>
                </div>
              </div>
              <div class="field-wrap">
                <label>
                  Username<span class="req">*</span>
                </label>
                <input type="text" name="usernameR" required autocomplete="off"/>
              </div>
              <div class="field-wrap">
                <label>
                  Password<span class="req">*</span>
                </label>
                <input type="password" name="passwordR" required autocomplete="off"/>
              </div>
              <div class="field-wrap">
                <label class="active">
                  Role<span class="req">*</span>
                </label>
                <select name = "roleR">
                  <option value="Patient">Patient</option>
                  <option value="Lab">Lab</option>
                  <option value="Doctor">Doctor</option>
                </select>
              </div> 
              <button type="submit" class="button button-block" name="register" method="post"/>Get Started</button>
            </form>
          </div>
<?php
session_start();


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
$error = "";
if(isset($_POST['register'])) {
  register();
}

// Call only when new table has to be created. The name of new table should be different from existing ones.
function createtable(){
    $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
    $tableClient = TableRestProxy::createTableService($connectionString);
    try {
        $tableClient->createTable("Users");
    }
    catch(ServiceException $e){
      $code = $e->getCode();
      $error_message = $e->getMessage();
      echo $code.": ".$error_message."<br />";
    }

}

// Registers the user as Patient, Doctor or Lab.
// In case of a new Patient is being registered, it creates a new container for the images of the reports for the particular patient.
function register(){
  $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
  $tableClient = TableRestProxy::createTableService($connectionString);
  $fname = $_POST['fname'];
  $lname = $_POST['lname'];
  $username = $_POST['usernameR'];
  $password = $_POST['passwordR'];
  $registeras = $_POST['roleR'];

  if (strlen($password)<6){
    echo '<script> alert("Password must be atleast 6 characters long."); </script>';
  }else{
    $filter = "PartitionKey eq '".$registeras."'";
    try    {
        $result = $tableClient->queryEntities("Users", $filter);
    }
    catch(ServiceException $e){
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message."<br />";
    }
    $entities1 = $result->getEntities();
    $accountexists = 0;
    foreach($entities1 as $entity){
      $user = $entity->getPropertyValue("Username");
      if(strcmp($user, $username )== 0)
      {
        $accountexists = 1;
        break;
      }
    }

    if ($accountexists==1) {
        echo '<script> alert("The username already exists. Please try another username."); </script>';
    }
    else{
      if (preg_match("/^[A-Z]/", $username )==true){
          echo '<script> alert("Username must start with a small letter"); </script>';
      }else{
          require_once 'vendor/autoload.php';
          $entity = new Entity();
          $entity->setPartitionKey($registeras);
          $entity->setRowKey($username);
          $entity->addProperty("FirstName",EdmType::STRING,$fname);
          $entity->addProperty("LastName",EdmType::STRING,$lname);
          $entity->addProperty("Username",EdmType::STRING,$username);
          $entity->addProperty("Password",EdmType::STRING,$password);
          $entity->addProperty("Role",EdmType::STRING,$registeras);

          try{
              $tableClient->insertEntity("Users", $entity);
          }
          catch(ServiceException $e){
              $code = $e->getCode();
              $error_message = $e->getMessage();
          }

          if (strcmp($registeras, "Patient")==0){
            createContainer($username);
          }
          add_details($username, $fname, $lname, $registeras);
          echo '<script> alert("You have successfully registered!"); </script>';
        }
    }
  }
} 

//Creates a container with patient's username if the new user registered is a patient.
function createContainer(string $username){
    $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
    $blobClient = BlobRestProxy::createBlobService($connectionString);
    $containerName = $username;
    $createContainerOptions = new CreateContainerOptions();

    $createContainerOptions->setPublicAccess(PublicAccessType::CONTAINER_AND_BLOBS);

    // Set container metadata.
    $createContainerOptions->addMetaData("key1", "value1");
    $createContainerOptions->addMetaData("key2", "value2");
    try{
      // Create container.
          $blobClient->createContainer($containerName, $createContainerOptions);
    }
    catch(ServiceException $e){
      $code = $e->getCode();
      $error_message = $e->getMessage();
      echo $code.": ".$error_message."<br />";
    }
}   

//Initialises all the profile details of the new user to None.
function add_details(string $username, string $fname, string $lname, string $role){
  $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
  $tableClient = TableRestProxy::createTableService($connectionString);
  require_once 'vendor/autoload.php';

  if (strcmp($role, "Patient")==0){
    $age = "";
    $sex = "";
    $bgroup = "";
    $allergies = "";
    $habits = "";
    $illness = "";
    $diseases = "";
    $surgeries = "";

    $entity = new Entity();
    $entity->setPartitionKey($username);
    $entity->setRowKey($username);
    $entity->addProperty("FirstName",EdmType::STRING,$fname);
    $entity->addProperty("LastName",EdmType::STRING,$lname);
    $entity->addProperty("Age",EdmType::STRING,$age);
    $entity->addProperty("Sex",EdmType::STRING,$sex);
    $entity->addProperty("Bgroup",EdmType::STRING,$bgroup);
    $entity->addProperty("Allergies",EdmType::STRING,$allergies);
    $entity->addProperty("Illness",EdmType::STRING,$illness);
    $entity->addProperty("Habits",EdmType::STRING,$habits);
    $entity->addProperty("Diseases",EdmType::STRING,$diseases);
    $entity->addProperty("Surgeries",EdmType::STRING,$surgeries);

    try{
        $tableClient->insertOrReplaceEntity("Patients", $entity);
    }
    catch(ServiceException $e){
        $code = $e->getCode();
        $error_message = $e->getMessage();
    }
  }

  if (strcmp($role, "Doctor")==0){
    $age = "";
    $sex = "";
    $specialisation = "";
    $address = "";
    $time = "";
    $contact = "";

    $entity = new Entity();
    $entity->setPartitionKey($username);
    $entity->setRowKey($username);
    $entity->addProperty("FirstName",EdmType::STRING,$fname);
    $entity->addProperty("LastName",EdmType::STRING,$lname);
    $entity->addProperty("Age",EdmType::STRING,$age);
    $entity->addProperty("Sex",EdmType::STRING,$sex);
    $entity->addProperty("Specialisation",EdmType::STRING,$specialisation);
    $entity->addProperty("Address",EdmType::STRING,$address);
    $entity->addProperty("Time",EdmType::STRING,$time);
    $entity->addProperty("Contact",EdmType::STRING,$contact);

    try{
        $tableClient->insertOrReplaceEntity("Doctors", $entity);
    }
    catch(ServiceException $e){
        $code = $e->getCode();
        $error_message = $e->getMessage();
    }
  }

  if (strcmp($role, "Lab")==0){
    $address = "";
    $time = "";
    $contact = "";

    $entity = new Entity();
    $entity->setPartitionKey($username);
    $entity->setRowKey($username);
    $entity->addProperty("LabName",EdmType::STRING,$fname);
    $entity->addProperty("Address",EdmType::STRING,$address);
    $entity->addProperty("Time",EdmType::STRING,$time);
    $entity->addProperty("Contact",EdmType::STRING,$contact);

    try{
        $tableClient->insertOrReplaceEntity("Labs", $entity);
    }
    catch(ServiceException $e){
        $code = $e->getCode();
        $error_message = $e->getMessage();
    }
  }
}    

?>


<?php
if(isset($_POST['login'])) {
  login();
}

// Login as Patient, Lab or Doctor. Does not allow you to login if there is any mismatch in the values of username, password and Role.
function login(){
  require_once 'vendor/autoload.php';
  $connectionString = 'DefaultEndpointsProtocol=http;AccountName=csg59433326242cx4d55x805;AccountKey=KwdFxSy/v8nzx0lcNQ9FdiwaJQsOP4IavOsKwTxIZUquOvFSaWdzJ6PLEJNlenJ6mY+2tg9IIZmfSkQdAD/0Gw==';
  $tableClient = TableRestProxy::createTableService($connectionString);
  $username = $_POST['username'];
  $password = $_POST['password'];
  $role = $_POST['role'];
  $filter = "PartitionKey eq '".$role."'";

  try    {
      $result = $tableClient->queryEntities("Users", $filter);
  }
  catch(ServiceException $e){
      $code = $e->getCode();
      $error_message = $e->getMessage();
      echo $code.": ".$error_message."<br />";
  }
  $entities1 = $result->getEntities();
  $accountexists = 0;
  foreach($entities1 as $entity){
    $user = $entity->getPropertyValue("Username");
    $pass = $entity->getPropertyValue("Password");
    $role_g = $entity->getPropertyValue("Role");
    if(strcmp($user, $username )== 0 && strcmp($pass, $password )== 0 && strcmp($role_g, $role )== 0)
    {
      $accountexists = 1;
      break;
    }
  }
  if ($accountexists == 0){
     echo '<script> alert("Username/Password/Role do not match."); </script>';
  }
  else{
  // Redirect to corresponding profile according to the role.
      $_SESSION['current_user'] = $username;
      $_SESSION['role'] = $role;
      if (strcmp($role, "Doctor")==0) {
          header('Location: ../doctor/doctor_profile.php');
      }
      if (strcmp($role, "Lab")==0) {
          header('Location: ../lab/lab_profile.php');
      }
      if (strcmp($role, "Patient")==0) {
          header('Location: ../patient/patient_profile.php');
      }
  }
}
?>

          <div id="login">   
            <h1>Welcome Back!</h1>
            <form method="post" action="index.php">
              <div class="field-wrap">
                <label>
                  Username<span class="req">*</span>
                </label>
                <input type="text" name="username" required autocomplete="off"/>
              </div>
              <div class="field-wrap">
                <label>
                  Password<span class="req">*</span>
                </label>
                <input type="password" name="password" required autocomplete="off"/>
              </div>
              <div class="field-wrap">
                <label class="active">
                  Role<span class="req">*</span>
                </label>
                <select name = "role">
                  <option value="Patient">Patient</option>
                  <option value="Lab">Lab</option>
                  <option value="Doctor">Doctor</option>
                </select>
              </div>
              <button class="button button-block" name="login" method="post"/>Log In</button>
            </form>
          </div>
        </div>
    </div>  
     <script type="text/javascript">
      $('.form').find('input, textarea').on('keyup blur focus', function (e) {

        var $this = $(this),
            label = $this.prev('label');

          if (e.type === 'keyup') {
            if ($this.val() === '') {
                label.removeClass('active highlight');
              } else {
                label.addClass('active highlight');
              }
          } else if (e.type === 'blur') {
            if( $this.val() === '' ) {
              label.removeClass('active highlight'); 
            } else {
              label.removeClass('highlight');   
            }   
          } else if (e.type === 'focus') {
            
            if( $this.val() === '' ) {
              label.removeClass('highlight'); 
            } 
            else if( $this.val() !== '' ) {
              label.addClass('highlight');
            }
          }

      });

      $('.tab a').on('click', function (e) {
        
        e.preventDefault();
        
        $(this).parent().addClass('active');
        $(this).parent().siblings().removeClass('active');
        
        target = $(this).attr('href');

        $('.tab-content > div').not(target).hide();
        
        $(target).fadeIn(600);
        
      });  
       
    </script>
  </body>   
</html>