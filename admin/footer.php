<footer class="main-footer mt-auto py-3">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                <span class="text-muted">© 2025 ITDS Equipment Monitoring System</span>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <span class="text-muted">Version 1.0.0</span>
            </div>
        </div>
    </div>
</footer>

<style>
/* Footer Styles */


/* Fixed footer styles */
.main-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background-color: #1e3a8a; /* Dark blue */
    color: #fff;
    padding: 10px 0;
    font-size: 14px;
    z-index: 1000;
    box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
}

/* Make sure body content doesn’t overlap footer */
body {
    padding-bottom: 60px; /* same as footer height */
}

/* Responsive text alignment */
@media (max-width: 768px) {
    .main-footer .row {
        text-align: center;
    }
    .main-footer .col-md-6 {
        margin-bottom: 5px;
    }
}
.main-footer {
    background-color: #1150a8;   /* Dark blue background */
    color: #ffffff;             /* White text */
    font-size: 14px;
    box-shadow: 0 -2px 6px rgba(0, 0, 0, 0.1); /* subtle shadow at top */
    width: 100%;
}

.main-footer .text-muted {
    color: #dcdcdc !important;  /* lighter gray text */
}

.main-footer a {
    color: #ffffff;
    text-decoration: none;
    transition: color 0.2s ease-in-out;
}

.main-footer a:hover {
    color: #ffd700;  /* gold on hover */
}

/* Responsive tweaks */
@media (max-width: 768px) {
    .main-footer {
        text-align: center;
        font-size: 13px;
        padding: 15px 10px;
    }
    .main-footer .row {
        flex-direction: column;
    }
}
</style>
