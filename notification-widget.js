function toggleNotifDropdown(event) {
    event.stopPropagation();
    const dropdown = document.getElementById('notif-dropdown');
    if (dropdown) {
        dropdown.classList.toggle('active');
    }
}

document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('notif-dropdown');
    const bellBtn = event.target.closest('.notif-bell-btn');
    
    if (!bellBtn && dropdown && dropdown.classList.contains('active')) {
        dropdown.classList.remove('active');
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const dropdown = document.getElementById('notif-dropdown');
        if (dropdown && dropdown.classList.contains('active')) {
            dropdown.classList.remove('active');
        }
    }
});
