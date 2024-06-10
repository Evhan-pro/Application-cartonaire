function applyDarkModePreference() {
    const isDarkMode = localStorage.getItem('darkMode') === 'true';
    document.body.classList.toggle('dark-mode', isDarkMode);
    const darkModeSwitch = document.getElementById('darkModeSwitch');
    if (darkModeSwitch) darkModeSwitch.checked = isDarkMode;
  }
  
  document.addEventListener('DOMContentLoaded', applyDarkModePreference);
  