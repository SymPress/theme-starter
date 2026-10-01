# Meta Description ergänzt

Der Lighthouse-Hinweis **„Document does not have a meta description“ ist auf der
Test-Startseite behoben**. Der erneute Lighthouse-13.5.0-Lauf bewertet den
Meta-Description-Check als bestanden und SEO mit **100/100 statt 91/100**.

[Lighthouse-Bericht öffnen](home.report.html) · [JSON-Rohdaten](home.report.json)

## Verhalten

Die Ausgabe läuft über den nativen `wp_head`-Hook im
[WordPress-Adapter des Themes](../../../src/WordPress/Theme.php). Die Startseite
enthält jetzt genau einmal:

```html
<meta name="description" content="Notizen, Ideen und Geschichten, die bleiben.">
```

Die Blog-Startseite übernimmt den Untertitel aus **Einstellungen → Allgemein**.
Beiträge und Seiten verwenden ihren Auszug, ersatzweise den von WordPress aus
dem Inhalt erzeugten Auszug. Eine statische Startseite und eine separate
Beitragsseite verwenden ihren eigenen Seitenauszug. Kategorie-, Schlagwort- und
Taxonomiearchive übernehmen ihre hinterlegte Beschreibung. WordPress stellt
manuelle und automatisch erzeugte Auszüge über
[`get_the_excerpt()`](https://developer.wordpress.org/reference/functions/get_the_excerpt/)
bereit.

Das Theme entfernt HTML, normalisiert Leerraum, begrenzt die Beschreibung auf
30 Wörter und maskiert sie für das HTML-Attribut. Die Wortgrenze ist ein
Theme-Standard. Leere Quellen erzeugen kein leeres Meta-Tag. Passwortgeschützte
Inhalte, Vorschauen, Suche und 404-Seiten sind von dieser automatischen Ausgabe
ausgeschlossen.

Bei erkannten Versionskonstanten von Yoast SEO, Rank Math, All in One SEO,
SEOPress oder The SEO Framework übernimmt der jeweilige Anbieter die Metadaten.
Andere Integrationen können den Fallback über
`sympress_starter/meta_description_enabled` abschalten; über
`sympress_starter/meta_description` lässt sich der Text anpassen. Die Verwendung
steht in der [Theme-Dokumentation](../../../README.md).

## Nachweise

- `composer qa`: 19 PHP-Dateien syntaktisch geprüft, 14 Twig-Templates geparst,
  48 bestehende Assertions bestanden.
- [Metadaten-Integrationstest](../../../tests/site/meta-description-check.php):
  elf Assertions bestanden, darunter statische Startseite, Text-Escaping,
  leerer Text, deaktivierter Fallback und Passwortschutz. Die SEO-Erkennung
  wurde mit einer nur im Testprozess gesetzten Yoast-Konstante geprüft;
  echte SEO-Plugins wurden nicht installiert oder gemeinsam ausgeführt.
- `npm run test:browser`: elf Tests bestanden. Der neue Test prüft genau eine
  eigene Beschreibung auf Startseite, Beitrag und Inhaltsseite sowie das
  Ausbleiben eines Tags auf geschützten Seiten, Suche und 404.
- Ein gezielter Lighthouse-SEO-Lauf ohne Browser-Erweiterungen: 100/100,
  Meta Description bestanden, keine Laufzeitfehler oder Warnungen.

Assets und Layout wurden für diese Änderung nicht verändert; ein erneuter
Asset-Build oder kompletter Performance-Benchmark war deshalb nicht erforderlich.
Der SEO-Score belegt die automatisierten Lighthouse-Prüfungen, keine Platzierung
in Suchmaschinen. Die lokale Testseite bleibt unter `http://127.0.0.1:18943/`
erreichbar.
