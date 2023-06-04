<?php
// Initialize the session
session_start();

// Check if the user has chosen a theme
if (!isset($_SESSION['theme'])) {
  // Set the default theme to light
  $_SESSION['theme'] = 'light';
} else {
  // Check if the user has toggled the theme
  if (isset($_GET['theme'])) {
      $_SESSION['theme'] = $_GET['theme'];
  }
}

// Include the database configuration file
require_once "config.php";

// Prepare a SELECT statement to fetch the role_id of the logged-in user
$stmt = $pdo->prepare("SELECT role_id FROM users WHERE id = :id");

// Bind the parameter
$stmt->bindParam(":id", $_SESSION["id"], PDO::PARAM_INT);

// Execute the statement
$stmt->execute();

// Fetch the result
$result = $stmt->fetch(PDO::FETCH_ASSOC);

// Get the role_id
$role_id = $result["role_id"];
 
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        body{ font: 14px sans-serif; text-align: center; }
    </style>
    <style>
      .dropbtn {
        background-color: #04AA6D;
        color: white;
        padding: 16px;
        font-size: 16px;
        border: none;
      }

      .dropdown {
        position: relative;
        display: inline-block;
      }

      .dropdown-content {
        display: none;
        position: absolute;
        background-color: #f1f1f1;
        min-width: 160px;
        box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
        z-index: 1;
      }

      .dropdown-content a {
        color: black;
        padding: 12px 16px;
        text-decoration: none;
        display: block;
      }

      .dropdown-content a:hover {background-color: #ddd;}

      .dropdown:hover .dropdown-content {display: block;}

      .dropdown:hover .dropbtn {background-color: #3e8e41;}

      @keyframes download {
        0% {
          transform: scale(1);
        }
        50% {
          transform: scale(0.8);
        }
        100% {
          transform: scale(1);
        }
      }

      .download-link {
        transition: transform 0.5s;
      }

      .download-link.downloading {
        animation: download 1s infinite;
        color: green;
      }
    </style>
    <script>
      function startDownloadAnimation(element) {
        element.classList.add("downloading");
        setTimeout(function() {
          element.classList.remove("downloading");
        }, 10000); // Adjust the delay as needed
      }
    </script>
</head>
<body>
    <?php include 'theme-button.php'; ?>
    <h1 class="my-5">Hi, <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>. Welcome to the site.</h1>
    <p>
        <a href="reset-password.php" class="btn btn-warning">Reset Your Password</a>
        <a href="logout.php" class="btn btn-danger ml-3">Sign Out of Your Account</a>
        <a href="users.php" class="btn btn-primary ml-3">Viev User Data</a>
    </p>

    <?php if ($role_id == 1): ?>
    <h2>Generate CSV List</h2>
    <p>Move the mouse over the button to open the list of suppliers.</p>
    <p>Use button <span style="color: red;">All in One</span> to Get all products in a single CSV file, but only after you have downloaded all files before.</p>

    <div class="dropdown">
      <button class="dropbtn">GENERATE</button>
      <div class="dropdown-content">
        <a href="kosatec_csv.php" class="download-link" onclick="startDownloadAnimation(this)">KOSATEC</a>
        <a href="wave_csv.php" class="download-link" onclick="startDownloadAnimation(this)">WAVE DISTRIBUTION</a>
        <a href="mediacom_csv.php" class="download-link" onclick="startDownloadAnimation(this)">MEDIACOM</a>
        <a href="coscomputer_csv.php" class="download-link" onclick="startDownloadAnimation(this)">COS COMPUTER</a>
        <a href="makant_csv.php" class="download-link" onclick="startDownloadAnimation(this)">MAKANT EUROPE</a>
        <a href="siewert_csv.php" class="download-link" onclick="startDownloadAnimation(this)">SIEWERT & KAU</a>
        <!-- Button to trigger the modal -->
        <a href="button" class="download-link" data-toggle="modal" data-target="#generateModal"><span style="color: red;">All in One</a>
      </div>
    </div>
    
    <!-- Modal -->
    <div class="modal fade" id="generateModal" tabindex="-1" role="dialog" aria-labelledby="generateModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="generateModalLabel">Generate All in One</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <form action="allinOne.php" method="POST">
              <p>Select suppliers to include:</p>
              <?php
              $tableNames = ['kosatec', 'wave', 'mediacom', 'coscomputer', 'makant', 'siewert'];
              foreach ($tableNames as $tableName) {
                  echo '<label><input type="checkbox" name="tables[]" value="' . $tableName . '"> ' . $tableName . '</label><br>';
              }
              ?>
            </div>
            <p>Choose to exclude products with zero (0) stock:</p>
            <label><input type="radio" name="data_inclusion_Stock" value="excludeStock"> Exclude</label>
            <label><input type="radio" name="data_inclusion_Stock" value="includeStock" checked> Include</label>

            <p>Choose to exclude products with the same EAN:</p>
            <label><input type="radio" name="data_inclusion_EAN" value="includeEAN" checked> Include products with the same EAN</label>
            <label><input type="radio" name="data_inclusion_EAN" value="excludeEAN"> Exclude products with the same EAN, and include only the lowest price</label>

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-primary" onclick="startDownloadAnimation(this)">Generate</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <?php else: ?>
      <p>You do not have permission to generate CSV lists.</p>
    <?php endif; ?>

</body>
</html>