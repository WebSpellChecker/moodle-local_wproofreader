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

### Where WProofreader is available

One matrix decides both halves of the question: the areas of the site run down
the side, every site role runs across the top, and a ticked cell means users
holding that role keep proofreading in that area.

A fresh install starts with *Quiz attempts*, *System pages* and *Site
administration* switched off and everything else on, which is where the
per-area toggles of earlier versions started. Sites upgrading keep whatever
they had configured.

|                        | Manager | Course creator | Teacher | Student | Authenticated user | Auth. user on site home |
|------------------------|---------|----------------|---------|---------|--------------------|-------------------------|
| Courses and activities | ticked  | ticked         | ticked  | ticked  | ticked             | ticked                  |
| Quiz attempts          | ticked  | ticked         | ticked  | **unticked** | ticked        | ticked                  |
| Course categories      | ticked  | ticked         | ticked  | ticked  | ticked             | &mdash;                 |
| User pages             | ticked  | ticked         | ticked  | ticked  | ticked             | &mdash;                 |
| System pages           | ticked  | ticked         | ticked  | ticked  | ticked             | &mdash;                 |
| Site administration    | ticked  | ticked         | &mdash; | &mdash; | &mdash;            | &mdash;                 |

The example above shows the common request once *Quiz attempts* has been
switched on: students write quiz answers unaided while teachers keep
proofreading as they mark, and everyone keeps it in forums, assignments and
everywhere else.

Three rules make the matrix predictable:

* **Every role the user holds has to be ticked.** Unticking one role is enough
  to withhold proofreading from the people who hold it. It has to work this way
  because everyone who is logged in also holds *Authenticated user*, so a rule
  of "any ticked role grants it" would make every other column meaningless.
* **Unticking a whole row switches that area off for everyone**, site
  administrators included. This replaces the per-area on/off checkboxes that
  earlier versions had, and it is stored as an off row rather than as a list of
  today's roles, so the area stays off for roles created later.
* **A cell drawn as &mdash; is one Moodle would never reach**, so there is
  nothing to configure. Site administration only offers the roles holding
  `moodle/site:configview`, which out of the box is Manager and Course creator,
  and the front page role is only offered in the course areas, since it applies
  on the site home alone. The dashes are worked out from the site's own role
  definitions, so granting that capability to a custom role adds its checkbox.

Note that *System pages* keeps every column. Those are pages with system-level
context that ordinary users reach all the time, such as the global calendar,
global search and tag browsing, so restricting a role there is meaningful.

Site administrators keep proofreading whatever the role columns say, exactly as
they pass every capability check, so a role restriction cannot be reproduced
from an admin account. Use a real teacher or student login to check one.

#### Which roles a cell matches

| Area | A cell matches |
|------|----------------|
| Courses and activities, Quiz attempts | the role the user holds **in the course being viewed** |
| Course categories, User pages, System pages, Site administration | **any role the user holds anywhere** on the site |

The split is not arbitrary. Role assignments are context scoped, so a user who
is a student inside courses holds no student role on their own dashboard, in a
category, or on a system page, where they are simply an authenticated user. If
those rows matched the page context, their Student and Teacher cells could
never take effect. Matching site-wide roles there means unticking Student for
*User pages* really does switch proofreading off on a student's dashboard.

One consequence worth knowing: on those four rows, someone who teaches one
course and studies another counts as both, so an unticked Student cell reaches
them too.

#### Per-course exceptions

The matrix is site-wide. For a single course or category, use the
`local/wproofreader:use` capability in *Course > Participants > Permissions*
and set it to **Prohibit** for the role.

Prohibit rather than Prevent, and the reason is worth knowing. The capability
is granted to the authenticated user role at system level so that proofreading
works on pages outside courses. When Moodle aggregates permissions, an Allow
held through any one of the user's roles wins, so setting Student to *Prevent*
or *Not set* leaves that grant in place and changes nothing. Prohibit is the
only capability value that overrides another role's Allow.

## How it works

* On each page render, the plugin resolves which area the page belongs to, checks the `local/wproofreader:use` capability in the page context and the area's row in the availability matrix, then emits an inline bootstrap script with the proofreader config and queues an AMD module to start.
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

PHP unit tests live in `tests/`, and cover the availability gates, the
settings matrix and the install defaults. From an initialised Moodle checkout:

```
php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit --testsuite local_wproofreader_testsuite
```

Behat features live in `tests/behat/`, and drive the matrix through real page
requests: they set a cell, visit a course, activity or admin page as a real
user, and assert whether the plugin injected itself. They need no JavaScript
and never call the proofreading service, so they run on the default profile.

```
php admin/tool/behat/cli/init.php
vendor/bin/behat --config /path/to/behatdata/behatrun/behat/behat.yml \
  --tags @local_wproofreader
```

`tests/behat/behat_local_wproofreader.php` adds the steps those features use:
*the WProofreader availability matrix is cleared*, *WProofreader is withheld
from the "student" role in the "quiz" area*, *the WProofreader "courses" area
is switched off*, and the assertions *WProofreader should be active* and
*should not be active*.

## Support

Report bugs or request features on the [GitHub issue tracker](https://github.com/WebSpellChecker/moodle-local_wproofreader/issues).

For general technical assistance, visit [webspellchecker.com/contact-us](https://webspellchecker.com/contact-us/).

## License

GPL v3 or later. See `license.txt`.
