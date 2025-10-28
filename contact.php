<?php
require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/vendor/autoload.php'; // ✅ PHPMailer Autoload

$pageSlug = 'contact';
$formData = ['name' => '', 'email' => '', 'message' => ''];

// Flash messages
$successMessage = $_SESSION['successMessage'] ?? '';
$errorMessage = $_SESSION['errorMessage'] ?? '';
unset($_SESSION['successMessage'], $_SESSION['errorMessage']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    $formData = ['name' => $name, 'email' => $email, 'message' => $message];

    if ($name && $email && $message) {

        try {
            // ✅ Database Insert
            $stmt = $pdo->prepare("INSERT INTO leads (name, email, message, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$name, $email, $message]);

            // ✅ Email Sending using PHPMailer
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'cstltest4@gmail.com';
                $mail->Password   = 'vwrs cubq qpqg wfcg'; // ✅ App Password
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('cstltest4@gmail.com', 'Chandusoft Contact');
                $mail->addAddress('saleem.mohammad@chandusoft.com'); // ✅ Admin email
                $mail->addReplyTo($email, $name);

                $mail->isHTML(true);
                $mail->Subject = "New Contact Form Submission";
                $mail->Body = "
                    <h3>New Contact Form Message</h3>
                    <p><strong>Name:</strong> {$name}</p>
                    <p><strong>Email:</strong> {$email}</p>
                    <p><strong>Message:</strong><br>" . nl2br($message) . "</p>
                ";

                $mail->send();
            } catch (Exception $e) {
                // Log mail error silently — don't break user success flow
                error_log("Mail Error: " . $mail->ErrorInfo);
            }

            $_SESSION['successMessage'] = "Your message has been sent successfully!";
            $formData = ['name' => '', 'email' => '', 'message' => ''];

            header("Location: /contact");
            exit;

        } catch (Exception $e) {
            $_SESSION['errorMessage'] = "Sorry, something went wrong. Please try again.";
            header("Location: /contact");
            exit;
        }
    } else {
        $_SESSION['errorMessage'] = "All fields are required!";
        header("Location: /contact");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chandusoft - Contact</title>
<link rel="stylesheet" href="/styles.css">
<style>
body {
    font-family: Arial, sans-serif;
    background-color: #f5f7fa;
    margin: 0;
    padding: 0;
}
.contact-page {
    max-width: 500px;
    margin: 40px auto;
    background-color: #fff;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 10px 2px rgba(0,0,0,0.1);
    transform: translateX(-20px);
}
.contact-page h2 {
    text-align: center;
    color: #1E90FF;
    margin-bottom: 30px;
}
.contact-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #333;
}
.contact-form input[type="text"],
.contact-form input[type="email"],
.contact-form textarea {
    width: 100%;
    padding: 5px;
    margin-bottom: 20px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 14px;
    transition: border-color 0.3s;
}
.contact-form input[type="text"]:focus,
.contact-form input[type="email"]:focus,
.contact-form textarea:focus {
    border-color: #1E90FF;
    outline: none;
}
.contact-form button {
    width: 100%;
    padding: 12px;
    background-color: #1E90FF;
    color: #fff;
    font-size: 16px;
    font-weight: bold;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.3s;
}
.contact-form button:hover {
    background-color: #187bcd;
}
.alert {
    padding: 12px;
    border-radius: 6px;
    font-weight: bold;
    text-align: center;
    margin-top: 10px;
    opacity: 1;
    transition: opacity 0.5s, transform 0.5s;
}
.alert.success {
    background-color: #d4edda;
    color: #155724;
}
.alert.error {
    background-color: #f8d7da;
    color: #721c24;
}
.alert.show {
    transform: translateY(0);
    opacity: 1;
}
</style>
</head>
<body>

<?php include("admin/header.php"); ?>

<main class="contact-page">
    <h2>Contact Us</h2>

    <form class="contact-form" action="/contact" method="post">
        <label for="name">Your Name</label>
        <input type="text" name="name" id="name" value="<?= htmlspecialchars($formData['name']) ?>" required>

        <label for="email">Your Email</label>
        <input type="email" name="email" id="email" value="<?= htmlspecialchars($formData['email']) ?>" required>

        <label for="message">Your Message</label>
        <textarea name="message" id="message" rows="5" required><?= htmlspecialchars($formData['message']) ?></textarea>

        <button type="submit">Send Message</button>

        <?php if ($successMessage): ?>
            <div class="alert success show" id="formMessage"><?= htmlspecialchars($successMessage) ?></div>
        <?php elseif ($errorMessage): ?>
            <div class="alert error show" id="formMessage"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>
    </form>
</main>

<script>
window.addEventListener('DOMContentLoaded', () => {
    const msg = document.getElementById('formMessage');
    if(msg) {
        setTimeout(() => { 
            msg.style.opacity = '0';
            msg.style.transform = 'translateY(-10px)';
        }, 5000);
    }
});
</script>

<?php include("admin/footer.php"); ?>
<button id="back-to-top" title="Back to Top">↑</button>
<script src="include.js"></script>
</body>
</html>
