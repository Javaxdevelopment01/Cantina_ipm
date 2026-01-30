<?php
// Shared pure CSS for vendor/admin login screens
?>
<style>
    :root{
        --primary-dark: #012E40;
        --primary: #013a63;
        --accent: #D4AF37;
        --muted: #7a8699;
        --panel-bg: #ffffff;
        --page-bg-start: #013a63;
        --page-bg-mid: #012E40;
        --page-bg-end: #020b1f;
        --text-main: #012E40;
    }

    *{ box-sizing: border-box; }

    body {
        min-height: 100vh;
        margin: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: radial-gradient(circle at top left, var(--page-bg-start) 0, var(--page-bg-mid) 40%, var(--page-bg-end) 100%);
        font-family: 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, 'Helvetica Neue', Arial, sans-serif;
        color: var(--text-main);
        -webkit-font-smoothing:antialiased;
        -moz-osx-font-smoothing:grayscale;
        padding: 1rem;
    }

    .auth-wrapper { width:100%; max-width:1080px; padding:1.25rem; }

    .auth-card{ border-radius:18px; overflow:hidden; box-shadow:0 18px 45px rgba(0,0,0,0.45); background:var(--panel-bg); border:none; display:flex; }

    .row{ display:flex; flex-wrap:wrap; gap:0; }
    .g-0 > *{ padding-right:0; padding-left:0; }
    .col-md-6{ flex:1 1 50%; max-width:50%; }
    .col-12{ flex:1 1 100%; max-width:100%; }

    /* Left info panel */
    .auth-illustration{
        background: linear-gradient(135deg, rgba(1,58,99,0.12), rgba(212,175,55,0.08));
        padding:3rem 2.5rem;
        display:flex; flex-direction:column; justify-content:center; position:relative; gap:1rem;
        color:var(--panel-bg);
        color:var(--primary-dark);
        min-height: 360px;
    }
    .auth-illustration .accent-graphic{
        position:absolute; right:-60px; top:-40px; width:260px; height:260px; border-radius:50%; background: linear-gradient(180deg, rgba(212,175,55,0.18), rgba(1,58,99,0.06)); filter: blur(18px); z-index:0; pointer-events:none;
    }
    .auth-illustration .brand-mark{ z-index:1; background: radial-gradient(circle at 30% 20%, #ffeaa7, var(--accent)); color:var(--primary-dark); }

    .auth-illustration h1{ margin:0; font-size:1.6rem; color:var(--primary-dark); }
    .auth-illustration p{ margin:0; color:var(--muted); }

    .features-list{ margin-top:1rem; display:flex; flex-direction:column; gap:0.6rem; z-index:1; }
    .feature-item{ display:flex; gap:0.75rem; align-items:flex-start; }
    .feature-icon{ width:36px; height:36px; border-radius:8px; background:linear-gradient(180deg, #fff, rgba(255,255,255,0.9)); display:flex; align-items:center; justify-content:center; box-shadow:0 8px 18px rgba(0,0,0,0.08); color:var(--primary-dark); font-weight:700; }
    .feature-text{ font-size:0.95rem; color:#344055; }

    /* Right form panel */
    .auth-form-container{ padding:3rem 2.25rem; width:100%; }
    .auth-form-header{ margin-bottom:1.25rem; }
    .auth-form-title{ font-size:1.35rem; font-weight:700; color:var(--primary-dark); margin-bottom:0.25rem; }
    .auth-form-subtitle{ font-size:0.92rem; color:var(--muted); }

    label{ font-size:0.9rem; font-weight:600; color:#374151; margin-bottom:0.35rem; display:block; }

    .form-control{ border-radius:10px; border:1px solid #e2e8f0; padding:0.72rem 0.9rem; font-size:0.95rem; box-shadow:none; width:100%; background:#fbfdff; }
    .form-control:focus{ border-color:var(--accent); box-shadow:0 8px 24px rgba(20,30,40,0.06); background-color:#fff; outline:none; }

    .row-form{ display:flex; gap:0.75rem; }

    .btn-dourado{ background: linear-gradient(135deg, var(--accent), #f3d26a); color:var(--primary-dark); font-weight:700; border:none; width:100%; padding:0.85rem 1rem; border-radius:12px; font-size:1rem; letter-spacing:0.02em; box-shadow:0 14px 30px rgba(2,12,24,0.12); cursor:pointer; }
    .btn-dourado:hover{ transform:translateY(-2px); box-shadow:0 20px 40px rgba(2,12,24,0.16); }

    .auth-footer-text{ font-size:0.82rem; color:#9aa3b2; margin-top:1.25rem; text-align:left; }
    .auth-footer-text strong{ color:#6b7280; }

    .alert{ border-radius:10px; font-size:0.9rem; padding:0.6rem 0.9rem; display:flex; align-items:center; gap:0.5rem; margin-bottom:1rem; }
    .alert-danger{ background-color:#fff1f0; color:#7a1e1e; border:1px solid #ffd1d1; }

    /* small utility */
    .text-md-start{ text-align:center; }
    .text-center{ text-align:center; }
    .fw-semibold{ font-weight:700; }

    @media (max-width:991.98px){
        .auth-card{ flex-direction:column; }
        .auth-illustration{ order:0; padding:1.5rem 1.25rem; min-height: auto; }
        .auth-form-container{ order:1; padding:1.5rem 1.25rem; }
    }

    @media (max-width:767.98px){
        body{ align-items:flex-start; }
        .auth-wrapper{ padding-top:2.5rem; padding-bottom:2.5rem; }
        .auth-illustration{ display:block; }
        .auth-form-container{ padding:2rem 1.75rem 1.75rem; }
        .col-md-6{ max-width:100%; }
        .auth-card{ box-shadow:0 12px 32px rgba(0,0,0,0.45); }
    }

    @media (min-width:768px){
        .text-md-start{ text-align:start; }
        .d-md-flex{ display:flex; }
    }
</style>
