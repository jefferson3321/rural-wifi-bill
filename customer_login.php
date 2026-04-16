<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rural WiFi — Customer Login</title>
<link rel="stylesheet" href="style.css">
<style>
body{display:flex;align-items:center;justify-content:center;min-height:100vh;
     background:linear-gradient(135deg,#1a1208,#3d1f00);}
.authBox{background:#fff;width:100%;max-width:400px;padding:44px 36px;
         border-radius:24px;box-shadow:0 24px 60px rgba(0,0,0,.35);}
.errMsg{background:#fff0f0;border:1px solid #ffcccc;color:var(--red);
        padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:12px;display:none;}
.adminLink{text-align:center;margin-top:16px;font-size:12px;color:var(--muted);}
.adminLink a{color:var(--gold);text-decoration:none;font-weight:600;}
.adminLink a:hover{text-decoration:underline;}
@media(max-width:480px){.authBox{padding:32px 22px;border-radius:18px;margin:16px;}}

/* Suspension overlay */
.suspOverlay{display:none;position:fixed;inset:0;z-index:9999;background:rgba(10,5,0,.92);align-items:center;justify-content:center;padding:20px;}
.suspOverlay.show{display:flex;}
.suspBox{background:#fff;border-radius:24px;padding:40px 32px;max-width:400px;width:100%;text-align:center;animation:suspUp .3s ease;}
@keyframes suspUp{from{transform:translateY(24px);opacity:0}to{transform:translateY(0);opacity:1}}
.suspIcon{width:68px;height:68px;background:linear-gradient(135deg,#c94040,#e05252);border-radius:18px;display:inline-flex;align-items:center;justify-content:center;font-size:30px;margin-bottom:16px;box-shadow:0 8px 24px rgba(201,64,64,.3);}
.suspTitle{font-family:'DM Serif Display',serif;font-size:22px;color:#1a1208;margin-bottom:8px;}
.suspMsg{font-size:13px;color:#7a7060;line-height:1.6;margin-bottom:16px;}
.suspAlert{background:#fff0f0;border:1.5px solid #fca5a5;border-radius:12px;padding:14px;margin-bottom:14px;font-size:13px;color:#991b1b;text-align:left;line-height:1.7;}
.suspContact{background:#f5f2eb;border-radius:12px;padding:14px;font-size:13px;color:#5a4a30;line-height:1.7;margin-bottom:20px;}
@media(max-width:480px){.suspBox{padding:28px 20px;}}
</style>
</head>
<body>
<div class="authBox">
  <div style="text-align:center;margin-bottom:24px;">
    <div style="display:inline-flex;align-items:center;justify-content:center;width:68px;height:68px;
                background:linear-gradient(135deg,#ea6f00,#ff9a3c);border-radius:18px;
                box-shadow:0 8px 24px rgba(234,111,0,.35);margin-bottom:14px;">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none">
        <path d="M12 18.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3z" fill="white"/>
        <path d="M8.5 15.5a5 5 0 017 0" stroke="white" stroke-width="2" stroke-linecap="round"/>
        <path d="M5 12a9.5 9.5 0 0114 0" stroke="white" stroke-width="2" stroke-linecap="round"/>
        <path d="M1.5 8.5a14 14 0 0121 0" stroke="white" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </div>
    <div style="font-family:'DM Serif Display',serif;font-size:26px;color:#1a1208;letter-spacing:-.5px;">Rural WiFi</div>
    <div style="font-size:10px;font-weight:700;letter-spacing:3px;color:#ea6f00;text-transform:uppercase;margin-top:3px;">Internet Services</div>
  </div>

  <div style="text-align:center;margin-bottom:18px;">
    <span style="display:inline-flex;align-items:center;gap:6px;background:#f5f3ef;border-radius:20px;
                 padding:6px 14px;font-size:12px;font-weight:600;color:var(--muted);">
      👤 Customer Portal
    </span>
  </div>
  <div style="text-align:center;font-size:13px;color:var(--muted);margin-bottom:22px;">
    Sign in to view your bills &amp; payments
  </div>

  <div class="errMsg" id="errMsg"></div>

  <div class="field">
    <label>Username</label>
    <input type="text" id="loginUser" placeholder="Enter your username" autocomplete="username">
  </div>
  <div class="field">
    <label>Password</label>
    <input type="password" id="loginPass" placeholder="Enter your password"
           autocomplete="current-password" onkeydown="if(event.key==='Enter')doLogin()">
  </div>
  <button class="btnPrimary" id="loginBtn" onclick="doLogin()">Sign In</button>

  <div class="adminLink">
    Are you an admin? <a href="admin_login.php">Go to Admin Login →</a>
  </div>
</div>

<!-- Suspension screen -->
<div class="suspOverlay" id="suspOverlay">
  <div class="suspBox">
    <div class="suspIcon">🚫</div>
    <div class="suspTitle">Account Suspended</div>
    <div class="suspMsg">Your Rural WiFi account has been suspended.</div>
    <div class="suspAlert">
      <strong>Your internet service has been disconnected.</strong><br>
      You cannot access the customer portal at this time.<br>
      Please settle your outstanding balance to restore your service.
    </div>
    <div class="suspContact">
      <strong>To restore your service:</strong><br>
      Visit the office personally and settle your balance with the administrator.
      Your account will be reactivated once payment is confirmed.
    </div>
    <button class="btnPrimary" style="background:linear-gradient(135deg,#1a1208,#2d1f00);"
            onclick="document.getElementById('suspOverlay').classList.remove('show');
                     document.getElementById('loginPass').value='';">
      ← Back to Login
    </button>
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
  const res = await api(API.auth, { action:'login', username:u, password:p, role:'customer' });
  btn.textContent = 'Sign In'; btn.disabled = false;
  if (res.suspended || res.data?.suspended) {
    document.getElementById('suspOverlay').classList.add('show');
    return;
  }
  if (!res.success) {
    err.textContent = res.message || 'Login failed.';
    err.style.display = 'block'; return;
  }
  window.location.href = 'customer.html';
}
(async () => {
  const res = await api(API.auth, { action:'me' });
  if (res.success && res.data.role === 'customer') window.location.href = 'customer.html';
})();
</script>
</body>
</html>
