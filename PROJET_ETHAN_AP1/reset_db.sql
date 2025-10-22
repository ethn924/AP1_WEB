-- Réinitialisation complète de la BDD
SET FOREIGN_KEY_CHECKS = 0;

-- Vider toutes les tables en respectant l'ordre des dépendances
DELETE FROM logs_erreurs;
DELETE FROM analytics_cr;
DELETE FROM rappels_soumission;
DELETE FROM validations_cr;
DELETE FROM checklists_modeles;
DELETE FROM modeles_cr;
DELETE FROM archives_cr;
DELETE FROM sauvegardes_auto;
DELETE FROM versions_cr;
DELETE FROM statuts_cr;
DELETE FROM pieces_jointes;
DELETE FROM commentaires;
DELETE FROM cr;
DELETE FROM notifications;
DELETE FROM membres_groupe;
DELETE FROM groupes;
DELETE FROM tuteur;
DELETE FROM stage;
DELETE FROM utilisateur;

SET FOREIGN_KEY_CHECKS = 1;

-- Insérer les utilisateurs avec IDs 1 et 2
INSERT INTO utilisateur (num, nom, prenom, tel, login, motdepasse, type, email, `option`, num_stage, token, `date`, token_created_at, email_valide, code_verification, email_verification_token, email_verification_token_created_at) VALUES
(1, 'Lalienne', 'Ethan', NULL, 'ethan.lalienne', '29f5ea398a0230273b0231d8643d6b9d', 0, 'ethanlalienne92@gmail.com', NULL, NULL, '', NULL, NULL, 1, NULL, NULL, NULL),
(2, 'Gravouil', 'Benjamin', NULL, 'benjamin.gravouil', '3e5df8603e419d62842d33ff7e00e5db', 1, 'benjamin.gravouil@gmail.com', NULL, NULL, '', NULL, NULL, 1, NULL, NULL, NULL);

-- Réinitialiser les auto_increment pour toutes les tables
ALTER TABLE utilisateur AUTO_INCREMENT = 3;
ALTER TABLE cr AUTO_INCREMENT = 1;
ALTER TABLE commentaires AUTO_INCREMENT = 1;
ALTER TABLE notifications AUTO_INCREMENT = 1;
ALTER TABLE pieces_jointes AUTO_INCREMENT = 1;
ALTER TABLE statuts_cr AUTO_INCREMENT = 1;
ALTER TABLE versions_cr AUTO_INCREMENT = 1;
ALTER TABLE sauvegardes_auto AUTO_INCREMENT = 1;
ALTER TABLE archives_cr AUTO_INCREMENT = 1;
ALTER TABLE stage AUTO_INCREMENT = 1;
ALTER TABLE tuteur AUTO_INCREMENT = 1;
ALTER TABLE groupes AUTO_INCREMENT = 1;
ALTER TABLE membres_groupe AUTO_INCREMENT = 1;
ALTER TABLE checklists_modeles AUTO_INCREMENT = 1;
ALTER TABLE validations_cr AUTO_INCREMENT = 1;
ALTER TABLE modeles_cr AUTO_INCREMENT = 1;
ALTER TABLE rappels_soumission AUTO_INCREMENT = 1;
ALTER TABLE logs_erreurs AUTO_INCREMENT = 1;
ALTER TABLE analytics_cr AUTO_INCREMENT = 1;