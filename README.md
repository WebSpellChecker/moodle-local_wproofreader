# WProofreader plugin for Moodle

WProofreader is a real-time spelling, grammar, and style checker for editor
content in Moodle. It works inside the Atto editor (Moodle 4.5 LTS), TinyMCE 6,
and plain HTML textareas, with no per-editor configuration required.

## Features

* Real-time spell and grammar checking as users type
* Multilanguage support, with the language list refreshed from the WebSpellChecker service
* Free version out of the box, limited to 10,000 words a day. Purchase through Moodle Marketplace to extend the usage
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

Extended grammar checking, the AI writing assistant, lifted daily usage limits,
access to custom dictionaries, and style-guide functionality require a paid licence
obtained through Moodle Marketplace. After purchase, paste the licence key from
your Marketplace receipt into *Site administration > Plugins > Local plugins >
WProofreader > License key*.

## Supported Moodle versions

* Moodle 4.5 LTS (2024100700) and any later release up to the 5.2 branch
* Atto support is only relevant on 4.5. From 5.0 onward Atto is removed and the
  Atto integration becomes a no-op automatically.
* On Moodle 5.x the plugin installs under `public/local/wproofreader/` (Moodle's
  5.0+ web-root split). On 4.5 it installs under `local/wproofreader/`.

## Supported editors

| Editor          | How it is detected                                              |
|-----------------|------------------------------------------------------------------|
| Atto            | `.editor_atto_content[contenteditable="true"]` in the main DOM   |
| TinyMCE 6       | `window.tinymce.editors`, hooked on each editor's `init` event  |
| Plain textareas | Bundle's `autoSearch` plus a small list of code-field exclusions |

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
| Show badge button    | enabled     | Toggles the orange WProofreader badge on or off. |
| Badge placement      | Page corner | Where to render the badge. "Page corner" shows a single floating badge in the bottom-right corner of the page that controls all editors. "Per editor" attaches a separate badge to each editor. |

### Proofreading features

| Setting              | Default   | Maps to (bundle keys) |
|----------------------|-----------|------------------------|
| Spelling             | enabled   | `spellingSuggestions` |
| Grammar              | enabled   | `enableGrammar` + `grammarSuggestions` |
| Style                | enabled   | `styleGuideSuggestions` |
| Autocorrect          | disabled  | `autocorrect` |
| Text autocomplete    | disabled  | `autocomplete` |
| AI writing assistant | enabled   | `aiWritingAssistant` (paid only) |

### Spelling ignore options

| Setting                          | Default   | Maps to (bundle keys)       |
|----------------------------------|-----------|-----------------------------|
| Ignore all caps words            | enabled   | `ignoreAllCapsWords`        |
| Ignore domain names              | enabled   | `ignoreDomainNames`         |
| Ignore words with mixed case     | enabled   | `ignoreWordsWithMixedCases` |
| Ignore words with numbers        | enabled   | `ignoreWordsWithNumbers`    |

### Where to enable WProofreader

| Setting                          | Default  | Maps to |
|----------------------------------|----------|---------|
| Enable in courses and activities | enabled  | `CONTEXT_COURSE`, `CONTEXT_MODULE` (excluding quiz / feedback) |
| Enable in course categories      | enabled  | `CONTEXT_COURSECAT` |
| Enable on user pages             | enabled  | `CONTEXT_USER` (profile, dashboard, personal blog) |
| Enable in quiz attempts          | disabled | `mod_quiz`, `mod_questionnaire`, `mod_feedback` |
| Enable on system pages           | disabled | `CONTEXT_SYSTEM` pages such as the global calendar, global search, and system tag browsing. The site front page itself is treated as a course and falls under *Enable in courses and activities*. |
| Enable in site administration    | disabled | Pages with a `pagetype` that starts with `admin-` |

## How it works

* On each page render, the plugin checks the per-context toggles, emits an inline bootstrap script with the proofreader config, and queues an AMD module to start.
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
