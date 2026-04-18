<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rural WiFi — Admin Login</title>
<link rel="stylesheet" href="style.css">
<style>
body{display:flex;align-items:center;justify-content:center;min-height:100vh;
     background:linear-gradient(135deg,#0d1a2b,#0a2a1f);}
.authBox{background:#fff;width:100%;max-width:400px;padding:44px 36px;
         border-radius:24px;box-shadow:0 24px 60px rgba(0,0,0,.45);}
.errMsg{background:#fff0f0;border:1px solid #ffcccc;color:var(--red);
        padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:12px;display:none;}
.secNote{display:flex;align-items:center;gap:8px;background:#f0f7ff;border:1px solid #cce0ff;
         border-radius:8px;padding:10px 14px;font-size:12px;color:#004a99;margin-bottom:16px;}
.custLink{text-align:center;margin-top:16px;font-size:12px;color:var(--muted);}
.custLink a{color:#0070e0;text-decoration:none;font-weight:600;}
.custLink a:hover{text-decoration:underline;}
@media(max-width:480px){.authBox{padding:32px 22px;border-radius:18px;margin:16px;}}
</style>
</head>
<body>
<div class="authBox">
  <div style="text-align:center;margin-bottom:24px;">
    <div style="display:inline-flex;align-items:center;justify-content:center;width:68px;height:68px;
                background:linear-gradient(135deg,#0057b8,#0070e0);border-radius:18px;
                box-shadow:0 8px 24px rgba(0,87,184,.35);margin-bottom:14px;">
      <svg width="34" height="34" viewBox="0 0 24 24" fill="none">
        <rect x="5" y="11" width="14" height="10" rx="2" fill="white" opacity=".9"/>
        <path d="M8 11V7a4 4 0 118 0v4" stroke="white" stroke-width="2" stroke-linecap="round"/>
        <circle cx="12" cy="16" r="1.5" fill="#0070e0"/>
      </svg>
    </div>
    <div style="font-family:'DM Serif Display',serif;font-size:26px;color:#1a1208;letter-spacing:-.5px;">Rural WiFi</div>
    <div style="font-size:10px;font-weight:700;letter-spacing:3px;color:#0070e0;text-transform:uppercase;margin-top:3px;">Admin Portal</div>
  </div>

  <div style="text-align:center;margin-bottom:18px;">
    <span style="display:inline-flex;align-items:center;gap:6px;background:#eef3ff;border-radius:20px;
                 padding:6px 14px;font-size:12px;font-weight:600;color:#0057b8;">
      🔐 Administrator Access
    </span>
  </div>

  <div class="secNote">🛡️ This page is for system administrators only.</div>
  <div class="errMsg" id="errMsg"></div>

  <div class="field">
    <label>Admin Username</label>
    <input type="text" id="loginUser" placeholder="Enter admin username" autocomplete="username">
  </div>
  <div class="field">
    <label>Password</label>
    <input type="password" id="loginPass" placeholder="Enter password"
           autocomplete="current-password" onkeydown="if(event.key==='Enter')doLogin()">
  </div>
  <button class="btnPrimary" id="loginBtn" onclick="doLogin()"
          style="background:linear-gradient(135deg,#0057b8,#0070e0);">
    Sign In as Admin
  </button>

  <div class="custLink">
    Not an admin? <a href="customer_login.php">← Customer Login</a>
  </div>
</div>

<script src="api.js"></script>
<script>
async function doLogin() {
  const u   = document.getElementById('loginUser').value.trim();
  const p   = document.getElementById('loginPass').value;
  const err = document.getElementById('errMsg');
  const btn = document.getElementById('loginBtn');
  err.style.display = 'none';
  if (!u || !p) {
    err.textContent = 'Please enter your username and password.';
    err.style.display = 'block'; return;
  }
  btn.textContent = 'Signing in…'; btn.disabled = true;
  const res = await api(API.auth, { action:'login', username:u, password:p, role:'admin' });
  btn.textContent = 'Sign In as Admin'; btn.disabled = false;
  if (!res.success) {
    err.textContent = res.message || 'Login failed.';
    err.style.display = 'block'; return;
  }
  window.location.href = 'admin.php';
}
(async () => {
  const res = await api(API.auth, { action:'me' });
  if (res.success && res.data.role === 'admin') window.location.href = 'admin.html';
})();
</script>
</body>
</html>
