# Repository security

Main changes require a pull request and all four QA checks. No second human
approval is required for this single-maintainer organization. Release tags are
immutable. Secret scanning, push protection and Dependabot security updates are
enabled; Composer, npm and GitHub Actions also receive weekly update proposals.

The dependency canary pins a reviewed commit reachable on the workflows main
branch. A manual compatibility run is evidence of that run, not proof that a
future scheduled run has happened.

Use the maintainer's verified GitHub no-reply identity for new public commits.
Existing release history is preserved: replacing old author metadata would change
commit IDs and invalidate published signatures and references. The earlier email
addresses in that history have not been removed. Never commit tokens, private
configuration, database fixtures or generated site contents.
