<?php


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="/cms.css">
</head>

<body>
    <div class="container">
        <?php if (isset($_GET['wrong'])) { ?>
            <div class="alert">
                <p>Wrong password</p>
            </div>
        <?php } ?>
        <div class="flex">
            <form class="side-by-side" action="/cms.php" method="post">
                <input type='password' name='password' autofocus placeholder='password' id="password">
                <button type="submit">Submit</button>
            </form>
        </div>
        <hr>
    </div>
</body>

</html>