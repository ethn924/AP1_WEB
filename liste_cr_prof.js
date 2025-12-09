// JavaScript pour la page liste_cr_prof.php
document.addEventListener('DOMContentLoaded', function () {
    // Confirmation pour "Marquer comme non vu"
    const nonVuButtons = document.querySelectorAll('.non-vu-btn');
    nonVuButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            if (!confirm('Êtes-vous sûr de vouloir marquer ce compte rendu comme non vu ?')) {
                e.preventDefault();
            }
        });
    });
});