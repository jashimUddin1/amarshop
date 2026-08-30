</main> <!-- Main Content Container End -->
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Theme Toggle Logic
    const html = document.documentElement;
    const btn = document.getElementById("themeToggle");

    function applyTheme(theme) {
      html.setAttribute("data-bs-theme", theme);
      if (btn) {
        btn.innerHTML = theme === "dark" ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon"></i>';
      }
    }

    const savedTheme = localStorage.getItem("amarshop_admin_theme");
    if (savedTheme) {
      applyTheme(savedTheme);
    }

    if (btn) {
      btn.addEventListener("click", () => {
        const currentTheme = html.getAttribute("data-bs-theme") === "dark" ? "light" : "dark";
        applyTheme(currentTheme);
        localStorage.setItem("amarshop_admin_theme", currentTheme);
      });
    }

    // Auto-Banish Timer for Alerts
    function initAutoBanish() {
      const autoAlert = document.querySelector('.auto-banish-alert');
      if (autoAlert) {
        setTimeout(() => {
          const bsAlert = new bootstrap.Alert(autoAlert);
          bsAlert.close();
        }, 10000);
      }
    }
    initAutoBanish();

    // Reusable AJAX Form Submission Function
    function handleAjaxSubmit(event, url, modalId) {
      event.preventDefault();
      const form = event.target;
      const submitBtn = form.querySelector('.submit-btn');
      const originalBtnText = submitBtn.innerHTML;

      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Processing...';

      const formData = new FormData(form);

      fetch(url, {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          const modalElement = document.querySelector(modalId);
          const modalInstance = bootstrap.Modal.getInstance(modalElement);
          if (modalInstance) {
            modalInstance.hide();
          }

          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnText;

          if (data.status === 'success') {
            window.location.reload();
          } else {
            alert(data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnText;
          alert('An error occurred. Please try again.');
        });
    }
  </script>
</body>

</html>