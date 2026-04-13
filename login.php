<?php

session_start();


$errmsg_arr = array();


$errflag = false;

$conn = mysqli_connect('localhost', 'root', '', 'sales');
if (!$conn) {
    die('Failed to connect to server: ' . mysqli_connect_error());
}


function clean($conn, $str) {
    return mysqli_real_escape_string($conn, trim($str)); 
}


$login = isset($_POST['username']) ? clean($conn, $_POST['username']) : '';
$password = isset($_POST['password']) ? clean($conn, $_POST['password']) : '';


if ($login == '') {
    $errmsg_arr[] = 'Username missing';
    $errflag = true;
}
if ($password == '') {
    $errmsg_arr[] = 'Password missing';
    $errflag = true;
}


if ($errflag) {
    $_SESSION['ERRMSG_ARR'] = $errmsg_arr;
    session_write_close();
    header("location: index.php");
    exit();
}


$qry = "SELECT * FROM user WHERE username = ? AND password = ?";
$stmt = mysqli_prepare($conn, $qry);
mysqli_stmt_bind_param($stmt, "ss", $login, $password);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);


if ($result) {
    if (mysqli_num_rows($result) > 0) {
        
        session_regenerate_id();
        $member = mysqli_fetch_assoc($result);
        $_SESSION['SESS_MEMBER_ID'] = $member['id'];
        $_SESSION['SESS_FIRST_NAME'] = $member['name'];
        $_SESSION['SESS_LAST_NAME'] = $member['position'];
        session_write_close();
        header("location: main/index.php");
        exit();
    } else {
        
        header("location: index.php");
        exit();
    }
} else {
    die("Query failed: " . mysqli_error($conn)); 
}

mysqli_close($conn);
?>
