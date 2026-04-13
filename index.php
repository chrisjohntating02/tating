<?php
session_start();

// Preserve error flash messages
$errors = [];
if (isset($_SESSION['ERRMSG_ARR']) && is_array($_SESSION['ERRMSG_ARR']) && count($_SESSION['ERRMSG_ARR']) > 0) {
    $errors = $_SESSION['ERRMSG_ARR'];
    unset($_SESSION['ERRMSG_ARR']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>SP POS SYSTEM - Login</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="shortcut icon" href="main/images/pos.jpg">

  <link href="main/css/bootstrap.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="main/css/DT_bootstrap.css">
  <link rel="stylesheet" href="main/css/font-awesome.min.css">
  <link href="main/css/bootstrap-responsive.css" rel="stylesheet">
  <link href="style.css" media="screen" rel="stylesheet" type="text/css" />

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root{
      --glass-bg: rgba(255,255,255,0.9);
      --accent-1: #1e3c72;   /* midnight blue */
      --accent-2: #2a5298;   /* deep indigo */
      --muted: #6c757d;
      --text-strong: #111;   /* dark text for clarity */
    }

    body{
      height:100vh;
      font-family:"Inter",sans-serif;
      background: linear-gradient(135deg,var(--accent-1),var(--accent-2));
      display:flex;
      align-items:center;
      justify-content:center;
      padding:20px;
    }

    .login-card{
      background: var(--glass-bg);
      border-radius:16px;
      padding:30px;
      width:100%;
      max-width:420px;
      box-shadow:0 20px 40px rgba(0,0,0,0.3);
      animation: fadeIn 0.5s ease;
    }

    .login-card h2{
      margin:0;
      font-weight:800;
      font-size:26px;
      text-align:center;
      color: var(--text-strong); /* darker text */
    }

    .login-card img{
      display:block;
      margin:0 auto 14px;
      width:64px; height:64px;
      border-radius:12px;
      box-shadow:0 6px 20px rgba(0,0,0,0.25);
    }

    .field{
      display:flex;
      margin-top:16px;
    }
    .addon{
      width:50px;
      display:flex;
      align-items:center;
      justify-content:center;
      border:1px solid #bbb;
      border-radius:10px 0 0 10px;
      background:#f1f1f1;
      color:#333;
      font-size:18px;
    }
    .field input{
      flex:1;
      border:1px solid #bbb;
      border-left:0;
      border-radius:0 10px 10px 0;
      padding:12px;
      font-size:15px;
      color: var(--text-strong); /* sharp text inside inputs */
      background:#fff;
      outline:none;
      transition: border-color .2s, box-shadow .2s;
    }
    .field input::placeholder {
      color: #666;
      font-weight: 500;
    }
    .field input:focus{
      border-color: var(--accent-2);
      box-shadow:0 0 0 3px rgba(42,82,152,0.25);
    }

    .pw-toggle{
      display:inline-flex;
      align-items:center;
      gap:6px;
      margin-top:6px;
      cursor:pointer;
      font-size:13px;
      color:var(--muted);
      user-select:none;
    }

    .btn-login{
      margin-top:20px;
      width:100%;
      padding:12px;
      border:none;
      border-radius:10px;
      background: linear-gradient(90deg,var(--accent-1),var(--accent-2));
      color:white;
      font-weight:700;
      font-size:16px;
      box-shadow:0 12px 28px rgba(30,60,114,0.35);
      transition: transform .15s, box-shadow .15s;
    }
    .btn-login:hover{
      transform:translateY(-1px);
      box-shadow:0 16px 34px rgba(30,60,114,0.45);
    }
    .btn-login:active{ transform:translateY(1px); }

    .err-box{
      background: rgba(255,235,238,0.9);
      border:1px solid #e57373;
      color:#b71c1c;
      border-radius:8px;
      padding:10px 14px;
      margin-top:16px;
      font-size:14px;
    }

    .small-note{
      margin-top:14px;
      text-align:center;
      font-size:13px;
      color:#444; /* darker for clarity */
    }

    @keyframes fadeIn{
      from{opacity:0; transform:translateY(12px);}
      to{opacity:1; transform:translateY(0);}
    }
  </style>
</head>
<body>
  <div class="login-card">
    <img src="main/images/pos.jpg" alt="POS logo">
    <h2>SP POS SYSTEM</h2>

    <?php if (!empty($errors)): ?>
      <div class="err-box" role="alert">
        <strong>Login error:</strong>
        <ul style="margin:6px 0 0 18px; padding:0;">
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="login.php" method="post" autocomplete="off">
      <div class="field">
        <span class="addon"><i class="fa fa-user"></i></span>
        <input type="text" name="username" placeholder="Username" required>
      </div>

      <div class="field" style="margin-top:16px;">
        <span class="addon"><i class="fa fa-lock"></i></span>
        <input type="password" id="password" name="password" placeholder="Password" required>
      </div>

      <div class="pw-toggle" id="togglePw">
        <i class="fa fa-eye" id="eyeIcon"></i> <span>Show Password</span>
      </div>

      <button type="submit" class="btn-login">
        <i class="fa fa-sign-in"></i> &nbsp; Login
      </button>

      <div class="small-note">© <?= date('Y') ?> SP POS SYSTEM</div>
    </form>
  </div>

  <script src="main/js/jquery.js"></script>
  <script src="main/js/bootstrap.js"></script>

  <script>
    const pw = document.getElementById('password');
    const toggle = document.getElementById('togglePw');
    const eye = document.getElementById('eyeIcon');
    toggle.addEventListener('click', () => {
      const isPwd = pw.type === 'password';
      pw.type = isPwd ? 'text' : 'password';
      eye.className = isPwd ? 'fa fa-eye-slash' : 'fa fa-eye';
      toggle.querySelector('span').textContent = isPwd ? 'Hide Password' : 'Show Password';
    });
  </script>
</body>
</html>
