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

function upload_image()
{
    $uploadTo = "images/";
    $allowImageExt = array('jpg', 'png', 'jpeg', 'gif');
    $imageName = $_FILES['file']['name'];
    $tempPath = $_FILES["file"]["tmp_name"];
    $imageQuality = 60;
    $basename = basename($imageName);
    $originalPath = $uploadTo . $basename;
    $imageExt = pathinfo($originalPath, PATHINFO_EXTENSION);
    if (empty($imageName)) {
        $error = "Please Select files..";
        return $error;
    } else {

        if (in_array($imageExt, $allowImageExt)) {
            $compressedImage = compress_image($tempPath, $originalPath, $imageQuality);
            if ($compressedImage) {
                if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
                    $url = "https://";
                else
                    $url = "http://";
                $url .= $_SERVER['HTTP_HOST'];
                return "Image was compressed and uploaded to server as <a target='_blank' href='$url/$originalPath'>$url/$originalPath</a>";
            } else {
                return "Some error !.. check your script";
            }
        } else {
            return "Image Type not allowed";
        }
    }
}
function compress_image($tempPath, $originalPath, $imageQuality)
{

    // Get image info 
    $imgInfo = getimagesize($tempPath);
    $mime = $imgInfo['mime'];

    // Create a new image from file 
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($tempPath);
            break;
        case 'image/png':
            $image = imagecreatefrompng($tempPath);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($tempPath);
            break;
        default:
            $image = imagecreatefromjpeg($tempPath);
    }

    // Save image 
    imagejpeg($image, $originalPath, $imageQuality);
    // Return compressed image 
    return $originalPath;
}

// handle image upload
if (isset($_FILES['file'])) {
    // if there is no images directory, create it
    if (!file_exists('images')) {
        mkdir('images');
    }
    $message = upload_image();
}

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
<?php
if (isset($_GET['tiny'])) {
    include('tinystuff.php');
}
?>
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
                        <?php
                        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
                            $url = "https://";
                        else
                            $url = "http://";
                        $url .= $_SERVER['HTTP_HOST'];
                        $url .= $_SERVER['REQUEST_URI'];
                        ?>
                        <span>
                            <button type='submit'>Save</button>
                            <?php error_log($_SERVER['REQUEST_URI']);?>
                            <?php if (! strstr($_SERVER['REQUEST_URI'], 'tiny=true')) {
                            ?><a href="<?php echo $url ?>&tiny=true">switch to visual</a>
                            <?php } ?>
                        </span>
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

        <h2>Uploaded Images</h2>
        <div class="rows">
            <?php foreach (scandir('images') as $file) : ?>
                <?php if ($file !== '.' && $file !== '..') : ?>
                    <a target='_blank' href='/images/<?= $file ?>'><?= $file ?></a>
                <?php endif ?>
            <?php endforeach ?>
        </div>
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