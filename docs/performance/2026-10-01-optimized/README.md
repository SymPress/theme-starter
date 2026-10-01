# Lighthouse-Nachbesserung

**Nachtrag:** Die hier noch als offen dokumentierte Meta Description wurde
anschließend ergänzt. Der [gezielte SEO-Nachtest](../2026-10-01-metadata/README.md)
erreicht 100/100. Die folgenden Messwerte bleiben der damalige Zwischenstand.

Die Hinweise zur CSS-Minifizierung, HTTP-Kompression und Browser-Cache-Laufzeit wurden behoben. Die Startseite überträgt in Lighthouse jetzt **15.921 statt 48.810 Byte**, rund **67 % weniger**. Der mobile LCP sinkt im Median von **1,053 auf 0,904 Sekunden**. Der Performance-Score bleibt bei 100/100. Es handelt sich weiterhin um einen lokalen Labortest auf derselben kleinen WordPress-Fixture.

## Einordnung deiner Hinweise

| Lighthouse-Hinweis | Ursache und Ergebnis |
| --- | --- |
| Minify CSS, ca. 3 KB | Echter Build-Fehler: Encore 7 minifiziert CSS erst mit explizit konfiguriertem Minifier. Lightning CSS ist jetzt als direkte Entwicklungsabhängigkeit installiert und konfiguriert. Der Hinweis verschwindet. |
| Cache TTL „None“, ca. 17 KB | Der PHP-Testserver lieferte keine Cache-Header. Dateien mit Inhalts-Hash erhalten jetzt `public, max-age=31536000, immutable`. Der Hinweis verschwindet. |
| No compression applied, ca. 18 KB | Der Testserver komprimiert jetzt Frontend-HTML sowie die Theme-CSS-/JS-Dateien mit gzip. Der Hinweis verschwindet. |
| Minify JavaScript, ca. 208 KB | Die genannten `chrome-extension://…`-Dateien stammen aus einer Browser-Erweiterung. Sie gehören nicht zum WordPress-/Theme-Payload. In den Messungen mit einem sauberen Browserprofil treten sie nicht auf. |
| Reduce unused JavaScript, ca. 349 KB | Ebenfalls das injizierte Erweiterungsscript. Die pauschale WordPress-Empfehlung im Audit begründet hier keinen Plugin-Wechsel. Das Theme-Script umfasst unkomprimiert 761 Byte. |
| Render-blocking CSS | Bleibt als Hinweis mit etwa 10 ms geschätzter Einsparung im mobilen Nachtest. Das CSS gestaltet bereits den sichtbaren Seitenanfang und bleibt im Head. Ein asynchrones Laden könnte ungestaltete Zwischenzustände verursachen. Lighthouse berechnet hier weiterhin keinen FCP-/LCP-Gewinn. |
| Network dependency tree | HTML → CSS ist weiterhin die kurze notwendige Abhängigkeit. Es gibt keine geeigneten zusätzlichen Preconnect-Origins. Kein künstlicher Preconnect oder Preload wurde ergänzt. |
| Missing meta description | Weiterhin offen; betrifft SEO, nicht die Ladegeschwindigkeit. Die Demo hat keine SEO-Integration. Eine passende Beschreibung sollte die spätere Website über ihre SEO-/Content-Integration ausgeben. Das Theme fügt keine generische Beschreibung ein, die später mit SEO-Plugins doppelt erscheinen könnte. |

Die fehlende CSS-Minifizierung war im ursprünglichen Starter übersehen worden: `encore production` allein genügt dafür seit Encore 7 nicht. Die vorherige Dokumentation bezeichnete die Produktionsassets zu pauschal als vollständig minifiziert. [Quelle: Symfony Encore Minification](https://symfony.com/doc/current/frontend/encore/minification.html)

## Gemessene Datenmenge

| Ressource | Vorher, übertragener Inhalt | Nachher, übertragener Inhalt |
| --- | ---: | ---: |
| Startseiten-HTML | 27.795 Byte | 6.993 Byte gzip |
| Theme-CSS | 15.981 Byte | 3.919 Byte gzip |
| Theme-JavaScript | 761 Byte | 423 Byte gzip |

Die CSS-Datei selbst schrumpft durch Minifizierung von 15.981 auf 12.853 Byte; gzip reduziert dann zusätzlich die Übertragung. Die Zahlen sind tatsächlich empfangene Antwortkörper. Lighthouse zählt zusätzlich Header und das Favicon und kommt deshalb auf 15.921 Byte insgesamt.

## Änderungen und Prüfungen

- [Webpack-Konfiguration](../../../webpack.config.js): expliziter Lightning-CSS-Minifier; [package.json](../../../package.json) und Lockfile enthalten die direkte Abhängigkeit.
- [Testserver-Router](../../../tests/site/router.php): gzip über PHP/zlib sowie langes Caching ausschließlich für existierende CSS-/JS-Dateien mit Inhalts-Hash im Theme-Build.
- [HTTP-Prüfung](../../../scripts/check-http.mjs): prüft gzip, `Vary`, Cache-Header, unveränderten Inhalt nach Dekompression, unkomprimierte Auslieferung und HEAD. HTML und unversionierte Manifeste erhalten keine Immutable-Regel.
- [Messskript](../../../scripts/performance-audit.mjs): dekodiert komprimierte HTML-Antworten vor der Inhaltsanalyse und hält übertragene und dekodierte Bytes getrennt.

`npm run build`, `composer qa` mit 18 PHP-Dateien, 14 Twig-Templates und 48 Assertions sowie alle zehn Playwright-Browsertests bestanden. Dazu gehören Navigation, Suche, Kommentare, Passwortschutz, 404 und Editor-Styles. Die HTTP-Prüfung bestand ebenfalls. Die Installation meldete keine npm-Sicherheitslücken. Encores bekannte Warnung zum relativen Public Path bleibt absichtlich bestehen; SymPress löst die URLs gegen das Theme-Verzeichnis auf.

**Die HTTP-Änderungen gelten für diese lokale Fixture.** Auf einem späteren Hosting müssen Kompression und Cache-Header am Webserver/CDN konfiguriert werden. Die Theme-PHP-Dateien greifen nicht in dessen Auslieferung ein. Debug-Modus und deaktivierter Twig-Cache der Fixture bleiben unverändert.

## Nachmessung und Grenzen

Das gleiche Skript misst vier HTTP-Routen jeweils 20-mal, drei Seitentypen mit Playwright jeweils dreimal pro Bildschirmprofil und die Startseite mit Lighthouse jeweils dreimal mobil und auf Desktop. Lighthouse verwendet dieselben simulierten Netzwerk-/CPU-Einstellungen wie beim [Ausgangstest](../2026-10-01/README.md). Browser-Erweiterungen sind in diesen separaten Chromium-Profilen nicht geladen.

Die lokale Startseiten-TTFB beträgt jetzt im Median 41,93 ms gegenüber zuvor 40,86 ms; hier ist kein Backend-Geschwindigkeitsgewinn belegt. Die Verbesserung betrifft vor allem die übertragene Datenmenge. Einzelne mobile Lighthouse-Läufe erfassen weiterhin einen kleinen Layout Shift von 0,049; eine durchgehend verschiebungsfreie Darstellung ist damit nicht nachgewiesen. INP und echte Nutzererfahrungen wurden nicht als Feldmetriken gemessen.

| Lighthouse, je drei abgeschlossene Läufe | Mobil | Desktop |
| --- | ---: | ---: |
| Performance in allen Läufen | 100/100 | 100/100 |
| LCP, Median | 0,904 s | 0,243 s |
| FCP, Median | 0,754 s | 0,203 s |
| TBT in allen Läufen | 0 ms | 0 ms |
| CLS-Spannweite | 0–0,049 | 0 |
| SEO in allen Läufen | 91/100 | 91/100 |

Alle sechs Lighthouse-Läufe endeten ohne Warnung oder Laufzeitfehler. Die 80
HTTP-Stichproben lieferten jeweils Status 200; die zusätzlichen 18
Playwright-Navigationen erfassten keine JavaScript-Ausnahmen oder
fehlgeschlagenen Netzwerktransporte.

- [Lighthouse mobil, Lauf 1](mobile-1.report.html), [Lauf 2](mobile-2.report.html), [Lauf 3](mobile-3.report.html)
- [Lighthouse Desktop, Lauf 1](desktop-1.report.html), [Lauf 2](desktop-2.report.html), [Lauf 3](desktop-3.report.html)
- [Alle Rohdaten](measurements.json); zu jedem HTML-Bericht liegt auch `.report.json` vor.

Für eigene vergleichbare Tests ein frisches Chrome-Profil ohne Erweiterungen verwenden. Ein Inkognito-Fenster genügt nur, wenn dort keine Erweiterungen zugelassen sind. Den lokalen Testserver erreicht man weiterhin unter `http://127.0.0.1:18943/`.
