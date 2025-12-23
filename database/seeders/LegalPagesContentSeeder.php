<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LegalPage;

class LegalPagesContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'cgu',
                'title' => 'Conditions Générales d\'Utilisation',
                'content' => '<h2>Conditions Générales d\'Utilisation de Weylo</h2>
<p><strong>Dernière mise à jour :</strong> ' . date('d/m/Y') . '</p>

<h3>1. Présentation du service</h3>
<p>Weylo est une plateforme de messagerie anonyme qui permet aux utilisateurs d\'envoyer et de recevoir des messages de manière confidentielle. En utilisant notre service, vous acceptez les présentes conditions d\'utilisation.</p>

<h3>2. Inscription et compte utilisateur</h3>
<p>Pour utiliser Weylo, vous devez créer un compte en fournissant des informations exactes et à jour. Vous êtes responsable de la sécurité de votre compte et de votre mot de passe.</p>
<ul>
    <li>Vous devez avoir au moins 13 ans pour utiliser Weylo</li>
    <li>Vous ne devez créer qu\'un seul compte par personne</li>
    <li>Vous êtes responsable de toutes les activités effectuées depuis votre compte</li>
</ul>

<h3>3. Utilisation du service</h3>
<p>Vous vous engagez à utiliser Weylo de manière responsable et à respecter les droits des autres utilisateurs.</p>
<p><strong>Il est strictement interdit de :</strong></p>
<ul>
    <li>Publier du contenu illégal, offensant, diffamatoire ou haineux</li>
    <li>Harceler, menacer ou intimider d\'autres utilisateurs</li>
    <li>Usurper l\'identité d\'une autre personne</li>
    <li>Partager du contenu à caractère pornographique ou inapproprié</li>
    <li>Utiliser des bots ou des scripts automatisés</li>
</ul>

<h3>4. Contenu et propriété intellectuelle</h3>
<p>Vous conservez tous vos droits sur le contenu que vous publiez sur Weylo. Toutefois, en publiant du contenu, vous accordez à Weylo une licence mondiale, non exclusive et gratuite pour l\'utiliser, le reproduire et le diffuser.</p>

<h3>5. Modération et sanctions</h3>
<p>Weylo se réserve le droit de modérer le contenu publié et de suspendre ou supprimer tout compte qui ne respecte pas les présentes conditions.</p>

<h3>6. Limitation de responsabilité</h3>
<p>Weylo est fourni "tel quel" sans garantie d\'aucune sorte. Nous ne sommes pas responsables des dommages directs ou indirects résultant de l\'utilisation du service.</p>

<h3>7. Modifications des CGU</h3>
<p>Nous nous réservons le droit de modifier ces conditions à tout moment. Les utilisateurs seront informés des changements importants.</p>

<h3>8. Contact</h3>
<p>Pour toute question concernant ces conditions, veuillez nous contacter à : <strong>support@weylo.com</strong></p>',
                'is_active' => true,
                'order' => 1,
            ],
            [
                'slug' => 'privacy',
                'title' => 'Politique de Confidentialité',
                'content' => '<h2>Politique de Confidentialité</h2>
<p><strong>Dernière mise à jour :</strong> ' . date('d/m/Y') . '</p>

<h3>1. Introduction</h3>
<p>Chez Weylo, nous prenons votre vie privée très au sérieux. Cette politique explique comment nous collectons, utilisons et protégeons vos données personnelles.</p>

<h3>2. Données collectées</h3>
<p>Nous collectons les informations suivantes :</p>
<ul>
    <li><strong>Informations de compte :</strong> nom d\'utilisateur, adresse e-mail, mot de passe (chiffré)</li>
    <li><strong>Données de profil :</strong> photo de profil, bio, informations publiques</li>
    <li><strong>Messages :</strong> contenu des messages envoyés et reçus</li>
    <li><strong>Données techniques :</strong> adresse IP, type d\'appareil, navigateur</li>
    <li><strong>Données d\'utilisation :</strong> pages visitées, fonctionnalités utilisées</li>
</ul>

<h3>3. Utilisation des données</h3>
<p>Nous utilisons vos données pour :</p>
<ul>
    <li>Fournir et améliorer nos services</li>
    <li>Personnaliser votre expérience</li>
    <li>Assurer la sécurité de la plateforme</li>
    <li>Communiquer avec vous (notifications, mises à jour)</li>
    <li>Respecter nos obligations légales</li>
</ul>

<h3>4. Partage des données</h3>
<p>Nous ne vendons jamais vos données personnelles. Nous pouvons partager vos données uniquement dans les cas suivants :</p>
<ul>
    <li>Avec votre consentement explicite</li>
    <li>Pour respecter une obligation légale</li>
    <li>Avec des prestataires de services qui nous aident à faire fonctionner la plateforme</li>
</ul>

<h3>5. Protection des données</h3>
<p>Nous mettons en place des mesures de sécurité pour protéger vos données :</p>
<ul>
    <li>Chiffrement des mots de passe</li>
    <li>Connexion sécurisée (HTTPS)</li>
    <li>Accès restreint aux données personnelles</li>
    <li>Sauvegardes régulières</li>
</ul>

<h3>6. Vos droits</h3>
<p>Conformément au RGPD, vous disposez des droits suivants :</p>
<ul>
    <li><strong>Droit d\'accès :</strong> obtenir une copie de vos données</li>
    <li><strong>Droit de rectification :</strong> corriger vos données inexactes</li>
    <li><strong>Droit à l\'effacement :</strong> supprimer vos données</li>
    <li><strong>Droit d\'opposition :</strong> vous opposer au traitement de vos données</li>
    <li><strong>Droit à la portabilité :</strong> récupérer vos données dans un format lisible</li>
</ul>

<h3>7. Cookies</h3>
<p>Nous utilisons des cookies pour améliorer votre expérience. Consultez notre <a href="/legal/cookies">Politique des Cookies</a> pour plus d\'informations.</p>

<h3>8. Conservation des données</h3>
<p>Nous conservons vos données aussi longtemps que votre compte est actif. Après suppression de votre compte, vos données sont conservées 30 jours puis définitivement supprimées.</p>

<h3>9. Contact</h3>
<p>Pour toute question sur cette politique ou pour exercer vos droits, contactez-nous à : <strong>privacy@weylo.com</strong></p>',
                'is_active' => true,
                'order' => 2,
            ],
            [
                'slug' => 'cookies',
                'title' => 'Politique des Cookies',
                'content' => '<h2>Politique des Cookies</h2>
<p><strong>Dernière mise à jour :</strong> ' . date('d/m/Y') . '</p>

<h3>1. Qu\'est-ce qu\'un cookie ?</h3>
<p>Un cookie est un petit fichier texte stocké sur votre appareil lorsque vous visitez un site web. Les cookies nous permettent de mémoriser vos préférences et d\'améliorer votre expérience.</p>

<h3>2. Types de cookies utilisés</h3>

<h4>Cookies essentiels</h4>
<p>Ces cookies sont nécessaires au fonctionnement du site. Ils vous permettent de naviguer sur le site et d\'utiliser ses fonctionnalités.</p>
<ul>
    <li><strong>Cookie de session :</strong> maintient votre connexion</li>
    <li><strong>Cookie CSRF :</strong> protège contre les attaques de sécurité</li>
</ul>

<h4>Cookies de performance</h4>
<p>Ces cookies collectent des informations sur la façon dont vous utilisez notre site pour nous aider à l\'améliorer.</p>
<ul>
    <li>Pages visitées</li>
    <li>Temps passé sur le site</li>
    <li>Erreurs rencontrées</li>
</ul>

<h4>Cookies de fonctionnalité</h4>
<p>Ces cookies mémorisent vos choix et préférences pour personnaliser votre expérience.</p>
<ul>
    <li>Langue préférée</li>
    <li>Thème (clair/sombre)</li>
    <li>Préférences d\'affichage</li>
</ul>

<h3>3. Gestion des cookies</h3>
<p>Vous pouvez gérer vos préférences de cookies à tout moment :</p>
<ul>
    <li><strong>Via votre navigateur :</strong> la plupart des navigateurs permettent de refuser ou supprimer les cookies</li>
    <li><strong>Via nos paramètres :</strong> vous pouvez gérer vos préférences dans les paramètres de votre compte</li>
</ul>

<h3>4. Cookies tiers</h3>
<p>Nous n\'utilisons pas de cookies tiers publicitaires. Les seuls cookies tiers sont ceux de nos prestataires de services essentiels.</p>

<h3>5. Durée de conservation</h3>
<ul>
    <li><strong>Cookies de session :</strong> supprimés à la fermeture du navigateur</li>
    <li><strong>Cookies persistants :</strong> conservés jusqu\'à 12 mois maximum</li>
</ul>

<h3>6. Impact du refus des cookies</h3>
<p>Si vous refusez les cookies non essentiels, certaines fonctionnalités du site peuvent être limitées mais vous pourrez toujours utiliser les fonctions principales.</p>

<h3>7. Contact</h3>
<p>Pour toute question sur notre utilisation des cookies, contactez-nous à : <strong>cookies@weylo.com</strong></p>',
                'is_active' => true,
                'order' => 3,
            ],
            [
                'slug' => 'community-rules',
                'title' => 'Règles de la Communauté',
                'content' => '<h2>Règles de la Communauté Weylo</h2>
<p><strong>Dernière mise à jour :</strong> ' . date('d/m/Y') . '</p>

<h3>Bienvenue sur Weylo !</h3>
<p>Notre communauté repose sur le respect mutuel et la bienveillance. Ces règles garantissent une expérience positive pour tous les utilisateurs.</p>

<h3>1. Respectez les autres</h3>
<ul>
    <li>Traitez les autres comme vous aimeriez être traité</li>
    <li>Soyez respectueux même en cas de désaccord</li>
    <li>Acceptez les différences d\'opinion</li>
    <li>Ne harcelez pas, n\'intimidez pas et ne menacez pas les autres</li>
</ul>

<h3>2. Contenu interdit</h3>
<p><strong>Les contenus suivants sont strictement interdits :</strong></p>
<ul>
    <li>Contenu pornographique ou sexuellement explicite</li>
    <li>Violence graphique ou incitation à la violence</li>
    <li>Discours haineux, racisme, sexisme, homophobie</li>
    <li>Harcèlement ou intimidation</li>
    <li>Informations personnelles d\'autrui (doxxing)</li>
    <li>Spam ou publicité non sollicitée</li>
    <li>Contenu illégal</li>
</ul>

<h3>3. Anonymat responsable</h3>
<p>L\'anonymat est une fonctionnalité clé de Weylo, mais elle doit être utilisée de manière responsable :</p>
<ul>
    <li>N\'abusez pas de l\'anonymat pour blesser les autres</li>
    <li>Restez bienveillant même lorsque vous êtes anonyme</li>
    <li>L\'anonymat ne vous protège pas des conséquences légales</li>
</ul>

<h3>4. Authenticité</h3>
<ul>
    <li>Ne vous faites pas passer pour quelqu\'un d\'autre</li>
    <li>N\'utilisez pas de fausses informations pour tromper les autres</li>
    <li>Ne créez pas de faux comptes</li>
</ul>

<h3>5. Sécurité</h3>
<ul>
    <li>Ne partagez pas vos identifiants de connexion</li>
    <li>Signalez tout comportement suspect</li>
    <li>Ne cliquez pas sur des liens suspects</li>
    <li>Protégez vos informations personnelles</li>
</ul>

<h3>6. Signalement</h3>
<p>Si vous voyez du contenu ou un comportement qui enfreint ces règles :</p>
<ul>
    <li>Utilisez la fonction de signalement</li>
    <li>Ne répondez pas au contenu inapproprié</li>
    <li>Notre équipe de modération examinera chaque signalement</li>
</ul>

<h3>7. Conséquences</h3>
<p>En cas de violation de ces règles, nous pouvons :</p>
<ul>
    <li><strong>Avertissement :</strong> pour une première infraction mineure</li>
    <li><strong>Suspension temporaire :</strong> pour infractions répétées ou graves</li>
    <li><strong>Bannissement permanent :</strong> pour infractions très graves ou répétées</li>
    <li><strong>Signalement aux autorités :</strong> en cas d\'activité illégale</li>
</ul>

<h3>8. Faire appel</h3>
<p>Si vous pensez avoir été sanctionné injustement, vous pouvez faire appel en contactant <strong>appeals@weylo.com</strong></p>

<h3>9. Évolution des règles</h3>
<p>Ces règles peuvent évoluer. Les modifications importantes seront communiquées aux utilisateurs.</p>

<p><strong>Ensemble, créons une communauté bienveillante et respectueuse !</strong></p>',
                'is_active' => true,
                'order' => 4,
            ],
            [
                'slug' => 'legal-notice',
                'title' => 'Mentions Légales',
                'content' => '<h2>Mentions Légales</h2>
<p><strong>Dernière mise à jour :</strong> ' . date('d/m/Y') . '</p>

<h3>1. Éditeur du site</h3>
<p><strong>Weylo</strong><br>
Société : Weylo SAS<br>
Capital social : 10 000 €<br>
Siège social : 123 Avenue de la République, 75011 Paris, France<br>
RCS Paris : 123 456 789<br>
SIRET : 123 456 789 00012<br>
TVA intracommunautaire : FR12 123456789</p>

<p><strong>Directeur de la publication :</strong> Jean Dupont<br>
<strong>Contact :</strong> contact@weylo.com</p>

<h3>2. Hébergement</h3>
<p><strong>Hébergeur :</strong> OVH<br>
Société : OVH SAS<br>
Siège social : 2 rue Kellermann, 59100 Roubaix, France<br>
Téléphone : +33 9 72 10 10 07<br>
Site web : <a href="https://www.ovh.com" target="_blank">www.ovh.com</a></p>

<h3>3. Propriété intellectuelle</h3>
<p>L\'ensemble du contenu de ce site (textes, images, vidéos, logos, graphismes) est la propriété exclusive de Weylo, sauf mention contraire.</p>
<p>Toute reproduction, distribution, modification ou exploitation du contenu sans autorisation préalable est interdite et constitue une contrefaçon sanctionnée par le Code de la propriété intellectuelle.</p>

<h3>4. Protection des données personnelles</h3>
<p>Conformément au Règlement Général sur la Protection des Données (RGPD) et à la loi Informatique et Libertés, vous disposez de droits sur vos données personnelles.</p>
<p><strong>Responsable du traitement :</strong> Weylo SAS<br>
<strong>DPO (Délégué à la Protection des Données) :</strong> dpo@weylo.com</p>

<p>Pour plus d\'informations, consultez notre <a href="/legal/privacy">Politique de Confidentialité</a>.</p>

<h3>5. Cookies</h3>
<p>Ce site utilise des cookies pour améliorer votre expérience. Pour en savoir plus, consultez notre <a href="/legal/cookies">Politique des Cookies</a>.</p>

<h3>6. Limitation de responsabilité</h3>
<p>Weylo met tout en œuvre pour fournir des informations exactes et à jour. Cependant, nous ne pouvons garantir l\'exactitude, la précision ou l\'exhaustivité des informations présentes sur le site.</p>
<p>Weylo ne saurait être tenu responsable :</p>
<ul>
    <li>Des dommages directs ou indirects résultant de l\'utilisation du site</li>
    <li>Des interruptions temporaires du service</li>
    <li>Des contenus publiés par les utilisateurs</li>
    <li>Des liens vers des sites tiers</li>
</ul>

<h3>7. Loi applicable</h3>
<p>Les présentes mentions légales sont régies par le droit français. Tout litige relatif à l\'utilisation du site est de la compétence exclusive des tribunaux français.</p>

<h3>8. Médiation</h3>
<p>En cas de litige, vous pouvez recourir à une médiation conventionnelle ou à tout autre mode alternatif de règlement des différends.</p>
<p><strong>Médiateur :</strong> Médiateur de la consommation<br>
<strong>Contact :</strong> mediateur@weylo.com</p>

<h3>9. Crédits</h3>
<ul>
    <li><strong>Design :</strong> Weylo Design Team</li>
    <li><strong>Développement :</strong> Weylo Tech Team</li>
    <li><strong>Icônes :</strong> Font Awesome</li>
</ul>

<h3>10. Contact</h3>
<p>Pour toute question concernant ces mentions légales :<br>
<strong>Email :</strong> legal@weylo.com<br>
<strong>Téléphone :</strong> +33 1 23 45 67 89<br>
<strong>Courrier :</strong> Weylo SAS, 123 Avenue de la République, 75011 Paris, France</p>',
                'is_active' => true,
                'order' => 5,
            ],
        ];

        foreach ($pages as $page) {
            LegalPage::updateOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }

        $this->command->info('✅ Pages légales mises à jour avec leur contenu !');
    }
}
