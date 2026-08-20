# Changelog

All notable changes to the WProofreader plugin for Moodle are documented here.

## 2.0.0 (2026-08-20)

* Widened official Moodle support to 4.1 through the 5.2 branch (previously 4.5 LTS through 5.2).
* Added support for the legacy TinyMCE editor (Moodle 4.1's "TinyMCE HTML editor (legacy)").
* Removed the global badge option.
* Surfaced the WProofreader bundle's own runtime warnings (unsupported language, service unavailable) and the WebSpellChecker service's HTTP error responses (400/403/404/409/500/503) as a non-blocking, per-editor note instead of leaving them silent or shown only in the browser console.

## 1.0.10 (2026-08-11)

* Added warning messages for an exceeded usage limit and an invalid license key on the settings page.

## 1.0.9 (2026-07-14)

* Changed the integration appType sent to the WebSpellChecker service to `wpr_moodle`.

## 1.0.8 (2026-06-26)

* Updated plugin versioning.

## 1.0.7 (2026-06-26)

* Fixed multiple WProofreader instances initializing on a single TinyMCE editor.
* Renamed the `customer_id` setting to `service_id` and the `slang` setting to `lang` to align with the service configuration keys.

## 1.0.6 (2026-05-22)

* Updated plugin versioning.

## 1.0.5 (2026-05-22)

* Improved spelling in the README and language files.

## 1.0.4 (2026-05-22)

* Updated plugin versioning.

## 1.0.3 (2026-05-22)

* Corrected spelling in the README and language file, and tidied the documentation.

## 1.0.2 (2026-05-21)

* Added "Auto" as the default proofreading language.
* Removed the user-facing settings entry from the badge dropdown.
* Polished README wording and naming.

## 1.0.1 (2026-05-20)

* Added a null privacy provider so the privacy registry stops warning.
* Relabeled the "enable on system pages" toggle to match its actual scope.
* Detected the quiz module via pagetype when `$PAGE->cm` is null.
* Stopped the bundle from persisting per-user toggles in localStorage.

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
