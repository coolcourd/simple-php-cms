<?php

if ($_GET['json']) {
    $json = file_get_contents('data.json');
    // allow coors
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET");
    header("Content-Type: application/json; charset=UTF-8");
    echo $json;
    die();
}

include('config.php');
session_start();

if (isset($_GET['logout'])) {
    unset($_SESSION['user']);
    session_destroy();
    header("Location: login.php?loggedout");
    die();
}

$date = date('Y-m-d');
$log_file = "logs/$date.txt";
$ip = $_SERVER['REMOTE_ADDR'];


function blacklisted()
{
    $ip = $_SERVER['REMOTE_ADDR'];
    $date = date('Y-m-d');
    $log_file = "logs/$date.txt";
    $lines = file($log_file);
    $count = 0;
    foreach ($lines as $line) {
        if (strpos($line, $ip) !== false) {
            $count++;
        }
    }
    if ($count > 3) {
        header("Location: blacklisted.html");
        die();
    }
}

blacklisted();

if (isset($_POST['password'])) {
    if ($_POST['password'] == PASSWORD) {
        $_SESSION['loggedin'] = true;
    } else {
        file_put_contents($log_file, $ip . "\n", FILE_APPEND);
        header("Location: login.php?wrong=true");
        die();
    }
}
session_regenerate_id();
if (!isset($_SESSION['loggedin']))      // if there is no valid session
{
    header("Location: login.php?nosession=true");
    die();
}


function updateData($data)
{
    file_put_contents('data.json', json_encode($data));
}
if (!file_exists('data.json')) {
    $data = [];
    updateData($data);
}

$data = json_decode(file_get_contents('data.json'), true);


// check if POST title and body are set
if (isset($_POST['title']) && isset($_POST['body'])) {
    $title = $_POST['title'];
    $body = $_POST['body'];
    $data[$title] = $body;
    if (empty($body)) {
        unset($data[$title]);
    }

    updateData($data);
}

// check if GET title is set
if (isset($_GET['title'])) {
    $default_title = $_GET['title'];
    if (isset($data[$default_title])) {
        $default_body = $data[$default_title];
    } else {
        $default_body = "";
    }
} else {
    $default_title = "";
    $default_body = "";
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>cms</title>
    <link rel="stylesheet" href="/cms.css">
</head>

<body>
    <a href="/cms.php?logout" class="logout">logout</a>
    <div class="container">
        <h1>CMS</h1>
        <div class="rows">
            <a href="/cms.php" class='clear'>new</a>
            <!-- loop over data and render links -->
            <?php foreach ($data as $title => $body) : ?>
                <a href="/cms.php?title=<?= $title ?>"><?= $title ?></a>
            <?php endforeach ?>
        </div>
        <div class="flex">
            <div class='flex'>
                <form action="" method="post">
                    <div class="group">
                        <input type='text' name='title' placeholder='Title' value='<?php echo $default_title ?>' id="title">
                        <textarea name="body" class='vw80' id="body-text" cols="30" rows="10"><?php echo $default_body ?></textarea>
                        <button type='submit'>Save</button>
                    </div>
                </form>
            </div>
        </div>
        <hr>
    </div>
</body>

</html>