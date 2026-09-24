# Update notes

## 0. Login form was posting to the wrong page (this was the real cause of the "stuck on index.php" bug)
`login.php`'s form had `action="index.php"`, so every login attempt actually
submitted the username/password to `index.php` — a completely static page
with no PHP logic at all, which just re-displayed itself no matter what was
posted to it. No session was ever created. Then, every protected page
(`views/punto_de_venta.php`, etc.) checked for a session, found none, and
sent the user back to that same static `index.php`, creating the endless loop.

Fixed:
- `login.php`'s form now posts to itself (`action="login.php"`), so it
  actually runs the authentication logic.
- The root `index.php` is no longer a static mock page. It's now a small
  router: if you're logged in it sends you to `views/dashboard.php`, if not
  it sends you to `login.php`.
- Every "no session" redirect across the app (`views/*.php`) now points to
  `login.php` instead of the old static page.
- Every "Log Out" link now points to the actual `logout.php` script (which
  destroys the session) instead of just navigating to a page while leaving
  the session active. Three pages (`ingredientes.php`, `recetas.php`,
  `usuarios.php`) had no Log Out link at all — added.

## 1. Login fixed
`login.php` had a bracket mismatch that made the "invalid credentials" error
branch unreachable, and `views/ingredientes.php` contained a hard-coded
testing bypass that silently created a fake session for anyone who opened
that page directly (without ever logging in). Both are fixed:

- `login.php` now correctly rejects any username/password that doesn't match
  a real row in the `usuarios` table.
- `views/ingredientes.php` now redirects to the login page like every other
  screen if there is no active session.
- `views/recetas.php` had no login check at all and now has one too.

### Required accounts
Run the new migration once against your database to create the three
official accounts:

```
mysql -u root -p pupuseria_test < migrations/02_apply_seed_users.sql
```

| Username      | Password | Role          |
|---------------|----------|---------------|
| mary_admin    | mary123  | Administrator |
| chepe_cajero  | mary123  | Cashier       |
| ana_cajera    | mary123  | Cashier       |

Passwords are stored as bcrypt hashes (the same algorithm `login.php` already
verifies with `password_verify()`), so they work out of the box. To remove
them later, run `migrations/02_undo_seed_users.sql`.

> Note: any other username/password combination that was working before
> because of the old bugs will **no longer work**. Only accounts that exist
> in the `usuarios` table (with a matching bcrypt password hash) can log in.

## 2. Ingredient units
The "Unit" dropdown on the Ingredients page now offers a proper set of
weight, volume and count units instead of just Pounds/Kilograms/Units/Liters:

- Weight: Grams (g), Kilograms (kg), Pounds (lb), Ounces (oz)
- Volume: Milliliters (ml), Liters (L)
- Count: Units

## 3. Current user shown everywhere
Every screen (Dashboard, Point of Sale, Products, Ingredients, Recipes,
Reports, Users) now shows "Signed in as: <name> (<role>)" so it's always
clear which account is operating the system.

Sales records already stored which user made each sale; now:

- **Reports** ("Processed By" column) show the seller's name *and* role
  (Administrator or Cashier).
- **PDF tickets** print "Served by: <name> (<role>)" instead of just the name.
- The **Dashboard**'s recent-transactions table also shows the role next to
  the name.

## Not changed (in case you want it later)
User management (`views/usuarios.php`) is still open to *any* logged-in
account, not just Administrators. If you'd like it restricted to admins
only, that's a small follow-up change.
