        </main> <!-- closes col-lg-9 -->
    </div> <!-- closes row -->
</div> <!-- closes container -->

<footer class="site-footer py-4 mt-auto">
    <div class="container">
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between">

            <!-- vänster sida, logga och copyright -->
            <div class="d-flex flex-column flex-md-row align-items-center mb-3 mb-md-0">
                <a href="index.php">
                    <img src="../public/images/QuackerLogo.svg" alt="Quacker Logo" class="header-img">
                </a>
                
                <p class="m-0">&copy; <?= date('Y') ?> Quacker. All rights reserved.</p>
            </div>

            <!-- Höger sida, länkar -->
            <nav class="footer-links d-flex flex-column flex-md-row align-items-center gap-3 gap-md-4">
                <a href="#">About us</a>
                <a href="#">Terms of Service</a>
                <a href="#">Privacy Policy</a>
            </nav>
        </div>
    </div>
</footer>
<!-- Delete Quack Confirmation Modal -->
<div class="modal fade" id="deleteQuackModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Delete Quack?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-muted">
                This can’t be undone and it will be removed from your profile, the timeline of any accounts that follow you, and from search results.
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmDeleteBtn" class="btn btn-danger rounded-pill px-4 fw-bold">Delete</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>