<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Luxury Resort</title>
    <link rel="stylesheet" href="static/css/style.css">
</head>
<body>

    <section class="page-header">
        <div class="container">
            <h1>Contact Us</h1>
            <p>We'd love to hear from you</p>
        </div>
    </section>

    <section class="contact-section">
        <div class="container">
            <div class="contact-container">
                <div class="contact-form">
                    <h2>Send us a Message</h2>
                    <form id="contactForm">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" required class="form-input">
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required class="form-input">
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" class="form-input">
                        </div>

                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" required class="form-input">
                        </div>

                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" required class="form-input" rows="6"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>

                    <div id="contactMessage" class="success-message" style="display:none;"></div>
                </div>

                <div class="contact-info">
                    <h2>Get In Touch</h2>

                    <div class="info-block">
                        <h4>Address</h4>
                        <p>123 Luxury Avenue<br>Paradise Beach, CA 90210<br>United States</p>
                    </div>

                    <div class="info-block">
                        <h4>Phone</h4>
                        <p><a href="tel:+18008800123">+1 (800) 880-0123</a></p>
                    </div>

                    <div class="info-block">
                        <h4>Email</h4>
                        <p><a href="mailto:info@luxuryresort.com">info@luxuryresort.com</a></p>
                    </div>

                    <div class="info-block">
                        <h4>Hours</h4>
                        <p>Monday - Friday: 9:00 AM - 6:00 PM<br>
                        Saturday - Sunday: 10:00 AM - 4:00 PM</p>
                    </div>

                    <div class="social-links">
                        <h4>Follow Us</h4>
                        <div class="social-icons">
                            <a href="#" class="social-icon">f</a>
                            <a href="#" class="social-icon">𝕏</a>
                            <a href="#" class="social-icon">📷</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>About Us</h4>
                    <p>Luxury Resort offers world-class hospitality and unforgettable experiences.</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="rooms.php">Rooms</a></li>
                        <li><a href="amenities.php">Amenities</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contact</h4>
                    <p>Email: info@luxuryresort.com</p>
                    <p>Phone: +1-800-RESORT</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Luxury Resort. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="app.js"></script>
    <script src="contact.js"></script>
</body>
</html>
