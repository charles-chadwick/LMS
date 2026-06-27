# PrimeVue → shadcn-vue Migration — Design

**Date:** 2026-06-27

## Goal

Replace PrimeVue entirely with shadcn-vue across the application while
preserving the current purple/stone visual identity and all existing behavior.
The PHP/Inertia contract (page component names and props) stays unchanged so the
existing feature tests remain valid.

## Decisions

- **WYSIWYG:** Drop PrimeVue `Editor`; wrap the already-installed Quill in a
  custom `QuillEditor.vue`. No TipTap.
- **Theme:** Keep the purple/stone palette — map shadcn tokens so primary=purple
  and neutrals=stone.
- **Auth pages:** Restyle the Breeze auth pages + `GuestLayout` to shadcn.

## Toolchain

- Add: `reka-ui`, `class-variance-authority`, `clsx`, `tailwind-merge`,
  `lucide-vue-next`, `tw-animate-css`, `vue-sonner`. Keep `quill`.
- Remove: `primevue`, `@primevue/themes`, `primeicons`.
- Add `resources/js/lib/utils.js` (`cn()`), `components.json`, and `@` alias for
  `@/components` and `@/lib` in `vite.config.js` + `jsconfig.json`.
- `app.css`: add shadcn CSS variables mapped to purple/stone, plus an
  `@theme inline` block exposing `bg-background`, `bg-primary`, `text-foreground`,
  etc. Keep the existing `--color-primary-*/accent-*/darker-*` tokens so
  partially-migrated screens never break.
- `app.js`: remove the PrimeVue plugin, ConfirmationService, ToastService,
  Tooltip directive, theme preset, and primeicons import. Keep Ziggy.

## shadcn components to generate (`resources/js/components/ui/*`)

`button`, `card`, `select`, `input`, `label`, `textarea`, `badge` (replaces
Tag), `sheet` (replaces mobile Drawer), `alert-dialog` (replaces ConfirmDialog),
`sonner` (flash toasts), `checkbox` (auth).

## Custom shared components

- **`QuillEditor.vue`** — wraps Quill, `v-model` HTML; used by `Courses/Form`
  and `Pages/Form`.
- **`ConfirmAction.vue`** — declarative `AlertDialog` wrapper: trigger slot +
  `title`/`description` props, emits `confirm`. Replaces imperative `useConfirm`.

## Files rewritten (PrimeVue → shadcn + lucide icons)

`Layouts/AppLayout.vue` (Drawer→Sheet; mount Sonner + render flash messages),
`Pages/Courses/Index.vue`, `Pages/Courses/Show.vue`, `Pages/Courses/Form.vue`,
`Pages/Pages/Form.vue`, `Pages/Pages/Show.vue`, `Pages/Dashboard.vue`,
`Components/ActionButtons.vue`, `Components/UserList.vue`. All `pi pi-*` icons →
`lucide-vue-next`.

## Auth pages

Convert `Auth/{Login,ForgotPassword,ResetPassword,ConfirmPassword,VerifyEmail}`
and `GuestLayout.vue` to shadcn `Input`/`Label`/`Button`/`Checkbox`. Delete the
now-unused Breeze `Components/*` (TextInput, InputLabel, PrimaryButton,
SecondaryButton, DangerButton, Checkbox, InputError, NavLink,
ResponsiveNavLink, Dropdown, DropdownLink, Modal, ApplicationLogo).

## Testing & staging

Inertia component names/props unchanged → the 49 PHP feature tests stay valid.
Run `npm run build` + full suite after each stage:

1. Toolchain setup + ui components
2. Shared `QuillEditor` / `ConfirmAction` + `AppLayout`
3. Course screens
4. Page screens + Dashboard
5. Auth pages + delete Breeze components
6. Remove PrimeVue deps + final build/test

## Notes

- vue-sonner shows success/flash messages (not currently displayed anywhere —
  minor UX gain).
- The unused Tooltip directive is dropped.
