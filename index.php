<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - Cabatangan Hardware</title>

  <link href="assets/third_party/poppins.css" rel="stylesheet"/>
  <link rel="stylesheet" href="assets/third_party/animate.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <script src="assets/third_party/tailwind.min.js"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            poppins: ['Poppins','sans-serif'],
          },
          colors: {
            theme: "#dc2626", // Red-600
          }
        }
      }
    }
  </script>
  <style>
    html, body {
      height: 100%;
      width: 100%;
      margin: 0;
      padding: 0;
    }
    
    .bg-theme-gradient {
      background: linear-gradient(135deg, rgb(180, 36, 36), rgb(220, 38, 38));
    }
    
    .btn-theme {
      background-color: #dc2626;
      transition: all 0.3s ease;
    }
    
    .btn-theme:hover {
      background-color: rgb(180, 36, 36);
      transform: translateY(-2px);
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .btn-theme:active {
      transform: translateY(0);
    }
    
    .focus-theme:focus {
      --tw-ring-color: #dc2626;
      border-color: #dc2626;
      outline: none;
      box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.2);
    }
    
    .text-theme {
      color: #dc2626;
    }
    
    .text-theme:hover {
      color: rgb(180, 36, 36);
    }
    
    .card-shadow {
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    
    .input-focus:focus {
      transform: translateY(-1px);
      transition: all 0.3s ease;
    }

    /* Mobile-specific styles */
    .mobile-container {
      min-height: 100vh;
      min-height: 100svh; /* Support for newer browsers */
    }

    /* Improved mobile layout */
    @media (max-width: 768px) {
      body {
        overflow-x: hidden;
        overflow-y: auto;
      }
      
      .mobile-header {
        padding-top: env(safe-area-inset-top, 0);
      }
      
      .mobile-content {
        padding-bottom: env(safe-area-inset-bottom, 1rem);
      }
      
      .card-shadow {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      }
      
      /* Stack layout for mobile */
      .mobile-stack {
        flex-direction: column;
        gap: 1rem;
      }
      
      /* Optimize spacing for mobile */
      .mobile-spacing {
        padding: 1.5rem 1rem;
      }
      
      /* Better mobile form styling */
      .mobile-form {
        gap: 1rem;
      }
      
      /* Improved mobile buttons */
      .btn-theme {
        padding: 0.875rem 1rem;
        font-size: 1rem;
        min-height: 48px; /* Ensure good touch target */
      }
      
      /* Better mobile inputs */
      input, select {
        min-height: 48px;
        font-size: 16px; /* Prevent zoom on iOS */
      }
    }

    @media (max-width: 480px) {
      .mobile-container {
        padding: 0.75rem;
      }
      
      .mobile-spacing {
        padding: 1rem 0.75rem;
      }
      
      /* Smaller screens adjustments */
      h1 {
        font-size: 1.75rem !important;
      }
      
      h2 {
        font-size: 1.25rem !important;
      }
      
      h3 {
        font-size: 1rem !important;
      }
      
      .logo-container {
        width: 4rem !important;
        height: 4rem !important;
      }
      
      .logo-icon {
        width: 2rem !important;
        height: 2rem !important;
      }
    }

    /* Landscape mobile optimization */
    @media (max-height: 600px) and (orientation: landscape) {
      .mobile-container {
        flex-direction: row;
        align-items: center;
        justify-content: center;
        gap: 2rem;
        padding: 1rem;
      }
      
      .left-column, .right-column {
        flex: 1;
        max-width: 50%;
      }
      
      .mobile-spacing {
        padding: 1rem;
      }
      
      .logo-container {
        width: 3rem !important;
        height: 3rem !important;
        margin-bottom: 0.5rem !important;
      }
      
      .logo-icon {
        width: 1.5rem !important;
        height: 1.5rem !important;
      }
      
      h1 {
        font-size: 1.5rem !important;
        margin-bottom: 0.25rem !important;
      }
      
      h3 {
        font-size: 0.875rem !important;
        margin-bottom: 0.25rem !important;
      }
      
      .mobile-form {
        gap: 0.75rem;
      }
      
      input, select, .btn-theme {
        padding: 0.5rem 0.75rem;
        min-height: 40px;
      }
    }

    /* Animation optimizations for mobile */
    @media (prefers-reduced-motion: reduce) {
      .animate__animated {
        animation: none !important;
      }
      
      .btn-theme:hover {
        transform: none;
      }
      
      .input-focus:focus {
        transform: none;
      }
    }

    /* Focus visibility improvements */
    .focus-visible {
      outline: 2px solid #dc2626;
      outline-offset: 2px;
    }

    /* Loading state for form submission */
    .btn-loading {
      opacity: 0.7;
      pointer-events: none;
    }

    .btn-loading::after {
      content: '';
      display: inline-block;
      width: 1rem;
      height: 1rem;
      margin-left: 0.5rem;
      border: 2px solid transparent;
      border-top: 2px solid currentColor;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }
  </style>
</head>
<body class="mobile-container font-poppins bg-theme-gradient flex items-center justify-center animate__animated animate__fadeIn">
  <div class="flex mobile-stack w-full max-w-5xl mx-auto mobile-spacing">

    <!-- LEFT COLUMN -->
    <div class="left-column w-full md:w-1/2 flex flex-col items-center justify-center space-y-2 sm:space-y-4 p-2 sm:p-4 md:p-8 animate__animated animate__fadeIn mobile-header">
      <div class="logo-container bg-white bg-opacity-30 backdrop-blur-sm rounded-full w-16 h-16 sm:w-20 sm:h-20 md:w-24 md:h-24 flex items-center justify-center shadow-lg mb-2 transform transition-transform duration-500 hover:scale-105">
        <svg class="logo-icon w-8 h-8 sm:w-10 sm:h-10 md:w-12 md:h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6m10-9H8a2 2 0 00-2 2v14a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2z" />
        </svg>
      </div>
      <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-white text-center drop-shadow-md">Cabatangan Hardware</h1>
      <h3 class="text-base sm:text-lg md:text-xl font-semibold text-white text-center opacity-90">Inventory System</h3>
    </div>

    <!-- RIGHT COLUMN -->
    <div class="right-column w-full md:w-1/2 flex items-center justify-center p-2 sm:p-4 md:p-6 animate__animated animate__fadeInUp md:animate__fadeInRight md:animate__delay-1s mobile-content">
      <div class="bg-white rounded-2xl card-shadow p-4 sm:p-6 w-full max-w-md transform transition-all duration-300 hover:scale-[1.02]">
        <h2 class="text-lg sm:text-xl md:text-2xl font-semibold text-gray-800 mb-4 sm:mb-6 text-center">Welcome Back!</h2>
        <form method="POST" action="login.php" class="space-y-3 sm:space-y-4 md:space-y-5 mobile-form" id="loginForm">
          <div>
            <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
            <input 
              type="text" 
              id="username" 
              name="username" 
              required 
              placeholder="Enter your username"
              autocomplete="username"
              class="w-full px-4 py-3 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus-theme input-focus" 
            />
          </div>
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
            <div class="relative">
              <input 
                type="password" 
                id="password" 
                name="password" 
                required 
                placeholder="Enter your password"
                autocomplete="current-password"
                class="w-full px-4 py-3 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus-theme input-focus" 
              />
              <i class="fas fa-eye absolute top-1/2 right-4 -translate-y-1/2 cursor-pointer text-gray-400 hover:text-gray-600" id="togglePassword"></i>
            </div>
          </div>
          <div>
            <label for="userType" class="block text-sm font-medium text-gray-700 mb-2">User Type</label>
            <select 
              id="userType" 
              name="userType" 
              required
              class="w-full px-4 py-3 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus-theme input-focus"
            >
              <option value="" disabled selected>Select User Type</option>
              <option value="cashier">Cashier</option>
              <option value="staff">Staff</option>
              <option value="procurement">Procurement</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <button 
            type="submit"
            class="w-full py-3 px-4 text-white text-sm font-medium rounded-lg shadow-md btn-theme mt-4"
            id="submitBtn"
          >
            Sign in
          </button>
        </form>
      </div>
    </div>
  </div>

  <script>
    // Add loading state to form submission
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      const submitBtn = document.getElementById('submitBtn');
      submitBtn.classList.add('btn-loading');
      submitBtn.textContent = 'Signing in';
    });

    // Add keyboard navigation improvements
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') {
        const focusedElement = document.activeElement;
        if (focusedElement.tagName === 'INPUT' || focusedElement.tagName === 'SELECT') {
          const form = focusedElement.closest('form');
          if (form) {
            const inputs = form.querySelectorAll('input, select, button');
            const currentIndex = Array.from(inputs).indexOf(focusedElement);
            const nextInput = inputs[currentIndex + 1];
            
            if (nextInput && nextInput.tagName !== 'BUTTON') {
              e.preventDefault();
              nextInput.focus();
            }
          }
        }
      }
    });

    // Improve accessibility
    document.querySelectorAll('input, select, button').forEach(element => {
      element.addEventListener('focus', function() {
        this.classList.add('focus-visible');
      });
      
      element.addEventListener('blur', function() {
        this.classList.remove('focus-visible');
      });
    });

    // Handle viewport height changes on mobile (for keyboard appearance)
    function handleViewportChange() {
      const vh = window.innerHeight * 0.01;
      document.documentElement.style.setProperty('--vh', `${vh}px`);
    }

    window.addEventListener('resize', handleViewportChange);
    window.addEventListener('orientationchange', handleViewportChange);
    handleViewportChange();

    // Prevent zoom on input focus for iOS
    if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
      const inputs = document.querySelectorAll('input, select, textarea');
      inputs.forEach(input => {
        input.addEventListener('focus', function() {
          this.style.fontSize = '16px';
        });
        input.addEventListener('blur', function() {
          this.style.fontSize = '';
        });
      });
    }

    // Password toggle
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');

    togglePassword.addEventListener('click', function (e) {
      // toggle the type attribute
      const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
      password.setAttribute('type', type);
      // toggle the eye slash icon
      this.classList.toggle('fa-eye-slash');
    });
  </script>

</body>
</html>