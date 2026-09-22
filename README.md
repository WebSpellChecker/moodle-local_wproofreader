# WProofreader plugin for Moodle

WProofreader is a real-time spelling, grammar, and style checker for editor
content in Moodle. It works inside the Atto editor (Moodle 4.1 through 4.5 LTS),
TinyMCE 6, the legacy TinyMCE editor (Moodle 4.1 only), and plain HTML
textareas, with no per-editor configuration required.

## Features

* Real-time spell and grammar checking as users type
* Multilanguage support, with the language list refreshed from the WebSpellChecker service
* Free version out of the box, limited to 10,000 words a day. Purchase through Moodle Marketplace to extend the usage and unlock additional features
* Granular per-context toggles (courses, categories, user profiles, quizzes, site administration, public pages)

## External service

WProofreader sends editor text to the WebSpellChecker service at
`svc.webspellchecker.net` for analysis. Real-time spell, grammar, and style
checking, the language list shown in the settings page, and the AI writing
assistant all depend on this service. Without network access to the service,
the plugin loads no proofreading UI and editors keep their default Moodle
behaviour.

## Free and paid versions

The plugin ships with a built-in free version that enables basic spelling and style
checks, with a usage cap of 10,000 words a day across the site.

Enhanced grammar checking, the AI writing assistant, lifted daily usage limits,
access to custom dictionaries, and style-guide functionality require a paid licence
obtained through Moodle Marketplace. After purchase, paste the licence key from
your Marketplace receipt into *Site administration > Plugins > Local plugins >
WProofreader > License key*.

## Supported Moodle versions

* Moodle 4.1 (2022112800) through the 5.2 branch
* Atto is relevant on 4.1 through 4.5 LTS. From 5.0 onward Atto is removed from
  core and the Atto integration becomes a no-op automatically.
* On Moodle 5.x the plugin installs under `public/local/wproofreader/` (Moodle's
  5.0+ web-root split). On 4.1 through 4.5 it installs under `local/wproofreader/`.

## Supported editors

| Editor              | How it is detected                                                  |
|---------------------|-----------------------------------------------------------------------|
| Atto                | `.editor_atto_content[contenteditable="true"]` in the main DOM       |
| TinyMCE 6           | `window.tinymce.editors`, hooked on each editor's `init` event      |
| Legacy TinyMCE (4.1 only) | `window.tinymce.editors` (same global as TinyMCE 6, told apart by API shape), hooked on each editor's `onInit` event |
| Plain textareas     | Attached explicitly (autoSearch disabled for textareas); a small list of code-field exclusions is skipped, along with any textarea already managed by a TinyMCE instance |

## Installation

1. Copy this directory into your Moodle local-plugins folder:
   * Moodle 4.5: `local/wproofreader/`
   * Moodle 5.x: `public/local/wproofreader/`
2. Sign in as a site administrator. Moodle will detect the new plugin and walk
   you through the upgrade screen.
3. Open *Site administration > Plugins > Local plugins > WProofreader* and
   configure the settings.

## Settings reference

All settings live under *Site administration > Plugins > Local plugins > WProofreader*.

### General

| Setting              | Default     | Description |
|----------------------|-------------|-------------|
| License key          | empty       | Paste the license key delivered with your Moodle Marketplace purchase. Leave empty to use the free version. |
| Default language     | Auto        | Initial proofreading language. *Auto* lets the WebSpellChecker service detect the language from the content being checked; pick a specific language to pin it. The dropdown is refreshed live from the service when the settings page loads. |
| Show badge button    | enabled     | Toggles the orange WProofreader badge on or off. A separate badge attaches to each editor on the page. |

### Feature access rules

Each rule reads as a sentence: *Student is allowed to check grammar in quiz
attempts*. Complete all three dropdowns and save the page to add one.

Rules only grant. A user gets a feature wherever at least one rule allows it,
and a site with no rules runs no proofreading at all. A fresh install starts
with a single rule, *Everyone is allowed to do everything everywhere*; a site
upgrading from 2.0.0 keeps the reach its old toggles gave it.

| Dropdown  | Values |
|-----------|--------|
| Role      | *Everyone*, plus every role defined on the site. |
| Feature   | *do everything*, check spelling, check grammar, apply style suggestions, autocorrect text, autocomplete text, use the AI writing assistant. |
| Site part | *everywhere*, in courses and activities, in quiz attempts, in course categories, on user pages, on system pages, in site administration. |

The rules added so far are listed underneath the dropdowns, numbered in the
order they were added, each with a button that removes it. Adding and removing
change the page only: nothing is stored until *Save changes* is pressed.

Roles are matched against the page being viewed. In *courses and activities*
and *quiz attempts* that means the role the user holds in that course; in the
other site parts, where no course role is in scope, it means any role the user
holds anywhere on the site. Site administrators are matched the same way as
anyone else, so a site whose rules name only specific roles gives an
administrator nothing until a rule covers a role they hold.

The AI writing assistant stays off on the free version whatever the rules say.

### Spelling ignore options

| Setting                          | Default   | Maps to (bundle keys)       |
|----------------------------------|-----------|-----------------------------|
| Ignore all caps words            | enabled   | `ignoreAllCapsWords`        |
| Ignore domain names              | enabled   | `ignoreDomainNames`         |
| Ignore words with mixed case     | enabled   | `ignoreWordsWithMixedCases` |
| Ignore words with numbers        | enabled   | `ignoreWordsWithNumbers`    |

## How it works

* On each page render, the plugin checks the `local/wproofreader:use` capability, maps the page to a site part, resolves the roles the user holds there, and asks the access rules which features they are allowed. If the answer is none, nothing is injected. Otherwise it emits an inline bootstrap script with the proofreader config and queues an AMD module to start.
* The AMD module loads the WProofreader JS library from `svc.webspellchecker.net` and attaches it to Atto, TinyMCE, and plain HTML textareas on the page.
* The settings page refreshes the supported-language list from the service when opened, and caches it server-side for the next render.

## Privacy

The plugin doesn't store personal data inside Moodle. Editor text is sent to
the WebSpellChecker service for analysis. See `classes/privacy/provider.php`
for the metadata declaration shown to users via the privacy API.

## Development

The AMD source files live in `amd/src/`. After editing them, rebuild with:

```
cd /path/to/moodle
npx grunt amd --root=local/wproofreader
```

The pre-built `amd/build/` files are shipped with the plugin so it can be
installed on production sites without a Node.js toolchain.

## Support

Report bugs or request features on the [GitHub issue tracker](https://github.com/WebSpellChecker/moodle-local_wproofreader/issues).

For general technical assistance, visit [webspellchecker.com/contact-us](https://webspellchecker.com/contact-us/).

## License

GPL v3 or later. See `license.txt`.
