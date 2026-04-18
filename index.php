<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rural WiFi BillFlow</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #0f172a;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container { text-align: center; color: white; }
        h1 { font-size: 2rem; margin-bottom: 10px; color: #38bdf8; }
        p { color: #94a3b8; margin-bottom: 40px; }
        .buttons { display: flex; gap: 20px; justify-content: center; }
        a {
            padding: 15px 40px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 1rem;
            font-weight: bold;
            transition: 0.3s;
        }
        .admin { background: #38bdf8; color: #0f172a; }
        .admin:hover { background: #0ea5e9; }
        .customer { background: #22c55e; color: white; }
        .customer:hover { background: #16a34a; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌐 Rural WiFi BillFlow</h1>
        <p>Welcome! Please select your login type.</p>
        <div class="buttons">
            <a href="/admin_login.php" class="admin">👤 Admin Login</a>
            <a href="/customer_login.php" class="customer">📱 Customer Login</a>
        </div>
    </div>
</body>
</html>
