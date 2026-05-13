<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>電影資料庫管理系統</title>
</head>

<body>
<h1 align="center">修改電影資料</h1>
	<form action="doupdate.php" method="post">	
	  <table width="500" border="1" bgcolor="#cccccc" align="center">
		
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

			$id = $_GET['id'];

			if (isset($id)) {
				
				$select_sql = ""; // TODO 
				$result = $conn->query($select_sql);

				if ($result->num_rows > 0) {
					$row = mysqli_fetch_array ( $result, MYSQLI_ASSOC );
					
					echo "<tr>
						<th>編號</th>
						
						</tr>";	// TODO
					
					echo "<tr>
					<th>標題</th>
					<td bgcolor='#FFFFFF'><input type='text' name='title' style='white-space: pre-wrap;' value=" . str_replace(' ', '&nbsp;', $row["title"]) . " required /></td>
					</tr>";
					
					echo "<tr>
						<th>導演</th>
						<td bgcolor='#FFFFFF'><input type='text' name='director' value=" . $row['director'] . " required /></td>
						</tr>";
					echo "<tr>
						<th>上映日期</th>
						<td bgcolor='#FFFFFF'><input type='text' name='release_date' value=" . $row['release_date'] . " required/></td>
						</tr>";
					
					echo "<th colspan='2'><input type='submit' value='更新'/></th>";
					echo "</tr>";

				}else{
					echo "查詢失敗!";
				}

			}else{
				echo "資料不完全";
			}
		?>

	  </table>
	</form>
</body>
</html>