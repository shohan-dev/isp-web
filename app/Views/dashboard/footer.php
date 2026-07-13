<footer class="footer">
    <div class="footer_container">
        <!-- RUET Contact Information -->
        <div class="footer-section" data-nosnippet>
            <h3>ISP Pay Bd Contact Information</h3>
            <p>Registrar<br>
                ISP Pay Bd ISP Management<br>
                841-Badda Link road Dhaka,1212.</p>
            <p>📞 Phone:<a href="tel:+8801781808231">+8801781808231</a><br>
                📞 Phone:<a href="tel:+8801610585100">+8801610585100</a><br>
                📞 Phone:<a href="tel:+8801628856735">+8801628856735</a><br>
                📠 Fax: 09638411110<br>
                📧 Email: <a href="mailto:info@isppaybd.com">info@isppaybd.com</a><br>
                🌐 Website: <a href="https://isppaybd.com/" target="_blank">www.isppaybd.com</a></p>
        </div>

        <!-- About RUET -->
        <div class="footer-section">
            <h3>About ISP Pay Bd</h3>
            <p>ISP Pay Bd ISP Management Company is a leading provider of comprehensive ISP management solutions,
                offering high-quality services and innovative technology to streamline operations and enhance customer
                satisfaction.</p>
            <a href="#about_us" class="button">More Info</a>
        </div>

        <!-- Important Links -->
        <div class="footer-section2">
            <h3>Important Links</h3>
            <p>
                <a href="#">Home</a><br>
                <a href="#">NOC</a><br>
                <a href="#">Career at ISP Pay Bd</a><br>
                <a href="#">Tender</a><br>
                <a href="#">Notice</a><br>
                <a href="#">News & Events</a><br>
                <a href="#">Achievements</a><br>

            </p>
        </div>

        <!-- Map -->
        <div class="footer-section">
            <h3>Location</h3>
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d934688.3221625541!2d89.2765008!3d23.7785179!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3755c7570006d9d9%3A0xb826f5bff72131b!2sISP%20Pay%20BD!5e0!3m2!1sbn!2sbd!4v1744918986672!5m2!1sbn!2sbd"
                allowfullscreen="" loading="lazy"></iframe>

        </div>

    </div>
    <div class="copyright">
        Copyright © 2022 - <span id="current-year"></span>&nbsp;
        <a style="color: var(--primary-500, #F75803); font-weight: bolder; font-size: larger;" href="https://isppaybd.com"
            target="_blank"><u> ISP Pay Bd </u></a>&nbsp;| &nbsp;All rights All rights <a
            href="https://portfolio-livid-nine-23.vercel.app/" target="_blank" rel="noopener noreferrer">reserved</a>

    </div>
</footer>

<script>
    document.getElementById("current-year").textContent = new Date().getFullYear();
</script>

<style>
    .footer {
        width: 100%;
        background-color: #002244;
        color: #ffffff;
        padding: 30px 40px;
    }

    .footer_container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        justify-content: space-between;
        padding: 0 20px;
    }

    .footer-section,
    .footer-section2 {
        flex: 1;
        min-width: 250px;
    }

    .footer h3 {
        font-size: 18px;
        border-bottom: 2px solid #ffffff;
        margin-bottom: 15px;
    }

    .footer p,
    .footer a {
        font-size: 16px;
        line-height: 1.8;
        color: #ffffff;
        text-decoration: none;
    }

    .footer a:hover {
        text-decoration: underline;
    }

    .button {
        display: inline-block;
        padding: 8px 15px;
        margin-top: 10px;
        background-color: rgb(250, 250, 250);
        color: rgb(0, 0, 0) !important;
        font-size: 14px;
        font-weight: bold;
        text-align: center;
        border-radius: 5px;
        text-decoration: none;
    }

    .button:hover {
        background-color: black;
        color: rgb(255, 255, 255) !important;
    }

    iframe {
        width: 100%;
        height: 200px;
        border: none;
        border-radius: 8px;
    }

    .copyright {
        text-align: center;
        margin-top: 20px;
        font-size: 14px;
        color: #ffffff;
    }

    /* Tablet View */
    @media (max-width: 768px) {
        .footer_container {
            flex-direction: column;
            align-items: center;
        }

        .footer-section,
        .footer-section2 {
            min-width: 100%;
            margin-bottom: 20px;
        }
    }

    /* Mobile View */
    @media (max-width: 400px) {
        .footer h3 {
            font-size: 16px;
        }

        .footer {
            padding: 30px 0;



        }

        .footer p,
        .footer a {
            font-size: 13px;
        }

        .button {
            font-size: 12px;
            padding: 6px 10px;
        }

        .copyright {
            width: 100%;
            font-size: 11px;
        }
    }
</style>