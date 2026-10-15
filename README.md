# 🏡 A New Home

**A New Home** is a moderated property marketplace for homes for sale and rent across Europe, built with Laravel, Inertia.js and React.
Guests browse listings and agencies freely; signed-in users unlock listing details, bookmarks, saved searches and real-time chat with owners. Owners and agencies submit listings, and every new or edited listing is reviewed by an admin before it goes public.

🌐 **Live demo:** [mintcream-koala-283697.hostingersite.com](https://mintcream-koala-283697.hostingersite.com/)

---

## 🔑 Demo Accounts

All demo accounts use the password **`password`**.

| Role | Email |
|---|---|
| 🛡️ Admin | `admin@newbeginning.test` |
| 🏢 Agency | `agency@newbeginning.test` |
| 👤 Regular User | `seller@newbeginning.test` |

> The demo is shared, so other visitors may have changed its data.

---

## ✨ Features

### 👀 Guest
- 🏠 Landing page with search, latest listings and highlights
- 📜 Browse listings with search, sort, filters (type, property type, price, beds, baths, area, amenities, city) and pagination
- 🏢 Agencies directory and agency pages
- ✉️ Contact form and static pages (About, Privacy, Terms, Cookies)

### 👤 Regular User
- 🔐 Authentication: Sign up, Sign in, Forgot/Reset password
- 🖼️ Full listing details with photo gallery, similar listings and a mortgage helper
- ⭐ Bookmark listings, with a "No longer available" group for sold, rented and expired ones
- 🔔 Saved searches with a daily email digest
- 💬 Real-time chat with listing owners, with unread counts
- 🚩 Report listings and chat messages
- 📝 Create, edit and delete listings with optimized image processing using **Intervention Image**
- ✅ Mark listings sold / rented, reopen and renew them
- ⚙️ Account settings: change password, delete own account

### 🏢 Agency
- 🧾 Agency registration and profile setup, verified by an admin
- ✏️ Profile changes on an approved agency go through admin review
- 🏘️ Agency listings that inherit the agency's contact details
- 💬 Inquiries through listing-tied chat, bookmarks and saved searches

### 🛡️ Admin
- 📊 Dashboard with moderation and platform stats
- ✅ Approve / reject listings, agencies and agency profile changes
- 🚩 Review reported listings (dismiss / take down) and reported messages (resolve / suspend)
- 👥 Manage users: suspend, reactivate, delete
- 📥 Contact messages inbox

### ⚙️ Platform
- 🔄 Every new or edited listing returns to `pending_review`
- ⌛ Approved listings expire after 90 days, with a reminder email 7 days before
- 🗑️ Rejected and taken-down listings are soft deleted and purged after 7 days
- 🙈 Suspending an account hides its listings, agency and chat without changing any data
- 📧 Twelve email events (approvals, rejections, expiry, digest, suspension, account deletion, …)
- 🔎 Server-rendered SEO head, `robots.txt` and `sitemap.xml`

---

## 🛠️ Tech Stack
- **Framework**: [Laravel 13](https://laravel.com/)
- **Frontend**: [Inertia.js 3](https://inertiajs.com/) + [React 19](https://react.dev/) + [TypeScript](https://www.typescriptlang.org/)
- **Styling**: [Tailwind CSS 4](https://tailwindcss.com/) + [shadcn/ui](https://ui.shadcn.com/)
- **Database**: [MySQL](https://www.mysql.com/)
- **Real-time**: [Pusher Channels](https://pusher.com/channels) + [Laravel Echo](https://laravel.com/docs/broadcasting) ([Reverb](https://reverb.laravel.com/) also supported)
- **Image Handling**: [Intervention Image](https://image.intervention.io/) (resize, compression, `.webp` conversion)
- **Testing**: [PHPUnit](https://phpunit.de/)

---

## 🚀 Getting Started

### 1. Clone the Repository
```bash
git clone https://github.com/bojan-ski/laravel-react-inertia-cc-no-code--a-new-home.git
cd laravel-react-inertia-cc-no-code--a-new-home
```

### 2. Install Dependencies
```bash
composer install
npm install
```

### 3. Environment Setup - .env
```bash
cp .env.example .env
php artisan key:generate
```

```env
DB_CONNECTION=mysql
DB_HOST=
DB_PORT=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=
```

### 4. Run Migrations & Seed Database
```bash
php artisan migrate --seed
```

For the full demo data set (the accounts above, listings in every state, chats and reports), seed the review scenario instead. It runs in the `local`, `staging` and `testing` environments only:
```bash
php artisan migrate:fresh
php artisan db:seed --class=ReviewScenarioSeeder
```

### 5. Link Storage
```bash
php artisan storage:link
```

### 6. Start Servers
```bash
composer run dev
```

### 7. Scheduled Tasks
Expiry reminders, the saved-search digest and the purge of rejected listings run through the scheduler:
```bash
php artisan schedule:work
```

---

## 🧪 Testing
```bash
php artisan test
```

Cross-feature integration tests and sanity checks live under `tests/Feature/System`:
```bash
php artisan test tests/Feature/System
```

---

## 📸 Photo Credits
All photos are from [Unsplash](https://unsplash.com), used under the [Unsplash License](https://unsplash.com/license). The photographers are credited in [`public/images/CREDITS.md`](public/images/CREDITS.md).

---

## 👨‍💻 Author
Developed with ❤️ by BPdevelopment (bojan-ski)
