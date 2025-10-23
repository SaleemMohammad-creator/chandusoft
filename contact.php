<?php $pageSlug = 'contact'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chandusoft - Contact</title>
<link rel="stylesheet" href="/styles.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include("admin/header.php"); ?>

<main class="contact-page">
    <h2>Contact Us</h2>
    <form class="contact-form" action="contact.php" method="post">
        <label for="name">Your Name</label>
        <input type="text" name="name" id="name" required>

        <label for="email">Your Email</label>
        <input type="email" name="email" id="email" required>

        <label for="message">Your Message</label>
        <textarea name="message" id="message" rows="5" required></textarea>

        <button type="submit">Send Message</button>
    </form>
</main>

<?php include("admin/footer.php"); ?>
</body>
</html>
