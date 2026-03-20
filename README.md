# Abide

> This scaffold exists because how you build matters as much as what you build — and because most projects don't fail from bad code, they fail from drift. Structure loosens, documentation lags, decisions lose their context, and six months later nobody can remember why anything works the way it does. This is a counterargument to that.
>
> It is a disciplined starting point for flat PHP projects — built for developers returning to the craft, learning the craft, or building alongside AI tools. Opinionated enough to keep you on a solid path, flexible enough to get out of your way. Consistent conventions, honest documentation, and a modular structure that starts small and earns complexity only when complexity is warranted.
>
> The right way to do each thing is obvious. Everything else stays out of the way.

---

## What Abide Is

A flat PHP project scaffold with a strong point of view:

- No framework overhead
- No build step
- Clean URLs by convention
- Framed layout — header locked, footer locked, content in between
- CSS custom properties for all visual configuration
- A single `config.php` for behavioral configuration
- Auth as an optional drop-in module
- AI-legible by design — consistent patterns, honest comments

## What Abide Is Not

- A framework
- An MVC system
- A CMS
- WordPress

---

## Structure

```
abide/
├── config.php              # User-facing configuration (copy from config.example.php)
├── config.example.php      # Template — safe to commit
│
├── public/                 # Web root — point your server here
│   ├── index.php           # Home page
│   ├── style.css           # CSS custom properties + core layout
│   ├── .htaccess           # Routing, bot rules, auto-prepend
│   │
│   ├── pages/              # Your pages go here
│   │   └── example.php     # Annotated starter page
│   │
│   └── assets/             # Static files
│       ├── img/
│       └── fonts/          # Local fonts only
│
├── core/                   # Framework internals — do not edit
│   ├── init.php            # Auto-prepended bootstrap
│   ├── db.php              # PDO singleton
│   ├── header.php          # Layout: header
│   ├── footer.php          # Layout: footer
│   └── nav.php             # Navigation
│
├── modules/                # Optional drop-in layers
│   └── auth/               # Full auth stack (registration, login, email verify, password reset)
│       └── ...
│
├── setup/                  # Guided setup wizard — disable after first run
│   └── index.php
│
└── README.md               # You are here
```

---

## Getting Started

Documentation in progress. Setup wizard coming soon.

---

## License

GPL v3 — free for open source use. Commercial licenses available.
See `LICENSE` for details.

---

## Contributing

Abide is open to contributions that align with its philosophy: clarity over cleverness, conventions over configuration, no bloat. Read the mission statement above before opening a pull request.

A Contributor License Agreement (CLA) is required for all contributions.
