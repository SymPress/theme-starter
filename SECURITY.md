# Security policy

Security fixes target the latest stable release. Upgrade older 0.x installations
using [UPGRADE-0.2.md](UPGRADE-0.2.md).

Report vulnerabilities privately to the maintainer at
brian.schaeffner@sympress.de. Include affected versions, reproduction steps and
impact. Avoid public issues containing exploitable details or private site data.

The site owns authentication, WordPress updates, Composer dependencies and
deployment. Twig templates and Composer build configuration are trusted code;
the NoRaw lint rule is not a sandbox. Only native WordPress-rendered HTML should
cross the trusted-markup boundary. Test fixtures contain public credentials and
must never be deployed to a production site.
