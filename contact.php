<?php
$page_title = 'Contact Us - Ubuntu Wheels';
require_once 'header.php';
?>

<section class="section-p1 contact-hero" style="background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://images.unsplash.com/photo-1543269824-c10ac124b86e?fit=crop&w=1920&q=80');">
    <div class="container text-center">
        <h1 class="white-text">Get in Touch</h1>
        <p class="white-text">Have a question or need assistance? We're here to help!</p>
    </div>
</section>

<section class="section-p1 contact-details">
    <div class="container">
        <div class="details-content">
            <div class="contact-info">
                <span>GET IN TOUCH</span>
                <h2>Visit one of our agency locations or contact us today</h2>
                <h3>Head Office</h3>
                <ul>
                    <li><i class="far fa-map-marker-alt"></i> 123 Market Street, Cape Town, South Africa</li>
                    <li><i class="far fa-envelope"></i> contact@ubuntuwheels.co.za</li>
                    <li><i class="far fa-phone-alt"></i> +27 21 123 4567</li>
                    <li><i class="far fa-clock"></i> Monday to Friday: 10:00 - 18:00</li>
                </ul>
                <h3>Customer Support Hours</h3>
                <ul>
                    <li><i class="far fa-clock"></i> Weekdays: 09:00 - 17:00</li>
                    <li><i class="far fa-clock"></i> Saturdays: 10:00 - 14:00</li>
                    <li><i class="far fa-clock"></i> Sundays & Public Holidays: Closed</li>
                </ul>
                <div class="social-contact">
                    <h4>Follow Us</h4>
                    <div class="icon">
                        <i class="fab fa-facebook-f"></i>
                        <i class="fab fa-twitter"></i>
                        <i class="fab fa-instagram"></i>
                        <i class="fab fa-pinterest-p"></i>
                        <i class="fab fa-youtube"></i>
                    </div>
                </div>
            </div>
            <div class="contact-map">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3310.8711417532357!2d18.42398531521096!3d-33.92487!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1dcc5dce2c92170b%3A0x7d2b4f9a3c3c7e7b!2sCape%20Town%20City%20Centre!5e0!3m2!1sen!2sza!4v1678880000000!5m2!1sen!2sza"
                        width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
        </div>
    </div>
</section>

<section class="section-p1 contact-form-section">
    <div class="container">
        <form class="contact-form">
            <span>LEAVE A MESSAGE</span>
            <h2>We love to hear from you</h2>
            <div class="form-group">
                <input type="text" placeholder="Your Name" required>
            </div>
            <div class="form-group">
                <input type="email" placeholder="E-mail" required>
            </div>
            <div class="form-group">
                <input type="text" placeholder="Subject" required>
            </div>
            <div class="form-group">
                <textarea cols="30" rows="10" placeholder="Your Message" required></textarea>
            </div>
            <button type="submit" class="primary">Submit</button>
        </form>
    </div>
</section>

<section id="newsletter" class="section-p1 section-m1">
    <div class="newstext">
        <h4>Sign Up For Our Newsletter</h4>
        <p>Get email updates about new listings in your area and <span>special offers.</span></p>
    </div>
    <div class="form">
        <input type="email" placeholder="Your email address">
        <button class="normal">Sign Up</button>
    </div>
</section>
<?php
require_once 'footer.php';
?>