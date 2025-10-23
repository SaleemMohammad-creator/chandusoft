<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';
$success = "";
$name = "";
$email = "";
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli("127.0.0.1", "root", "", "chandusoft");
    if ($conn->connect_error) {
        die("DB Connection failed: " . $conn->connect_error);
    }

    $name = $conn->real_escape_string($_POST['name'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    $message = $conn->real_escape_string($_POST['message'] ?? '');

    if (!empty($name) && !empty($email) && !empty($message) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $sql = "INSERT INTO leads (name, email, message) VALUES ('$name', '$email', '$message')";
        if ($conn->query($sql) === TRUE) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'cstltest4@gmail.com';
                $mail->Password = 'vwrs cubq qpqg wfcg'; // Gmail App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('cstltest4@gmail.com', 'Chandusoft Contact Form');
                $mail->addAddress('saleem.mohammad@chandusoft.com', 'Saleem');
                $mail->addReplyTo($email, $name);
                $mail->isHTML(true);
                $mail->Subject = 'New Lead Submission';
                $mail->Body = "
                    <h3>New Lead Details</h3>
                    <p><strong>Name:</strong> {$name}</p>
                    <p><strong>Email:</strong> {$email}</p>
                    <p><strong>Message:</strong><br>" . nl2br($message) . "</p>
                ";

                $mail->send();

                // Log the successful email sending to emails.log
                logEmail('success', $name, $email, $mail->Subject, '');

                $response = ["status" => "success", "message" => "✅ Message sent successfully!"];
            } catch (Exception $e) {
                // Log the error to emails.log
                logEmail('error', $name, $email, $mail->Subject, $mail->ErrorInfo);

                $response = ["status" => "error", "message" => "❌ Error sending email: " . htmlspecialchars($mail->ErrorInfo)];
            }
        } else {
            // Log the database insertion failure to emails.log
            logEmail('error', $name, $email, 'Lead Submission Failure', 'Database error: ' . $conn->error);

            $response = ["status" => "error", "message" => "❌ Error saving message."];
        }
    } else {
        // Log the input validation failure to emails.log
        logEmail('error', $name, $email, 'Lead Submission Failure', 'Invalid input data.');

        $response = ["status" => "error", "message" => "❌ Please fill all fields correctly."];
    }

    $conn->close();
    
    // Return the response as JSON
    echo json_encode($response);
    exit;
}
// Function to log email events (success or error) to emails.log
function logEmail($status, $name, $email, $subject, $errorMessage) {
    // Open the log file in append mode
    $logFile = '../storage/emails.log';
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0777, true);
    }
// Prepare the log entry
    $timestamp = date("Y-m-d H:i:s");
    $logEntry = "[$timestamp] - Status: $status - Name: $name - Email: $email - Subject: $subject - Error: $errorMessage" . PHP_EOL;
// Append the log entry to the file
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Chandusoft - Contact</title>
    <link rel="stylesheet" href="styles.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
</head>
<body>
    
       <?php include("header.php"); ?>
       
    <main>
        <h2>Contact Us</h2>
        <form id="contactForm" class="contact-form" action="contact.php" method="post" novalidate>
            <!-- Name -->
            <label for="name">Your Name</label>
            <input type="text" id="name" name="name" placeholder="Enter your name" required />
            <span class="error-message" id="nameError"></span>

            <!-- Email -->
            <label for="email">Your Email</label>
            <input type="email" id="email" name="email" placeholder="Enter your email" required />
            <span class="error-message" id="emailError"></span>

            <!-- Message -->
            <label for="message">Your Message</label>
            <textarea id="message" name="message" rows="5" placeholder="Type your message here..." required></textarea>
            <span class="error-message" id="messageError"></span>

            <!-- Submit Button -->
            <button type="submit" id="submitBtn" disabled>Send Message</button>
        </form>

        <!-- Feedback Message (Success/Error) -->
        <div id="feedbackMessage" style="font-weight: bold; margin-top: 15px; display: none;"></div>
    </main>

    <div id="footer"></div>
    <?php include("footer.php"); ?>

    <script src="include.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('contactForm');
            const submitBtn = document.getElementById('submitBtn');
            const feedbackMessage = document.getElementById('feedbackMessage');

            // Enable Submit Button if the form is valid
            form.addEventListener('input', () => {
                submitBtn.disabled = !form.checkValidity();
            });

            // Handle form submission
            form.addEventListener('submit', (e) => {
                e.preventDefault();

                const formData = new FormData(form);
                fetch('contact.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Show the feedback message based on the response
                    feedbackMessage.style.display = 'block';
                    feedbackMessage.textContent = data.message;
                    feedbackMessage.style.color = data.status === 'success' ? 'green' : 'red';
                    
                    // Optionally reset form if success
                    if (data.status === 'success') {
                        form.reset();
                        submitBtn.disabled = true;
                    }

                    // Hide the message after 10 seconds
                    setTimeout(() => {
                        feedbackMessage.style.display = 'none';
                    }, 10000);
                })
                .catch(error => {
                    feedbackMessage.style.display = 'block';
                    feedbackMessage.textContent = '❌ Something went wrong. Please check your input.';
                    feedbackMessage.style.color = 'red';
                    setTimeout(() => {
                        feedbackMessage.style.display = 'none';
                    }, 10000);
                });
            });
        });
    </script>
</body>
</html>
