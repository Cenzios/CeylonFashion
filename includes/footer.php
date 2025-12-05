<?php
// footer.php
?>
<footer class="custom-footer">
  <div class="footer-container">
    <div class="footer-grid">
      
      <!-- About Us Section -->
      <div class="footer-column">
        <h5 class="footer-heading">ABOUT US</h5>
        <p class="footer-text">
          At Ceylon Fashion.lk, we bring you the finest bridal, party, and traditional wear collections. Our goal is to make every moment memorable with timeless fashion.
        </p>
        <div class="contact-info">
          <h6 class="contact-heading">Contact Us</h6>
          <p class="contact-item">
            <i class="bi bi-geo-alt-fill"></i>
            123 Main Street, Colombo, Sri Lanka
          </p>
          <p class="contact-item">
            <i class="bi bi-telephone-fill"></i>
            +94 77 123 4567
          </p>
          <p class="contact-item">
            <i class="bi bi-envelope-fill"></i>
            support@ceylonfashion.lk
          </p>
        </div>
      </div>

      <!-- Information Section -->
      <div class="footer-column">
        <h5 class="footer-heading">INFORMATION</h5>
        <ul class="footer-links">
          <li><a href="#">Shipping Policy</a></li>
          <li><a href="#">Returns & Exchanges</a></li>
          <li><a href="#">Terms & Conditions</a></li>
          <li><a href="#">Privacy Policy</a></li>
          <li><a href="#">FAQ</a></li>
        </ul>
      </div>

      <!-- Products Section -->
      <div class="footer-column">
        <h5 class="footer-heading">PRODUCTS</h5>
        <ul class="footer-links">
          <li><a href="index.php#bridalAttireSection">Bridal Attire</a></li>
          <li><a href="index.php#brideMaidsSection">Bridemaid's Attire</a></li>
          <li><a href="index.php#partyWearSection">Party Wear</a></li>
          <li><a href="index.php#usedCollectionSection">Used Collection</a></li>
        </ul>
      </div>

      <!-- Newsletter Section -->
      <div class="footer-column newsletter-column">
        <h5 class="footer-heading">SUBSCRIBE TO NEWSLETTER</h5>
        <form class="newsletter-form">
          <input type="email" class="newsletter-input" placeholder="Enter your email" required>
          <button type="submit" class="newsletter-btn">Subscribe</button>
        </form>
      </div>

    </div>

    <!-- Copyright -->
    <div class="footer-bottom">
      <p class="copyright">©2025 Ceylon Fashion.lk All Rights Reserved</p>
    </div>
  </div>
</footer>

<style>
  .custom-footer {
    background-color: #e8e8e8;
    width: 100%;
    padding: 50px 0 20px;
    margin-top: 60px;
  }

  .footer-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 40px;
  }

  .footer-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 2fr;
    gap: 40px;
    margin-bottom: 40px;
  }

  .footer-column {
    color: #333;
  }

  .footer-heading {
    font-size: 16px;
    font-weight: 700;
    color: #000;
    margin: 0 0 20px 0;
    letter-spacing: 0.5px;
  }

  .footer-text {
    font-size: 14px;
    line-height: 1.6;
    color: #333;
    margin-bottom: 20px;
  }

  .contact-info {
    margin-top: 20px;
  }

  .contact-heading {
    font-size: 15px;
    font-weight: 700;
    color: #000;
    margin: 0 0 12px 0;
  }

  .contact-item {
    font-size: 14px;
    color: #333;
    margin: 8px 0;
    display: flex;
    align-items: flex-start;
    gap: 8px;
  }

  .contact-item i {
    margin-top: 2px;
    font-size: 14px;
  }

  .footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .footer-links li {
    margin-bottom: 12px;
  }

  .footer-links a {
    color: #333;
    text-decoration: none;
    font-size: 14px;
    transition: color 0.2s ease;
  }

  .footer-links a:hover {
    color: #7c3aed;
  }

  /* Newsletter */
  .newsletter-column {
    padding-left: 20px;
  }

  .newsletter-form {
    display: flex;
    gap: 10px;
    margin-top: 15px;
  }

  .newsletter-input {
    flex: 1;
    padding: 14px 16px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
    background: #fff;
    color: #333;
    outline: none;
  }

  .newsletter-input::placeholder {
    color: #999;
  }

  .newsletter-input:focus {
    border-color: #7c3aed;
  }

  .newsletter-btn {
    padding: 14px 30px;
    background: #7c3aed;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.3s ease;
    white-space: nowrap;
  }

  .newsletter-btn:hover {
    background: #6d28d9;
  }

  /* Footer Bottom */
  .footer-bottom {
    text-align: center;
    padding-top: 30px;
    border-top: 1px solid #d0d0d0;
  }

  .copyright {
    font-size: 14px;
    color: #333;
    margin: 0;
  }

  /* Responsive Design */
  @media (max-width: 1024px) {
    .footer-grid {
      grid-template-columns: 1fr 1fr;
      gap: 30px;
    }

    .newsletter-column {
      padding-left: 0;
    }
  }

  @media (max-width: 768px) {
    .custom-footer {
      padding: 40px 0 20px;
    }

    .footer-container {
      padding: 0 20px;
    }

    .footer-grid {
      grid-template-columns: 1fr;
      gap: 30px;
    }

    .footer-heading {
      font-size: 15px;
    }

    .newsletter-form {
      flex-direction: column;
    }

    .newsletter-btn {
      width: 100%;
    }

    .contact-item {
      font-size: 13px;
    }

    .footer-text,
    .footer-links a {
      font-size: 13px;
    }
  }
</style>

<!-- Bootstrap Icons (if not already included) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">