# Abide Roadmap

This is where Abide is going. It is not a promise or a timeline —  
it is a direction. Priorities shift, phases get reordered, and some  
items may never ship if they turn out not to belong here.

If something on this list matters to you, the best way to move it  
forward is to open an issue, start a conversation, or contribute.

---

## What's next

### Permission tier management
The current three tiers (Node, Backbone, Headend) are hardcoded constants.  
The next architectural phase replaces them with a database-driven tier system —  
owner-definable labels, slugs, and bit values managed via the admin page.  
The bit flag model stays. The names become yours.

### Usernames
Registration and profile will gain an optional username field — a unique,  
human-readable handle that replaces the email address in display contexts  
like the nav and profile page. Set at registration, editable in profile,  
falls back to the email prefix if not set.

### Nav improvements
The navigation panel is functional but has room to grow. Planned work  
includes displaying the signed-in user's name and permission tier,  
and a review of interaction patterns based on real-world use.

---

## On the radar

These are real intentions, not wishlist items. They will get built when  
the time is right and the design is clear.

- **CSRF protection** on auth forms — currently a documented v1 gap
- **Rate limiting** on login and password reset endpoints — also a documented gap
- **Multi-method support** on the donate page — GitHub Sponsors and others
  alongside Cash App
- **Email change flow** — request, verify new address, confirm before switching
- **Admin user management** — view registered users, adjust permissions,
  disable accounts
- **`auth.css` sync automation** — eliminate the manual copy step from
  `modules/auth/` to `public/assets/`

---

## What will not be added to core

Abide has a primary guard against bloat: if a feature doesn't clearly belong  
in `config.php` (behavior) or `style.css` (appearance), it probably doesn't  
belong in core. The following will not be added regardless of demand:

- A routing layer or front controller
- A templating engine
- A build step or asset pipeline
- An ORM or query builder
- External font loading
- Any feature that makes a fresh install heavier without a clear, unavoidable reason

These belong in downstream projects built on Abide, not in Abide itself.

---

## Versioning

Abide does not use version numbers yet. When the permission tier system  
and username support ship, that will be a reasonable point to cut v1.0  
and begin keeping a changelog.
