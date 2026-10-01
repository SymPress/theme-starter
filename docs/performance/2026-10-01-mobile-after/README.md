# Mobile Ladezeit: CSS-Anfrage entfernt

Das kleine Theme-Stylesheet wird jetzt direkt im HTML-Head ausgegeben. Dadurch
entfallen seine zusätzliche Netzwerkanfrage und die Abhängigkeit HTML → CSS.
Lighthouse meldet nach der Änderung **keine renderblockierende Anfrage** mehr;
auch der Hinweis zur kritischen Anfragekette ist bestanden. Der mobile LCP sinkt
in der Vergleichsmessung von **0,903 auf 0,776 Sekunden** im Median, etwa 14 %.

## Vorher und nachher

Am 1. Oktober 2026 wurden unmittelbar vor und nach der Änderung jeweils drei
mobile und drei Desktop-Läufe mit Lighthouse 13.5.0 ausgeführt. Beide Serien
verwenden frische Chromium-Profile ohne Erweiterungen und identische simulierte
Netzwerk-/CPU-Einstellungen. Der lokale PHP-Testserver und die Inhalte blieben
unverändert. Alle zwölf Läufe endeten ohne Laufzeitfehler oder Warnung.

| Messwert | Vorher | Nachher |
| --- | ---: | ---: |
| Mobiler Performance-Score, alle Läufe | 100/100 | 100/100 |
| Mobiler LCP, Median | 903 ms | 776 ms |
| Mobiler FCP, Median | 753 ms | 755 ms |
| Mobile TBT, alle Läufe | 0 ms | 0 ms |
| Desktop-LCP, Median | 243 ms | 226 ms |
| Desktop-FCP, Median | 203 ms | 212 ms |
| Externe Theme-CSS-Anfragen je Startseitenaufruf | 1 | 0 |
| Gesamte Übertragung laut Lighthouse | 15.936 Byte | 15.167 Byte |
| Startseiten-HTML, gzip-Antwortkörper | 7.008 Byte | 10.419 Byte |

Der Vorteil liegt beim LCP und beim Wegfall einer Netzwerkanfrage. Eine allgemeine
Beschleunigung aller Kennzahlen wäre durch diese Zahlen nicht belegt: FCP ist
etwa gleich beziehungsweise auf Desktop geringfügig höher. Ohne simulierte
Netzwerkverzögerung lag der mobile Playwright-LCP sogar bei 136 statt 116 ms.
Die kleine lokale Stichprobe erlaubt keine Aussage über reale Nutzerdaten.

## Zu deinen 260 ms Total Blocking Time

Diese 260 ms waren im sauberen Browserprofil bereits **vor** der Änderung nicht
reproduzierbar. Daher ist kein durch die CSS-Änderung verursachter Rückgang von
260 auf 0 ms nachgewiesen.

Dein Screenshot zeigt JavaScript unter `chrome-extension://…`, darunter ein
Autofill-Overlay mit rund 559 KiB. Das sind Skripte einer Browser-Erweiterung.
Das Theme-JavaScript bleibt unverändert bei 761 Byte unkomprimiert beziehungsweise
423 Byte gzip. Browser-Erweiterungen können Lighthouse-Messungen beeinflussen.
Eine genaue Zuordnung der 260 ms zu dieser Erweiterung würde aber den vollständigen
Bericht beziehungsweise Trace deines Laufs benötigen.
[Quelle: Lighthouse-Dokumentation zu Messschwankungen](https://github.com/GoogleChrome/lighthouse/blob/main/docs/variability.md)

TBT erfasst blockierende Anteile langer Aufgaben im betreffenden Ladezeitfenster.
0 ms bedeutet deshalb nicht, dass der Browser keinerlei CPU-Arbeit oder lange
Aufgaben ausgeführt hat.
[Quelle: Chrome, Total Blocking Time](https://developer.chrome.com/docs/lighthouse/performance/lighthouse-total-blocking-time)

Für einen vergleichbaren eigenen Test: ein frisches Chrome-Profil ohne
Erweiterungen öffnen, dieselbe URL mobil dreimal messen und den Median vergleichen.
Inkognito genügt nur, wenn dort keine Erweiterungen freigegeben sind. An deinem
Browserprofil oder seinen Erweiterungen wurde nichts verändert.

## Implementierung und Abwägung

Der [Theme-Adapter](../../../src/WordPress/Theme.php) nutzt die vorhandene
`useInlineFilter()`-Funktion von SymPress Assets. Sie bleibt Teil der normalen
WordPress-Asset-Verarbeitung. Es gibt keinen zusätzlichen JavaScript-Lademechanismus.
Die gesamten 12.853 Byte des kleinen Produktionsstylesheets stehen vor dem ersten
Rendern bereit, einschließlich der Regeln für weitere Bildschirmgrößen und
Interaktionen.

Die Inline-Ausgabe ist auf Dateien mit Produktions-Hash und maximal 16 KiB
beschränkt. Dateien mit `url()` oder `@import` bleiben extern, damit relative
Ressourcenpfade ihre Bedeutung behalten. SymPress prüft außerdem erlaubte lokale
Pfade und Lesbarkeit. Editor-Styles und JavaScript werden wie bisher separat
geladen.

Der Preis: Inline-CSS kann nicht unabhängig vom HTML gecacht werden und wird bei
jedem Seitenwechsel erneut übertragen. Hier wächst der komprimierte HTML-Körper
um rund 3,4 KB. Für Websites mit vielen Folgebesuchen oder einer CSP, die
Inline-Styles verbietet, lässt sich die Änderung abschalten:

```php
add_filter('sympress_starter/inline_styles', '__return_false');
```

Dann gelten wieder die vorhandenen langfristigen Cache-Header der CSS-Datei.
Die Entscheidung bevorzugt den mobilen Erstaufruf dieses kleinen Starter-Themes;
bei einem deutlich erweiterten Design muss erneut gemessen werden.
[Quelle: web.dev, Inlining und Cache-Abwägung](https://web.dev/articles/extract-critical-css)

## Validierung und Berichte

- `composer qa` bestanden: 19 PHP-Dateien, 14 Twig-Templates, 48 Assertions.
- Alle elf Playwright-Tests bestanden. Die Startseite lädt bei 320, 768, 1024
  und 1440 px genau ein Inline-Theme-Stylesheet und keine externe Theme-CSS-Datei.
  Darstellung ohne JavaScript, Menü, Suche, Passwortschutz, Metadaten und Editor
  bleiben geprüft.
- Der Opt-out wurde separat mit dem echten WordPress-StyleHandler geprüft und
  gibt wieder einen externen Stylesheet-Link aus.
- Je Messserie zusätzlich 80 HTTP-Stichproben und 18 Browsernavigationen.
- Nachher weiterhin SEO 100/100; einzelne mobile Läufe zeigen wie zuvor einen
  kleinen CLS von 0,049. Eine CLS-Verbesserung wird nicht behauptet.

Die CSS-Datei selbst wurde nicht verändert; ein erneuter Asset-Build war für
diese PHP-seitige Ausgabeänderung nicht erforderlich. Die Testsite bleibt unter
`http://127.0.0.1:18943/` erreichbar.

| Berichte | Mobil | Desktop |
| --- | --- | --- |
| Vorher | [1](../2026-10-01-mobile-before/mobile-1.report.html), [2](../2026-10-01-mobile-before/mobile-2.report.html), [3](../2026-10-01-mobile-before/mobile-3.report.html) | [1](../2026-10-01-mobile-before/desktop-1.report.html), [2](../2026-10-01-mobile-before/desktop-2.report.html), [3](../2026-10-01-mobile-before/desktop-3.report.html) |
| Nachher | [1](mobile-1.report.html), [2](mobile-2.report.html), [3](mobile-3.report.html) | [1](desktop-1.report.html), [2](desktop-2.report.html), [3](desktop-3.report.html) |

[Rohdaten vorher](../2026-10-01-mobile-before/measurements.json) ·
[Rohdaten nachher](measurements.json) ·
[Messskript](../../../scripts/performance-audit.mjs)
