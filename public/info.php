<?php
$pageTitle = "Information - Quacker";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5">
<h1 class="visually-hidden">Quacker - Information</h1>

    <div class="row justify-content-center">
        <div class="col-lg-8 custom-sidebar-card p-4 shadow-sm bg-light">
            <h2 class="fw-bold mb-4 text-dark">Information</h2>

            <!-- Tab-navigering -->
            <ul class="nav nav-tabs mb-4" id="infoTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold text-success" id="about-tab" data-bs-toggle="tab" data-bs-target="#about" type="button" role="tab">About Us</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-success" id="terms-tab" data-bs-toggle="tab" data-bs-target="#terms" type="button" role="tab">Terms</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-success" id="privacy-tab" data-bs-toggle="tab" data-bs-target="#privacy" type="button" role="tab">Privacy & GDPR</button>
                </li>
            </ul>

            <!-- Tab-innehåll -->
            <div class="tab-content text-dark" id="infoTabsContent">
                <!-- About Us -->
                <div class="tab-pane fade show active" id="about" role="tabpanel">
                    <h3 class="h4">Welcome to Quacker</h3>
                    <p>Quacker is a social media platform created to simulate real-world web applications. Our mission is to provide a safe and fast environment for users to share their thoughts and connect with others.</p>
                    <p class="text-muted small mt-4">
                        This platform was developed by David Norberg as a final project for 
                        <strong>Web Development 2</strong> and <strong>Web Server Programming 1</strong>.
                    </p>
                </div>


                <!-- Terms -->
                <div class="tab-pane fade" id="terms" role="tabpanel">
                    <h3 class="h4">Terms of Service</h3>
                    <p>By using Quacker, you agree to be nice to other ducks. No hate speech or "bad quacking" is allowed.</p>
                </div>

                <!-- Privacy & GDPR (Viktigt för A-betyget!) -->
                <div class="tab-pane fade" id="privacy" role="tabpanel">
                <h3 class="h4">Privacy Policy & GDPR</h3>
                    <p>We take your privacy seriously. To comply with GDPR regulations, we provide the following information:</p>
                    <ul>
                        <li><strong>Data Storage:</strong> We store information you provide, including your email, username, display name, profile biography, and profile images.</li>
                        <li><strong>Password Security:</strong> All passwords are hashed using <strong>bcrypt</strong>. We never store passwords in plain text.</li>
                        <li><strong>Right to be Forgotten:</strong> You have the full right to be forgotten. When you delete your account, our system automatically removes all your personal data, including your "Quacks", comments, likes, and profile information from our database.</li>
                        <li><strong>Usage:</strong> Your data is only used to provide the Quacker service and is not shared with third parties.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
