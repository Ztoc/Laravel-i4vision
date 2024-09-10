<?php

//error_reporting(E_ALL);
//ini_set("display_errors", 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if(isset($_POST['name'])&&isset($_POST["email"])&&isset($_POST["message"])){

	$name = $_POST["name"];
	$email = $_POST["email"];
	$message = $_POST["message"];


	$EmailTo = "i4vision@goering.de";
	$Subject = "New Message Received";

	// prepare email body text
	$Fields .= "Name: ";
	$Fields .= $name;
	$Fields .= "<br>";

	$Fields.= "Email: ";
	$Fields .= $email;
	$Fields .= "<br>";

	$Fields .= "Message: ";
	$Fields .= $message;
	$Fields .= "<br>";


	// send email
	//$success = mail($EmailTo,  $Subject,  $Fields, "From:".$email);





	//Create an instance; passing `true` enables exceptions
	$mail = new PHPMailer(true);

	try {
		//Server settings
		$mail->SMTPDebug = 0;                      //Enable verbose debug output
		$mail->isSMTP();                                            //Send using SMTP
		//$mail->Host       = 'smtp.ionos.de';                     //Set the SMTP server to send through
		$mail->Host       = 'smtp.ionos.de';
		$mail->SMTPAuth   = true;                                   //Enable SMTP authentication
		//$mail->Username   = 'message@goering.de';                     //SMTP username
		//$mail->Password   = 'Mail!2015';                               //SMTP password
		$mail->Username   = 'webi4vision@goering365.de'; 
		$mail->Password   = 'babva6-zasreq-romxaD';    
		$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
		$mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

		//Recipients
		$mail->setFrom('webi4vision@goering365.de', 'i4Vision');
		//$mail->setFrom('message@goering.de','i4Vision');
		$mail->addAddress($EmailTo);               
		//$mail->addReplyTo('i4vision@goering.de', 'Information');
		//$mail->addCC('info@goering.de');
		//$mail->addBCC('bcc@example.com');

		//Content
		$mail->isHTML(true);                                  //Set email format to HTML
		$mail->Subject = $Subject;
		$mail->Body    = $Fields;

		$mail->send();
		echo 'Message has been sent';
	} catch (Exception $e) {
		echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
	}

}