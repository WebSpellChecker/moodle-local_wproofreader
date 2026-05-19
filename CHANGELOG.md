# Changelog

All notable changes to the WProofreader plugin for Moodle are documented here.

## 1.0.0 (2026-05-19)

Initial release.

* Single local plugin (`local_wproofreader`).
* Compatible with Moodle 4.5 LTS through the 5.2 branch.
* Editor coverage: Atto (4.5 only), TinyMCE 6, and plain HTML textareas. No per-editor configuration required.
* Hook-based asset injection through `\core\hook\output\before_standard_top_of_body_html_generation`, with a legacy `before_standard_top_of_body_html` callback as a fallback for sites that disable the hook system.
* Settings panel covering general options (license key, default language, badge button, badge placement), proofreading features (spelling, style, autocorrect, text autocomplete), advanced features (grammar, AI writing assistant), spelling ignore options, and per-context toggles (courses, categories, user pages, quizzes, public pages, site administration).
* Dynamic language list fetched from the WebSpellChecker service on the settings page and cached server-side.
* Privacy API metadata declaring text content, language, IP address, and user agent transmitted to the WebSpellChecker service.
* Paid Moodle Marketplace listing model: an empty license key falls back to a bundled trial that enables spelling and style checks; the Marketplace-issued license key unlocks grammar checking and the AI writing assistant.
