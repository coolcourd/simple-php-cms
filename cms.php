<?php

if (isset($_GET['json'])) {
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
$log_file = "logs/.$date.txt";
$ip = $_SERVER['REMOTE_ADDR'];


function blacklisted()
{
    $ip = $_SERVER['REMOTE_ADDR'];
    $date = date('Y-m-d');
    $log_file = "logs/.$date.txt";
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


// handle image upload
if (isset($_FILES['file'])) {
    // if there is no images directory, create it
    if (!file_exists('images')) {
        mkdir('images');
    }
    $image = $_FILES['file'];
    $image_name = $image['name'];
    $image_tmp_name = $image['tmp_name'];
    $image_size = $image['size'];
    $image_error = $image['error'];
    $image_type = $image['type'];
    $image_ext = explode('.', $image_name);
    $image_ext = strtolower(end($image_ext));
    $allowed = array('jpg', 'jpeg', 'png');
    if (in_array($image_ext, $allowed)) {
        if ($image_error === 0) {
            if ($image_size <= 1000000) {
                $image_destination = 'images/' . $image_name;
                move_uploaded_file($image_tmp_name, $image_destination);
                if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
                    $url = "https://";
                else
                    $url = "http://";
                $url .= $_SERVER['HTTP_HOST'];
                $message = "Image uploaded successfully. the url is <a target='_blank' href='$url/$image_destination'>$url/$image_destination</a>";
            } else {
                // resize image
                $image_destination = 'images/' . $image_name;
                move_uploaded_file($image_tmp_name, $image_destination);
                $image_size = getimagesize($image_destination);
                $image_width = $image_size[0];
                $image_height = $image_size[1];
                $image_ratio = $image_width / $image_height;
                if ($image_ratio > 1) {
                    $image_width = 500;
                    $image_height = 500 / $image_ratio;
                } else {
                    $image_height = 500;
                    $image_width = 500 * $image_ratio;
                }
                $image_resized = imagecreatetruecolor($image_width, $image_height);
                $image_source = imagecreatefromjpeg($image_destination);
                imagecopyresampled($image_resized, $image_source, 0, 0, 0, 0, $image_width, $image_height, $image_width, $image_height);
                imagejpeg($image_resized, $image_destination);
                if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
                    $url = "https://";
                else
                    $url = "http://";
                $url .= $_SERVER['HTTP_HOST'];
                $message = "Image resized and uploaded successfully. the url is <a target='_blank' href='$url/$image_destination'>$url/$image_destination</a>";
            }
        } else {
            $message = "There was an error uploading the image";
        }
    } else {
        $message = "Image type is not allowed";
    }
}
// random string
function randomString($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>cms</title>
    <link rel="stylesheet" href="/cms.css?<?php echo randomString() ?>">
</head>

<body>
    <a href="/cms.php?logout" class="logout">logout</a>
    <div class="container">
        <h1>CMS</h1>
        <div class="rows">
            <a href="/cms.php?new" class='clear'>new</a>
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
        <!-- image upload form -->
        <form action="" method="post" enctype="multipart/form-data">
            <div class="group">
                <input type="file" name="file" id="file">
                <button type='submit'>Upload</button>
            </div>
        </form>
        <?php if (isset($message)) : ?>
            <p id='message'><?= $message ?></p>
        <?php endif ?>
        <hr>
    </div>
    <script>
        if (!!window.location.search.match('title') || !!window.location.search.match('new')) {
            document.getElementById('body-text').focus();
        }
        if (!!document.getElementById('message')) {
            document.getElementById('message').scrollIntoView();
        }
    </script>
</body>

</html>