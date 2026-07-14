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

// alle anderen toggle-switches (z.B. Passwortschutz) auf der Seite

document.getElementById('password_protect').addEventListener('change', function() {
    document.getElementById('password_field').style.display = this.checked ? 'block' : 'none';
    if (this.checked){
        document.getElementById('password').focus();
    }
});
document.getElementById('expiration').addEventListener('change', function() {
    document.getElementById('expiration_field').style.display = this.checked ? 'block' : 'none';
    if (this.checked){
        document.getElementById('expires_at').focus();
    }
});
document.getElementById('max_clicks').addEventListener('change', function() {
    document.getElementById('max_clicks_field').style.display = this.checked ? 'block' : 'none';
    if (this.checked){
        document.getElementById('max_clicks_value').focus();
    }
});
document.getElementById('custom_hash_toggle').addEventListener('change', function() {
    document.getElementById('custom_hash_field').style.display = this.checked ? 'block' : 'none';
    if (this.checked){
        document.getElementById('custom_hash_value').focus();
    }
});