<?php

// ******** update your personal settings ******** 
$servername = "";
$username = "";
$password = "";
$dbname = "";

// Connecting to and selecting a MySQL database
$conn = new mysqli($servername, $username, $password, $dbname);

if (!$conn->set_charset("utf8")) {
    printf("Error loading character set utf8: %s\n", $conn->error);
    exit();
}

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

if (isset($_POST['id']) && isset($_POST['title']) && isset($_POST['director']) && isset($_POST['release_date'])) {
	$id = $_POST['id'];
	$title = $_POST['title'];
	$director = $_POST['director'];
	$release_date = $_POST['release_date'];
	

	$update_sql = "";	// TODO 
	echo $update_sql;
	if ($conn->query($update_sql) === TRUE) {
		// 重定向用戶到下一頁
		header('Location: index.php');
		exit;

	} else {
		echo "<h2 align='center'><font color='antiquewith'>修改失敗!!</font></h2>";
	}

}else{
	echo "資料不完全";
}
				
?>