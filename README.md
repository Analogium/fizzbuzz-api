# FizzBuzz API

Endpoint REST qui renvoie la suite FizzBuzz de 1 à `limit`, protégé par une clé d'API.

NOTE: Ici j'utilise docker même si c'est inutile pour un projet aussi simple, c'est surtout si vous n'avez pas envie d'installer tout le nécessaire juste pour ça.

## Lancer

**Avec PHP ≥ 8.2 et Composer installés :**

```bash
composer install
php -S 127.0.0.1:8000 -t public      # ou : symfony serve
```

**Sans PHP, avec Docker** (image PHP 8.4) :

```bash
docker build -t fizzbuzz-api .
docker run --rm -p 8000:8000 fizzbuzz-api                  # lance l'API
docker run --rm fizzbuzz-api php bin/phpunit               # lance les tests
docker run --rm fizzbuzz-api vendor/bin/phpstan analyse    # analyse statique
```

```bash
curl -H "X-API-KEY: dev-api-key-change-me" "http://127.0.0.1:8000/api/fizzbuzz?limit=15"
# {"limit":15,"result":["1","2","Fizz","4","Buzz",...,"14","FizzBuzz"]}
```

| Cas | Code |
|---|---|
| Clé valide | `200` + JSON |
| Header `X-API-KEY` absent, vide ou faux | `401 {"error":"Invalid or missing API key."}` |
| `limit` non entier, < 1 ou > 10 000 | `400 {"error":"..."}` |
| `limit` absent | défaut `100` |
| Mauvaise méthode, route inconnue, autre erreur | `405` / `404` / ... `{"error":"Not Found"}` (toujours du JSON) |

### Qualité

- **Tests** : `php bin/phpunit` (15 tests ; échouent aussi si le code utilise une API dépréciée).
- **Analyse statique** : `vendor/bin/phpstan analyse`, PHPStan au **niveau max**, 0 erreur (config : `phpstan.dist.neon`).

### Mise en production

Le `.env` versionné est fait pour le dev (`APP_ENV=dev` affiche les traces d'erreur). En prod, définir en variables d'environnement (ou via `bin/console secrets:set`) :

- `APP_ENV=prod`
- `API_KEY` : longue et aléatoire, ex. `php -r 'echo bin2hex(random_bytes(32));'`
- `APP_SECRET` : idem

Et côté serveur : `expose_php = Off` dans le php.ini (masque le header `X-Powered-By: PHP/x.y`).

## Structure

| Fichier | Rôle |
|---|---|
| `src/FizzBuzz.php` | L'algorithme, pur, sans dépendance à HTTP → testable unitairement |
| `src/Controller/FizzBuzzController.php` | Validation de `limit` + réponse JSON |
| `src/Security/ApiKeyAuthenticator.php` | Vérification du header `X-API-KEY` |
| `config/packages/security.yaml` | Firewall `stateless` sur `^/api` + `access_control` |
| `src/EventListener/ApiExceptionListener.php` | Erreurs sous `/api` → JSON au lieu de la page HTML de Symfony |
| `tests/` | 1 test unitaire (algo) + 1 test fonctionnel (HTTP, auth, validation) |

## Choix de sécurité

- **Composant Security de Symfony** (custom authenticator) plutôt qu'un simple listener : c'est le mécanisme prévu par le framework, il s'intègre avec `access_control`, et ajouter des routes protégées ne demande rien de plus.
- **`stateless: true`** : pas de session ni de cookie, la clé est vérifiée à chaque requête (normal pour une API).
- **`hash_equals`** : comparaison en temps constant, contre les attaques par timing.
- **Clé vide refusée** même si `API_KEY` n'est pas configurée → on échoue fermé, pas ouvert.
- **Clé hors du code** : variable d'environnement `API_KEY`. La valeur dans `.env` est une valeur de dev ; en prod → vraie variable d'env ou `bin/console secrets:set API_KEY` (coffre chiffré de Symfony).
- **Borne max sur `limit`** : évite qu'un client génère des réponses énormes.
- **Erreurs JSON génériques** (`"Not Found"`...) : le message interne des exceptions n'est jamais renvoyé au client.
- **`WWW-Authenticate` sur le 401** : exigé par la norme HTTP (RFC 9110).

Limite connue : le routage Symfony passe avant le firewall, donc une route inexistante répond 404 même sans clé (on peut deviner quelles routes existent). Sans enjeu ici.

Volontairement pas fait (à ajouter si besoin réel) : plusieurs clés / une clé par client en base, rate limiting (`symfony/rate-limiter`), doc OpenAPI.

## Choix des versions : Symfony 7.4 LTS / PHP ≥ 8.2

**Contexte** : la machine de dev (WSL Ubuntu 20.04, je prefere à Powershell ou Cmd) n'a que PHP 7.4 et 8.2 ; PHP 8.4 n'est plus packagé pour cette version d'Ubuntu.

- **PHP 7.4 écarté** : fin de vie depuis novembre 2022, et limite à Symfony 5.4.
- **Symfony 8 écarté** : exige PHP 8.4.
- **Symfony 7.4 LTS retenu** : version Long Term Support (bugs jusqu'en novembre 2028, sécurité jusqu'en novembre 2029). C'est ce qu'on choisit en entreprise pour une appli qu'on ne veut pas migrer tous les 6 mois.
- **`"php": ">=8.2"`** : tourne aussi tel quel sur PHP 8.3 / 8.4 / 8.5.

**Limite assumée** : PHP 8.2 ne reçoit plus de correctifs de sécurité après le 31/12/2026. En production, on le ferait tourner sur PHP 8.4, sans changer une ligne de code. C'est vérifié : le `Dockerfile` utilise PHP 8.4 et les tests y passent, sans aucune dépréciation.

Le Docker sert uniquement à lancer le projet sans installer la bonne version de PHP. Ce n'est pas une image de production (serveur intégré de PHP, dépendances de dev incluses).