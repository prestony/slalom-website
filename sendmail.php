<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';



if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // ... (your form processing code)

    // Create a PHPMailer object
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com'; // Your SMTP server address
        $mail->SMTPAuth = true;
        $mail->Username = 'your_username'; // Your SMTP username
        $mail->Password = 'your_password'; // Your SMTP password
        $mail->SMTPSecure = 'tls'; // Enable TLS encryption
        $mail->Port = 587; // TCP port to connect to

        //Recipients
        $mail->setFrom($email, $name);
        $mail->addAddress('info@slalomconsultants.co.ke'); // Recipient email address

        //Content
        $mail->Subject = "Contact Form Submission from $name";
        $mail->Body = $email_message;

        $mail->send();
        echo "Thank you for your message. We will get back to you shortly.";
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
} else {
    echo "Invalid request.";
}
?>
