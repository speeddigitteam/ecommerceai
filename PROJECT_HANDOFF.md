# Project Transfer and Codex Handoff Guide

এই ফাইলটি project অন্য PC-তে নেওয়া, একই data দিয়ে চালানো এবং অন্য ChatGPT/Codex account থেকে কাজ চালিয়ে যাওয়ার জন্য স্থায়ী handoff note। নতুন PC বা নতুন AI session-এ প্রথমে `AGENTS.md`, `.ai/rules/index.md` এবং এই ফাইলটি পড়তে হবে।

## 1. Project-এর বর্তমান environment

- Framework: Laravel 13
- PHP: 8.3
- Database: MySQL
- Frontend: Vite, Tailwind CSS এবং Alpine.js
- Recommended local stack: Laragon
- Node.js: বর্তমান machine-এ 22.x
- Composer: 2.x
- Database name: `practice` (নতুন PC-তে প্রয়োজনমতো বদলানো যাবে)

## 2. পুরোনো PC থেকে যা backup করতে হবে

Project folder-এর সঙ্গে নিচের জিনিসগুলো অবশ্যই রাখতে হবে:

1. সম্পূর্ণ source code, বিশেষ করে `app`, `bootstrap`, `config`, `database`, `public`, `resources`, `routes`, `tests`, `.ai`, `AGENTS.md`, `composer.json`, `composer.lock`, `package.json` এবং `package-lock.json`।
2. MySQL database-এর একটি SQL export। শুধু migration নিলে customer, order, product এবং setting data আসবে না।
3. User-uploaded files থাকলে `storage/app/public` folder।
4. বর্তমান `.env` file নিরাপদ private backup-এ রাখুন। এটি public repository, public drive বা chat-এ upload করবেন না।

`vendor` এবং `node_modules` transfer করা জরুরি নয়; নতুন PC-তে lock file অনুযায়ী আবার install করাই ভালো। `public/build` পুনরায় তৈরি করা যাবে।

## 3. Database export

Laragon/phpMyAdmin থেকে `practice` database export করে একটি `.sql` file রাখুন। Command line ব্যবহার করলে:

```powershell
mysqldump -u root -p practice > practice-backup.sql
```

Password না থাকলে `-p` বাদ দেওয়া যায়। Backup file-এ customer information থাকতে পারে, তাই এটিও private রাখুন।

## 4. নতুন PC-তে installation

Laragon, PHP 8.3, Composer 2 এবং Node.js 22 install করার পর project folder web root-এ রাখুন। তারপর project directory থেকে:

```powershell
composer install
npm install
Copy-Item .env.example .env
php artisan key:generate
```

এরপর `.env`-এ নতুন PC-এর database connection দিন:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=practice
DB_USERNAME=root
DB_PASSWORD=
```

যদি পুরোনো `.env` নিরাপদে transfer করেন, সেটি ব্যবহার করা যাবে। Existing encrypted data/cookies প্রয়োজন হলে পুরোনো `APP_KEY` সংরক্ষণ করুন; existing `.env` ব্যবহার করলে আবার `php artisan key:generate` চালাবেন না।

## 5. Database ও uploaded files restore

প্রথমে MySQL-এ `practice` database তৈরি করে SQL backup import করুন। SQL backup না থাকলে শুধু fresh structure/data তৈরির জন্য:

```powershell
php artisan migrate --seed
```

SQL backup import করলে সাধারণত আবার migration চালানোর দরকার নেই; তবে নতুন migration যোগ হয়ে থাকলে চালান:

```powershell
php artisan migrate
```

পুরোনো `storage/app/public` restore করার পর public link তৈরি করুন:

```powershell
php artisan storage:link
```

## 6. Final setup এবং verification

```powershell
php artisan optimize:clear
npm run build
php artisan test --compact
```

Development server চালাতে:

```powershell
composer run dev
```

অথবা Laragon virtual host ব্যবহার করুন। `APP_URL` বাস্তব local URL অনুযায়ী update করুন। Frontend পরিবর্তন দেখা না গেলে `npm run dev` অথবা `npm run build` চালিয়ে browser-এ hard refresh দিন।

## 7. Transfer checklist

- [ ] Source code transfer হয়েছে
- [ ] `.ai` এবং `AGENTS.md` আছে
- [ ] `composer.lock` এবং `package-lock.json` আছে
- [ ] SQL database backup import হয়েছে
- [ ] `storage/app/public` uploads restore হয়েছে
- [ ] `.env` তৈরি/নিরাপদে restore হয়েছে
- [ ] `APP_KEY` বিষয়ে সঠিক সিদ্ধান্ত নেওয়া হয়েছে
- [ ] `composer install` এবং `npm install` সফল
- [ ] `php artisan storage:link` চালানো হয়েছে
- [ ] `npm run build` সফল
- [ ] Relevant tests pass করেছে

## 8. অন্য ChatGPT/Codex account ব্যবহার

Subscription, account settings এবং original sidebar chat history project folder-এর অংশ নয়। অন্য account দিয়ে sign in করলে project files ব্যবহার করে কাজ করা যাবে, কিন্তু পুরোনো chats স্বয়ংক্রিয়ভাবে সেই account-এ দেখা যাবে না।

Official OpenAI process:

1. পুরোনো account-এ Profile menu → Settings → Data controls → Export data থেকে export request করুন।
2. Email/SMS-এ export এলে ZIP download করুন; download link সাধারণত 24 ঘণ্টা কার্যকর থাকে।
3. ZIP extract করে `conversations.json` খুঁজুন।
4. নতুন personal account-এ একটি নতুন chat খুলে JSON file upload করুন, যাতে পুরোনো conversation reference হিসেবে ব্যবহার করা যায়।

এটি full transfer নয়: accounts merge হবে না, old chats আলাদা sidebar conversations হিসেবে ফিরে আসবে না, এবং Plus/Pro subscription, memories, settings, GPTs বা workspace access transfer হবে না। Export file-এ sensitive information থাকতে পারে—public repository বা অন্য কারও সঙ্গে share করবেন না।

Official references:

- https://help.openai.com/en/articles/7260999-how-do-i-export-my-data
- https://help.openai.com/en/articles/9106926-transferring-conversations-from-1-chatgpt-account-to-another-chatgpt-account

## 9. নতুন Codex session-এর recommended opening message

নতুন PC/account-এ project folder open করে এই message দিন:

> Read `AGENTS.md`, `.ai/rules/index.md`, all matching project rules, and `PROJECT_HANDOFF.md` before making changes. Inspect the existing implementation and continue using its Laravel conventions. Do not change dependencies without approval. Run focused PHPUnit tests, Laravel Pint, and the frontend build after relevant changes.

Chat export না থাকলেও এই project-local files নতুন session-কে coding conventions এবং setup context দেবে। Feature-specific সিদ্ধান্ত ভবিষ্যতে `.ai/rules`-এ লিখে রাখলে account বা chat বদলালেও project knowledge টিকে থাকবে।
