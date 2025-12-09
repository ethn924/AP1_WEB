<?php
session_start();
include '_conf.php';
include 'fonctions.php';

if (!isset($_SESSION['Sid'])) {
    header("Location: index.php");
    exit();
}

$type = $_SESSION['Stype'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📚 Tutoriel</title>
    <link rel="stylesheet" href="common.css">
    <link rel="stylesheet" href="global-header.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .tutorial-section {
            background: white;
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.08);
            border-left: 5px solid #667eea;
        }
        
        .tutorial-section:hover {
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.15);
            transform: translateY(-2px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .tutorial-section h3 {
            border-bottom: 2px solid #667eea;
            padding-bottom: 7px;
            margin: 0 0 10px 0;
            color: #333;
            font-size: 1.05em;
            font-weight: 700;
        }
        
        .tutorial-section p {
            margin: 6px 0;
            color: #555;
            line-height: 1.5;
            font-size: 0.95em;
        }
        
        .tutorial-section ol, .tutorial-section ul {
            margin: 6px 0;
            padding-left: 20px;
            font-size: 0.95em;
            color: #555;
            line-height: 1.6;
        }
        
        .tutorial-section li {
            margin: 4px 0;
        }
    </style>
</head>
<body>
    <?php afficherHeaderNavigation(); ?>
    
    <?php afficherHeaderPage('📚', 'Tutoriel', 'Guide complet d\'utilisation de la plateforme'); ?>
    
    <div class="container">
        <?php if ($type == 0): ?>
            <!-- TUTORIEL ÉTUDIANT -->
            
            <div class="tutorial-section">
                <h3>🏠 Accueil</h3>
                <p>Votre page d'accueil vous donne un accès rapide à toutes les fonctionnalités disponibles :</p>
                <ul>
                    <li><strong>Créer un CR</strong> : Rédiger un nouveau compte rendu</li>
                    <li><strong>Mes CRs</strong> : Consulter tous vos comptes rendus</li>
                    <li><strong>Exporter</strong> : Télécharger vos CRs en PDF, Word ou Excel</li>
                    <li><strong>Rechercher</strong> : Effectuer une recherche avancée dans vos CRs</li>
                    <li><strong>Mon Stage</strong> : Gérer vos informations de stage et tuteur</li>
                    <li><strong>Mon Profil</strong> : Modifier vos paramètres personnels</li>
                </ul>
            </div>
            
            <div class="tutorial-section">
                <h3>✏️ Créer un Compte Rendu</h3>
                <ol>
                    <li>Cliquez sur <strong>"Créer un CR"</strong> ou allez directement dans <strong>"Mes CRs"</strong></li>
                    <li>Remplissez les champs obligatoires :
                        <ul>
                            <li><strong>Date</strong> : Date du compte rendu</li>
                            <li><strong>Titre</strong> : Titre descriptif (ex: "Journée de formation")</li>
                            <li><strong>Contenu</strong> : Description détaillée de votre travail</li>
                        </ul>
                    </li>
                    <li>Optionnel : Ajoutez une <strong>description courte</strong> et des <strong>pièces jointes</strong></li>
                    <li>Cliquez sur <strong>"Créer"</strong> pour sauvegarder</li>
                </ol>
                <div class="info-box">
                    💡 <strong>Conseil :</strong> Remplissez vos CRs régulièrement pour ne rien oublier
                </div>
            </div>
            
            <div class="tutorial-section">
                <h3>📋 Consulter Vos Comptes Rendus</h3>
                <ol>
                    <li>Allez dans <strong>"Mes CRs"</strong></li>
                    <li>La liste affiche tous vos comptes rendus avec :
                        <ul>
                            <li>Titre et date de création</li>
                            <li>Aperçu du contenu</li>
                            <li>Statut (✅ Consulté ou ⏳ Non consulté)</li>
                        </ul>
                    </li>
                    <li>Actions disponibles :
                        <ul>
                            <li><strong>📄 Détails</strong> : Voir le CR complet avec commentaires</li>
                            <li><strong>✏️ Modifier</strong> : Éditer le compte rendu</li>
                            <li><strong>🗑️ Supprimer</strong> : Supprimer le CR (confirmation requise)</li>
                            <li><strong>📥 Exporter PDF</strong> : Télécharger le CR en PDF</li>
                        </ul>
                    </li>
                </ol>
            </div>
            
            <div class="tutorial-section">
                <h3>📥 Exporter un Compte Rendu en PDF</h3>
                <ol>
                    <li>Dans <strong>"Mes CRs"</strong>, trouvez le CR à exporter</li>
                    <li>Cliquez sur le bouton <strong>"📥 Exporter PDF"</strong> dans la colonne Actions</li>
                    <li>Le fichier PDF se télécharge automatiquement</li>
                </ol>
                <div class="success-box">
                    ✅ <strong>Le PDF contient :</strong> Titre, date, contenu complet et pièces jointes
                </div>
            </div>
            
            <div class="tutorial-section">
                <h3>🏢 Gérer Mon Stage</h3>
                <ol>
                    <li>Cliquez sur <strong>"Mon Stage"</strong></li>
                    <li>Remplissez ou modifiez les informations :
                        <ul>
                            <li>Nom de l'entreprise</li>
                            <li>Adresse et contact</li>
                            <li>Informations du tuteur</li>
                        </ul>
                    </li>
                    <li>Cliquez sur <strong>"Enregistrer"</strong></li>
                </ol>
            </div>
            
            <div class="tutorial-section">
                <h3>⚙️ Modifier Mon Profil</h3>
                <p>Dans <strong>"Mon Profil"</strong>, vous pouvez :</p>
                <ul>
                    <li>Modifier votre login</li>
                    <li>Modifier votre email</li>
                    <li>Changer votre mot de passe</li>
                </ul>
                <div class="warning-box">
                    ⚠️ <strong>Sécurité :</strong> Gardez votre mot de passe confidentiel et unique
                </div>
            </div>
            
            <div class="tutorial-section">
                <h3>🔍 Rechercher un Compte Rendu</h3>
                <ol>
                    <li>Cliquez sur <strong>"Rechercher"</strong></li>
                    <li>Entrez les critères de recherche (date, titre, mot-clé)</li>
                    <li>Les résultats apparaissent instantanément</li>
                </ol>
            </div>
            
        <?php else: ?>
            <!-- TUTORIEL PROFESSEUR -->
            
            <div class="tutorial-section">
                <h3>🏠 Accueil</h3>
                <p>Votre tableau de bord enseignant vous permet de :</p>
                <ul>
                    <li><strong>Réviser CRs</strong> : Examiner les comptes rendus soumis par les étudiants</li>
                    <li><strong>Validations</strong> : Valider les CRs avec une checklist</li>
                    <li><strong>Exporter CRs</strong> : Télécharger les CRs en lot</li>
                    <li><strong>Rechercher</strong> : Effectuer une recherche avancée</li>
                    <li><strong>Groupes</strong> : Créer et gérer les groupes d'étudiants</li>
                    <li><strong>Élèves</strong> : Consulter la liste des étudiants</li>
                </ul>
            </div>
            
            <div class="tutorial-section">
                <h3>📋 Réviser les Comptes Rendus</h3>
                <ol>
                    <li>Cliquez sur <strong>"Réviser CRs"</strong></li>
                    <li>La liste affiche tous les CRs des étudiants avec :
                        <ul>
                            <li>Nom de l'étudiant</li>
                            <li>Titre et date du CR</li>
                            <li>Statut de validation</li>
                        </ul>
                    </li>
                    <li>Actions disponibles :
                        <ul>
                            <li><strong>📄 Détails</strong> : Voir le CR complet</li>
                            <li><strong>✏️ Modifier</strong> : Éditer ou commenter</li>
                            <li><strong>📥 Exporter PDF</strong> : Télécharger le CR en PDF</li>
                        </ul>
                    </li>
                </ol>
            </div>
            
            <div class="tutorial-section">
                <h3>✅ Valider avec Checklist</h3>
                <ol>
                    <li>Cliquez sur <strong>"Validations"</strong></li>
                    <li>Sélectionnez un CR à valider</li>
                    <li>Utilisez la checklist pour vérifier les éléments requis</li>
                    <li>Commentez et validez le CR</li>
                </ol>
            </div>
            
            <div class="tutorial-section">
                <h3>📥 Exporter un Compte Rendu en PDF</h3>
                <ol>
                    <li>Dans <strong>"Réviser CRs"</strong>, trouvez le CR à exporter</li>
                    <li>Cliquez sur le bouton <strong>"📥 Exporter PDF"</strong> dans la colonne Actions</li>
                    <li>Le fichier PDF se télécharge automatiquement</li>
                </ol>
                <div class="success-box">
                    ✅ <strong>Le PDF contient :</strong> Étudiant, titre, date, contenu et pièces jointes
                </div>
            </div>
            
            <div class="tutorial-section">
                <h3>👥 Gérer les Groupes</h3>
                <ol>
                    <li>Cliquez sur <strong>"Groupes"</strong></li>
                    <li>Créez un nouveau groupe en cliquant sur <strong>"+ Créer un groupe"</strong></li>
                    <li>Nommez votre groupe et ajoutez des étudiants</li>
                    <li>Vous pouvez modifier ou supprimer des groupes existants</li>
                </ol>
            </div>
            
            <div class="tutorial-section">
                <h3>👨‍🎓 Consulter la Liste des Élèves</h3>
                <ol>
                    <li>Cliquez sur <strong>"Élèves"</strong></li>
                    <li>Consultez les informations des étudiants :</li>
                    <li>Nom, prénom, email, groupe</li>
                    <li>Nombre de CRs et statut général</li>
                </ol>
            </div>
            
            <div class="tutorial-section">
                <h3>🔍 Rechercher des Comptes Rendus</h3>
                <ol>
                    <li>Cliquez sur <strong>"Rechercher"</strong></li>
                    <li>Filtrez par :
                        <ul>
                            <li>Nom d'étudiant</li>
                            <li>Date du CR</li>
                            <li>Mot-clé dans le contenu</li>
                        </ul>
                    </li>
                    <li>Les résultats s'affichent instantanément</li>
                </ol>
            </div>
            
        <?php endif; ?>
        
        <div class="tutorial-section">
            <h3>❓ Questions Fréquemment Posées</h3>
            <p><strong>Q : Puis-je récupérer un CR supprimé ?</strong></p>
            <p style="margin-left: 20px; color: #666;">R : Non, la suppression est définitive. Soyez prudent avant de supprimer.</p>
            
            <p><strong>Q : Combien de temps avant que mon CR soit consulté ?</strong></p>
            <p style="margin-left: 20px; color: #666;">R : Cela dépend de votre professeur. Un délai de 3-5 jours est courant.</p>
            
            <p><strong>Q : Puis-je modifier un CR après sa création ?</strong></p>
            <p style="margin-left: 20px; color: #666;">R : Oui, vous pouvez modifier vos CRs à tout moment via le bouton "✏️ Modifier".</p>
            
            <p><strong>Q : Quel format pour les pièces jointes ?</strong></p>
            <p style="margin-left: 20px; color: #666;">R : JPG, PNG, GIF, PDF, DOC, DOCX (Max 10MB par fichier).</p>
        </div>
        
        <div class="link-group">
            <a href="accueil.php" class="retour-btn">← Retour à l'accueil</a>
        </div>
    </div>
    <?php include 'footer.php'; ?>
