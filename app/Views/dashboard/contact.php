<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISP Billing Software - Contact</title>
    <!-- 08 §10 / 07 F3 — self-hosted Font Awesome (was cdnjs; a blocked CDN silently drops every icon) -->
    <link rel="stylesheet" href="<?= base_url('assets/vendor/fontawesome/all.min.css'); ?>">
    <!-- 08 §2(b) — decided theme-exempt: base.css's body.ipb rule sets a
         literal background/font-family/color that would out-specificity
         and silently override this page's own body{} below.
         Self-contained inline styles stay authoritative here. -->
    <style>
        :root {
            --primary: #001F57; /* Your navy blue color */
            --secondary: #0038A8; /* Slightly lighter blue */
            --accent: #FFD700; /* Gold accent */
            --light: #F5F7FA;
            --dark: #212529;
            --gray: #6C757D;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light);
        }

        /* Contact Container */
        .contact-container {
            max-width: 1200px;
            margin: 60px auto;
            padding: 40px;
            border-radius: 16px;
            background: white;
            box-shadow: var(--shadow-1, 0 10px 30px rgba(0, 0, 0, 0.08));
            position: relative;
            overflow: hidden;
        }

        .contact-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        /* Header */
        .contact-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .contact-header h2 {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .contact-header p {
            color: var(--gray);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Grid Layout */
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 1fr));
            gap: 40px;
        }

        /* Contact Info Cards */
        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .info-card {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            padding: 25px;
            border-radius: 12px;
            background: var(--light);
            transition: all 0.3s ease;
            cursor: pointer;
            border-left: 4px solid var(--primary);
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-2, 0 8px 20px rgba(0, 0, 0, 0.1));
        }

        .info-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .info-content h3 {
            color: var(--dark);
            margin-bottom: 8px;
            font-size: 1.2rem;
        }

        .info-content p {
            color: var(--gray);
            line-height: 1.6;
        }

        /* Contact Form */
        .contact-form {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--shadow-1, 0 5px 15px rgba(0, 0, 0, 0.05));
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark);
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: var(--shadow-focus, 0 0 0 3px rgba(0, 31, 87, 0.1));
            outline: none;
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-brand, 0 5px 15px rgba(0, 31, 87, 0.3));
        }

        /* Social Links */
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: center;
        }

        .social-link {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            transition: all 0.3s;
        }

        .social-link:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .contact-container {
                padding: 24px 16px;
                margin: 16px 12px;
            }
            
            .contact-header h2 {
                font-size: 1.6rem;
            }

            .contact-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .info-card {
                padding: 16px;
                gap: 12px;
            }

            .contact-form input,
            .contact-form textarea,
            .contact-form button {
                width: 100%;
                min-height: 44px;
            }
        }

        @media (max-width: 480px) {
            .contact-container {
                margin: 8px;
                padding: 16px 12px;
            }

            .contact-header h2 {
                font-size: 1.35rem;
            }
        }
    </style>
</head>
<body>
    <div class="contact-container">
        <div class="contact-header">
            <h2>Contact Us</h2>
            <p>Get in touch with our support team for any queries or assistance</p>
        </div>
        
        <div class="contact-grid">
            <div class="contact-info">
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="info-content">
                        <h3>Our Address</h3>
                        <p>123 ISP Street, Business District, City 1000</p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <div class="info-content">
                        <h3>Phone Number</h3>
                        <p>+880 1234 567890</p>
                        <p>+880 9876 543210</p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="info-content">
                        <h3>Email Address</h3>
                        <p>support@yourisp.com</p>
                        <p>billing@yourisp.com</p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="info-content">
                        <h3>Working Hours</h3>
                        <p>Monday - Saturday: 9:00 AM - 11:00 PM</p>
                        <p>Sunday: Closed</p>
                    </div>
                </div>
                <div class="social-links">
                    <a href="#" class="social-link">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="social-link">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="social-link">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <a href="#" class="social-link">
                        <i class="fab fa-instagram"></i>
                    </a>
                </div>
            </div>
            
            <div class="contact-form">
            <?php if (session()->getFlashdata('success')): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= esc(session()->getFlashdata('success')) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php elseif (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= esc(session()->getFlashdata('error')) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                <form id="contactForm" action="<?= route_to('route.auth.store'); ?>" method="POST">
                <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" class="form-control" placeholder="e.g., John Doe" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" class="form-control" placeholder="e.g., 0123456789" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" class="form-control" placeholder="e.g., john.doe@example.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Inquiry Type *</label>
                        <div style="margin-top: 10px;">
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="radio" name="inquiryType" value="Demo Request" required> Demo Request
                            </label>
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="radio" name="inquiryType" value="Feature Update Request"> Feature Update Request
                            </label>
                            <label style="display: block;">
                                <input type="radio" name="inquiryType" value="Other Message"> Other Message
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Your Message *</label>
                        <textarea id="message" class="form-control" placeholder="Write your message here..." required></textarea>
                    </div>
                    <div class="mb-3 recaptcha-wrapper">
                            <div class="g-recaptcha" data-sitekey="6LfDpxkrAAAAACEqEE72A1OtR9d9XJNCOUXn49aK"></div>
                        </div>
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
                
                
            </div>
        </div>
    </div>

    <script>
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Collect form data
            const formData = {
                name: document.getElementById('name').value,
                phone: document.getElementById('phone').value,
                email: document.getElementById('email').value,
                inquiryType: document.querySelector('input[name="inquiryType"]:checked').value,
                message: document.getElementById('message').value
            };
            
            // Here you would typically send the data to your server
            console.log('Form submitted:', formData);
            
            // Show success message
            alert('Your message has been sent successfully! We will contact you soon.');
            
            // Reset form
            this.reset();
            
            // Button animation
            const submitBtn = this.querySelector('.submit-btn');
            submitBtn.innerHTML = '<i class="fas fa-check"></i> Message Sent';
            submitBtn.style.background = 'linear-gradient(135deg, #4CAF50, #2E7D32)';
            
            setTimeout(() => {
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Message';
                submitBtn.style.background = 'linear-gradient(135deg, var(--primary), var(--secondary))';
            }, 3000);
        });
        
        // Animation on load
        document.addEventListener('DOMContentLoaded', function() {
            const infoCards = document.querySelectorAll('.info-card');
            
            infoCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });
    </script>
</body>
</html>