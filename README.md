# Modèle de site pour artisans

Socle PHP réutilisable pour créer des sites professionnels d’artisans. L’installateur configure l’entreprise, son métier, les services réellement proposés, les zones d’intervention, l’identité visuelle et les contenus. Aucune identité client n’est distribuée avec le modèle.

## Caracteristiques

- Aucun framework lourd : architecture PHP 8.2+ modulaire faite main (routeur, controleurs, modeles, services, vues, middlewares), sans dependance Composer obligatoire au runtime.
- Fonctionne sur un hebergement mutualise classique (PHP + MySQL/MariaDB, Apache ou Nginx), sans Docker.
- Assistant d'installation web en plusieurs etapes : verification technique, base de donnees, compte administrateur, entreprise, identite visuelle, metier, services, zones d'intervention, fournisseur IA, informations redactionnelles, generation initiale du site.
- Pour chaque service, choix indépendant entre une page générée par l’IA et un texte rédigé manuellement. Un contenu manuel n’est jamais écrasé par la génération automatique.
- Générateur de pages locales « service × ville » : activation depuis l’administration, contenu manuel ou IA, import JSON en cascade, génération progressive, publication contrôlée, sitemap et maillage automatique depuis les articles WordPress.
- Generation de contenus par IA avec garde-fous stricts contre les inventions (aucune fausse certification, prix, annee d'experience, avis, etc.).
- Fondations SEO completes : sitemap XML dynamique, robots.txt dynamique, donnees structurees JSON-LD adaptees au metier, URLs propres, fil d'Ariane, redirections 301.
- Connexion à un WordPress externe via son API REST, affichage des articles dans le thème du site et maillage contextuel vers les pages locales publiées.

## Prerequis

- PHP >= 8.2 avec les extensions : pdo_mysql, gd, sodium, mbstring, json, fileinfo, curl.
- MySQL ou MariaDB.
- Apache (mod_rewrite) ou Nginx.
- Composer est optionnel (utilise uniquement pour les tests PHPUnit en developpement).

## Demarrage rapide (developpement local)

```bash
cp .env.example .env
php -S localhost:8000 -t public
```

Puis ouvrez `http://localhost:8000` : vous serez redirige vers l'assistant d'installation (`/install`).

## Déploiement sur un serveur

Depuis une connexion SSH, après avoir récupéré le dépôt :

```bash
bash scripts/deploy.sh git@github.com:VOTRE-COMPTE/VOTRE-DEPOT.git /var/www/nom-du-client main
```

Le script installe ou met à jour le code sans écraser le `.env`, les médias ni les données propres au client. Renseignez ensuite `.env`, configurez le document root sur `public/`, puis ouvrez `/install`.

Ne publiez jamais un `.env`, une base de données, les dossiers d’uploads ou une copie d’un ancien client. Le `.gitignore` fourni protège ces éléments.

## Tests

```bash
composer install   # installe PHPUnit (dev uniquement, non requis au runtime)
composer test       # ou : vendor/bin/phpunit
```

Les tests couvrent le routeur, le moteur de migration, la validation/nouvelle tentative JSON du moteur de generation IA, et le generateur de donnees structurees JSON-LD. Certains tests necessitent une base de donnees configuree (`.env`) - ils sont ignores automatiquement sinon.

## Perimetre de cette version

Cette version couvre l’installateur, les métiers et services, les zones, la génération IA ou manuelle, les pages locales service × ville, l’import/export JSON, WordPress, les formulaires, l’administration et les fondations SEO. La génération en cascade est exécutée progressivement dans le navigateur afin de rester compatible avec les hébergements mutualisés. Google Search Console doit encore être configuré depuis le compte du propriétaire du domaine.

## Licence

Proprietaire - projet prive.
