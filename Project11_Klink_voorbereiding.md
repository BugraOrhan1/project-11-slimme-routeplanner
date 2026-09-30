# Project 11 – Slimme routeplanner voor inspecteurs
**Opdrachtgever:** Ingenieursbureau Klink B.V. – contactpersoon Marijn Klink
**Team:** Bugra, Valdrian + jij
**Doel van dit document:** voorbereiding op de eerste call/ontmoeting

---

## 1. Wie is Ingenieursbureau Klink?

| Onderwerp | Wat we weten |
|---|---|
| Wat doen ze | Onafhankelijk inspectiebureau, vooral **keuringen van ondergrondse en bovengrondse tankinstallaties** (opslag van o.a. brandstof en chemicaliën) |
| Sinds | 1993 (KvK 23072994) |
| Locatie | Bamendaweg 62, 3319 GS Dordrecht |
| Onderdeel van | **FLOWS Groep** (Dordrecht), familiebedrijf van de familie Klink |
| Zusterbedrijven | Reehorst Dordrecht (bouwt tankinstallaties), Van Dongen Installateurs, Klink Drone Inspecties |
| Grootte | Klein. De B.V. zelf staat met 1 werkzaam persoon bij de KvK, de groep als geheel met 10–19 medewerkers. Personeel zit waarschijnlijk op groepsniveau → **vragen hoeveel inspecteurs er echt zijn** |
| Keurmerk | Geaccrediteerd door de Raad voor Accreditatie (RvA); werkt volgens normen als AS SIKB 6800, BRL SIKB 7800, PGS 28, EEMUA 159 |

### Wat doen de inspecteurs precies?
- **Jaarlijkse controles** – wettelijk verplicht voor tankeigenaren; meerdere inspecties in één bezoek
- **Tankkeuringen/herkeuringen** – wanddiktemetingen (bodem, wand, dak), dichtheidsbeproeving, corrosie-inspectie binnenkant tank (soms met camera)
- **Kathodische bescherming** – potentiaal- en stroommetingen tegen corrosie
- **Specials** – bodemweerstandmetingen, ultrasoon onderzoek, DCVG-metingen, kabel-/leidingdetectie
- **Drone- en camera-inspecties** via Klink Drone Inspecties

### Waarom dit belangrijk is voor ONZE app (inzichten)
1. **Terugkerende deadlines** – inspecties zijn jaarlijks verplicht. De app kan dus niet alleen routes berekenen, maar ook laten zien *welke klanten binnenkort een keuring nodig hebben*. Dat is misschien nog waardevoller dan de route zelf.
2. **Afhankelijk van derden** – herkeuringen gebeuren samen met een erkend tankinstallatiebedrijf dat de tank klaarmaakt. Dat betekent **vaste tijdvensters** die niet flexibel zijn.
3. **Niet elke inspecteur kan alles** – diploma's zoals besloten ruimte, ademlucht, gasmetingen, IKT-2. De planner moet dus weten *wie* welke klus mag doen.
4. **Speciale apparatuur** – DCVG, ultrasoon, drukmeetapparatuur, drones. Als er maar één set is, kunnen twee inspecteurs die niet tegelijk gebruiken.
5. **Duur verschilt enorm** – een jaarlijkse controle is iets anders dan een volledige tankkeuring of een dichtheidsbeproeving (die heeft een meetperiode).
6. **Werkgebied** – vanaf Dordrecht door heel Nederland (zie kaart op de opdracht). Files rond Rotterdam/A16 maken tijdstip belangrijk.

---

## 2. Vragen voor de eerste meeting

> ⭐ = moet je zeker vragen als de tijd kort is

### A. Huidige werkwijze
1. ⭐ Kunt u een normale planningsweek beschrijven, van aanvraag tot uitgevoerde inspectie?
2. ⭐ Wie maakt nu de planning en hoeveel tijd kost dat per week?
3. Waarmee wordt nu gepland (Excel, agenda, papier, planningssoftware)?
4. ⭐ Wat gaat er nu het vaakst mis of kost onnodig tijd?
5. Hoe komen nieuwe inspectieopdrachten binnen (mail, telefoon, vast contract)?

### B. Inspecteurs en team
6. ⭐ Hoeveel inspecteurs rijden er rond, en hoeveel planners zijn er?
7. Starten inspecteurs vanaf kantoor in Dordrecht of vanaf huis?
8. Hebben ze vaste werktijden, een maximale rijtijd of pauzeregels?
9. Rijden ze alleen of soms met twee (bijv. mangatwacht bij besloten ruimte)?
10. Zijn er inspecteurs die alleen bepaalde inspecties mogen of kunnen doen (certificaten)?

### C. Inspecties en locaties
11. ⭐ Hoeveel locaties bezoekt één inspecteur gemiddeld per dag?
12. ⭐ Hoe lang duren de verschillende soorten inspecties ongeveer?
13. Zijn er vaste tijdvensters (openingstijden klant, afspraak met tankinstallateur)?
14. Welke apparatuur/bedrijfswagen is nodig per inspectietype, en is die beperkt beschikbaar?
15. Zijn er klanten met meerdere tanks op één locatie?
16. Hoe vaak verandert de planning op de dag zelf (spoed, afzeggingen, uitloop)?
17. Werkt Reehorst (zusterbedrijf) vaak als tankinstallateur mee? Moet hun planning ook meegenomen worden?

### D. Wat moet de app kunnen
18. ⭐ Wat zou voor u het belangrijkste resultaat zijn: minder kilometers, minder tijd, meer inspecties per dag, of minder planwerk?
19. Wie gaat de app gebruiken: alleen de planner, of ook de inspecteurs onderweg?
20. Moet het een website (kantoor), app (telefoon/tablet) of beide worden?
21. Moet de app ook waarschuwen voor keuringen die bijna verlopen?
22. Wilt u de berekende route nog handmatig kunnen aanpassen?
23. Moet de route naar Google Maps / Waze gestuurd kunnen worden?
24. Zijn er ook dingen die de app juist NIET moet doen?

### E. Data en systemen
25. ⭐ Waar staan klant- en locatiegegevens nu opgeslagen? Kunnen we daar een (geanonimiseerde) export van krijgen om te testen?
26. Gebruikt u software die moet koppelen (boekhouding, CRM, Outlook-agenda, rapportagesoftware)?
27. Zijn er eisen voor privacy/AVG, beveiliging of waar data opgeslagen wordt?
28. Is er budget voor betaalde kaartdiensten (bijv. Google Maps API), of liever gratis/open source?

### F. Praktisch en samenwerking
29. ⭐ Hoe vaak en hoe willen jullie contact (wekelijks, tweewekelijks, mail/Teams)?
30. Mogen we een dag meelopen of meerijden met een inspecteur?
31. Wanneer is het project voor u geslaagd?

**Tip:** vraag of je de meeting mag opnemen, en verdeel rollen: één stelt vragen, één maakt notities, één houdt tijd en vraagt door.

---

## 3. Technische eerste verkenning

**Het probleem heeft een naam:** het *Vehicle Routing Problem with Time Windows (VRPTW)*. Voor één inspecteur is het een *Travelling Salesman Problem (TSP)*. Dit is goed onderzocht, dus er zijn kant-en-klare tools.

| Onderdeel | Opties om te onderzoeken |
|---|---|
| Route-optimalisatie | Google OR-Tools (gratis, Python/Java/C#), VROOM, eigen heuristiek (nearest neighbour + 2-opt) als leerdoel |
| Reistijden tussen punten | OpenRouteService, OSRM (gratis, OpenStreetMap), Google Distance Matrix (betaald, wel met verkeer) |
| Adres → coördinaten | PDOK Locatieserver (gratis, officiële Nederlandse adressen) |
| Kaart tonen | Leaflet + OpenStreetMap, of Google Maps |

---

## 4. Eerste idee voor de schermen

1. **Dashboard planner** – kaart met alle openstaande opdrachten, lijst met keuringen die binnenkort verlopen
2. **Opdracht toevoegen** – adres met autocomplete, inspectietype, deadline, tijdvenster, eventueel CSV-import
3. **Route plannen** – kies datum + inspecteurs → "Bereken route" → kaart + tijdlijn per inspecteur, stops versleepbaar
4. **Inspecteur (mobiel)** – dagroute, knop "Navigeer", stop afvinken als klaar
5. **Beheer** – inspecteurs, certificaten, apparatuur, klanten

---

## 5. Database (eerste concept)

> **Update na gesprek 23-09-2026:** het schema is bijgewerkt. Apparatuur is eruit (elke inspecteur heeft alles in zijn bus), inspectietype is vervangen door activiteiten (3–4 per station), en gebruiker/rol, wijzigingslog en notificatie zijn toegevoegd. Zie `Gesprek 1 - Marijn Klink (23-09-2026).md`.

Zie het aparte bestand `Project11_database_schema.mermaid`. Kort samengevat:

- **inspecteur** + **certificaat** (wie mag wat)
- **klant** → **locatie** → **tankinstallatie** (met datum volgende keuring)
- **inspectietype** (duur, vereist certificaat, vereiste apparatuur)
- **opdracht** (wat moet waar, deadline, tijdvenster, status)
- **route** (één inspecteur, één dag) → **routestop** (volgorde, geplande en werkelijke tijden)
- **apparatuur** (beschikbaarheid)

Dit schema is bewust een concept: pas het aan na de meeting, want de antwoorden op vragen 10–17 bepalen welke tabellen echt nodig zijn.
