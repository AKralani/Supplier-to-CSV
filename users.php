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

// Check if the user is an admin
$is_admin = false;
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    $stmt = $pdo->prepare("SELECT role_id FROM users WHERE id = :id");
    $stmt->bindParam(":id", $_SESSION["id"], PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $is_admin = ($result["role_id"] === "1");
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["user_id"]) && isset($_POST["new_role_id"]) && isset($_POST["new_username"])) {
        $user_id = $_POST["user_id"];
        $new_role_id = $_POST["new_role_id"];
        $new_username = $_POST["new_username"];

        // Check if the user is allowed to edit the username and role_id
        if (!$is_admin) {
            // Redirect to an error page or display an error message
            header("Location: error.php");
            exit;
        }

        // Update the username and role_id
        $stmt = $pdo->prepare("UPDATE users SET username = :username, role_id = :role_id WHERE id = :id");
        $stmt->bindParam(":id", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":username", $new_username, PDO::PARAM_STR);
        $stmt->bindParam(":role_id", $new_role_id, PDO::PARAM_INT);
        $stmt->execute();
    }
    else if (isset($_POST["delete_user_id"])) {
        $delete_user_id = $_POST["delete_user_id"];

        // Check if the user is allowed to delete the user
        if (!$is_admin) {
            // Redirect to an error page or display an error message
            header("Location: error.php");
            exit;
        }

        // Delete the user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->bindParam(":id", $delete_user_id, PDO::PARAM_INT);
        $stmt->execute();
    }
}

if ($is_admin) {

    // Prepare a SELECT statement to fetch all users
    $stmt = $pdo->prepare("SELECT * FROM users");

    // Execute the statement
    $stmt->execute();

    // Fetch all the results
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} else {
    // Prepare a SELECT statement to fetch the data of the logged-in user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");

    // Bind the parameter
    $stmt->bindParam(":id", $_SESSION["id"], PDO::PARAM_INT);

    // Execute the statement
    $stmt->execute();

    // Fetch the result
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Put the data of the logged-in user into an array to loop through
    $users = array($user);
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <style>
        table {
            font-family: Arial, Helvetica, sans-serif;
            border-collapse: collapse;
            width: 100%;
        }

        thead {
            background-color: #ccc;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        th {
            padding-top: 12px;
            padding-bottom: 12px;
            text-align: left;
            background-color: #04AA6D;
            color: white;
        }
        </style>
    </head>
    <body>
    <?php include 'theme-button.php'; ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role ID</th>
                    <th>Created At</th>
                    <?php if ($is_admin): ?>
                        <th>Edit</th>
                        <th>Delete</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td>
                        <?php if ($is_admin && ($user['role_id'] !== 1 || $user['id'] !== $_SESSION['id'])): ?>
                            <input type="text" id="username-<?php echo $user['id']; ?>" value="<?php echo $user['username']; ?>">
                        <?php else: ?>
                            <?php echo $user['username']; ?>
                        <?php endif; ?>
                        </td>
                        <td><?php echo ($user['role_id'] == 1 ? 'Admin' : 'Simple User'); ?></td>
                        <td><?php echo $user['created_at']; ?></td>
                        <?php if ($is_admin  == "1"): ?>
                            <td>
                                <button onclick="showEditForm(<?php echo $user['id']; ?>, '<?php echo $user['role_id']; ?>')" class="btn btn-success">Edit</button>
                            </td>
                            <td>
                                <form method="post" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                    <input type="hidden" name="delete_user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn btn-danger ml-3">Delete</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$is_admin): ?>
        <p>Contact Administrator if you want to edit your data.</p>
<?php endif; ?>
        <div id="edit-form" style="display: none;">
            <h3>Edit User Role</h3>
            <form method="post">
                <input type="hidden" name="user_id" id="user-id-input">
                <label for="username-input">Username:</label>
                <input type="text" name="new_username" id="username-input">
                <label for="role-id-select">Role ID:</label>
                <select name="new_role_id" id="role-id-select">
                    <option value="1">Admin</option>
                    <option value="0">Simple User</option>
                </select>
                <button type="submit" class="btn btn-success">Save</button>
            </form>
        </div>
        <p>
            <a href="welcome.php" class="btn btn-primary ml-3">Go back</a>
            <a href="reset-password.php" class="btn btn-warning">Reset Your Password</a>
        </p>

        <script>
        function showEditForm(userId, currentRoleId) {
            // Show the edit form
            document.getElementById("edit-form").style.display = "block";

            // Set the user ID input value
            document.getElementById("user-id-input").value = userId;

            // Set the current username value
            document.getElementById("username-input").value = document.getElementById("username-" + userId).value;

            // Set the selected option in the role ID select
            var roleSelect = document.getElementById("role-id-select");
            for (var i = 0; i < roleSelect.options.length; i++) {
                var option = roleSelect.options[i];
                if (option.value === currentRoleId) {
                    option.selected = true;
                    break;
                }
            }
        }
        </script>
    </body>
</html>
