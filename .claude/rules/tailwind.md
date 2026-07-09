---
paths:
  - "resources/css/**"
  - "resources/views/**/*.blade.php"
---

# Tailwind CSS v4

This project uses Tailwind v4.1+. **Do not use v3 patterns or deprecated utilities.**

## Key v4 Differences

- Config is CSS-first using `@theme` directive (no `tailwind.config.js`)
- Import via `@import "tailwindcss"` (not `@tailwind` directives)
- `corePlugins` is not supported

```css
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
```

## Important Modifier

The `!important` modifier now goes at the **end**: `bg-red-500!` (not `!bg-red-500`)

## Deprecated Utilities (v4 replacements)

| Deprecated | Replacement |
|------------|-------------|
| `bg-opacity-*` | `bg-black/50` (slash syntax) |
| `text-opacity-*` | `text-black/50` |
| `border-opacity-*` | `border-black/50` |
| `flex-shrink-*` | `shrink-*` |
| `flex-grow-*` | `grow-*` |
| `overflow-ellipsis` | `text-ellipsis` |
| `decoration-slice` | `box-decoration-slice` |
| `decoration-clone` | `box-decoration-clone` |

## Project Conventions

- Use `gap` utilities for spacing in flex/grid (not margins)
- Support dark mode with `dark:` variants where existing components do
- Extract repeated patterns into TPL components
- Check existing conventions before adding new utility patterns

```html
<!-- Use gap, not margins -->
<div class="flex gap-4">
  <div>One</div>
  <div>Two</div>
</div>
```
