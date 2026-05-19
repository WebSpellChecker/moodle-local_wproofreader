# WProofreader plugin for Moodle

WProofreader is a real-time spelling, grammar, and style checker for editor
content in Moodle. It works inside the Atto editor (Moodle 4.5 LTS), TinyMCE 6,
and plain HTML textareas, with no per-editor configuration required.

## Features

* Real-time spell and grammar checking as users type
* Multilanguage support, with the language list refreshed from the WebSpellChecker service
* Free trial out of the box. Purchase through Moodle Marketplace to unlock grammar checking and the AI writing assistant
* Granular per-context toggles (courses, categories, user profiles, quizzes, site administration, public pages)

## External service

WProofreader sends editor text to the WebSpellChecker service at
`svc.webspellchecker.net` for analysis. Real-time spell, grammar, and style
checking, the language list shown in the settings page, and the AI writing
assistant all depend on this service. Without network access to the service,
the plugin loads no proofreading UI and editors keep their default Moodle
behaviour.

The plugin ships with a built-in free trial that enables spelling and style
checks. Grammar checking and the AI writing assistant require a paid license
obtained through Moodle Marketplace. After purchase, paste the license key
from your Marketplace receipt into *Site administration > Plugins > Local
plugins > WProofreader > License key*.

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
| License key          | empty       | Paste the license key delivered with your Moodle Marketplace purchase. Leave empty to use the free trial. |
| Default language     | English     | Initial proofreading language. The dropdown is refreshed live from the service when the settings page loads. |
| Show badge button    | enabled     | Toggles the orange WProofreader badge on or off. |
| Badge placement      | Page corner | Where to render the badge. "Page corner" shows a single floating badge in the bottom-right corner of the page that controls all editors. "Per editor" attaches a separate badge to each editor. |

### Proofreading features

| Setting              | Default   | Maps to (bundle keys) |
|----------------------|-----------|------------------------|
| Spelling             | enabled   | `spellingSuggestions` |
| Style                | enabled   | `styleGuideSuggestions` |
| Autocorrect          | disabled  | `autocorrect` |
| Text autocomplete    | disabled  | `autocomplete` |

### Spelling ignore options

| Setting                          | Default   | Maps to (bundle keys)       |
|----------------------------------|-----------|-----------------------------|
| Ignore all caps words            | enabled   | `ignoreAllCapsWords`        |
| Ignore domain names              | enabled   | `ignoreDomainNames`         |
| Ignore words with mixed case     | enabled   | `ignoreWordsWithMixedCases` |
| Ignore words with numbers        | enabled   | `ignoreWordsWithNumbers`    |

### Advanced features

| Setting              | Default   | Maps to (bundle keys) |
|----------------------|-----------|------------------------|
| Grammar              | enabled   | `enableGrammar` + `grammarSuggestions` (forced off on free trial) |
| AI writing assistant | enabled   | `aiWritingAssistant` (forced off on free trial) |

### Where to enable WProofreader

| Setting                          | Default  | Maps to |
|----------------------------------|----------|---------|
| Enable in courses and activities | enabled  | `CONTEXT_COURSE`, `CONTEXT_MODULE` (excluding quiz / feedback) |
| Enable in course categories      | enabled  | `CONTEXT_COURSECAT` |
| Enable on user pages             | enabled  | `CONTEXT_USER` (profile, dashboard, personal blog) |
| Enable in quiz attempts          | enabled  | `mod_quiz`, `mod_questionnaire`, `mod_feedback` |
| Enable on public pages           | disabled | Front page and unclassified `CONTEXT_SYSTEM` pages |
| Enable in site administration    | disabled | Pages with a `pagetype` that starts with `admin-` |

## How it works

1. On every page request, the `\core\hook\output\before_standard_top_of_body_html_generation`
   hook fires. The plugin's callback runs `page_injector::inject()`, which:
   * Checks `context_evaluator::should_enable()` against the current page's
     context and the plugin settings.
   * Builds the JS config via `config_builder::build()`.
   * Calls `$PAGE->requires->js_call_amd('local_wproofreader/init', 'init', [$config])`.
2. The AMD `init` module writes `window.WEBSPELLCHECKER_CONFIG`, then dynamically
   loads `https://svc.webspellchecker.net/spellcheck31/wscbundle/wscbundle.js`.
3. Once the bundle is ready, three environment helpers fan out:
   * `environment_textarea` adds Moodle-specific exclusion classes so the bundle's
     `autoSearch` skips code / CSS fields.
   * `environment_atto` adds a `MutationObserver` safety net for Atto editors
     created after the initial autoSearch pass.
   * `environment_tinymce` waits for `window.tinymce`, then attaches a
     WProofreader instance to each TinyMCE editor's iframe via
     `editor.on('init', ...)`.
4. On the plugin settings page, `settings_page.js` asks the bundle for its
   supported languages and posts the result back through the
   `local_wproofreader_save_languages` external function. The cached list is
   then served by `language_catalog::options()` on the next settings page render.

## Privacy

The plugin does not store personal data inside Moodle. Editor text is sent to
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

Visit [webspellchecker.com/contact-us](https://webspellchecker.com/contact-us/)
for technical assistance.

## License

GPL v3 or later. See `license.txt`.
