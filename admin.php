<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Rural WiFi — Admin</title>
<link rel="stylesheet" href="style.css">
<style>
/* ── ADMIN OVERRIDES ── */
:root{--sidebar-w:220px;}

/* Top bar accent for admin */
.mobileTopBar{background:#0d1520;}

/* Compact sidebar */
.sidebar{background:#0d1520;}
.sidebarLogo{padding:18px 16px;}
.navItem{padding:10px 10px;margin:1px 6px;font-size:13px;}
.navSection{padding:12px 10px 3px;font-size:9px;}

/* Page title row */
.pgRow{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;}
.pgTitle{font-family:'DM Serif Display',serif;font-size:20px;color:var(--ink);}
@media(min-width:768px){.pgTitle{font-size:24px;}}

/* Stats row */
.statRow{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:16px;}
@media(min-width:600px){.statRow{grid-template-columns:repeat(4,1fr);}}
.sc{background:#fff;border-radius:12px;padding:14px 16px;border-top:3px solid var(--gold);box-shadow:var(--shadow);}
.sc-label{font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:5px;}
.sc-val{font-family:'DM Serif Display',serif;font-size:22px;}
.sc-val.g{color:var(--green);}
.sc-val.r{color:var(--red);}
.sc-val.b{color:var(--blue);}

/* Proof card */
.proofCard{background:#fff;border:1px solid var(--border);border-radius:14px;padding:16px 18px;margin-bottom:12px;box-shadow:var(--shadow);}
.proofCard.pend{border-left:4px solid var(--gold);}
.proofCard.done{border-left:4px solid var(--green);opacity:.75;}
.proofMeta{font-size:13px;color:var(--muted);margin-top:3px;}
.proofRef{display:inline-block;background:#fff8f2;border:1px solid #f0ddd0;border-radius:8px;padding:5px 12px;font-size:13px;margin-top:8px;}
.proofActions{display:flex;gap:8px;margin-top:12px;}
.proofActions button{flex:1;}

/* Customer row actions */
.cRow-actions{display:flex;gap:6px;}

/* Invoice row */
.invRow{display:flex;align-items:center;justify-content:space-between;padding:11px 0;border-bottom:1px solid var(--border);gap:8px;flex-wrap:wrap;}
.invRow:last-child{border-bottom:none;}
.invRow-left{flex:1;min-width:160px;}
.invRow-right{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}

/* Alert banner */
.alertBanner{display:none;background:#fff8e1;border:1.5px solid #ffe082;border-radius:12px;padding:13px 16px;margin-bottom:14px;align-items:center;gap:12px;flex-wrap:wrap;}
.alertBanner.show{display:flex;}

/* Revenue row */
.revRow{display:flex;gap:0;border-radius:12px;overflow:hidden;border:1px solid var(--border);background:#fff;margin-bottom:16px;}
.revItem{flex:1;text-align:center;padding:16px 10px;}
.revItem+.revItem{border-left:1px solid var(--border);}
.revItem-label{font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:5px;}
.revItem-val{font-family:'DM Serif Display',serif;font-size:22px;}
@media(max-width:480px){.revRow{flex-direction:column;} .revItem+.revItem{border-left:none;border-top:1px solid var(--border);}}

/* GCash preview card */
.gcashPreview{background:linear-gradient(135deg,#0a4fff,#0070e0);border-radius:14px;padding:20px;text-align:center;color:#fff;margin-top:14px;}

/* Section header */
.sectionHd{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin:18px 0 10px;}
.sectionHd.gold{color:var(--gold);}
.sectionHd.green{color:var(--green);}

/* Empty */
.empty{text-align:center;padding:32px 20px;color:var(--muted);}
.emptyIcon{font-size:32px;margin-bottom:8px;}

/* Customer list */
.custCard{background:#fff;border:1px solid var(--border);border-radius:12px;padding:14px 16px;margin-bottom:10px;box-shadow:var(--shadow);}
.custCard-row{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:wrap;}
.cRow-actions{display:flex;gap:6px;flex-wrap:wrap;}

/* Due Soon Alert Cards */
/* dueSoonCard styles removed */

/* ── MESSAGES ── */
.msgLayout{display:grid;grid-template-columns:1fr;gap:14px;}
@media(min-width:768px){.msgLayout{grid-template-columns:260px 1fr;min-height:calc(100vh - 130px);}}
.convList{background:#fff;border:1px solid var(--border);border-radius:12px;overflow-y:auto;max-height:420px;}
@media(min-width:768px){.convList{max-height:none;}}
.convItem{display:flex;align-items:center;gap:11px;padding:12px 14px;border-bottom:1px solid var(--border);cursor:pointer;transition:background .15s;}
.convItem:last-child{border-bottom:none;}
.convItem:hover{background:#faf9f7;}
.convItem.active{background:#fff8ee;border-left:3px solid var(--gold);}
.convAvatar{width:36px;height:36px;border-radius:50%;background:var(--gold);display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:13px;flex-shrink:0;}
.convName{font-weight:600;font-size:14px;line-height:1.2;}
.convPreview{font-size:11px;color:var(--muted);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:150px;}
.convUnread{background:var(--red);color:#fff;border-radius:10px;font-size:10px;padding:1px 6px;margin-left:auto;flex-shrink:0;}
.chatPanel{background:#fff;border:1px solid var(--border);border-radius:12px;display:flex;flex-direction:column;}
.chatPanelHdr{padding:13px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;flex-shrink:0;}
.chatMsgs{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:9px;min-height:220px;max-height:380px;}
@media(min-width:768px){.chatMsgs{max-height:none;flex:1;}}
.msg{max-width:78%;padding:9px 13px;border-radius:14px;font-size:13.5px;line-height:1.5;word-break:break-word;}
.msg.sent{background:var(--ink);color:#fff;align-self:flex-end;border-bottom-right-radius:3px;}
.msg.recv{background:var(--cream);color:var(--ink);align-self:flex-start;border-bottom-left-radius:3px;}
.msgTime{font-size:10px;opacity:.55;margin-top:3px;}
.chatInputRow{display:flex;gap:8px;padding:11px 13px;border-top:1px solid var(--border);flex-shrink:0;}
.chatInputRow input{flex:1;padding:9px 13px;border:1.5px solid var(--border);border-radius:10px;font-size:13px;font-family:'DM Sans',sans-serif;outline:none;}
.chatInputRow input:focus{border-color:var(--gold);}
.noConvYet{display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:200px;color:var(--muted);}
</style>
</head>
<body>
<div class="appWrap">

  <!-- Mobile Topbar -->
  <div class="mobileTopBar">
    <div class="mLogoWrap">
      <div class="mLogoIcon" style="background:linear-gradient(135deg,#0057b8,#0070e0);">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
          <rect x="5" y="11" width="14" height="10" rx="2" fill="white" opacity=".9"/>
          <path d="M8 11V7a4 4 0 118 0v4" stroke="white" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
      <div class="mLogoText" style="color:#fff;">Admin Portal</div>
    </div>
    <button class="hamburger" onclick="toggleSidebar()">
      <span></span><span></span><span></span>
    </button>
  </div>
  <div class="sidebarOverlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

  <!-- Sidebar -->
  <div class="sidebar">
    <div class="sidebarLogo">
      <div style="display:flex;align-items:center;gap:11px;">
        <div style="width:38px;height:38px;background:linear-gradient(135deg,#c8973a,#e8a84a);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
            <path d="M12 18.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3z" fill="white"/>
            <path d="M8.5 15.5a5 5 0 017 0" stroke="white" stroke-width="2" stroke-linecap="round"/>
            <path d="M5 12a9.5 9.5 0 0114 0" stroke="white" stroke-width="2" stroke-linecap="round"/>
            <path d="M1.5 8.5a14 14 0 0121 0" stroke="white" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
        <div>
          <div style="font-family:'DM Serif Display',serif;font-size:16px;color:#fff;line-height:1.1;">Rural WiFi</div>
          <div style="font-size:9px;letter-spacing:2px;color:var(--gold);text-transform:uppercase;font-weight:700;">Admin Portal</div>
        </div>
      </div>
    </div>

    <div class="navSection">Main</div>
    <div class="navItem active" id="nav-dashboard" onclick="showPage('dashboard')">
      <span class="icon">📊</span> Dashboard

    </div>

    <div class="navSection">Manage</div>
    <div class="navItem" id="nav-customers" onclick="showPage('customers')"><span class="icon">👥</span> Customers</div>
    <div class="navItem" id="nav-invoices" onclick="showPage('invoices')"><span class="icon">📄</span> Invoices &amp; Billing</div>

    <div class="navSection">Config</div>
    <div class="navItem" id="nav-settings" onclick="showPage('settings')"><span class="icon">⚙️</span> Settings</div>

    <div class="navSection">Support</div>
    <div class="navItem" id="nav-messages" onclick="showPage('messages')">
      <span class="icon">💬</span> Messages
      <span class="notifBadge" id="msgBadge" style="display:none;margin-left:auto;"></span>
    </div>
    <a href="demo.html" style="display:none;"></a>
    <div class="navItem" onclick="openModal('demoModal')" style="color:#c9993a;opacity:.85;cursor:pointer;">
      <span class="icon">🎮</span> Demo Mode
    </div>

    <div class="sidebarFooter">
      <div class="userChip" onclick="doLogout()">
        <div class="avatar" id="sidebarAvatar">A</div>
        <div>
          <div class="userName" id="sidebarName">Admin</div>
          <div class="userRole">Sign out →</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Content -->
  <div class="mainContent">

    <!-- ══ DASHBOARD ══ -->
    <div class="page active" id="page-dashboard">
      <div class="pgRow">
        <div class="pgTitle">Dashboard</div>
        <button class="btn btn-outline" onclick="showPage('invoices')">📄 Invoices &amp; Billing →</button>
      </div>

      <!-- Alert for pending actions -->
      <div class="alertBanner" id="alertBanner">
        <span style="font-size:20px;">⚠️</span>
        <div style="flex:1;" id="alertText"></div>
      </div>

      <!-- Stats -->
      <div class="statRow">
        <div class="sc"><div class="sc-label">Customers</div><div class="sc-val" id="s-cust">—</div></div>
        <div class="sc"><div class="sc-label">Overdue</div><div class="sc-val r" id="s-pend">—</div></div>
        <div class="sc"><div class="sc-label">Paid This Month</div><div class="sc-val g" id="s-paid">—</div></div>
        <div class="sc"><div class="sc-label">Unpaid</div><div class="sc-val" id="s-unp">—</div></div>
      </div>

      <!-- Revenue summary -->
      <div class="revRow" id="revRow">
        <div class="revItem"><div class="revItem-label">Total Billed</div><div class="revItem-val" id="rv-billed">—</div></div>
        <div class="revItem"><div class="revItem-label">Collected</div><div class="revItem-val g" id="rv-coll" style="color:var(--green);">—</div></div>
        <div class="revItem"><div class="revItem-label">Outstanding</div><div class="revItem-val" id="rv-out" style="color:var(--gold);">—</div></div>
      </div>

      <!-- Pending payment proofs — inline review -->
      <div id="dashPendingSection">
        <div id="dashPendingList"></div>
      </div>

      <div id="dashUnsentSection">
        <div class="sectionHd" id="unsentHd" style="display:none;"></div>
        <div id="dashUnsentList"></div>
      </div>
    </div>

    <!-- ══ CUSTOMERS ══ -->
    <div class="page" id="page-customers">
      <div class="pgRow">
        <div class="pgTitle">Customers</div>
        <button class="btn btn-gold" onclick="openAddCustomer()">+ Add Customer</button>
      </div>
      <div id="custList"></div>
    </div>

    <!-- ══ INVOICES & BILLING ══ -->
    <div class="page" id="page-invoices">
      <div class="pgRow">
        <div class="pgTitle">Invoices &amp; Billing</div>
      </div>

      <!-- Pending payment proofs — inline review -->
      <div class="sectionHd gold" id="pendingHd" style="display:none;"></div>
      <div id="dashPendingList" style="margin-bottom:8px;"></div>

      <!-- Due Soon / Action Needed -->
      <div id="dueSoonSection"></div>

      <!-- This Month -->
      <div id="thisMonthSection"></div>

      <!-- Previous Months -->
      <div id="prevMonthSection"></div>
    </div>

    <!-- ══ SETTINGS ══ -->
    <div class="page" id="page-settings">
      <div class="pgRow"><div class="pgTitle">Settings</div></div>
      <div style="max-width:460px;">
        <div class="card">
          <div class="cardHeader"><div class="cardTitle">💳 GCash Account</div></div>
          <div class="cardBody">
            <div class="field">
              <label>GCash Number</label>
              <input type="text" id="gcashNumber" placeholder="09XXXXXXXXX" maxlength="11"
                     oninput="document.getElementById('pvNum').textContent=this.value||'—'">
            </div>
            <div class="field">
              <label>Account Name</label>
              <input type="text" id="gcashName" placeholder="Juan dela Cruz"
                     oninput="document.getElementById('pvName').textContent=this.value||'—'">
            </div>
            <div id="gcashMsg" style="display:none;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:12px;"></div>
            <button class="btnPrimary" onclick="saveGcash()" style="background:linear-gradient(135deg,#0a4fff,#0070e0);">💾 Save GCash Settings</button>
          </div>
        </div>
        <div class="gcashPreview">
          <div style="font-size:10px;opacity:.5;letter-spacing:2px;font-weight:700;margin-bottom:6px;">PAY VIA GCASH TO</div>
          <div style="font-size:28px;font-weight:800;letter-spacing:2px;margin-bottom:4px;" id="pvNum">—</div>
          <div style="font-size:14px;font-weight:600;opacity:.8;" id="pvName">—</div>
        </div>


        <!-- Email Settings -->
        <div class="card" style="margin-top:20px;">
          <div class="cardHeader"><div class="cardTitle">📧 Email Settings (Gmail SMTP)</div></div>
          <div class="cardBody">
            <div style="background:#f0f7ff;border:1px solid #cce0ff;border-radius:8px;padding:10px 14px;font-size:12px;color:#004a99;margin-bottom:14px;">
              💡 Use a <strong>Gmail App Password</strong> — not your regular Gmail password.<br>
              Get it at: <strong>myaccount.google.com → Security → App Passwords</strong>
            </div>
            <div class="field">
              <label>Gmail Address</label>
              <input type="email" id="smtpUser" placeholder="yourname@gmail.com">
            </div>
            <div class="field">
              <label>From Name</label>
              <input type="text" id="smtpFromName" placeholder="Rural WiFi">
            </div>
            <div class="field">
              <label>Gmail App Password <span style="font-size:11px;color:var(--muted);">(16 chars, no spaces)</span></label>
              <input type="password" id="smtpPass" placeholder="Leave blank to keep existing" autocomplete="new-password">
            </div>
            <div id="emailMsg" style="display:none;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:12px;"></div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
              <button class="btnPrimary" onclick="saveEmail()" style="flex:1;min-width:140px;">💾 Save Email Settings</button>
              <button class="btn btn-outline" onclick="testEmail()" id="testEmailBtn" style="flex:1;min-width:140px;">🧪 Send Test Email</button>
            </div>
            <div id="emailStatus" style="margin-top:10px;font-size:12px;color:var(--muted);"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ MESSAGES ══ -->
    <div class="page" id="page-messages">
      <div class="pgRow"><div class="pgTitle">Messages</div></div>
      <div class="msgLayout">
        <!-- Conversation list -->
        <div class="convList" id="convList">
          <div class="noConvYet"><div style="font-size:28px;margin-bottom:8px;">💬</div><p>Loading…</p></div>
        </div>
        <!-- Chat panel -->
        <div class="chatPanel" id="chatPanel">
          <div class="noConvYet" id="noChatSelected">
            <div style="font-size:32px;margin-bottom:10px;">💬</div>
            <p style="font-size:14px;">Select a customer to view messages</p>
          </div>
          <div id="activeChatWrap" style="display:none;flex-direction:column;flex:1;">
            <div class="chatPanelHdr">
              <div class="convAvatar" id="chatAvatar">?</div>
              <div>
                <div style="font-weight:700;font-size:14px;" id="chatName">—</div>
                <div style="font-size:11px;color:var(--muted);" id="chatPlan">—</div>
              </div>
            </div>
            <div class="chatMsgs" id="chatMsgs"></div>
            <div class="chatInputRow">
              <input type="text" id="chatInput" placeholder="Type a message…" onkeydown="if(event.key==='Enter')sendAdminMsg()">
              <button class="btn btn-gold" onclick="sendAdminMsg()">📤 Send</button>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /mainContent -->
</div><!-- /appWrap -->

<!-- Bottom nav removed — sidebar only -->

<!-- ═══ MODALS ═══ -->

<!-- Add Customer -->
<div class="overlay" id="addCustModal">
  <div class="modal">
    <button class="closeBtn" onclick="closeModal('addCustModal')">&times;</button>
    <div class="modalTitle">Add New Customer</div>
    <div class="modalSub">Fill in details and assign a plan. First invoice is auto-generated.</div>
    <div class="formRow">
      <div class="field"><label>Full Name</label><input type="text" id="cName" placeholder="Juan dela Cruz"></div>
      <div class="field"><label>Username</label><input type="text" id="cUser" placeholder="juan123"></div>
    </div>
    <div class="formRow">
      <div class="field"><label>Password</label><input type="password" id="cPass" placeholder="Password"></div>
      <div class="field"><label>Phone</label><input type="text" id="cPhone" placeholder="09XX-XXX-XXXX"></div>
    </div>
    <div class="formRow">
      <div class="field"><label>Plan</label><select id="cPlan"><option value="">Loading…</option></select></div>
      <div class="field"><label>Billing Day</label>
        <select id="cBday">
          <option value="1">1st</option><option value="2">2nd</option><option value="3">3rd</option><option value="4">4th</option><option value="5">5th</option><option value="6">6th</option><option value="7">7th</option><option value="8">8th</option><option value="9">9th</option><option value="10">10th</option><option value="11">11th</option><option value="12">12th</option><option value="13">13th</option><option value="14">14th</option><option value="15">15th</option><option value="16">16th</option><option value="17">17th</option><option value="18">18th</option><option value="19">19th</option><option value="20">20th</option><option value="21">21st</option><option value="22">22nd</option><option value="23">23rd</option><option value="24">24th</option><option value="25">25th</option><option value="26">26th</option><option value="27">27th</option><option value="28">28th</option>
        </select>
      </div>
    </div>
    <div class="field"><label>Address</label><input type="text" id="cAddr" placeholder="Barangay, Municipality"></div>
    <div class="field"><label>Email Address <span style="font-size:11px;color:var(--muted);">(for billing notifications)</span></label><input type="email" id="cEmail" placeholder="customer@gmail.com"></div>
    <button class="btnPrimary" onclick="addCustomer()" style="margin-top:8px;">✅ Add Customer</button>
  </div>
</div>

<!-- Edit Plan -->
<div class="overlay" id="editPlanModal">
  <div class="modal" style="max-width:420px;">
    <button class="closeBtn" onclick="closeModal('editPlanModal')">&times;</button>
    <div class="modalTitle">Edit Plan</div>
    <div class="modalSub" id="editPlanName">—</div>
    <div class="formRow">
      <div class="field"><label>Plan</label><select id="ePlan"><option value="">Loading…</option></select></div>
      <div class="field"><label>Billing Day</label>
        <select id="eBday">
          <option value="1">1st</option><option value="2">2nd</option><option value="3">3rd</option><option value="4">4th</option><option value="5">5th</option><option value="6">6th</option><option value="7">7th</option><option value="8">8th</option><option value="9">9th</option><option value="10">10th</option><option value="11">11th</option><option value="12">12th</option><option value="13">13th</option><option value="14">14th</option><option value="15">15th</option><option value="16">16th</option><option value="17">17th</option><option value="18">18th</option><option value="19">19th</option><option value="20">20th</option><option value="21">21st</option><option value="22">22nd</option><option value="23">23rd</option><option value="24">24th</option><option value="25">25th</option><option value="26">26th</option><option value="27">27th</option><option value="28">28th</option>
        </select>
      </div>
    </div>
    <button class="btnPrimary" onclick="saveEditPlan()">💾 Save</button>
  </div>
</div>

<!-- Edit Due Date -->
<div class="overlay" id="editDueDateModal">
  <div class="modal" style="max-width:360px;">
    <button class="closeBtn" onclick="closeModal('editDueDateModal')">&times;</button>
    <div class="modalTitle">Edit Due Date</div>
    <div class="field" style="margin-top:12px;"><label>New Due Date</label><input type="date" id="newDueDate"></div>
    <button class="btnPrimary" onclick="saveDueDate()">💾 Save</button>
  </div>
</div>

<!-- Cash Payment Modal -->
<div class="overlay" id="cashPayModal">
  <div class="modal" style="max-width:400px;">
    <button class="closeBtn" onclick="closeModal('cashPayModal')">&times;</button>
    <div class="modalTitle">💵 Mark as Paid (Cash)</div>
    <div class="modalSub" id="cash-invInfo">—</div>
    <div style="background:var(--cream);border-radius:12px;padding:14px 16px;margin-bottom:16px;">
      <div style="font-size:13px;color:var(--muted);margin-bottom:4px;">Customer</div>
      <div style="font-weight:700;font-size:16px;" id="cash-custName">—</div>
      <div style="font-size:13px;color:var(--muted);margin-top:8px;margin-bottom:4px;">Amount</div>
      <div style="font-family:'DM Serif Display',serif;font-size:26px;" id="cash-amount">—</div>
    </div>
    <div class="field">
      <label>Note (optional)</label>
      <input type="text" id="cash-note" placeholder="e.g. Paid in person, Mar 10" maxlength="100">
    </div>
    <div style="display:flex;gap:8px;margin-top:4px;">
      <button class="btn btn-outline" style="flex:1;" onclick="closeModal('cashPayModal')">Cancel</button>
      <button class="btnPrimary" style="flex:2;background:linear-gradient(135deg,#1a7a3c,#22a84e);" onclick="confirmCashPay()">✅ Confirm Cash Payment</button>
    </div>
  </div>
</div>

<!-- ══ DEMO MODE MODAL ══ -->
<div class="overlay" id="demoModal">
  <div class="modal" style="max-width:500px;">
    <button class="closeBtn" onclick="closeModal('demoModal')">&times;</button>
    <div class="modalTitle">🎮 Demo Mode</div>
    <div class="modalSub">Generate invoices for any month, delete test data, or reset customers.</div>

    <!-- Stats strip -->
    <div style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap;" id="demoStats">
      <div style="flex:1;min-width:80px;background:var(--cream);border-radius:10px;padding:10px 12px;text-align:center;">
        <div style="font-size:20px;font-weight:800;color:var(--ink);" id="dst-inv">—</div>
        <div style="font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">Invoices</div>
      </div>
      <div style="flex:1;min-width:80px;background:var(--cream);border-radius:10px;padding:10px 12px;text-align:center;">
        <div style="font-size:20px;font-weight:800;color:var(--ink);" id="dst-pay">—</div>
        <div style="font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">Pending Proofs</div>
      </div>
      <div style="flex:1;min-width:80px;background:var(--cream);border-radius:10px;padding:10px 12px;text-align:center;">
        <div style="font-size:20px;font-weight:800;color:var(--ink);" id="dst-notif">—</div>
        <div style="font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">Unpaid Bills</div>
      </div>
      <div style="flex:1;min-width:80px;background:var(--cream);border-radius:10px;padding:10px 12px;text-align:center;">
        <div style="font-size:20px;font-weight:800;color:var(--ink);" id="dst-susp">—</div>
        <div style="font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">Suspended</div>
      </div>
    </div>

    <!-- Generate for month -->
    <div style="background:var(--cream);border-radius:12px;padding:14px 16px;margin-bottom:12px;">
      <div style="font-size:12px;font-weight:700;margin-bottom:10px;">⚡ Generate Invoices for a Month</div>
      <div style="display:flex;gap:8px;align-items:center;">
        <select id="demo-genMonth" style="flex:1;padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;background:#fff;"></select>
        <button class="btn btn-gold btn-sm" onclick="demoGenerate(this)">Generate</button>
      </div>
    </div>

    <!-- Delete invoices -->
    <div style="background:var(--cream);border-radius:12px;padding:14px 16px;margin-bottom:12px;">
      <div style="font-size:12px;font-weight:700;margin-bottom:10px;">🗑 Delete Invoices</div>
      <div style="display:flex;gap:8px;align-items:center;">
        <select id="demo-delMonth" style="flex:1;padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;background:#fff;">
          <option value="">— Delete ALL invoices —</option>
        </select>
        <button class="btn btn-red btn-sm" onclick="demoDelete(this)">Delete</button>
      </div>
    </div>

    <!-- Reset customers -->
    <div style="background:var(--cream);border-radius:12px;padding:14px 16px;margin-bottom:16px;">
      <div style="font-size:12px;font-weight:700;margin-bottom:6px;">✅ Reset Customer Status</div>
      <div style="font-size:12px;color:var(--muted);margin-bottom:10px;">I-reactivate ang lahat ng suspended customers.</div>
      <button class="btn btn-outline btn-sm" style="color:var(--green);border-color:var(--green);" onclick="demoResetCustomers(this)">✅ Reactivate All Customers</button>
    </div>

    <!-- Invoice list -->
    <div style="font-size:12px;font-weight:700;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center;">
      <span>📋 Current Invoices</span>
      <button class="btn btn-outline btn-sm" onclick="demoLoadInvoices()">🔄 Refresh</button>
    </div>
    <div id="demoInvList" style="max-height:200px;overflow-y:auto;"></div>
  </div>
</div>

<script src="api.js"></script>
<script>
// Helper: get secure proof image URL
function proofUrl(path) {
  const filename = (path || '').replace('uploads/proofs/', '');
  return 'api/serve_proof.php?file=' + encodeURIComponent(filename);
}

// Helper: ordinal suffix (1 → "st", 2 → "nd", etc.)
function ord(n) {
  const s = ['th','st','nd','rd'], v = n % 100;
  return s[(v-20)%10] || s[v] || s[0];
}

// Helper: status badge HTML for invoices
function badgeHtml(i) {
  if (i.status === 'paid')    return `<span class="badge paid">Paid</span>`;
  if (i.status === 'overdue') return `<span class="badge overdue">Overdue</span>`;
  return `<span class="badge unpaid">Unpaid</span>`;
}

// ── STATE ──
let currentUser   = null;
let plans         = [];
let editCustId    = null;
let editInvId     = null;

// ── INIT ──
window.addEventListener('load', async () => {
  currentUser = await checkSession('admin');
  if (!currentUser) return;
  document.getElementById('sidebarAvatar').textContent = currentUser.name[0].toUpperCase();
  document.getElementById('sidebarName').textContent   = currentUser.name;
  const pr = await api(API.plans);
  if (pr.success) plans = pr.data;
  // Auto-suspend overdue customers silently on every admin load
  // Auto-suspend removed — manual lang ang suspend
  // Auto-generate + send invoices on billing_day, daily reminders for unpaid
  fetch('api/cron_reminders.php?key=ruralwifi_cron', {headers:{'ngrok-skip-browser-warning':'1'}})
    .then(r=>r.json()).then(res => {
      if (res.generated > 0)
        showToast(`📬 ${res.generated} invoice(s) generated & sent!`);
      if (res.reminded > 0)
        showToast(`🔔 ${res.reminded} payment reminder(s) sent.`);
      if (res.errors && res.errors.length > 0)
        console.warn('[AutoBilling] Errors:', res.errors);
    }).catch(()=>{});
  showPage('dashboard');
  // Polling — reliable fallback for all data
  Realtime.start('badge', pollBadge, 8000);
  Realtime.start('msgbadge', pollMsgBadge, 5000);
  setInterval(() => Realtime.invalidate('dash-data'), 8000);

  // Start SSE AFTER a short delay so dashboard loads first
  // SSE is bonus realtime — polling handles reliability
  setTimeout(() => {
    try {
      // Register SSE handlers
      Realtime.onSSE('pending_proofs', ({count}) => {
        const b = document.getElementById('pendingBadge');
        if (b) { b.textContent = count; b.style.display = count > 0 ? 'inline' : 'none'; }
        if (count > 0) { Realtime.invalidate('dash-data'); renderDashboard(); }
      });
      Realtime.onSSE('unread_messages', ({count}) => {
        const mb = document.getElementById('msgBadge');
        if (mb) { mb.textContent = count; mb.style.display = count > 0 ? 'inline' : 'none'; }
      });
      Realtime.onSSE('invoice_counts', () => {
        Realtime.invalidate('dash-data');
        Realtime.invalidate('invs');
        if (document.getElementById('page-dashboard')?.classList.contains('active')) renderDashboard();
        if (document.getElementById('page-invoices')?.classList.contains('active')) renderInvoices();
      });
      Realtime.onSSE('notifications', () => {
        Realtime.invalidate('dash-data');
        if (document.getElementById('page-dashboard')?.classList.contains('active')) renderDashboard();
      });
      Realtime.connectSSE();
    } catch(e) {
      console.warn('[SSE] Failed to connect, using polling only:', e);
    }
  }, 2000); // wait 2s for dashboard to load first
});

// ── NAV ──
function showPage(name) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  ['dashboard','customers','invoices','settings','messages'].forEach(n => {
    const el = document.getElementById('nav-'+n);
    if (el) el.classList.remove('active');
  });
  const pg = document.getElementById('page-'+name);
  if (pg) pg.classList.add('active');
  const ni = document.getElementById('nav-'+name);
  if (ni) ni.classList.add('active');
  if (window.innerWidth < 768) closeSidebar();
  Realtime.stopExcept('badge','msgbadge');
  const pollers = {
    dashboard: () => Realtime.start('dash', renderDashboard, 10000),
    customers: () => { Realtime.invalidate('custs-data'); Realtime.start('custs', renderCustomers, 20000); },
    invoices:  () => { Realtime.invalidate('invs-data');  Realtime.start('invs',  renderInvoices,  15000); },
    settings:  () => loadGcash(),
    messages:  () => {
      loadConvList();
      Realtime.start('convlist', () => loadConvList(), 5000);
      Realtime.start('msgs', pollMessages, 4000);
    },
  };
  if (pollers[name]) pollers[name]();
}

// ── BADGE (payments) ──
async function pollBadge() {
  const pr = await api(API.payments);
  const n  = (pr.data||[]).filter(p=>p.proof_status==='pending').length;
  const b  = document.getElementById('pendingBadge');
  if (!b) return;
  const mb = document.getElementById('mbnBadge');
  if (b)  { b.textContent=n;  b.style.display=n>0?'inline':'none'; }
  if (mb) { mb.textContent=n; mb.style.display=n>0?'inline':'none'; }
}

// ── MESSAGES ──
let activeCustId   = null;
let activeCustName = '';
let convData       = [];

async function loadConvList() {
  const res = await api(API.messages);
  convData = res.data || [];
  const el = document.getElementById('convList');
  if (!convData.length) {
    el.innerHTML = '<div class="noConvYet"><div style="font-size:24px;margin-bottom:6px;">💬</div><p style="font-size:13px;color:var(--muted);">No messages yet.</p></div>';
    return;
  }
  el.innerHTML = convData.map(c => `
    <div class="convItem ${activeCustId===c.customer_id?'active':''}" onclick="openConv(${c.customer_id},'${c.full_name.replace(/'/g,"\\'")}','${(c.plan_name||'').replace(/'/g,"\\'")}')">
      <div class="convAvatar" style="background:${c.status==='suspended'?'#c94040':'var(--gold)'}">${c.full_name[0].toUpperCase()}</div>
      <div style="flex:1;min-width:0;">
        <div class="convName">${c.full_name}${c.status==='suspended'?' <span style="font-size:10px;color:#c94040;">(susp)</span>':''}</div>
        <div class="convPreview" style="color:${c.last_message?'inherit':'var(--muted)'}">${c.last_message||'No messages yet'}</div>
      </div>
      ${c.unread_count>0?`<span class="convUnread">${c.unread_count}</span>`:''}
    </div>`).join('');
}

async function openConv(custId, name, plan) {
  activeCustId   = custId;
  activeCustName = name;
  document.getElementById('noChatSelected').style.display   = 'none';
  const wrap = document.getElementById('activeChatWrap');
  wrap.style.display    = 'flex';
  document.getElementById('chatAvatar').textContent = name[0].toUpperCase();
  document.getElementById('chatName').textContent   = name;
  document.getElementById('chatPlan').textContent   = plan || '';
  // Highlight active conv
  document.querySelectorAll('.convItem').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.convItem').forEach(el => {
    if (el.textContent.trim().startsWith(name[0])) {/* handled by re-render */}
  });
  await loadConvList(); // re-render to update active highlight
  await loadMessages();
  document.getElementById('chatInput').focus();
}

async function loadMessages() {
  if (!activeCustId) return;
  const res = await api(API.messages+'?customer_id='+activeCustId);
  const msgs = res.data || [];
  // Mark as read
  await api(API.messages, {action:'mark_read', customer_id:activeCustId});
  // Update badge
  await pollMsgBadge();
  const el = document.getElementById('chatMsgs');
  if (!msgs.length) {
    el.innerHTML = '<div style="text-align:center;color:var(--muted);font-size:13px;padding:20px;">No messages yet. Start the conversation!</div>';
    return;
  }
  el.innerHTML = msgs.map(m => {
    const isSent = m.sender_type === 'admin';
    const time   = new Date(m.sent_at||m.created_at).toLocaleString('en-PH',{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'});
    return `<div class="msg ${isSent?'sent':'recv'}">
      ${m.message_text}
      <div class="msgTime">${time}</div>
    </div>`;
  }).join('');
  el.scrollTop = el.scrollHeight;
}

async function sendAdminMsg() {
  const input = document.getElementById('chatInput');
  const txt   = input.value.trim();
  if (!txt || !activeCustId) return;
  input.value = '';
  const res = await api(API.messages, {action:'send', customer_id:activeCustId, message_text:txt});
  if (res.success) loadMessages();
  else { input.value = txt; showToast('❌ Send failed: ' + (res.message||'Error')); }
}

async function pollMessages() {
  await pollMsgBadge();
  if (activeCustId) loadMessages();
  // Refresh conv list preview + unread counts
  const convRes = await api(API.messages);
  const newData = JSON.stringify(convRes.data||[]);
  if (pollMessages._lastConv !== newData) {
    pollMessages._lastConv = newData;
    convData = convRes.data || [];
    // Re-render conv list without losing active selection
    const el = document.getElementById('convList');
    if (el && convData.length) {
      el.innerHTML = convData.map(c => `
        <div class="convItem ${activeCustId===c.customer_id?'active':''}" onclick="openConv(${c.customer_id},'${c.full_name.replace(/'/g,"\\'")}','${(c.plan_name||'').replace(/'/g,"\\'")}')">
          <div class="convAvatar" style="background:${c.status==='suspended'?'#c94040':'var(--gold)'};">${c.full_name[0].toUpperCase()}</div>
          <div style="flex:1;min-width:0;">
            <div class="convName">${c.full_name}${c.status==='suspended'?' <span style="font-size:10px;color:#c94040;">(susp)</span>':''}</div>
            <div class="convPreview" style="color:${c.last_message?'inherit':'var(--muted)'};">${c.last_message||'No messages yet'}</div>
          </div>
          ${c.unread_count>0?`<span class="convUnread">${c.unread_count}</span>`:''}
        </div>`).join('');
    }
  }
}

async function pollMsgBadge() {
  const res = await api(API.messages, {action:'unread_count'});
  const total = res.data?.count || 0;
  ['msgBadge','mbnMsgBadge'].forEach(id => {
    const el = document.getElementById(id);
    if (el) { el.textContent=total; el.style.display=total>0?'inline':'none'; }
  });
  // Toast if new message and not on messages page
  if (total > 0 && !document.getElementById('page-messages')?.classList.contains('active')) {
    if (pollMsgBadge._last !== total) {
      pollMsgBadge._last = total;
      showToast('💬 New message from customer!');
    }
  } else {
    pollMsgBadge._last = total;
  }
}

// ── DASHBOARD ──
async function renderDashboard() {
  await Realtime.ifChanged('dash-data',
    () => Promise.all([api(API.customers), api(API.invoices), api(API.payments)]),
    ([cR, iR, pR]) => {
      if (!cR.success || !iR.success || !pR.success) {
        console.warn('[Dashboard] API error:', cR.message, iR.message);
        return; // Will retry on next interval
      }
      const custs   = cR.data || [];
      const invs    = iR.data || [];
      const proofs  = pR.data || [];
      const pending = proofs.filter(p => p.proof_status === 'pending');
      const paid    = invs.filter(i => i.status === 'paid');
      const unpaid  = invs.filter(i => i.status === 'unpaid');

      document.getElementById('s-cust').textContent = custs.length;
      document.getElementById('s-pend').textContent = pending.length;
      document.getElementById('s-paid').textContent = paid.length;
      document.getElementById('s-unp').textContent  = unpaid.length;

      const billed = invs.reduce((s,i) => s+parseFloat(i.amount||0), 0);
      const coll   = paid.reduce((s,i) => s+parseFloat(i.amount||0), 0);
      document.getElementById('rv-billed').textContent = '₱'+billed.toLocaleString();
      document.getElementById('rv-coll').textContent   = '₱'+coll.toLocaleString();
      document.getElementById('rv-out').textContent    = '₱'+(billed-coll).toLocaleString();

      // Alert banner
      const today      = new Date(); today.setHours(0,0,0,0);
      const unsent     = unpaid.filter(i => i.sent_to_customer!=1 && Math.ceil((new Date(i.due_date)-today)/86400000)<=2 && Math.ceil((new Date(i.due_date)-today)/86400000)>=0);
      const suspended  = custs.filter(c => c.status === 'suspended');
      const banner     = document.getElementById('alertBanner');
      const alertTxt   = document.getElementById('alertText');
      const parts      = [];
      if (pending.length)   parts.push(`<strong>${pending.length} payment proof(s)</strong> waiting for your review below`);
      if (suspended.length) parts.push(`<strong>${suspended.length} account(s) suspended</strong> — overdue`);
      if (parts.length) {
        alertTxt.innerHTML = parts.join(' &nbsp;·&nbsp; ');
        banner.classList.add('show');
      } else {
        banner.classList.remove('show');
      }

      // Pending proofs section
      const pendHd   = document.getElementById('pendingHd');
      const pendList = document.getElementById('dashPendingList');
      if (pending.length) {
        pendHd.style.display = 'block';
        pendHd.textContent   = `⏳ Pending Payment Verification — ${pending.length}`;
        pendList.innerHTML   = pending.map(p => `
          <div class="proofCard pend" id="pc-${p.proof_id}">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;">
              <div>
                <div style="font-weight:700;font-size:15px;">${p.customer_name}</div>
                <div class="proofMeta">${p.billing_month} &nbsp;·&nbsp; ₱${parseFloat(p.monthly_fee||p.amount||0).toLocaleString()} &nbsp;·&nbsp; Due: ${p.due_date}</div>
                <div class="proofRef">GCash Ref: <strong>${p.gcash_ref}</strong></div>
              </div>
              <div style="font-family:'DM Serif Display',serif;font-size:22px;">₱${parseFloat(p.monthly_fee||p.amount||0).toLocaleString()}</div>
            </div>
            ${p.proof_image
              ? `<div style="margin-top:10px;"><img src="${proofUrl(p.proof_image)}" style="max-width:100%;max-height:200px;border-radius:8px;border:2px solid var(--blue);cursor:pointer;" onclick="window.open('${proofUrl(p.proof_image)}','_blank')"></div>`
              : '<div style="font-size:12px;color:var(--muted);margin-top:8px;">⚠️ No screenshot uploaded.</div>'}
            <div class="proofActions" id="pa-${p.proof_id}" style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">
              <button class="btn btn-green" onclick="reviewProof(${p.proof_id},'accepted',this)">✅ Accept</button>
              <button class="btn btn-red"   onclick="openRejectBox(${p.proof_id})">❌ Reject</button>
            </div>
            <div id="rb-${p.proof_id}" style="display:none;margin-top:10px;">
              <input type="text" id="rr-${p.proof_id}" placeholder="Reason for rejection (e.g. Wrong ref, Blurry screenshot...)"
                style="width:100%;padding:9px 12px;border:1.5px solid var(--red);border-radius:8px;font-size:13px;outline:none;margin-bottom:8px;box-sizing:border-box;">
              <div style="display:flex;gap:8px;">
                <button class="btn btn-outline btn-sm" style="flex:1;" onclick="closeRejectBox(${p.proof_id})">Cancel</button>
                <button class="btn btn-red btn-sm" style="flex:2;" onclick="confirmReject(${p.proof_id})">❌ Confirm Reject</button>
              </div>
            </div>
          </div>`).join('');
      } else {
        pendHd.style.display = 'none';
        pendList.innerHTML   = '';
      }

      // Unsent invoices due soon
      const unsentHd   = document.getElementById('unsentHd');
      const unsentList = document.getElementById('dashUnsentList');
      if (unsent.length) {
        unsentHd.style.display = 'block';
        unsentHd.textContent   = `🔔 Unsent Invoices Due Soon — ${unsent.length}`;
        unsentList.innerHTML   = unsent.map(i => {
          const dl = Math.ceil((new Date(i.due_date)-today)/86400000);
          return `
          <div class="proofCard" style="border-left:4px solid ${dl===0?'var(--red)':'var(--gold)'};">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
              <div>
                <div style="font-weight:700;font-size:15px;">${i.customer_name}</div>
                <div class="proofMeta">₱${parseFloat(i.monthly_fee||i.amount||0).toLocaleString()} &nbsp;·&nbsp; Due: ${i.due_date} &nbsp;<strong style="color:var(--red);">${dl===0?'TODAY':dl===1?'Tomorrow':'In 2 days'}</strong></div>
              </div>
              <button class="btn btn-gold" onclick="sendOne(${i.invoice_id},this)">📤 Send</button>
            </div>
          </div>`}).join('');
      } else {
        unsentHd.style.display = 'none';
        unsentList.innerHTML   = '';
      }
    }
  );
}

function openRejectBox(proofId) {
  document.getElementById('rb-'+proofId).style.display = 'block';
  document.getElementById('rr-'+proofId).focus();
}
function closeRejectBox(proofId) {
  document.getElementById('rb-'+proofId).style.display = 'none';
  document.getElementById('rr-'+proofId).value = '';
}
async function confirmReject(proofId) {
  const reason = document.getElementById('rr-'+proofId).value.trim();
  if (!reason) {
    document.getElementById('rr-'+proofId).style.borderColor = 'var(--red)';
    document.getElementById('rr-'+proofId).placeholder = '⚠️ Enter a reason before rejecting.';
    document.getElementById('rr-'+proofId).focus(); return;
  }
  await reviewProof(proofId, 'rejected', null, reason);
}
async function reviewProof(proofId, decision, btn, reason = '') {
  const pa = document.getElementById('pa-'+proofId);
  if (pa) pa.innerHTML = decision === 'accepted'
    ? `<div style="background:#dcfce7;color:#166534;border-radius:8px;padding:9px 14px;font-size:13px;font-weight:600;">✅ Accepted — receipt sent to customer</div>`
    : `<div style="background:#fee2e2;color:#991b1b;border-radius:8px;padding:9px 14px;font-size:13px;font-weight:600;">❌ Rejected — customer notified</div>`;
  const rb = document.getElementById('rb-'+proofId);
  if (rb) rb.style.display = 'none';
  const res = await api(API.payments, {action:'review', proof_id:proofId, decision, reason});
  if (!res.success) { showToast('❌ '+res.message); renderDashboard(); return; }
  showToast(decision === 'accepted' ? '✅ Payment accepted!' : '❌ Payment rejected.');
  setTimeout(() => renderDashboard(), 800);
}

async function sendOne(invoiceId, btn) {
  btn.textContent='⏳…'; btn.disabled=true;
  const res = await api(API.invoices, {action:'send', invoice_id:invoiceId});
  if (res.success) renderDashboard();
  else { alert('Error: '+res.message); btn.textContent='📤 Send'; btn.disabled=false; }
}

async function genAndSendAll(btn) {
  btn.textContent='⏳ Generating…'; btn.disabled=true;
  const gRes = await api(API.invoices, {action:'generate'});
  btn.textContent='⚡ Generate Invoices'; btn.disabled=false;
  const count = gRes.data?.generated || 0;
  if (count > 0) {
    showToast(`✅ Generated ${count} invoice(s). Review them in Invoices before sending.`);
  } else {
    showToast(gRes.message || 'All invoices already exist for this month.');
  }
  renderDashboard();
}

// ── CUSTOMERS ──
async function renderCustomers() {
  await Realtime.ifChanged('custs-data',
    () => api(API.customers),
    (res) => {
      const el   = document.getElementById('custList');
      const list = res.data || [];
      if (!list.length) { el.innerHTML='<div class="empty"><div class="emptyIcon">👥</div><p>No customers yet.</p></div>'; return; }
      el.innerHTML = list.map(c => `
        <div class="custCard">
          <div class="custCard-row">
            <div style="flex:1;min-width:160px;">
              <div style="font-weight:700;font-size:15px;">${c.full_name}</div>
              <div style="font-size:12px;color:var(--muted);margin-top:2px;">${c.username} &nbsp;·&nbsp; ${c.address||'—'}</div>
              <div style="display:flex;align-items:center;gap:8px;margin-top:6px;flex-wrap:wrap;">
                <span class="planChip">${c.plan_name}</span>
                <span style="font-size:13px;font-weight:600;">₱${parseFloat(c.monthly_fee).toLocaleString()}/mo</span>
                <span style="font-size:11px;color:var(--muted);">Bill: ${c.billing_day}${ord(c.billing_day)}</span>
                <span class="badge ${c.status}">${c.status}</span>
              </div>
            </div>
            <div class="cRow-actions" style="flex-shrink:0;">
              <button class="btn btn-outline btn-sm" onclick="openEditPlan(${c.customer_id},'${c.full_name.replace(/'/g,"\\'")}',${c.plan_id},${c.billing_day})">✏️ Edit Plan</button>
              <button class="btn ${c.status==='active'?'btn-red':'btn-green'} btn-sm" onclick="toggleStatus(${c.customer_id})">${c.status==='active'?'🚫 Suspend':'✅ Activate'}</button>
            </div>
          </div>
        </div>`).join('');
    }
  );
}

function openAddCustomer() {
  document.getElementById('cPlan').innerHTML = plans.map(p=>`<option value="${p.plan_id}">${p.plan_name} — ₱${parseFloat(p.monthly_fee).toLocaleString()}/mo</option>`).join('');
  ['cName','cUser','cPass','cPhone','cAddr','cEmail'].forEach(id=>document.getElementById(id).value='');
  openModal('addCustModal');
}
async function addCustomer() {
  const name=document.getElementById('cName').value.trim(), uname=document.getElementById('cUser').value.trim(),
        pass=document.getElementById('cPass').value, phone=document.getElementById('cPhone').value.trim(),
        addr=document.getElementById('cAddr').value.trim(), email=document.getElementById('cEmail').value.trim(),
        planId=document.getElementById('cPlan').value, bday=document.getElementById('cBday').value;
  if (!name||!uname||!pass||!planId) { alert('Name, username, password and plan are required.'); return; }
  const res = await api(API.customers,{action:'add',full_name:name,username:uname,password:pass,phone,address:addr,email,plan_id:parseInt(planId),billing_day:parseInt(bday)});
  if (!res.success) { alert('Error: '+res.message); return; }
  closeModal('addCustModal'); showToast('✅ Customer added!'); renderCustomers();
}
function openEditPlan(id, name, planId, bday) {
  editCustId = id;
  document.getElementById('editPlanName').textContent = 'Editing: '+name;
  document.getElementById('ePlan').innerHTML = plans.map(p=>`<option value="${p.plan_id}" ${p.plan_id==planId?'selected':''}>${p.plan_name} — ₱${parseFloat(p.monthly_fee).toLocaleString()}/mo</option>`).join('');
  document.getElementById('eBday').value = bday;
  openModal('editPlanModal');
}
async function saveEditPlan() {
  const res = await api(API.customers,{action:'edit_plan',customer_id:editCustId,plan_id:parseInt(document.getElementById('ePlan').value),billing_day:parseInt(document.getElementById('eBday').value)});
  if (!res.success) { alert('Error: '+res.message); return; }
  closeModal('editPlanModal'); showToast('✅ Plan updated!'); renderCustomers();
}
async function toggleStatus(id) {
  const res = await api(API.customers,{action:'toggle_status',customer_id:id});
  if (res.success) { showToast('✅ Status updated'); renderCustomers(); }
  else alert('Error: '+res.message);
}

// ── INVOICES ──
let cashInvId   = null;

async function renderInvoices() {

  const [iRes, cRes] = await Promise.all([api(API.invoices), api(API.customers)]);
  const list       = iRes.data || [];
  const custs      = cRes.data || [];
  const today      = new Date(); today.setHours(0,0,0,0);
  const monthLabel = new Date().toLocaleString('en-US', {month:'long', year:'numeric'});

  // Due Soon section removed — cron handles invoice generation on billing_day
  document.getElementById('dueSoonSection').innerHTML = '';

  const thisMonth = list.filter(i => i.billing_month === monthLabel);
  const prevMonth = list.filter(i => i.billing_month !== monthLabel);

  document.getElementById('thisMonthSection').innerHTML = renderInvTable(thisMonth, today, `📅 ${monthLabel}`, true);
  document.getElementById('prevMonthSection').innerHTML = prevMonth.length
    ? renderInvTable(prevMonth, today, '🗂 Previous Months', false)
    : '';
}

function renderInvTable(list, today, title, isThisMonth) {
  const unpaidCount = list.filter(i=>i.status==='unpaid').length;
  const paidCount   = list.filter(i=>i.status==='paid').length;

  if (!list.length) return `
    <div class="card" style="margin-bottom:12px;">
      <div class="cardHeader"><div class="cardTitle">${title}</div></div>
      <div class="cardBody"><div class="empty"><div class="emptyIcon">📄</div>
        <p>${isThisMonth ? 'No invoices yet — will appear & send automatically on each customer\'s billing day.' : 'No previous invoices.'}</p>
      </div></div>
    </div>`;

  const rows = list.map(i => {
    const due = new Date(i.due_date); due.setHours(0,0,0,0);
    const dl  = Math.ceil((due - today) / 86400000);
    const billingDayThisMonth = new Date(today.getFullYear(), today.getMonth(), parseInt(i.billing_day||1));
    const payUnlocked = today >= billingDayThisMonth;
    const method = i.payment_method === 'cash' ? '💵 Cash' : '📱 GCash';
    let sentCol, actionsCol;
    const delBtn = `<button class="btn btn-red btn-sm" onclick="deleteInvoice(${i.invoice_id},'${i.customer_name.replace(/'/g,"\\'")}','${i.billing_month}')" title="Delete Invoice">🗑 Delete</button>`;
    if (i.status === 'paid') {
      sentCol    = `<span style="color:var(--green);font-size:12px;">✅ Sent</span>`;
      actionsCol = `<span style="font-size:12px;color:var(--green);">${method}</span>`;
    } else if (i.sent_to_customer == 1) {
      sentCol    = `<span style="color:var(--green);font-size:12px;">✅ Sent</span>`;
      actionsCol = `
        <button class="btn btn-outline btn-sm" onclick="openEditDD(${i.invoice_id},'${i.due_date}')">📅 Edit Date</button>
        <button class="btn btn-green btn-sm" onclick="openCashPay(${i.invoice_id},'${i.customer_name.replace(/'/g,"\\'")}',${(i.monthly_fee||i.amount||0)},'${i.billing_month}')">💵 Cash</button>
        ${delBtn}`;
    } else {
      // Invoice generated — cron will auto-send on billing_day
      sentCol    = `<span style="font-size:12px;color:var(--muted);">⏳ Sends on ${i.due_date}</span>`;
      actionsCol = `
        <button class="btn btn-outline btn-sm" onclick="openEditDD(${i.invoice_id},'${i.due_date}')">📅 Edit Date</button>
        ${delBtn}`;
    }
    return `<tr>
      <td><strong>${i.customer_name}</strong></td>
      <td>${i.billing_month}</td>
      <td><strong>₱${parseFloat(i.monthly_fee||i.amount||0).toLocaleString()}</strong></td>
      <td>${i.due_date}${dl>=0&&dl<=3&&i.status==='unpaid'?`<br><small style="color:var(--red);font-weight:600;">${dl===0?'TODAY':dl+'d left'}</small>`:''}</td>
      <td>${badgeHtml(i)}</td>
      <td>${sentCol}</td>
      <td style="white-space:nowrap;">${actionsCol}</td>
    </tr>`;
  }).join('');

  const mCards = list.map(i => {
    const due = new Date(i.due_date); due.setHours(0,0,0,0);
    const dl  = Math.ceil((due - today) / 86400000);
    const method = i.payment_method === 'cash' ? '💵 Cash' : '📱 GCash';
    return `
    <div class="m-card" style="border-left:4px solid ${i.status==='paid'?'var(--green)':dl<=0?'var(--red)':'var(--gold)'};"> 
      <div class="m-card-row">
        <div><div class="m-card-title">${i.customer_name}</div><div class="m-card-sub">${i.billing_month} · Due: ${i.due_date}</div></div>
        <div style="text-align:right;"><div class="m-card-amount">₱${parseFloat(i.monthly_fee||i.amount||0).toLocaleString()}</div>${badgeHtml(i)}</div>
      </div>
      <div class="m-card-actions">
        ${i.status==='paid'
          ? `<span style="font-size:12px;color:var(--green);">${method}</span>`
          : i.sent_to_customer==1
            ? `<span style="font-size:12px;color:var(--green);">✅ Sent</span>
               <button class="btn btn-outline btn-sm" onclick="openEditDD(${i.invoice_id},'${i.due_date}')">📅 Edit</button>
               <button class="btn btn-green btn-sm" onclick="openCashPay(${i.invoice_id},'${i.customer_name.replace(/'/g,"\\'")}',${(i.monthly_fee||i.amount||0)},'${i.billing_month}')">💵 Cash</button>
               <button class="btn btn-red btn-sm" onclick="deleteInvoice(${i.invoice_id},'${i.customer_name.replace(/'/g,"\\'")}','${i.billing_month}')">🗑 Delete</button>`
            : `<span style="font-size:12px;color:var(--muted);">⏳ Sends on ${i.due_date}</span>
               <button class="btn btn-outline btn-sm" onclick="openEditDD(${i.invoice_id},'${i.due_date}')">📅 Edit</button>
               <button class="btn btn-red btn-sm" onclick="deleteInvoice(${i.invoice_id},'${i.customer_name.replace(/'/g,"\\'")}','${i.billing_month}')">🗑 Delete</button>`
            }
      </div>
    </div>`;
  }).join('');

  return `
  <div class="card" style="margin-bottom:12px;">
    <div class="cardHeader" style="justify-content:space-between;">
      <div class="cardTitle">${title}</div>
      <div style="font-size:12px;color:var(--muted);">${paidCount} paid · ${unpaidCount} unpaid</div>
    </div>
    <div class="cardBody" style="padding:0;">
      <div class="tableWrap">
        <table>
          <thead><tr><th>Customer</th><th>Month</th><th>Amount</th><th>Due Date</th><th>Status</th><th>Sent?</th><th>Actions</th></tr></thead>
          <tbody>${rows}</tbody>
        </table>
      </div>
      <div class="mobile-cards" style="padding:12px;">${mCards}</div>
    </div>
  </div>`;
}

async function deleteInvoice(invId, name, month) {
  if (!confirm(`Delete invoice for ${name} — ${month}?\nHindi na ito maibabalik.`)) return;
  const res = await api(API.invoices, {action:'delete', invoice_id: invId});
  if (res.success) {
    showToast(`🗑 Invoice deleted — ${name} ${month}`);
    Realtime.invalidateAll();
    renderInvoices();
  } else {
    showToast('❌ ' + (res.message || 'Failed to delete.'), 'error');
  }
}

// generateForCustomer removed — cron handles this automatically

// sendNow removed — auto-send on billing_day via cron

function openCashPay(invId, custName, amount, month) {
  cashInvId = invId;
  document.getElementById('cash-custName').textContent = custName;
  document.getElementById('cash-amount').textContent   = '₱' + parseFloat(amount).toLocaleString();
  document.getElementById('cash-invInfo').textContent  = month;
  document.getElementById('cash-note').value           = '';
  openModal('cashPayModal');
}

async function confirmCashPay() {
  const btn  = document.querySelector('#cashPayModal .btnPrimary');
  const note = document.getElementById('cash-note').value.trim() || 'Cash payment';
  btn.textContent='⏳ Saving…'; btn.disabled=true;
  const res = await api(API.invoices, {action:'mark_paid_cash', invoice_id:cashInvId, note});
  btn.textContent='✅ Confirm Cash Payment'; btn.disabled=false;
  if (!res.success) { showToast('❌ '+(res.message||'Failed')); return; }
  closeModal('cashPayModal');
  showToast('✅ Marked as paid (Cash)!');
  renderInvoices();
}


function openEditDD(id, date) { editInvId=id; document.getElementById('newDueDate').value=date; openModal('editDueDateModal'); }
async function saveDueDate() {
  const date = document.getElementById('newDueDate').value;
  if (!date) { alert('Pick a date.'); return; }
  const res = await api(API.invoices,{action:'edit_due_date',invoice_id:editInvId,due_date:date});
  if (!res.success) { alert('Error: '+res.message); return; }
  closeModal('editDueDateModal'); showToast('✅ Due date updated'); renderInvoices();
}

// ── GCASH ──
async function loadGcash() {
  const res = await api(API.settings); const s = res.data||{};
  document.getElementById('gcashNumber').value   = s.gcash_number||'';
  document.getElementById('gcashName').value     = s.gcash_name||'';
  document.getElementById('pvNum').textContent   = s.gcash_number||'—';
  document.getElementById('pvName').textContent  = s.gcash_name||'—';
  // Load email settings too
  document.getElementById('smtpUser').value      = s.smtp_user||'';
  document.getElementById('smtpFromName').value  = s.smtp_from_name||'Rural WiFi';
  document.getElementById('smtpPass').placeholder = s.smtp_pass_set ? 'App password saved ✅ (leave blank to keep)' : 'Enter Gmail App Password';
  if (s.smtp_pass_set) document.getElementById('emailStatus').textContent = '📧 Email is configured and ready.';
  document.getElementById('appUrlInput').value = s.app_url || '';
}
async function saveEmail() {
  const user = document.getElementById('smtpUser').value.trim();
  const name = document.getElementById('smtpFromName').value.trim();
  const pass = document.getElementById('smtpPass').value.trim();
  const msg  = document.getElementById('emailMsg');
  if (!user) { msg.style.cssText='display:block;background:#fff0f0;color:var(--red);'; msg.textContent='Gmail address required.'; return; }
  const res = await api(API.settings, {action:'save_email', smtp_user:user, smtp_from_name:name, smtp_pass:pass});
  msg.style.display='block';
  if (res.success) { msg.style.cssText='display:block;background:#f0fff4;color:var(--green);'; msg.textContent='✅ Email settings saved!'; document.getElementById('emailStatus').textContent='📧 Email is configured and ready.'; }
  else { msg.style.cssText='display:block;background:#fff0f0;color:var(--red);'; msg.textContent='❌ '+(res.message||'Failed.'); }
  setTimeout(()=>msg.style.display='none', 3500);
}
async function testEmail() {
  const btn = document.getElementById('testEmailBtn');
  btn.textContent = 'Sending…'; btn.disabled = true;
  const res = await api(API.settings, {action:'test_email'});
  btn.textContent = '🧪 Send Test Email'; btn.disabled = false;
  const msg = document.getElementById('emailMsg');
  msg.style.display = 'block';
  if (res.success) { msg.style.cssText='display:block;background:#f0fff4;color:var(--green);'; msg.textContent='✅ '+(res.message||'Test email sent!'); }
  else { msg.style.cssText='display:block;background:#fff0f0;color:var(--red);'; msg.textContent='❌ '+(res.message||'Failed.'); }
  setTimeout(()=>msg.style.display='none', 5000);
}
async function saveAppUrl() {
  const url = document.getElementById('appUrlInput').value.trim();
  const msg = document.getElementById('appUrlMsg');
  if (!url) { msg.style.cssText='display:block;background:#fff0f0;color:var(--red);'; msg.textContent='Ilagay ang ngrok URL.'; setTimeout(()=>msg.style.display='none',3000); return; }
  if (!url.startsWith('http')) { msg.style.cssText='display:block;background:#fff0f0;color:var(--red);'; msg.textContent='Dapat magsimula sa https://'; setTimeout(()=>msg.style.display='none',3000); return; }
  const res = await api(API.settings, {action:'save_app_url', app_url:url});
  msg.style.display='block';
  if (res.success) { msg.style.cssText='display:block;background:#f0fff4;color:var(--green);'; msg.textContent='✅ Portal URL saved! Lahat ng email links ay updated na.'; }
  else { msg.style.cssText='display:block;background:#fff0f0;color:var(--red);'; msg.textContent='❌ '+(res.message||'Failed.'); }
  setTimeout(()=>msg.style.display='none', 4000);
}
async function saveGcash() {
  const num=document.getElementById('gcashNumber').value.trim(), name=document.getElementById('gcashName').value.trim();
  const msg=document.getElementById('gcashMsg');
  if (!num||!name) { msg.style.display='block';msg.style.background='#fff0f0';msg.style.color='var(--red)';msg.textContent='Fill in both fields.';return; }
  const res = await api(API.settings,{action:'save_gcash',gcash_number:num,gcash_name:name});
  msg.style.display='block';
  if (res.success) { msg.style.background='#f0fff4';msg.style.color='var(--green)';msg.textContent='✅ Saved!'; }
  else { msg.style.background='#fff0f0';msg.style.color='var(--red)';msg.textContent='❌ '+(res.message||'Failed.'); }
  setTimeout(()=>msg.style.display='none',3000);
}

// ── TOAST ──
function showToast(msg) {
  let t = document.getElementById('toast');
  if (!t) { t=document.createElement('div'); t.id='toast'; t.className='toast'; document.body.appendChild(t); }
  t.textContent = msg; t.classList.add('show');
  setTimeout(()=>t.classList.remove('show'), 2600);
}

// ── SIDEBAR ──
function toggleSidebar() {
  const sb=document.querySelector('.sidebar'), ov=document.getElementById('sidebarOverlay');
  const open=sb.classList.toggle('open');
  ov.classList.toggle('show',open);
  document.body.style.overflow=open?'hidden':'';
}
function closeSidebar() {
  document.querySelector('.sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('show');
  document.body.style.overflow='';
}


// ── DEMO MODE ──
const DEMO_API = 'api/demo.php?key=demo2024';

async function demoCall(body) {
  const res = await fetch(DEMO_API, {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(body)});
  return res.json();
}

function demoPopulateMonths() {
  const months = [];
  const now = new Date();
  for (let i = -1; i <= 5; i++) {
    const d = new Date(now.getFullYear(), now.getMonth() + i, 1);
    months.push(d.toLocaleString('en-US', {month:'long', year:'numeric'}));
  }
  const genSel = document.getElementById('demo-genMonth');
  if (genSel) genSel.innerHTML = months.map((m,i) => `<option value="${m}" ${i===1?'selected':''}>${m}</option>`).join('');
}

async function demoLoadStats() {
  const [iRes, cRes, pRes, nRes] = await Promise.all([
    api(API.invoices),
    api(API.customers),
    api(API.payments),
    api(API.notifs)
  ]);
  const invs  = iRes.data  || [];
  const custs = cRes.data  || [];
  const pays  = pRes.data  || [];
  const notifs = nRes.data?.notifications || [];
  document.getElementById('dst-inv').textContent   = invs.length;
  document.getElementById('dst-pay').textContent   = pays.filter(p=>p.proof_status==='accepted').length;
  document.getElementById('dst-notif').textContent = invs.filter(i=>i.status==='unpaid').length;
  document.getElementById('dst-susp').textContent  = custs.filter(c=>c.status==='suspended').length;

  // Populate delete month options from actual invoices
  const months = [...new Set(invs.map(i=>i.billing_month))];
  const delSel = document.getElementById('demo-delMonth');
  if (delSel) {
    delSel.innerHTML = '<option value="">— Delete ALL invoices —</option>' +
      months.map(m=>`<option value="${m}">${m}</option>`).join('');
  }
}

async function demoLoadInvoices() {
  const res  = await demoCall({action:'list_invoices'});
  const el   = document.getElementById('demoInvList');
  const list = res.data || [];
  if (!list.length) {
    el.innerHTML = '<div style="text-align:center;color:var(--muted);padding:16px;font-size:13px;">No invoices.</div>';
    return;
  }
  el.innerHTML = list.map(i => `
    <div style="display:flex;align-items:center;gap:8px;padding:8px 10px;border:1px solid var(--border);border-radius:8px;margin-bottom:5px;background:#fff;">
      <div style="flex:1;min-width:0;">
        <div style="font-size:13px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${i.customer_name}</div>
        <div style="font-size:11px;color:var(--muted);">${i.billing_month} · ₱${parseFloat(i.monthly_fee||i.amount||0).toLocaleString()} · ${i.status}</div>
      </div>
      <button class="btn btn-outline btn-sm" style="color:var(--red);border-color:rgba(220,50,50,.3);flex-shrink:0;"
        onclick="demoDeleteOne(${i.invoice_id},'${i.customer_name.replace(/'/g,"\\'")}')">🗑</button>
    </div>`).join('');
}

async function demoGenerate(btn) {
  const month = document.getElementById('demo-genMonth').value;
  btn.textContent='⏳…'; btn.disabled=true;
  const res = await demoCall({action:'generate_for_month', month});
  btn.textContent='Generate'; btn.disabled=false;
  showToast(res.success ? `✅ ${res.message}` : `❌ ${res.message}`);
  if (res.success) { demoLoadStats(); demoLoadInvoices(); renderInvoices(); }
}

async function demoDelete(btn) {
  const month = document.getElementById('demo-delMonth').value;
  const label = month || 'ALL invoices';
  if (!confirm(`Delete ${label}? This also removes payment proofs and notifications.`)) return;
  btn.textContent='⏳…'; btn.disabled=true;
  const res = await demoCall({action:'delete_all_invoices', month});
  btn.textContent='Delete'; btn.disabled=false;
  showToast(res.success ? `✅ ${res.message}` : `❌ ${res.message}`);
  if (res.success) { demoLoadStats(); demoLoadInvoices(); renderInvoices(); renderDashboard(); }
}

async function demoDeleteOne(id, name) {
  if (!confirm(`Delete invoice of ${name}?`)) return;
  const res = await demoCall({action:'delete_invoice', invoice_id: id});
  showToast(res.success ? `✅ Deleted` : `❌ ${res.message}`);
  if (res.success) { demoLoadStats(); demoLoadInvoices(); renderInvoices(); renderDashboard(); }
}

async function demoResetCustomers(btn) {
  if (!confirm('Reactivate all suspended customers?')) return;
  btn.textContent='⏳…'; btn.disabled=true;
  const res = await demoCall({action:'reset_customers'});
  btn.textContent='✅ Reactivate All Customers'; btn.disabled=false;
  showToast(res.success ? '✅ All customers reactivated!' : `❌ ${res.message}`);
  if (res.success) { demoLoadStats(); renderCustomers(); }
}

// ── MODALS ──
function openModal(id) {
  document.getElementById(id).classList.add('show');
  if (id === 'demoModal') { demoPopulateMonths(); demoLoadStats(); demoLoadInvoices(); }
}
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
document.querySelectorAll('.overlay').forEach(o => o.addEventListener('click', e => { if (e.target === o) o.classList.remove('show'); }));
</script>
</body>
</html>
