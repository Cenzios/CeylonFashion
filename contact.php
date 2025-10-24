<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bootstrap demo</title>
    <style>


    </style>
  <link rel="stylesheet" href="style.css">
    </div>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
  </head>


  <body>
<nav class="navbar navbar-expand-lg navbar-purple">
  <div class="container-fluid">
   <img src="logo.png" alt="Logo" width="30" height="24" class="d-inline-block align-text-top">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
      <div class="navbar-nav">
        <a class="nav-link" href="#">New Arrivals</a>
        <a class="nav-link" href="#">Bridal Attire</a>
        <a class="nav-link" href="#">Bridemaids Attire</a>
        <a class="nav-link" href="#">Party Wear</a>
        <a class="nav-link" href="#">Used Collection</a>
        
      </div>
      <div class="icons" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); display: flex; gap: 15px; font-size: 32px; color: white; cursor: pointer;">
  <i class="bi bi-person-circle"></i>
  <i class="bi bi-heart"></i>
  <i class="bi bi-cart2"></i>
</div>

    </div>
  </div>
</nav>

<!-- search bar start -->

<div class="container-fluid pt-3">
        <div class="row">
            <div class="col-12 col-md-6 col-lg-4">
                <form class="d-flex" role="search">
                    <input class="form-control me-2 search-input" type="search" placeholder="Search by colour or Name" aria-label="Search"/>
                    <button class="btn btn-search" type="submit">Search</button>
                </form>
            </div>
        </div>
    </div>


 <!-- Contact Us Banner -->
  <section class="contact-banner position-relative pt-3">
    <img src="images/contact us page.jpg" 
         class="img-fluid w-100" alt="Contact Us Banner">

    <div class="banner-text position-absolute top-50 start-50 translate-middle text-center">
      <button id="scrollBtn" 
              class="fw-bold display-4 text-white px-4 py-2 border border-3 border-dark rounded-3">
        Contact Us
      </button>
    </div>
  </section>

  <!-- Map -->
<section class="my-4">
  <div class="container">
    <iframe 
      src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d63313.19083824921!2d79.8146768!3d6.9270786!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae2595a31b5e9db%3A0xdee58d7f6e4b7f87!2sColombo!5e0!3m2!1sen!2slk!4v1700000000000" 
      allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
    </iframe>
  </div>
</section>


  <!-- Contact Section -->
<section class="contact-section" id="contactForm">
  <div class="container mt-5">
    <div class="row">

      <div id="success-message" class="alert alert-success mt-4 d-none" role="alert">
</div>

      
      <!-- Contact Form -->
<div class="col-lg-7 mb-4">
  <h3 class="mb-4">Send us an Email</h3>
  <form class="row g-3 needs-validation" novalidate>
    <div class="col-12">
      <label for="name" class="form-label">Name</label>
      <input type="text" class="form-control" id="name" placeholder="Enter your name" minlength="2" maxlength="50" required>
      <div id="nameFeedback" class="invalid-feedback">
      </div>
    </div>
    <div class="col-12">
      <label for="phone" class="form-label">Phone Number</label>
      <input type="tel" class="form-control" id="phone" placeholder="Enter your phone number" required>
      <div id="phoneFeedback" class="invalid-feedback">
      </div>
    </div>
    <div class="col-12">
      <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
      <input type="email" class="form-control" id="email" placeholder="Enter your email" maxlength="254" required>
      <div id="emailFeedback" class="invalid-feedback">
             </div>
    </div>
    <div class="col-12">
      <label for="comment" class="form-label">Comment <span class="text-danger">*</span></label>
      <textarea class="form-control" id="comment" rows="4" placeholder="Enter your comment" minlength="10" maxlength="1000" required></textarea>
      <div id="commentFeedback" class="invalid-feedback">
            </div>
    </div>
    <div class="col-12">
      <button class="btn btn-primary px-4" type="submit">SUBMIT CONTACT</button>
    </div>
  </form>
</div>

      <!-- Contact Info -->
      <div class="col-lg-5">
        <h3 class="mb-4">Our Contact Details</h3>
        <p><strong>Address:</strong><br>
          123 Main Street,<br>
          Colombo, Sri Lanka</p>
        <p><strong>Phone:</strong><br>
          +94 77 123 4567</p>
        <p><strong>Email:</strong><br>
          <a href="mailto:support@ceylonfashion.lk">support@ceylonfashion.lk</a></p>
        <p><strong>Opening Hours:</strong><br>
          Everyday: 9:00AM – 6:00PM</p>
      </div>

    </div>
  </div>
</section>


  <script>// Smooth scroll to contact form when button is clicked
document.getElementById("scrollBtn").addEventListener("click", function () {
  document.getElementById("contactForm").scrollIntoView({
    behavior: "smooth"
  });
});</script>

<script>
  document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  const form = document.querySelector('.needs-validation');
  const nameInput = document.getElementById('name');
  const phoneInput = document.getElementById('phone');
  const emailInput = document.getElementById('email');
  const commentInput = document.getElementById('comment');
  const successMessageDiv = document.getElementById('success-message');

  // Function to validate a single field and update its state
  function validateField(inputElement, feedbackElementId, validationLogic, errorMessage) {
    if (validationLogic(inputElement.value)) {
      inputElement.classList.remove('is-invalid');
      inputElement.classList.add('is-valid');
      return true;
    } else {
      inputElement.classList.remove('is-valid');
      inputElement.classList.add('is-invalid');
      document.getElementById(feedbackElementId).textContent = errorMessage;
      return false;
    }
  }

  // --- Live Validation (on user input) ---
  nameInput.addEventListener('input', () => {
    const trimmedName = nameInput.value.trim();
    const namePattern = /^[a-zA-Z\s'-]+$/;
    validateField(
      nameInput,
      'nameFeedback',
      (value) => trimmedName.length >= 2 && trimmedName.length <= 100 && namePattern.test(trimmedName),
      'Please enter a valid name'
    );
  });

  phoneInput.addEventListener('input', () => {
    const phoneValue = phoneInput.value.replace(/[\s()-]/g, '');
    const localPhonePattern = /^0\d{9}$/;
    const internationalPhonePattern = /^\+94\d{9}$/;
    validateField(
      phoneInput,
      'phoneFeedback',
      (value) => localPhonePattern.test(phoneValue) || internationalPhonePattern.test(phoneValue),
      'Please enter a valid phone number'
    );
  });

  emailInput.addEventListener('input', () => {
    const emailValue = emailInput.value.trim().toLowerCase();
    const emailPattern = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    validateField(
      emailInput,
      'emailFeedback',
      (value) => emailValue.length <= 254 && emailPattern.test(emailValue),
      'Please enter a valid email address.'
    );
  });

  commentInput.addEventListener('input', () => {
    const trimmedComment = commentInput.value.trim();
    validateField(
      commentInput,
      'commentFeedback',
      (value) => trimmedComment.length >= 10 && trimmedComment.length <= 1000,
      'Please enter a comment (10-1000 characters).'
    );
  });

  // --- Form Submission Validation ---
  form.addEventListener('submit', function (event) {
    event.preventDefault();
    event.stopPropagation();

    // Re-run all validations on submit
    const isNameValid = validateField(
      nameInput, 'nameFeedback',
      (value) => {
        const trimmed = value.trim();
        const pattern = /^[a-zA-Z\s'-]+$/;
        return trimmed.length >= 2 && trimmed.length <= 50 && pattern.test(trimmed);
      },
      'Please enter a valid name.'
    );

    const isPhoneValid = validateField(
      phoneInput, 'phoneFeedback',
      (value) => {
        const trimmed = value.replace(/[\s()-]/g, '');
        const local = /^0\d{9}$/.test(trimmed);
        const international = /^\+94\d{9}$/.test(trimmed);
        return local || international;
      },
      'Please enter a valid Sri Lankan phone number.'
    );

    const isEmailValid = validateField(
      emailInput, 'emailFeedback',
      (value) => {
        const trimmed = value.trim().toLowerCase();
        const pattern = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return trimmed.length <= 254 && pattern.test(trimmed);
      },
      'Please enter a valid email address.'
    );

    const isCommentValid = validateField(
      commentInput, 'commentFeedback',
      (value) => {
        const trimmed = value.trim();
        return trimmed.length >= 10 && trimmed.length <= 100;
      },
      'Please enter a comment (10-1000 characters).'
    );

    const isValid = isNameValid && isPhoneValid && isEmailValid && isCommentValid;

    if (isValid) {
      // Remove validation classes after successful submission
      const formControls = form.querySelectorAll('.form-control.is-valid, .form-control.is-invalid');
      formControls.forEach(control => {
        control.classList.remove('is-valid', 'is-invalid');
      });
      // Show success message
      successMessageDiv.classList.remove('d-none');
      successMessageDiv.textContent = 'Thanks for contacting us. we will get back to you soon';
      form.reset(); // Clear the form fields
      
      // Optionally hide success message after a few seconds
      setTimeout(() => {
        successMessageDiv.classList.add('d-none');
      }, 5000); // 5 seconds
    } else {
      successMessageDiv.classList.add('d-none');
    }
  });
});
</script>
  
<!-- This is the Footer section -->

<!-- Footer -->
<footer class="custom-footer text-dark pt-5 pb-4 w-100 mt-5">
  <div class="container text-center text-md-start">
    <div class="row text-center text-md-start">

      <!-- About Us Section -->
      <div class="col-md- 3 col-lg-3 col-xl-3 mx-auto mt-3">
        <h5 class="text-uppercase mb-4 fw-bold">About Us</h5>
        <p>
          At Ceylon Fashion.lk, we bring you the finest bridal, party, and traditional wear collections. 
          Our goal is to make every moment memorable with timeless fashion.
        </p>
        <h6 class="fw-bold mt-3">
          <a href="#contact" class="text-dark text-decoration-none">Contact Us</a>
        </h6>
        <p class="mb-1"><i class="bi bi-geo-alt-fill me-2"></i> 123 Main Street, Colombo, Sri Lanka</p>
        <p class="mb-1"><i class="bi bi-telephone-fill me-2"></i> +94 77 123 4567</p>
        <p><i class="bi bi-envelope-fill me-2"></i> support@ceylonfashion.lk</p>
      </div>

      <!-- Information Section (NEW) -->
      <div class="col-md-2 col-lg-2 col-xl-2 mx-auto mt-3">
        <h5 class="text-uppercase mb-4 fw-bold">Information</h5>
        <p><a href="#" class="text-dark text-decoration-none">Shipping Policy</a></p>
        <p><a href="#" class="text-dark text-decoration-none">Returns & Exchanges</a></p>
        <p><a href="#" class="text-dark text-decoration-none">Terms & Conditions</a></p>
        <p><a href="#" class="text-dark text-decoration-none">Privacy Policy</a></p>
        <p><a href="#" class="text-dark text-decoration-none">FAQ</a></p>
      </div>

      <!-- Products Section -->
      <div class="col-md-2 col-lg-2 col-xl-2 mx-auto mt-3">
        <h5 class="text-uppercase mb-4 fw-bold">Products</h5>
        <p><a href="#" class="text-dark text-decoration-none">Bridal Attire</a></p>
        <p><a href="#" class="text-dark text-decoration-none">Bridemaid's Attire</a></p>
        <p><a href="#" class="text-dark text-decoration-none">Party Wear</a></p>
        <p><a href="#" class="text-dark text-decoration-none">Used Collection</a></p>
      </div>

      <!-- Newsletter Section -->
      <div class="col-md-5 col-lg-5 col-xl-5 mx-auto mt-3">
        <h5 class="text-uppercase mb-4 fw-bold ">Subscribe to Newsletter</h5>
        <form class="d-flex">
          <input type="email" class="form-control me-2 search-input" placeholder="Enter your email">
          <button class="btn btn-search">Subscribe</button>
        </form>
      </div>
    </div>

    <hr class="my-4 text-white">

    <!-- Copyright -->
    <div class="row">
      <div class="col text-center">
        <p class="mb-0">©2025 Ceylon Fashion.lk All Rights Reserved</p>
      </div>
    </div>
  </div>
</footer>
