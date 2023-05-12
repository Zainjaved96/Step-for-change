<?php

use PHPMailer\PHPMailer\PHPMailer;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

/*
*  CONFIGURATION
*/

$input = $_POST;

// Recipients
$fromEmail = 'contact@stepforchange.pk'; // Email address that will be in the from field of the message.
$fromName = $input['name']; // Name that will be in the from field of the message.
$sendToEmail = 'info@stepforchange.pk'; // Email address that will receive the message with the output of the form
$sendToName = 'STEP'; // Name that will receive the message with the output of the form

if($input['type'] == "join_hands")
{
  // Subject
  $subject = $input['name'] . ' wants to join hands.';
  // Fields - Value of attribute name => Text to appear in the email
  $fields = array('Name' => $input['name'], 'Email' => $input['email'], 'Phone' => $input['phone'], 'Join As' => $input['join_as'], 'Country/City' => $input['country_city'], 'Query' => $input['query']); 
}else{
  $subject = $input['name'] . ' is interested in Joining wait list.';
  // Fields - Value of attribute name => Text to appear in the email
  $fields = array('Name' => $input['name'], 'Email' => $input['email'], 'University Name' => $input['university_name'], 'Semester' => $input['semester'], 'City' => $input['city'], 'Referred' => $input['referred'], 'Motivation to Join?' => $input['why_join'] ,'Query' => $input['query']); 
}
// Success and error alerts
$okMessage = 'We have received your inquiry. Stay tuned, we’ll get back to you very soon.';
$errorMessage = 'There was an error while submitting the form. Please try again later';

// SMTP settings
$smtpUse = true; // Set to true to enable SMTP authentication
$smtpHost = 'mail.stepforchange.pk'; // Enter SMTP host ie. smtp.gmail.com
$smtpUsername = 'info@stepforchange.pk'; // SMTP username ie. gmail address
$smtpPassword = '3b222n:@k^$m'; // SMTP password ie gmail password
$smtpSecure = 'tls'; // Enable TLS or SSL encryption
$smtpAutoTLS = false; // Enable Auto TLS
$smtpPort = 587; // TCP port to connect to

// reCAPTCHA settings
$recaptchaUse = true; // Set to true to enable reCAPTHCA
$recaptchaSecret = '6LconoolAAAAAAI1RQ9rjfQ7L5rmu4AT7GMirJ8x'; // enter your secret key from https://www.google.com/recaptcha/admin

/*
*  LET'S DO THE SENDING
*/

// if you are not debugging and don't need error reporting, turn this off by error_reporting(0);
error_reporting(E_ALL & ~E_NOTICE);
try {
  if(count($_POST) == 0) throw new \Exception('Form is empty');
  if($recaptchaUse == true) {
    require('recaptcha/src/autoload.php');
    if (!isset($_POST['g-recaptcha-response'])) {
      throw new \Exception('ReCaptcha is not set.');
    }
    $recaptcha = new \ReCaptcha\ReCaptcha($recaptchaSecret, new \ReCaptcha\RequestMethod\CurlPost());
    // we validate the ReCaptcha field together with the user's IP address
    $response = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
    if (!$response->isSuccess()) {
      throw new \Exception('ReCaptcha was not validated.');
    }
  }
  $emailTextHtml = "<h2>You have received a new query from STEP</h2>";
  $emailTextHtml .= "<table>";
  foreach ($fields as $key => $value) {
    // If the field exists in the $fields array, include it in the email
    if (isset($fields[$key])) {
      $emailTextHtml .= "<tr><th><b>$key</b></th><td>$value</td></tr>";
    }
  }
  $emailTextHtml .= "</table>";
  $mail = new PHPMailer;
  $mail->setFrom($fromEmail, $fromName);
  $mail->addAddress($sendToEmail, $sendToName);
  $mail->addReplyTo($fromEmail);
  $mail->isHTML(true);
  $mail->CharSet = 'UTF-8';
  $mail->Subject = $subject;
  $mail->Body    = $emailTextHtml;
  $mail->msgHTML($emailTextHtml);
  if($smtpUse == true) {
    // Tell PHPMailer to use SMTP
    $mail->isSMTP();
    // Enable SMTP debugging
    // 0 = off (for production use)
    // 1 = client messages
    // 2 = client and server messages
    $mail->Debugoutput = function ($str, $level) use (&$mailerErrors) {
      $mailerErrors[] = [ 'str' => $str, 'level' => $level ];
    };
    $mail->SMTPDebug = 3;
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = $smtpSecure;
    $mail->SMTPAutoTLS = $smtpAutoTLS;
    $mail->Host = $smtpHost;
    $mail->Port = $smtpPort;
    $mail->Username = $smtpUsername;
    $mail->Password = $smtpPassword;
  }
  if(!$mail->send()) {
    throw new \Exception('I could not send the email.' . $mail->ErrorInfo);
  }
  $responseArray = array('type' => 'success', 'message' => $okMessage);
}
catch (\Exception $e) {
  $responseArray = array('type' => 'danger', 'message' => $e->getMessage());
}
// if requested by AJAX request return JSON response
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
  $encoded = json_encode($responseArray); 
  header('Content-Type: application/json');
  echo $encoded;
}
// else just display the message
else {
  echo $responseArray['message'];
}