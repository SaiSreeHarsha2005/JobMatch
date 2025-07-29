document.addEventListener('DOMContentLoaded', () => {
  const registerForm = document.querySelector('form[action="register.php"]');
  if (registerForm) {
    registerForm.addEventListener('submit', (e) => {
      const password = document.getElementById('password').value;
      if (password.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long.');
      }
    });
  }
});
