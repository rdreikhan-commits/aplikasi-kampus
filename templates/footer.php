<?php
/**
 * File: footer.php
 * Deskripsi: Bagian bawah template HTML dengan JavaScript untuk sidebar interaktif.
 */
?>
 <div class="card-footer text-center text-secondary small">
            &copy; <?php echo date('Y'); ?>  <span ><?php echo $nama_aplikasi; ?></span> Institut Teknologi Garut
        </div>
        </div> <!-- Menutup .main-content-inner -->
    </div> <!-- Menutup .content-wrapper -->
</div> <!-- Menutup .page-wrapper -->

<!-- Bootstrap 5 JS Bundle (termasuk Popper.js) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- JavaScript Kustom untuk Toggle Sidebar -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const sidebar = document.getElementById('sidebar');
        const contentWrapper = document.getElementById('content-wrapper');
        const toggler = document.getElementById('sidebar-toggler');
        const backdrop = document.getElementById('sidebar-backdrop');
        const isMobile = () => window.innerWidth <= 992;

        const toggleSidebar = () => {
            if (isMobile()) {
                sidebar.classList.toggle('toggled');
            } else {
                sidebar.classList.toggle('collapsed');
                contentWrapper.classList.toggle('collapsed');
            }
        };

        if (toggler) {
            toggler.addEventListener('click', toggleSidebar);
        }

        if (backdrop) {
            backdrop.addEventListener('click', () => {
                if (isMobile()) {
                    sidebar.classList.remove('toggled');
                }
            });
        }
        
        // Atur state awal saat load
        if (!isMobile()) {
            sidebar.classList.remove('collapsed');
            contentWrapper.classList.remove('collapsed');
        } else {
            // Di mobile, pastikan class collapsed ada untuk state awal
            sidebar.classList.add('collapsed'); 
            contentWrapper.classList.add('collapsed');
        }
    });
</script>

</body>
</html>

