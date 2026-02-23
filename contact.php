<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Luxury Resort</title>
    <link rel="stylesheet" href="static/css/style.css">
</head>
<body>

    <section class="contact-header">
        <div class="container">
            <h1>Contact Us</h1>
            <p>We'd love to hear from you. Get in touch with our team.</p>
        </div>
    </section>

    <div class="contact-wrapper">
        <div class="info-section">
            <h2>Get In Touch</h2>
            <p>
                Whether you have a question about reservations, amenities, or anything
                else, our team is ready to answer all your questions.
            </p>

            <div class="info-card">
                <div class="icon">
                    <img src="static/icons/location.svg" alt="Location icon">
                </div>
                <div class="info-text">
                    <h4>Location</h4>
                    <span>Barr Mont Le Paseo Island Resort</span>
                    <span>Paseo Island, Pacific Ocean</span>
                </div>
            </div>

            <div class="info-card">
                <div class="icon">
                    <img src="static/icons/call.svg" alt="Location icon">
                </div>
                <div class="info-text">
                    <h4>Phone</h4>
                    <span>Reservations: +1 (555) 123-4567</span>
                    <span>Front Desk: +1 (555) 123-4568</span>
                </div>
            </div>

            <div class="info-card">
                <div class="icon">
                    <img src="static/icons/mail.svg" alt="Location icon">
                </div>
                <div class="info-text">
                    <h4>Email</h4>
                    <span>General: info@barrmont.com</span>
                    <span>Reservations: bookings@barrmont.com</span>
                </div>
            </div>

            <div class="info-card">
                <div class="icon">
                    <img src="static/icons/clock.svg" alt="Location icon">
                </div>
                <div class="info-text">
                    <h4>Hours</h4>
                    <span>Front Desk: 24/7</span>
                    <span>Reservations: 8:00 AM - 10:00 PM</span>
                </div>
            </div>
        </div>

        <!-- RIGHT -->
        <div class="form-card">
            <h3>Send a Message</h3>

            <div class="form-row">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" placeholder="John">
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" placeholder="Doe">
                </div>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" placeholder="john@example.com">
            </div>

            <div class="form-group">
                <label>Subject</label>
                <input type="text" placeholder="How can we help?">
            </div>

            <div class="form-group">
                <label>Message</label>
                <textarea placeholder="Tell us more about your inquiry..."></textarea>
            </div>

            <button class="btn">Send Message</button>
        </div>
    </div>


    <script src="app.js"></script>
    <script src="contact.js"></script>
</body>
</html>
