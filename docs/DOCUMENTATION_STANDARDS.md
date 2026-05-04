# Documentation Standards Policy

Version: 1.0
Date: 2026-05-03

Purpose
-------
This policy defines the standards, templates, governance, and technical requirements for user-facing product documentation and the `Help / Docs` pages for the AIS system. It establishes a single source of truth, accessibility and quality expectations, and the process for maintaining documentation across releases and locales.

Scope
-----
Applies to all documentation hosted in the AIS repository and published to the client portal, including:
- User guides and how‑tos
- Troubleshooting playbooks
- Release notes and known issues
- Security & privacy summaries
- Operational runbooks used by support and administrators

Standards Summary
-----------------
1. Single Source of Truth
   - Store canonical content in Markdown under `/docs` or a documented content-repo.
   - Render Markdown into the client portal (`app/Views/client_portal/help.php`) during deploy.

2. Article Template (every page)
   - Title
   - Purpose (one-sentence)
   - Audience / Roles
   - Prerequisites
   - Step-by-step procedure
   - Expected result
   - Common issues & fixes
   - Related articles
   - Owner & reviewers
   - Version (semver) + Last updated (YYYY-MM-DD)

3. Writing & Tone
   - Task-focused, outcome-first headings.
   - Use plain, professional English; avoid marketing language.
   - One action per step; use numbered steps for procedures.
   - Provide copyable examples (code blocks, CLI, API samples).
   - Add alt text for every image.

4. Structure & Navigation
   - Use H1/H2/H3 consistently; H1 only for page title.
   - Include a short summary at the top (1–2 lines).
   - Add `Related articles` and `See also` lists.
   - Provide anchored headings (permalinks) for deep linking.

5. Accessibility
   - Meet WCAG 2.1 AA baseline.
   - Ensure headings are semantic, images have alt text, links are descriptive, and color contrast meets guidelines.
   - Keyboard navigable accordions and search functionality.

6. Search & Metadata
   - Add frontmatter/yaml metadata: `title`, `description`, `tags`, `roles`, `locale`, `owner`, `version`, `last_updated`.
   - Index headings, body text, code blocks, and metadata for search.

7. Localization
   - Keep locale-specific files under `/docs/{locale}/...`.
   - Translate top-priority pages first (login, core workflows, troubleshooting).
   - Include `last_translated` in metadata.

8. Versioning & Releases
   - Use `major.minor` versioning for docs aligned with product releases.
   - Archive prior major versions in `/docs/archives/v{major}/...`.
   - Keep a `Release Notes` page per release with date and impact summary.

9. Governance & Roles
   - Owner: single named owner per article responsible for accuracy.
   - Reviewer: at least one technical reviewer (peer) and one UX/editor reviewer for major pages.
   - Approval: sign-off required for security-sensitive and admin pages.
   - Review cadence: core workflows quarterly; other pages biannually.

10. Quality & CI Checks (recommended)
    - Lint Markdown (heading order, trailing whitespace).
    - Validate links and images (no broken links).
    - Check frontmatter presence and required fields.
    - Run accessibility automated checks on rendered HTML.

11. Analytics & Feedback
    - Add search analytics: top queries, no-result queries.
    - Track page views and time-on-page to identify stale content.
    - In-page feedback: `Was this helpful?` + short comment box.
    - Create action items for pages with low helpfulness score.

12. Security & Privacy
    - Do not include sensitive data or PII in docs or examples.
    - Include a `Data Handling` summary for pages that describe data flows.
    - Link to the Privacy Policy and Security contacts.

13. Templates & Reusable Components
    - Provide standard notice blocks for `Info`, `Warning`, and `Important`.
    - Standardize screenshot captions and image naming: `{page}-{section}-v{version}.png`.

14. Operational Runbooks
    - Maintain runbooks for support staff separate from end-user docs but following the same template.
    - Mark runbooks as `internal` in metadata and ensure access controls.

15. Implementation Options
    - Preferred: Markdown + static renderer into existing `help.php`.
    - Alternative: Lightweight CMS if non-dev authors need direct editing access.

16. Onboarding & Rollout Priorities
    - Immediately publish:
      - Login / Access guide
      - Top 10 core workflows
      - Troubleshooting top 20 incidents
      - Support & escalation guide
    - Next phase: Role-based help, localization, deep API reference.

17. Change Log & Audit
    - Each doc must include `version` and `last_updated` metadata.
    - Maintain a central `docs/CHANGELOG.md` listing major doc changes.

How to Publish (developer steps)
--------------------------------
1. Author or update Markdown under `/docs`.
2. Add/Update frontmatter metadata (`owner`, `version`, `tags`, `roles`, `locale`, `last_updated`).
3. Run local preview and CI checks (lint, link-check, accessibility).
4. Merge to `main` branch; CI builds the site and deploys rendered HTML into `app/Views/client_portal/help.php` or the client portal content area.
5. Notify owners/reviewers and publish a `docs` release note.

Appendix — Quick Article Checklist (before publishing)
-----------------------------------------------------
- [ ] Title and Purpose present
- [ ] Audience and Prerequisites filled
- [ ] Step-by-step validated and copy-tested
- [ ] Screenshots present where necessary (with alt text)
- [ ] Owner and Reviewer noted
- [ ] Version and Last updated set
- [ ] Links validated
- [ ] Accessibility automated checks passed
- [ ] Analytics hook enabled

Contact & Ownership
-------------------
Documentation team: docs@ais.local (replace with actual alias)
For security issues related to documentation, contact: security@ais.local


---
Saved as `docs/DOCUMENTATION_STANDARDS.md`.
