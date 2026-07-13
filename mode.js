// Toggle dark mode (guarded for pages without the switch)
(() => {
    const toggleCheckbox = document.getElementById('toggle-checkbox');
    if (!toggleCheckbox) return;

    const applyTheme = (theme) => {
        document.documentElement.setAttribute('data-theme', theme);
    };

    toggleCheckbox.addEventListener('change', () => {
        applyTheme(toggleCheckbox.checked ? 'dark' : 'light');
        localStorage.setItem('theme', toggleCheckbox.checked ? 'dark' : 'light');
    });

    // Load saved mode or fall back to system preference
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        toggleCheckbox.checked = savedTheme === 'dark';
        applyTheme(savedTheme);
        return;
    }

    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        toggleCheckbox.checked = true;
    }
    applyTheme(toggleCheckbox.checked ? 'dark' : 'light');
})();