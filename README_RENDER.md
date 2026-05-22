# Deploying to Render — Step-by-Step Guide

## Step 1: Get a Free MySQL Database (Clever Cloud)

1. Go to https://www.clever-cloud.com and create a free account
2. Click **Create → an add-on → MySQL**
3. Choose the **DEV** plan (free, 5 MB, enough for testing)
4. After creation, go to the add-on dashboard and copy:
   - **Host** (MYSQL_ADDON_HOST)
   - **Port** (MYSQL_ADDON_PORT)
   - **User** (MYSQL_ADDON_USER)
   - **Password** (MYSQL_ADDON_PASSWORD)
   - **Database name** (MYSQL_ADDON_DB)
5. Open **phpMyAdmin** (link in Clever Cloud dashboard), paste the contents of `setup.sql` in the SQL tab and run it.

> **Alternative free databases:**
> - https://planetscale.com (free tier, MySQL-compatible)
> - https://www.freemysqlhosting.net
> - https://db4free.net

---

## Step 2: Deploy on Render

1. Push your project to a GitHub/GitLab repo
2. Go to https://render.com → **New → Web Service**
3. Connect your repo
4. Set:
   - **Environment:** PHP
   - **Build Command:** *(leave empty)*
   - **Start Command:** *(leave empty)*
5. Under **Environment Variables**, add:

| Key         | Value (from Clever Cloud)         |
|-------------|-----------------------------------|
| DB_HOST     | your-mysql-host.cleverapps.io     |
| DB_USER     | your_db_user                      |
| DB_PASSWORD | your_db_password                  |
| DB_NAME     | amazon  (or your db name)         |
| DB_PORT     | 3306                              |

6. Click **Deploy**

---

## Important Notes

### Image Uploads on Render
Render's filesystem is **ephemeral** — uploaded images are deleted on every redeploy.
For permanent image storage, use a cloud storage service:
- **Cloudinary** (free tier: 25 GB) — https://cloudinary.com
- **Backblaze B2** (free tier: 10 GB) — https://backblaze.com

For now, images will work until the next deployment.

### Admin Credentials
Username: `siddharth`  
Password: `123456`  
*(Change these in `admin_login.php` before going live!)*

---

## What Was Fixed

1. **`config.php`** — Now reads DB credentials from environment variables instead of hardcoded `localhost`/`root`
2. **`admin_home.php`** — Fixed `file_exists()` calls to use `__DIR__` so they work regardless of the server's working directory
3. **`user_home.php`** — Same fix for image display
4. **`setup.sql`** — New file: run this SQL on your database to create the required tables
5. **`render.yaml`** — Render deployment config
