
# 🦆 Quacker - Slutprojekt
Quacker is a social media platform created to simulate real-world web applications. Our mission is to provide a safe and fast environment for users to share their thoughts and connect with others.

This platform was developed by David Norberg as a final project for Web Development 2 and Web Server Programming 1.

## 🛠️ Arkitektur & Filstruktur

Projektet är strikt uppdelat för att separera logik, presentationsvyer och klientsidetillgångar. Samtliga användartillgängliga sidor är placerade i den publika mappen för ökad server-säkerhet.

---
```yaml

├── config/                   # Konfigurationsfiler från ursprungsmallen
│   ├── config.php            # Konfigurationsfil
│   └── env.php               # Läser in systemvariabler från .env-filen
│
├── database/                 # Databaskopplingar och centrala queries
│   └── db.php                # Central PDO-anslutning till MySQL
│
├── includes/                 # Återanvändbara globala layoutkomponenter
│   ├── footer.php            # Global sidfot inklusive Bootstrap-modaler för radering
│   ├── header.php            # Global navigering, session-kontroller, och responsiv meny
│   ├── message_time_formatter.php # Omvandlar tidsstämplar till relativa format specifikt för chatten
│   ├── nav.php               # Navigationsmenyns underkomponenter och länkar
│   ├── quack_actions.php     # Layoutkomponent för interaktionsknappar (like, reply, requack, delete)
│   ├── quack_feed_logic.php  # Central SQL-fråga för flödeshantering (JOINs/COALESCE)
│   ├── quack_item.php        # HTML-struktur för att visa ett enskilt quack-inlägg på skärmen
│   ├── quack_loop.php        # Itererar igenom inläggsarrayen och hanterar tomma flöden
│   ├── quack_time_formatter.php # Omvandlar tidsstämplar till relativa format ("5m", "2h") för flödet
│   ├── send_mail.php         # Wrapper-funktion som sköter SMTP-konfigureringen mot PHPMailer
│   └── user_deletion_logic.php # Omfattande städningsrutin för kontoradering (GDPR-säkrad)
│
├── PHPMailer/                # Externt bibliotek för säker hantering och utskick av SMTP-mejl
│
├── public/                   # Utåtriktad mapp tillgänglig för webbservern
│   ├── actions/              # Ren backend-logik (endast PHP, inga presentationselement)
│   │   ├── delete_comment.php       # Asynkron radering av kommentarer med ägar- och admin-kontroll
    │   ├── delete_my_account.php    # Låter användaren radera sitt eget konto permanent (GDPR)
    │   ├── delete_quack.php         # Raderar ett specifikt inlägg och rensar relaterad data manuellt
    │   ├── delete_user.php          # Administratörsverktyg för säker användarradering via transaktioner
    │   ├── fetch_feed.php           # API-ändpunkt som genererar och returnerar flödets HTML via AJAX
    │   ├── follow_handler.php       # Hanterar asynkron följ- och avföljningslogik samt notisutskick
    │   ├── get_conversations.php    # Hämtar och grupperar användarens chattmeddelanden till inkorgen
    │   ├── get_messages.php         # Hämtar och renderar meddelandehistoriken live i chattrutan
    │   ├── get_notification_url.php # Helper-funktion som genererar rätt klick-URL baserat på notis-typ
    │   ├── get_unread_count.php     # Räknar olästa notiser och meddelanden för navbarens siffror
    │   ├── handle_forgot.php        # Validerar e-post och skickar kryptografiskt säkra reset-länkar
    │   ├── handle_login.php         # Hanterar användarautentisering och startar säkra sessioner
    │   ├── handle_register.php      # Registrerar nya användare med Bcrypt-lösenordshashning
    │   ├── handle_reset.php         # Kontrollerar tokens och sparar nya lösenord i databasen
    │   ├── like_handler.php         # Like-funktion som uppdaterar databasen och räknaren live
    │   ├── live_search.php          # Utför asynkrona och begränsade databassökningar via LIKE
    │   ├── logout.php               # Avslutar den aktiva sessionen och loggar ut användaren säkert
    │   ├── process_comment.php      # Bearbetar, sparar och validerar nya kommentarer, skapar notiser
    │   ├── process_quack.php        # Sparar nya quacks, hanterar textanalys (Regex) och massnotiser
    │   ├── requack_handler.php      # Toggle-logik för att asynkront dela eller ångra delning av inlägg
    │   ├── send_message.php         # Sparar skickade chattmeddelanden och bild-/videouppladdning
    │   └── update_profile.php       # Validerar och sparar uppdaterade biografier och profilbilder
│   │
│   ├── css/                  # Anpassade stilmallar som kompletterar Bootstrap 5
│   │   ├── admin.css         # Unik styling för adminpanelens tabeller, badges och layout
│   │   ├── loginregister.css # Styling för autentiseringsformulär, logotyper och inloggningskort
│   │   ├── messages.css      # Design för chattrutan, meddelandebubblor (sent/received) och inboxen
│   │   ├── notifications.css # Layoutregler för aviseringsflödet och olästa notis-indikatorer
│   │   ├── profile.css       # Design för profilheaders, statistikfältet och Canvas-grafens hållare
│   │   └── styles.css        # Globala stilmallar, CSS-variabler och det centrala Quack-flödet 
│   │
│   ├── images/               # Statiska gränssnittsbilder (t.ex. standardprofilbild)
│   │ 
│   ├── js/                   # Beteendeskikt (Asynkrona klientskript)
│   │   ├── admin.js          # Klientbaserad sökfiltrering och AJAX-användarradering
│   │   ├── app.js            # Gemensam JS som finns på alla sidor
│   │   ├── follow_ajax.js    # Hanterar asynkron följ- och avföljningslogik samt statistik i realtid
│   │   ├── index.js          # Filuppladdningsförhandsvisning och flikväxling på startsidan
│   │   ├── live_search.js    # Realtidssökning med inbyggd prestanda-debounce
│   │   ├── messages.js       # Driver realtidschatten via polling (setInterval) och scroll-spärr
│   │   ├── profile_edit.js   # Omedelbar lokal förhandsvisning av profilbilder (FileReader)
│   │   └── quacktivity.js    # Interaktiv 2D-grafik ritad på HTML5 Canvas (DPR-skalad)
│   │
│   ├── admin.php             # Huvudvy för administratörens kontrollpanel
│   ├── forgot_password.php   # Formulär för att begära återställningslänk
│   ├── index.php             # Applikationens startsida och centrala aktivitetsflöde
│   ├── info.php              # Systeminformation eller testsida för PHP-miljön
│   ├── login.php             # Inloggningssida för befintliga användare
│   ├── messages.php          # Chattränssnitt för privata direktmeddelanden (DM)
│   ├── notifications.php     # Listar användarens aktivitetsnotiser (likes, kommentarer, följare)
│   ├── profile.php           # Användarprofiler med personliga flöden och aktivitetsgraf
│   ├── quack.php             # Detaljerad vy för ett specifikt inlägg med kommentarsfält
│   ├── register.php          # Registreringssida för att skapa ett nytt konto
│   ├── reset_password.php    # Sida där användaren anger sitt nya lösenord via token
│   └── search.php            # Statisk söksida för quacks och hashtags
│
├── uploads/                  # Katalog för användargenererad media (Bilder och videor)
│   ├── messages/             # Media skickad privat via chattränssnittet
│   ├── pfp/                  # Uppladdade unika profilbilder
│   └── quacks/               # Bilder och videor bifogade i publika inlägg
│
├── .env                      # Lokala miljövariabler (Databasuppgifter, döljs via .gitignore)
└── README.md                 # Denna dokumentationsfil (Placerad i projektets rotmapp)
```
---

## ✨ Huvudfunktionalitet

*   **Dynamiskt aktivitetsflöde:** Användare kan publicera "quacks" (inlägg) med text, bilder eller videor. Flödet kan filtreras mellan globala inlägg eller enbart inlägg från konton man väljer att följa.
*   **Interaktioner i realtid:** Fullt stöd för att gilla (like), kommentera (reply) och dela (requack) inlägg asynkront via JavaScript Fetch API (AJAX).
*   **Privat chattränssnitt (DM):** Ett fullt fungerande chattsystem med asynkron uppdatering (polling) som stöder text, bilder och video direkt i konversationstråden.
*   **Live-sökning:** Global sökruta i navigationsfältet som sök-filtrerar live efter både användare och inlägg så fort användaren skriver.
*   **Aktivitetsdiagram (Canvas):** En skräddarsydd 2D-grafik ritas ut på en HTML5 Canvas på profilsidan som hämtar databasstatistik i realtid och animerar användarens veckovisa aktivitet.
*   **Admin-panel:** Ett administratörsgränssnitt där behöriga konton kan sök-filtrera bland användare, granska deras inlägg, samt radera konton permanent.

---

## 🔒 Säkerhet & God Praxis

*   **Skydd mot SQL-injections:** Applikationen använder konsekvent **Prepared Statements** via PDO för all databaskommunikation.
*   **Skydd mot Cross-Site Scripting (XSS):** All användargenererad data saneras strikt med `htmlspecialchars()` i presentationslagret samt med en anpassad `escapeHtml()`-funktion i JavaScript-lagret.
*   **Kryptografisk Lösenordshantering:** Lösenord lagras under envägshashning med industristandarden `password_hash()` (Bcrypt). Återställningstokens genereras med kryptografiskt säkra slumptal via `random_bytes()`.
*   **Säker Filuppladdning:** Servern validerar uppladdade filer mot en strikt vitlista baserat på filens faktiska binära struktur (`finfo_file` MIME-typ). Filer döps om till slumpmässiga hex-strängar för att förhindra exekvering av skadlig kod.
*   **GDPR-efterlevnad:** Systemet uppfyller "Rätten att bli glömd". Vid radering av en användare rensas all tillhörande data (likes, kommentarer, bilder, meddelanden och relationer) spårlöst bort via en manuell städningsrutin i PHP samt databasens `ON DELETE CASCADE`-regler.
*   **Prestandaoptimering (Debounce & Caching):** Sökfunktionen använder en *debounce*-client-timer (300ms) för att spara på serverresurser. Chatten sparar föregående polling-HTML för att undvika onödiga repaints i webbläsaren.
*   **HTML5-Validering:** Samtliga publika vyer har kontrollerats med W3C Nu Validator och renderas helt utan strukturella eller semantiska fel (0 Errors).

---

## 🚀 Kom igång & Konfigurering

### 1. Miljövariabler
Kopiera `.env.example` till `.env` i rotmappen och konfigurera dina lokala databasuppgifter:

```text
DB_HOST=localhost
DB_NAME=quacker_db
DB_USER=root
DB_PASS=root
```
*Märk: `.env`-filen är tillagd i `.gitignore` och ska aldrig laddas upp till versionshanteringen.*

### 2. Krav
*   PHP 7.4+
*   MySQL eller MariaDB
*   Lokal webbserver (t.ex. Herd, XAMPP eller Apache)
