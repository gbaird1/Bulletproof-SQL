# Bulletproof-SQL
A simple, secure, and pragmatic database abstraction class built on PHP mysqli.  * Features prepared statements, an escape hatch for complex queries, lockdown mode,  and 15+ years of battlefield testing.
# SQLQuery.php

**A bulletproof SQL abstraction layer — battle-tested for 15+ years.**

No ORM. No magic. Just clean, secure, pragmatic database code.

---

## Why Another Database Class?

Because most of them are over-engineered or under-secured.

This one sits in the middle:
- **Prepared statements everywhere** (no SQL injection)
- **Simple interface** (`select`, `insert`, `update`, `delete`)
- **Escape hatch** (`select2()` for complex queries)
- **Lockdown mode** (global read-only)
- **No dependencies** (just PHP 7.0+ and mysqli)

It has run clan sites, forums, marketplaces, archives, and surveillance dashboards for nearly two decades. **It still works.**

---

## Installation

Copy `SQLQuery.php` into your project. That's it.
