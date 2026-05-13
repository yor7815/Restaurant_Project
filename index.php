<html>
<head>
	<title>電影資料庫管理系統</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<style>
	table, th, td {
	border: 1px solid black;
	border-collapse: collapse;
	}
	th, td {
	padding: 5px;
	text-align: left;    
	}
</style>
<body>
	
	<h1 align="center">電影資料庫管理系統</h1>
	<p align="center"><a href="create.html">新增資料</a><p>
	<table style="width:50%" align="center">
		<tr><th>編號</th><th>標題</th><th>導演</th><th>上映日期</th><th colspan="2">Action</th></tr>
		
		<?php
			// ******** update your personal settings ******** 
			$servername = "";
			$username = "";
			$password = "";
			$dbname = "";

			// Connect MySQL server
			$conn = new mysqli($servername, $username, $password, $dbname);
			
			// set up char set
			if (!$conn->set_charset("utf8")) {
				printf("Error loading character set utf8: %s\n", $conn->error);
				exit();
			}
			
			// Check connection
			if ($conn->connect_error) {
				die("Connection failed: " . $conn->connect_error);
			} 
			
			// ******** update your personal settings ******** 
			$sql = "";	// TODO: set up your sql query
			$result = $conn->query($sql);	// Send SQL Query

			if ($result->num_rows > 0) {	
				// TODO: show the results
			} else {
				echo "0 results";
			}
		?>
		
	</table>
	
</body>
	
</html>


				
		