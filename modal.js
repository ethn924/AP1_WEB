let pendingAction = null;

function showModal(title, message, type = 'confirmation', callback = null) {
    const modal = document.getElementById('custom-modal');
    if (!modal) return false;

    modal.querySelector('h2').textContent = title;
    modal.querySelector('p').textContent = message;
    
    const confirmBtn = modal.querySelector('.custom-btn-confirm');
    const cancelBtn = modal.querySelector('.custom-btn-cancel');
    
    confirmBtn.onclick = function() {
        modal.style.display = 'none';
        if (callback && typeof callback === 'function') {
            callback(true);
        }
        return false;
    };
    
    cancelBtn.onclick = function() {
        modal.style.display = 'none';
        if (callback && typeof callback === 'function') {
            callback(false);
        }
        return false;
    };
    
    modal.style.display = 'flex';
    return false;
}

function confirmDelete(title, message = 'Êtes-vous sûr de vouloir supprimer cet élément ?', callback = null) {
    return showModal(title, message, 'error', callback);
}

function confirmAction(title, message = 'Êtes-vous sûr de vouloir continuer ?', callback = null) {
    return showModal(title, message, 'confirmation', callback);
}

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('custom-modal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    }
});
