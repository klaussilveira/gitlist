<?php
 
	include "config.php";
	 session_start();
	   
	   $email = $password = "";
	   if($_SERVER['REQUEST_METHOD'] == 'POST')
	   {
		  $email =    $_POST['email'];
		  $password = $_POST['password'];
		 
		  $query = ("select * from register where email='".$email."' AND password='".$password."'");
		 
		  $result = mysqli_query($con,$query);
		  $num    = mysqli_num_rows($result);
			 
		 if($num)
		 {
			$row     = mysqli_fetch_array($result);
			
			  $_SESSION['uId']   = $row['id'];
	          $_SESSION['email'] = $row['email'];
		 }
		 else
		{
			
			header("location:login.php");
			echo "user name and password does not match";
		}
			  $query = "select * from mail where receiver_id= '$email'";
				$result=mysqli_query($con,$query);
				$num    = mysqli_num_rows($result);
				
				if($num)
				{
					echo "<script>alert('you have a Message');</script>";
		
					header("location:receiver.php");
					
				}
		else
		{
			header("location:mail.php");
		}
					
		
	
	   }
?>
