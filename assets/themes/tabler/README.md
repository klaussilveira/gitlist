# Tabler theme

A GitList theme built on [Tabler](https://tabler.io) 1.7.1, with light and dark
color modes. Enable it in `config/config.yml`:

```yaml
parameters:
  theme: tabler
```

Then rebuild the assets as usual (`composer build-assets`, or `make build`).

## No npm required

Tabler is vendored prebuilt in `assets/vendor/tabler`, taken verbatim from the
published `@tabler/core` package (source maps removed). The GitList specific
styles in `assets/scss` only use Tabler's CSS custom properties, so they compile
with the dart-sass binary pinned by `symfonycasts/sass-bundle`. The asset mapper
then publishes everything to `public/assets/theme/`.

To upgrade Tabler, bump `TABLER_VERSION` in `bin/vendor-assets` and run it.

## Packaging

`make build` compiles the assets of every theme in `assets/themes`, not only
the active one, so a packaged GitList can switch themes by editing
`config/config.yml` and deleting `var/cache/prod`, with no build step. The
Kernel registers each theme's `assets` folder as `themes/<name>` and its Sass
entry point, which must be named `scss/<name>.scss` because Sass names compiled
files after the source file alone. A new theme also needs its
`themes/<name>/js/main.js` entrypoint listed in `importmap.php`.

## Color modes

- With no stored choice, the theme follows the browser's `prefers-color-scheme`
  and updates live when it changes.
- The sun/moon button in the navbar switches between light and dark.
- The Appearance panel (brush button) offers the Tabler theme settings: color
  scheme, accent color, font, gray base, corner radius, container width and
  navbar behavior. Choices are stored in `localStorage` under `tabler-*` keys,
  the same keys Tabler's own `tabler-theme.js` reads before the first paint.
- The Ace and CodeMirror viewers switch to the Tomorrow Night Eighties theme in
  dark mode.

## Icons and illustrations

`templates/icons.html.twig` is a sprite with only the
[Tabler Icons](https://tabler.io/icons) the templates use. To add one, copy the
inner paths of its outline SVG into a new `<symbol id="tabler-NAME">` (filled
variants use `tabler-NAME-filled`) and render it with
`{{ ui.icon('NAME') }}` from `macros.html.twig`.

`templates/illustrations` holds Tabler's free auto-dark illustrations, inlined
so they follow the color mode. They are used by empty states and error pages.

## Error pages

`templates/bundles/TwigBundle/Exception/error.html.twig` renders 404 and other
errors in production. Twig's `default_path` points at the active theme, so any
theme can ship such overrides.
