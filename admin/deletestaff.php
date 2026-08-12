<?php
include_once("../session_check.php");
	include("../functions.php");

	//Deleting Item
	if (isset($_GET['staffID'])) {
		
		$del_staffID = $sqlconnection->real_escape_string($_GET['staffID']);

		$deleteStaffQuery = "DELETE FROM tbl_users WHERE userID = {$del_staffID}";

		if ($sqlconnection->query($deleteStaffQuery) === TRUE) {
				echo "deleted.";
				header("Location: staff.php"); 
				exit();
			} 

		else {
				//handle
				echo "Algo salió mal";
				echo $sqlconnection->error;

		}
		//echo "<script>alert('{$del_menuID} & {$del_itemID}')</script>";
	}
?>