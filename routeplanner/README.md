# Klink Routeplanner

Webapplicatie (voor op de computer) voor **Ingenieursbureau Klink**: de app stelt efficiënte dagroutes voor
inspecteurs voor langs tankstations van Shell, BP en TotalEnergies, en de planner beslist of een route doorgaat.

Gebouwd met **Laravel 12 + Inertia + Vue 3 + Tailwind CSS 4**, kaart met **Leaflet + OpenStreetMap**.
De huisstijl komt van het Klink-logo en ingenieursbureauklink.nl: navy `#003D52`, donkerrood `#980022`
en Open Sans. Er is een donkere en een lichte modus.

## Installeren

Nodig: PHP 8.2+, Composer, Node 20+.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Open daarna http://127.0.0.1:8000. Voor ontwikkelen met automatisch herladen draai je `npm run dev` naast `php artisan serve`.

Standaard gebruikt de app SQLite (`database/database.sqlite`). Wil je MySQL (bijv. via AMPPS), pas dan `DB_CONNECTION`
en de andere `DB_`-regels in `.env` aan en draai `php artisan migrate:fresh --seed`.

### Adressen opzoeken bij importeren

De formulieren zoeken adressen op in de browser. De CSV-import doet dat op de server, en daarvoor moet PHP
HTTPS-certificaten kunnen controleren. Krijg je bij het importeren *"adresservice PDOK is niet bereikbaar"*
(cURL error 60), download dan `cacert.pem` van https://curl.se/docs/caextract.html en zet in je `php.ini`:

```ini
curl.cainfo = "C:\pad\naar\cacert.pem"
openssl.cafile = "C:\pad\naar\cacert.pem"
```

Staat er een virusscanner of schoolnetwerk tussen dat HTTPS inspecteert, voeg dan ook dat rootcertificaat toe aan `cacert.pem`.

### Testaccounts (alleen lokaal)

Wachtwoord voor alle accounts: `wachtwoord`

| Rol               | E-mailadres                                                                             |
| ----------------- | --------------------------------------------------------------------------------------- |
| Planner           | planner@klink.test                                                                      |
| Bedrijfsleider    | bedrijfsleider@klink.test                                                               |
| Technisch manager | manager@klink.test                                                                      |
| Administratie     | administratie@klink.test                                                                |
| Beheerder         | beheerder@klink.test                                                                    |
| Inspecteur        | jan@klink.test, sanne@klink.test, mehmet@klink.test, pieter@klink.test, lisa@klink.test |

De testdata is fictief (adressen heten "Voorbeeldweg"). Mehmet heeft met opzet een **verlopen** certificaat
voor kathodische bescherming, zodat je ziet dat hij daar niet voor wordt ingepland.

### Tests

```bash
php artisan test
```

## Wat zit erin (gesprek 23-09-2026 + user stories)

| Uit het gesprek met Marijn                                                    | Waar in de app                                                  | User story  |
| ----------------------------------------------------------------------------- | --------------------------------------------------------------- | ----------- |
| Eerst een applicatie voor de computer                                         | Webapp, desktop-layout                                          | –          |
| Planner, bedrijfsleider, technisch manager, administratie; geen klantaccounts | Rollen,*Gebruikers*                                           | US25, US26  |
| Elke inspecteur een eigen account met zijn route                              | *Mijn route* (inspecteur ziet alleen die pagina)              | US19        |
| Zien wie wat heeft aangepast                                                  | *Wijzigingen* (automatisch log van elke wijziging)            | US27        |
| App doet suggesties, planner beslist                                          | *Routeplanner*: voorstel → goedkeuren / afwijzen / aanpassen | US13, US17  |
| Stations van verschillende klanten in dezelfde regio combineren               | Algoritme in`app/Services/RoutePlanner.php`                   | US13        |
| 3–4 werkzaamheden per station, vaste tijd per activiteit                     | *Werkzaamheden*; duur = som, optioneel per tank               | US03, US15  |
| Meerdere tanks per station, dan duurt het langer                              | Tanks per locatie, activiteit "per tank"                        | US02, US15  |
| Shell/BP/Total, ~1000 stations/jaar in maandblokken                           | Klanten, maandblok per opdracht,**CSV-import**            | US01b, US07 |
| Alleen de deadline van de klant                                               | Eén deadline per opdracht                                      | US08        |
| Certificaat per inspecteur, alleen inplannen als hij het mag                  | Certificaten met geldigheidsdatum; planner kijkt ernaar         | US05        |
| Meestal alleen, 3–10 stations per dag                                        | Eén inspecteur per route; werkdag begrenst het aantal          | –          |
| Apparatuur: iedereen heeft alles in de bus                                    | Geen apparatuurbeheer (US06 vervallen)                          | –          |
| Melding als deadline bijna verloopt → planner + bedrijfsleider               | Bel rechtsboven +*Meldingen*, `php artisan deadlines:check` | US08b       |
| Planning verandert zelden op de dag zelf                                      | Stop verplaatsen/verwijderen/toevoegen op de routepagina        | US17        |
| Live locatie / Google Maps alleen als bonus                                   | Knop*Google Maps* bij elke stop in *Mijn route*             | US20        |
| Bedrijfskleuren, donker/licht                                                 | Klink-huisstijl + wisselknop rechtsboven                        | US28        |

### Hoe het routevoorstel werkt

1. Kandidaten: open opdrachten die nog niet in een route zitten, uit het maandblok van de gekozen maand of de maand erna
   (`PLANNING_LOOKAHEAD_MONTHS`), gesorteerd op deadline.
2. Per inspecteur alleen opdrachten waarvoor hij **geldige** certificaten heeft.
3. Start met een urgente opdracht dicht bij zijn startadres, voeg dan steeds het station toe dat de minste extra rijtijd
   kost (urgente deadlines wegen zwaarder), zolang de werkdag inclusief terugrijden niet vol is.
4. Volgorde verbeteren met 2-opt, daarna aankomst- en vertrektijden berekenen.
5. Waarschuwingen: werkdag loopt uit, buiten openingstijden, certificaat ontbreekt, deadline al verlopen.

Rijtijden zijn nu een schatting (hemelsbreed × 1,3, gemiddeld 75 km/u, 5 min parkeren – instelbaar in
`config/planning.php`). De kaart tekent de route over de weg via de gratis OSRM-demoserver. Adressen worden via de
**PDOK Locatieserver** omgezet naar coördinaten. Later kan de schatting vervangen worden door OSRM/OpenRouteService (TECH06).

### Nog open (navragen bij Marijn)

- Echte lijst met werkzaamheden en tijden (nu een aanname, zie *Werkzaamheden*).
- Starten inspecteurs vanaf kantoor of thuis, en wat zijn hun werktijden?
- Tijdvensters/openingstijden van stations.
- Hoeveel dagen van tevoren meldingen, en ook per e-mail?

## Code

- Code en tabelnamen in het Engels, interface in het Nederlands (PID CS-01).
- `app/Services/RoutePlanner.php` – routevoorstel, herberekenen, goedkeuren, conflicten
- `app/Services/TravelEstimator.php` – afstand en rijtijd
- `app/Services/Geocoder.php` – PDOK Locatieserver
- `app/Services/DeadlineAlerts.php` – meldingen
- `app/Models/Concerns/LogsChanges.php` – wijzigingslog
- `resources/js/Pages` – Vue-pagina's, `resources/js/Components/RouteMap.vue` – kaart
