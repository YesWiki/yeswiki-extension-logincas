# Extension logincas

Cette extension remplace le formulaire de connexion de YesWiki par une
authentification centralisée sur un serveur CAS.

## Activer l'extension

L'extension s'installe sans rien casser. Tant que `cas_host` n'est pas renseigné,
elle reste inactive : un message signale la configuration manquante et le formulaire
de connexion habituel de YesWiki continue de fonctionner. Même chose si le serveur
CAS ne répond pas ou refuse l'authentification.

## Configuration

Dans `wakka.config.php`.

| Clé | Obligatoire | Défaut | Rôle |
|---|---|---|---|
| `cas_host` | oui | vide | nom d'hôte du serveur CAS, sans schéma ni chemin |
| `cas_port` | non | `443` | port du serveur CAS |
| `cas_context` | non | `/cas` | chemin du service CAS sur ce serveur |
| `cas_server_ca_cert_path` | non | vide | chemin vers la chaîne de certification du serveur |
| `cas_bazar_mapping` | non | `[]` | création d'une fiche bazar à la première connexion |

Exemple minimal :

```php
'cas_host' => 'cas.example.org',
```

Laisser `cas_server_ca_cert_path` vide désactive la validation du certificat du
serveur CAS. C'est pratique pour un essai, et à proscrire en production : sans cette
validation, le protocole CAS ne garantit plus rien.

## Créer une fiche bazar à la première connexion

`cas_bazar_mapping` associe les attributs renvoyés par le serveur CAS aux champs d'un
formulaire bazar. À la première connexion, l'utilisateur voit une question de
consentement, puis sa fiche est créée.

```php
'cas_bazar_mapping' => [
    [
        'id' => '1',
        'consent_question' => 'Acceptez-vous que votre fiche soit créée sur ce wiki ?',
        'fields' => [
            'bf_nom' => 'field_last_name',
            'bf_prenom' => 'field_first_name',
            'bf_mail' => 'mail',
            'bf_latitude' => 'field_lat_lon.latlon',
        ],
    ],
],
```

`id` est l'identifiant du formulaire bazar. Chaque entrée de `fields` va d'un nom de
champ bazar vers un attribut CAS. La notation `attribut.clé` lit une clé dans un
attribut encodé en JSON.

Les trois clés `id`, `consent_question` et `fields` doivent être présentes et `fields`
non vide, sinon aucune fiche n'est créée.

## Utilisation

L'extension remplace l'action `{{login}}`. Rien à changer dans vos pages.

Si `loginldap`, `loginsso` ou une autre extension d'authentification est installée en
même temps, une seule l'emporte : YesWiki charge les extensions par ordre alphabétique
et la dernière chargée gagne. N'en installez qu'une.

## En cas de problème

| Message | Cause | Que faire |
|---|---|---|
| L'extension logincas n'est pas configurée | `cas_host` absent de `wakka.config.php` | renseigner `cas_host` |
| Le serveur CAS ne répond pas ou est mal configuré | hôte injoignable, ou `cas_port` ou `cas_context` erroné | vérifier les trois clés et la joignabilité du serveur |
| L'authentification sur le serveur CAS a échoué | le serveur a répondu sans authentifier l'utilisateur | vérifier le compte côté serveur CAS |

Dans les trois cas, le formulaire de connexion habituel reste affiché juste en
dessous du message, donc les comptes locaux du wiki restent utilisables.

Pour tracer les échanges avec le serveur CAS, ajouter `&debug=1` à l'URL de connexion.
Le journal est écrit dans `files/cas.log`. Ce fichier contient des tickets de session,
donc à désactiver et à supprimer une fois le diagnostic terminé.
