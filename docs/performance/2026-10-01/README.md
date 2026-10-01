# Performance-Test – SymPress Starter Theme

Gemessen am 1. Oktober 2026 ab 12:31 Uhr MESZ auf `http://127.0.0.1:18943/`.

**Nachtrag:** CSS-Minifizierung, Kompression und Cache-Header wurden anschließend
korrigiert. Der [Nachtest mit Vergleich](../2026-10-01-optimized/README.md) zeigt den
aktuellen Stand. Dieser Bericht und seine Rohdaten dokumentieren den Ausgangszustand.

Das Starter Theme ist in dieser Testinstallation sehr leichtgewichtig: Alle sechs Lighthouse-Läufe erreichen **100/100 Performance**. Die Startseite erreicht mobil einen LCP von **1,05 Sekunden**, auf Desktop **0,28 Sekunden**. Die lokale Serverantwort liegt im Median bei **41 Millisekunden**. Das ist ein gutes Ausgangsergebnis für WordPress mit SymPress, Twig, Tailwind und Encore. Eine Aussage über die Geschwindigkeit auf einem späteren Hosting lässt sich daraus noch nicht ableiten.

## Lighthouse: Startseite

Je drei getrennte Läufe mit Lighthouse 13.5.0, Chromium 153.0.8010.12, frischem Browserprofil und simulierter Netzwerk-/CPU-Leistung. Angegeben sind Mediane; die Spannweite macht Abweichungen sichtbar.

| Messwert | Mobil | Desktop |
| --- | ---: | ---: |
| Performance | 100/100 in allen Läufen | 100/100 in allen Läufen |
| First Contentful Paint | 0,903 s | 0,245 s |
| Largest Contentful Paint | 1,053 s | 0,283 s |
| LCP-Spannweite | 1,0526–1,0531 s | 0,2826–0,2833 s |
| Total Blocking Time | 0 ms | 0 ms |
| Cumulative Layout Shift, Median | 0 | 0 |
| CLS-Spannweite | 0–0,049 | 0 |
| Speed Index | 0,903 s | 0,245 s |
| Übertragungsvolumen laut Lighthouse | 48.810 Byte | 48.810 Byte |

Mobil: 412 × 823 px, simuliert 150 ms RTT, 1.638,4 Kbit/s und vierfache CPU-Verlangsamung. Desktop: 40 ms RTT, 10.240 Kbit/s und CPU-Faktor 1. Die vollständigen Einstellungen stehen in den JSON-Berichten.

LCP und CLS liegen in diesen Labormessungen im guten Bereich. Die entsprechenden Grenzwerte sind LCP ≤ 2,5 s und CLS ≤ 0,1. Für eine bestandene Bewertung realer Core Web Vitals braucht es jedoch Nutzerdaten am 75. Perzentil einschließlich INP. **INP wurde hier nicht als Feldmetrik gemessen**; TBT ersetzt diesen Nachweis nicht. [Quelle: Web Vitals](https://web.dev/articles/vitals)

Im ersten mobilen Lighthouse-Lauf verschob sich `main#content` mit CLS 0,049. Die übrigen fünf Lighthouse-Läufe und alle 18 separaten Browsernavigationen zeigten CLS 0. Die Ursache des einzelnen Ereignisses wurde nicht isoliert. Es bleibt dokumentiert, obwohl der Wert unter dem guten Grenzwert liegt.

Zusätzliche automatische Lighthouse-Wertungen: Accessibility 100, Best Practices 100, SEO 91 in allen Läufen. Der SEO-Abzug betrifft die fehlende Meta Description der Demo-Startseite. Diese Ergebnisse sind keine vollständige Barrierefreiheits- oder SEO-Prüfung.

## Serverantwort: vier Seitentypen

Je 20 aufeinanderfolgende HTTP-Anfragen mit neuen Verbindungen, ohne Browser und ohne künstliche Drosselung. Der Server war bereits gestartet und aufgewärmt. Eine zusätzliche erste Beobachtung pro Route ist in den Rohdaten enthalten, aber kein Kaltstart-Benchmark.

| Seite | Route | TTFB Median | TTFB p95 | Gesamte HTML-Antwort, Median |
| --- | --- | ---: | ---: | ---: |
| Startseite | `/` | 40,86 ms | 45,81 ms | 41,13 ms |
| Beitrag | `/gedanken-3/` | 38,30 ms | 42,24 ms | 38,47 ms |
| Inhaltsseite | `/ueber/` | 36,93 ms | 38,85 ms | 37,07 ms |
| Suche | `/?s=Notizbuch` | 37,21 ms | 39,57 ms | 37,37 ms |

Alle 80 Anfragen lieferten HTTP 200. p95 bezeichnet hier den 19. Wert der 20 sortierten Messungen; bei dieser kleinen Stichprobe ist es eine Orientierung.

Bei zusätzlich 20 Anfragen mit vier gleichzeitig aktiven Clients stieg der Median auf 155,89 ms, p95 auf 162,37 ms; alle Antworten waren HTTP 200. Der verwendete PHP-Entwicklungsserver arbeitet mit einem Worker. Dieser Versuch zeigt dessen Warteschlangenverhalten und erlaubt keine Hochrechnung auf Produktionslast oder Besucherzahlen.

TTFB umfasst die gesamte Anfrage einschließlich WordPress, Datenbank, SymPress und Twig. Es gab weder ein Komponentenprofiling noch einen Vergleich mit einem anderen Theme. Die einzelnen Anteile oder ein isolierter Symfony-/Twig-Overhead sind deshalb nicht beziffert.

## Frontend und Datenmenge

| Ressource | Aktueller Inhalt, unkomprimiert | Offline berechnet mit gzip |
| --- | ---: | ---: |
| Startseiten-HTML inklusive Inline-CSS | 27.795 Byte | 7.029 Byte |
| Theme-CSS | 15.981 Byte | 4.236 Byte |
| Theme-JavaScript | 761 Byte | 424 Byte |
| Summe dieser drei Ressourcen | 44.537 Byte | 11.689 Byte |

Die gzip-Spalte ist eine Berechnung mit Node/zlib, **keine gemessene komprimierte HTTP-Auslieferung**. Für diese Ressourcen ergibt sich rechnerisch rund 74 % weniger Inhalt. Lighthouse zählt zusätzlich Übertragungsdetails und das WordPress-Favicon samt Redirect; daher liegt sein Gesamtvolumen etwas höher.

Die Startseite lädt eine Theme-CSS-Datei und ein kleines Theme-Script. Es wurden keine externen Fonts oder Drittanbieter-Netzwerkanfragen beobachtet. Beim Beitrag kommt WordPress’ `comment-reply.min.js` hinzu. Die Editor-CSS-Datei mit 1.209 Byte wird separat gebaut und gehört nicht zum Startseiten-Payload.

WordPress liefert 17.999 Byte Inline-CSS innerhalb des HTML: 13.250 Byte globale Styles, 4.274 Byte Block-Styles und 475 Byte für Emoji-/Bildregeln. Diese Styles gehören zur WordPress-Inhaltsdarstellung. Ein pauschales Entfernen wäre ohne Tests verschiedener Blöcke nicht begründet.

Encore verwendet den Produktionsbuild mit Dateihashes und deaktivierten Produktions-Source-Maps; Tailwind läuft über PostCSS. Lighthouse meldet dennoch rund 3 KB zusätzliches CSS-Minifizierungspotenzial. Der berechnete LCP-/FCP-Gewinn beträgt dabei 0 ms. Das ist hier keine dringende Optimierung.

## Zusätzliche Browsermessungen

Playwright führte jeweils drei Navigationen mit frischen Browserkontexten aus. Hier gibt es **keine Netzwerk- oder CPU-Drosselung**; die mobilen Werte stammen aus einer Viewport-/Touch-Emulation mit 390 × 844 px, Desktop aus 1440 × 1000 px.

| Seite | Desktop-LCP, Median | Mobil-LCP, Median | CLS in diesen Läufen |
| --- | ---: | ---: | ---: |
| Startseite | 116 ms | 112 ms | 0 |
| Beitrag | 96 ms | 92 ms | 0 |
| Suche | 116 ms | 120 ms | 0 |

Keine JavaScript-Ausnahmen oder fehlgeschlagenen Netzwerktransporte wurden erfasst. Einzelne Long Tasks dauerten 50–61 ms. Beim mobilen Menü wurden Öffnen und Escape ausgeführt; die erfassten Interaktionsereignisse lagen bei 16 ms. Das sind einzelne Laborinteraktionen, keine belastbare INP-Bewertung über reale Besuche.

## Was vor einem Hosting-Test sinnvoll ist

1. **HTTP-Kompression konfigurieren.** Die Testantworten für HTML, CSS und JavaScript enthalten trotz `Accept-Encoding: gzip, br` kein `Content-Encoding`. Das oben berechnete Potenzial betrifft die Datenmenge; eine reale Zeitersparnis muss anschließend gemessen werden.
2. **Browser-Caching für Dateien mit Hash aktivieren.** Die Theme-Assets enthalten keine `Cache-Control`- oder `Expires`-Header. Für unveränderliche Dateien mit Inhalts-Hash bietet sich auf dem Produktionswebserver beispielsweise `Cache-Control: public, max-age=31536000, immutable` an. Das verbessert vor allem Folgebesuche; Lighthouse nennt dafür rund 17 KB. Diese Regel nur auf passende statische Assets anwenden. [Quelle: Chrome, Cache lifetimes](https://developer.chrome.com/docs/performance/insights/cache)
3. **Mit der tatsächlichen Produktionskonfiguration wiederholen.** Die Fixture nutzt `WP_DEBUG=true`, einen SymPress-Kernel mit Debug-Modus und `twig.cache: false`. Ein Produktionsprofil mit aktiviertem Twig-Cache ist separat zu messen; seine Einsparung wurde hier nicht ermittelt. Twig kann kompilierte Templates im Dateisystem zwischenspeichern. [Quelle: Symfony Twig configuration](https://symfony.com/doc/current/reference/configuration/twig.html)
4. **Repräsentative Inhalte und Hosting einbeziehen.** Bilderreiche Beiträge, zusätzliche Plugins, reale Datenmengen, TLS und der spätere PHP-FPM-/Webserver-Betrieb fehlen in diesem Test. Die Demo verwendet einen typografischen „Aa.“-Cover als LCP-Element und ist entsprechend günstig für Ladezeitmessungen.

Die gemessenen Werte geben aktuell keinen Anlass, Twig, Tailwind oder Encore aus Performancegründen zu ersetzen. Auch zusätzliche Preconnects oder ein Umbau des CSS-Ladens sind durch diese Messung nicht begründet: Lighthouse findet keine geeigneten zusätzlichen Origins und berechnet für die CSS-Abhängigkeit keinen LCP-Gewinn.

## Umgebung, Dateien und Wiederholung

Lokale WordPress-7.1.2-Fixture mit PHP 8.5.9, MariaDB 11.8, SymPress und Twig; PHP-CLI-Entwicklungsserver auf Loopback. Die ausgelesene CLI-Konfiguration aktiviert OPcache einschließlich CLI; dessen Effekt wurde nicht separat profiliert. Der Rechner wurde für die Messung nicht exklusiv reserviert. Es gab keine Runtime-Optimierungen, Inhaltsänderungen oder Cache-Purges während dieses Audits. Die Testseite bleibt gestartet.

Die Chrome-DevTools-MCP-Verbindung war wegen eines fehlenden X-Displays nicht nutzbar. Die Messungen liefen deshalb mit headless Chromium, Playwright und Lighthouse. Es liegen keine realen Nutzerdaten und keine separate DevTools-Trace-Datei vor.

- [Lighthouse Mobil, Lauf 1 inklusive beobachtetem Layout Shift](mobile-1.report.html)
- [Lighthouse Mobil, Lauf 2](mobile-2.report.html) und [Lauf 3](mobile-3.report.html)
- [Lighthouse Desktop, Lauf 1](desktop-1.report.html), [Lauf 2](desktop-2.report.html), [Lauf 3](desktop-3.report.html)
- [Rohdaten aller Messungen](measurements.json); zu jedem HTML-Bericht liegt außerdem eine gleichnamige `.report.json` vor.
- [Ausführbares Messskript](../../../scripts/performance-audit.mjs)

Aus dem Theme-Verzeichnis bei laufender Fixture und vorhandenen npm-/Playwright-Abhängigkeiten:

```bash
PERF_OUTPUT=docs/performance/retest-01 node scripts/performance-audit.mjs
```

Das Skript beschränkt Zieladressen auf localhost/127.0.0.1 und lädt bei Bedarf Lighthouse 13.5.0 über den npx-Cache. Ein neuer Ausgabeordner bewahrt die bisherigen Messungen. Die Ausführung wurde erfolgreich abgeschlossen; zusätzlich wurde die JavaScript-Syntax geprüft. Build-, PHP- und Funktionstests wurden für diesen reinen Audit nicht erneut ausgeführt.
