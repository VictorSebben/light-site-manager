<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title></title>
</head>
<body>

<?php
if ( isset( $this->file ) ) {
    require $this->file;
} else {
    throw new Exception( 'View file not provided.' );
}
?>

</body>
</html>
