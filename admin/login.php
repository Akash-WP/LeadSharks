<?php require_once('../config.php') ?>
<!DOCTYPE html>
<html lang="en" class="" style="height: auto;">
<?php require_once('inc/header.php') ?>
<body class="hold-transition">
  <script>start_loader()</script>
  <style>
    html, body {
      height: 100% !important;
      margin: 0;
  background: 
    linear-gradient(120deg, rgba(99,102,241, 0.5), rgba(139,92,246, 0.5), rgba(79,70,229, 0.5)),
    url("<?php echo validate_image($_settings->info('cover')) ?>") center center / cover no-repeat fixed;
  background-blend-mode: overlay;
  animation: bgShift 15s ease-in-out infinite;
      background-size: cover;
      font-family: 'Segoe UI', sans-serif;
    }

    #login-card {
      width: 100%;
    max-width: 1000px;
    height: 600px;
      margin: auto;
      display: flex;
      background: white;
      border-radius: 15px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
      overflow: hidden;
      animation: slideFadeIn 1s ease-out;
    }

    @keyframes slideFadeIn {
      from {
        transform: translateY(30px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .login-left {
      background: #f4f4f4;
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 30px;
    }

    .login-right {
      flex: 1;
      padding: 50px 40px;
      display: flex;
    flex-direction: column;
    justify-content: center;
    }

    #login .form-control {
      border-radius: 5px;
    }

    .btn-primary {
      background-color: #5a67d8;
      border-color: #5a67d8;
    }

    .btn-primary:hover {
      background-color: #434190;
    }

    @media (max-width: 768px) {
      #login-card {
        flex-direction: column;
      }
      .login-left, .login-right {
        flex: unset;
        width: 100%;
      }
      
      @keyframes bgShift {
  0% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
  100% {
    background-position: 0% 50%;
  }
}
    }
  </style>

  <div class="d-flex align-items-center justify-content-center h-100" id="login">
    <div id="login-card">
      <!-- Left animation section -->
      <div class="login-left">
        <lottie-player
          src="https://assets10.lottiefiles.com/packages/lf20_jcikwtux.json"
          background="transparent"
          speed="1"
          style="width: 100%; height: 300px;"
          loop
          autoplay>
        </lottie-player>
      </div>

      <!-- Right login form -->
      <div class="login-right">
        <div class="text-center mb-4">
          <img src="<?= validate_image($_settings->info('logo')) ?>" alt="Logo" id="logo-img" style="height: 80px;">
          <h4 class="mt-2"><b>Login</b></h4>
        </div>
        <form id="login-frm" action="" method="post">
          <div class="input-group mb-3">
            <input type="text" class="form-control" autofocus name="username" placeholder="Username">
            <div class="input-group-append">
              <div class="input-group-text"><span class="fas fa-user"></span></div>
            </div>
          </div>
          <div class="input-group mb-4">
            <input type="password" class="form-control" name="password" placeholder="Password">
            <div class="input-group-append">
              <div class="input-group-text"><span class="fas fa-lock"></span></div>
            </div>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Sign In</button>
          <button type="button" class="btn btn-secondary btn-block" data-toggle="modal" data-target="#forgotModal">Forgot Password</button>
        
        </form>
      </div>
    </div>
  </div>

  <!-- Forgot Password Modal -->
<div class="modal fade" id="forgotModal" tabindex="-1" aria-labelledby="forgotModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="forgot-password-form" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="forgotModalLabel">Forgot Password</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <label for="fp-email">Enter your registered email:</label>
        <input type="email" name="email" class="form-control" id="fp-email" required>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Send Reset Link</button>
      </div>
    </form>
  </div>
</div>

  <!-- Scripts -->
  <!-- <script src="plugins/jquery/jquery.min.js"></script>
  <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="dist/js/adminlte.min.js"></script> -->
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

  <!-- Use CDN to test -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>


     <!--Forgot Password -->
  <script>
    $('#forgot-password-form').submit(function(e) {
  e.preventDefault();
  const email = $('#fp-email').val();
  $.ajax({
    url: _base_url_ + 'classes/Login.php?f=forgot',
    method: 'POST',
    data: { email: email },
    dataType: 'json',
    success: function(res) {
      if (res.status === 'success') {
        alert('Reset link sent to your email.');
        $('#forgotModal').modal('hide');
      } else {
        alert(res.msg || 'Email not found.');
      }
    }
  });
});
  </script>


  <script>
    $(document).ready(function () {
      end_loader();
    });
  </script>

  
</body>
</html>
